<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

class BukuIndukController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        // Bypass login check for public verification route
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $project_folder = '/SINTA-SaaS';
        if (strncasecmp($path, $project_folder, strlen($project_folder)) === 0) {
            $path = substr($path, strlen($project_folder));
        }
        
        if ($path === '/verify-transkrip') {
            return;
        }

        SessionManager::requireLogin();
        
        // Cek role yang diperbolehkan
        $roles = $_SESSION['roles'] ?? [$_SESSION['role_name'] ?? ''];
        $allowed = array_intersect($roles, ['super_admin', 'operator_sekolah', 'guru']);
        if (empty($allowed)) {
            http_response_code(403);
            die("<h1>403 Forbidden</h1><p>Anda tidak memiliki akses ke halaman Buku Induk.</p>");
        }
    }

    public function index(): void {
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        
        // Ambil opsi kelas untuk filter dropdown
        $q = "SELECT id, nama_kelas, id_jenjang FROM kelas WHERE is_active = 1 AND deleted_at IS NULL";
        if ($tenantId) {
            $q .= " AND tenant_id = :tenant_id";
        }
        $q .= " ORDER BY nama_kelas ASC";
        $stmt = $db->prepare($q);
        if ($tenantId) {
            $stmt->execute(['tenant_id' => $tenantId]);
        } else {
            $stmt->execute();
        }
        $kelasList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil opsi jenjang untuk filter dropdown
        $qJenjang = "SELECT id, nama_jenjang FROM jenjang WHERE is_active = 1 AND deleted_at IS NULL";
        if ($tenantId) {
            $qJenjang .= " AND tenant_id = :tenant_id";
        }
        $qJenjang .= " ORDER BY nama_jenjang ASC";
        $stmtJenjang = $db->prepare($qJenjang);
        if ($tenantId) {
            $stmtJenjang->execute(['tenant_id' => $tenantId]);
        } else {
            $stmtJenjang->execute();
        }
        $jenjangList = $stmtJenjang->fetchAll(PDO::FETCH_ASSOC);

        // Ambil opsi sekolah/tenant untuk filter Super Admin
        $tenantList = [];
        if (!$tenantId) {
            $stmtTenant = $db->query("SELECT id, nama_sekolah FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
            $tenantList = $stmtTenant->fetchAll(PDO::FETCH_ASSOC);
        }

        $data = [
            'title' => 'Buku Induk Siswa',
            'user_nama' => $_SESSION['nama_lengkap'],
            'user_role' => $_SESSION['role_name'],
            'kelasList' => $kelasList,
            'jenjangList' => $jenjangList,
            'tenantList' => $tenantList
        ];

        $this->render('buku_induk', $data);
    }

    public function fetchApi(): void {
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        $filterTenant = isset($_GET['filter_tenant_id']) ? trim($_GET['filter_tenant_id']) : '';

        // Guard against Super Admin requesting data without a school filter context
        if (empty($tenantId) && empty($filterTenant)) {
            $this->jsonResponse([
                'data' => [],
                'total' => 0,
                'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10,
                'current_page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                'last_page' => 1,
                'from' => 0,
                'to' => 0
            ]);
            return;
        }

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filterKelas = isset($_GET['kelas_id']) ? trim($_GET['kelas_id']) : '';
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';

        $offset = ($page - 1) * $perPage;

        // Build query
        $where = ["s.deleted_at IS NULL"];
        $params = [];

        // Tenant filter
        if ($tenantId) {
            $where[] = "s.tenant_id = :tenant_id";
            $params['tenant_id'] = $tenantId;
        } else if ($filterTenant) {
            $where[] = "s.tenant_id = :filter_tenant_id";
            $params['filter_tenant_id'] = $filterTenant;
        }

        // Search filter (Nama, NISN, NIS)
        if ($search !== '') {
            $where[] = "(s.nama_lengkap LIKE :search_nama OR s.nisn LIKE :search_nisn OR s.nis LIKE :search_nis)";
            $params['search_nama'] = "%$search%";
            $params['search_nisn'] = "%$search%";
            $params['search_nis'] = "%$search%";
        }

        // Kelas filter
        if ($filterKelas !== '') {
            $where[] = "s.id_kelas = :kelas_id";
            $params['kelas_id'] = $filterKelas;
        }

        // Status filter
        if ($filterStatus !== '') {
            $where[] = "s.status = :status";
            $params['status'] = $filterStatus;
        }

        $whereClause = implode(" AND ", $where);

        // Count query
        $countSql = "SELECT COUNT(*) FROM siswa s WHERE $whereClause";
        $stmtCount = $db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        // Select query
        $sql = "SELECT s.id, s.nama_lengkap, s.nisn, s.nis, s.jenis_kelamin, s.status,
                       k.nama_kelas, t.nama_sekolah, j.nama_jurusan
                FROM siswa s
                LEFT JOIN kelas k ON s.id_kelas = k.id
                LEFT JOIN tenants t ON s.tenant_id = t.id
                LEFT JOIN jurusan j ON s.id_jurusan = j.id
                WHERE $whereClause
                ORDER BY s.nama_lengkap ASC
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $lastPage = ceil($total / $perPage);
        $from = $total > 0 ? $offset + 1 : 0;
        $to = min($offset + $perPage, $total);

        $this->jsonResponse([
            'data' => $data,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $from,
            'to' => $to
        ]);
    }

    public function fetchDetailApi(): void {
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            $this->jsonResponse(['error' => 'ID siswa tidak valid.'], 400);
        }
        
        $tenantId = SessionManager::getTenantId();
        $siswaModel = new \App\Models\Siswa($tenantId);
        $siswa = $siswaModel->findFullById($id);
        
        if (!$siswa) {
            $this->jsonResponse(['error' => 'Data siswa tidak ditemukan.'], 404);
        }
        
        $db = \App\Config\Database::getConnection();

        // Fetch current class name
        $siswa['nama_kelas'] = '-';
        if (!empty($siswa['id_kelas'])) {
            try {
                $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
                $stmtKelas->execute([$siswa['id_kelas']]);
                $siswa['nama_kelas'] = $stmtKelas->fetchColumn() ?: '-';
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        // Fetch tenant/school name
        $siswa['nama_sekolah'] = '-';
        if (!empty($siswa['tenant_id'])) {
            try {
                $stmtTenant = $db->prepare("SELECT nama_sekolah FROM tenants WHERE id = ?");
                $stmtTenant->execute([$siswa['tenant_id']]);
                $siswa['nama_sekolah'] = $stmtTenant->fetchColumn() ?: '-';
            } catch (\Throwable $e) {
                // Ignore
            }
        }

        // Dapatkan nama-nama wilayah (Provinsi, Kota, Kecamatan, Kelurahan) secara human readable jika ada
        if (!empty($siswa['id_kelurahan'])) {
            try {
                $stmt = $db->prepare("
                    SELECT kl.nama_kelurahan, kc.nama_kecamatan, kt.nama_kota, pr.nama_provinsi
                    FROM kelurahan kl
                    JOIN kecamatan kc ON kl.id_kecamatan = kc.id_kecamatan
                    JOIN kota kt ON kc.id_kota = kt.id_kota
                    JOIN provinsi pr ON kt.id_provinsi = pr.id_provinsi
                    WHERE kl.id_kelurahan = ?
                    LIMIT 1
                ");
                $stmt->execute([$siswa['id_kelurahan']]);
                $wilayahNames = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($wilayahNames) {
                    $siswa['nama_kelurahan'] = $wilayahNames['nama_kelurahan'];
                    $siswa['nama_kecamatan'] = $wilayahNames['nama_kecamatan'];
                    $siswa['nama_kota'] = $wilayahNames['nama_kota'];
                    $siswa['nama_provinsi'] = $wilayahNames['nama_provinsi'];
                }
            } catch (\Throwable $e) {
                // Ignore errors
            }
        }

        // Fetch class placement history
        $riwayatKelas = [];
        try {
            $stmtRK = $db->prepare("
                SELECT * 
                FROM riwayat_kenaikan_kelas 
                WHERE siswa_id = :siswa_id 
                  AND tenant_id = :tenant_id 
                ORDER BY created_at ASC
            ");
            $stmtRK->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $riwayatKelas = $stmtRK->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }

        // Fetch Tahun Masuk
        $tahunMasuk = '-';
        if (!empty($siswa['id_tahun_ajaran'])) {
            try {
                $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE id = ?");
                $stmtTa->execute([$siswa['id_tahun_ajaran']]);
                $tahunMasuk = $stmtTa->fetchColumn() ?: '-';
            } catch (\Throwable $e) {
                // Ignore
            }
        }
        $siswa['tahun_masuk'] = $tahunMasuk;

        // Inject Penempatan Kelas Awal
        $kelasAwal = $siswa['nama_kelas'];
        if (!empty($riwayatKelas)) {
            $kelasAwal = $riwayatKelas[0]['nama_kelas_asal'];
        }
        
        $penempatanAwal = [
            'id' => 'awal',
            'tahun_ajaran' => $tahunMasuk,
            'nama_kelas_tujuan' => $kelasAwal,
            'nama_pelaku' => 'Admin Sekolah',
            'jenis_aksi' => 'penempatan_awal',
            'catatan' => 'Penempatan Awal Siswa Baru',
            'created_at' => $siswa['created_at'] ?? date('Y-m-d H:i:s')
        ];
        array_unshift($riwayatKelas, $penempatanAwal);

        $siswa['riwayat_kelas'] = $riwayatKelas;

        // Fetch kesehatan
        $kesehatan = [];
        try {
            $stmtK = $db->prepare("SELECT * FROM kesehatan_siswa WHERE siswa_id = ? ORDER BY semester ASC");
            $stmtK->execute([$id]);
            $krows = $stmtK->fetchAll(PDO::FETCH_ASSOC);
            foreach ($krows as $k) {
                $kesehatan[$k['semester']] = $k;
            }
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['kesehatan'] = $kesehatan;

        // Fetch report card grades
        $nilaiRapor = [];
        try {
            $stmtNR = $db->prepare("
                SELECT dnr.*, mp.nama_mapel, mp.kode_mapel, k.nama_kelas
                FROM detail_nilai_rapor dnr
                JOIN mata_pelajaran mp ON dnr.mapel_id = mp.id
                JOIN kelas k ON dnr.kelas_id = k.id
                WHERE dnr.siswa_id = :siswa_id 
                  AND dnr.tenant_id = :tenant_id 
                  AND dnr.deleted_at IS NULL
                ORDER BY dnr.tahun_ajaran ASC, dnr.semester ASC, mp.nama_mapel ASC
            ");
            $stmtNR->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $nilaiRapor = $stmtNR->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['nilai_rapor'] = $nilaiRapor;

        // Fetch achievements
        $prestasi = [];
        try {
            $stmtP = $db->prepare("
                SELECT ps.*, ta.tahun_ajaran
                FROM prestasi_siswa ps
                JOIN prestasi_siswa_anggota psa ON ps.id = psa.id_prestasi
                LEFT JOIN tahun_ajaran ta ON ps.tahun_ajaran_id = ta.id
                WHERE psa.id_siswa = :siswa_id 
                  AND ps.tenant_id = :tenant_id 
                  AND ps.deleted_at IS NULL
                ORDER BY ps.tanggal_lomba DESC
            ");
            $stmtP->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $prestasi = $stmtP->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['prestasi'] = $prestasi;

        // Fetch violations / BK discipline records
        $pelanggaran = [];
        try {
            $stmtPL = $db->prepare("
                SELECT cps.*, mp.nama_pelanggaran, mp.bobot_poin, mp.kategori
                FROM catatan_pelanggaran_siswa cps
                JOIN master_pelanggaran mp ON cps.pelanggaran_id = mp.id
                WHERE cps.siswa_id = :siswa_id 
                  AND cps.tenant_id = :tenant_id 
                  AND cps.deleted_at IS NULL
                ORDER BY cps.tanggal_kejadian DESC
            ");
            $stmtPL->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $pelanggaran = $stmtPL->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['pelanggaran'] = $pelanggaran;

        // Fetch tracer study data
        $tracerKuliah = [];
        $tracerPekerjaan = [];
        try {
            $stmtTK = $db->prepare("
                SELECT * 
                FROM riwayat_kuliah 
                WHERE id_siswa = :siswa_id 
                  AND tenant_id = :tenant_id
                ORDER BY tahun_masuk DESC
            ");
            $stmtTK->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $tracerKuliah = $stmtTK->fetchAll(PDO::FETCH_ASSOC);

            $stmtTP = $db->prepare("
                SELECT * 
                FROM riwayat_pekerjaan 
                WHERE id_siswa = :siswa_id 
                  AND tenant_id = :tenant_id
                ORDER BY tahun_mulai DESC
            ");
            $stmtTP->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $tracerPekerjaan = $stmtTP->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['tracer_kuliah'] = $tracerKuliah;
        $siswa['tracer_pekerjaan'] = $tracerPekerjaan;
        
        // Fetch Prestasi
        $prestasi = [];
        try {
            $stmtPrestasi = $db->prepare("
                SELECT ps.* 
                FROM prestasi_siswa ps
                JOIN prestasi_siswa_anggota psa ON ps.id = psa.id_prestasi
                WHERE psa.id_siswa = :siswa_id AND ps.tenant_id = :tenant_id AND ps.deleted_at IS NULL
                ORDER BY ps.tanggal_lomba DESC
            ");
            $stmtPrestasi->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $prestasi = $stmtPrestasi->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['prestasi'] = $prestasi;

        // Fetch Beasiswa
        $beasiswa = [];
        try {
            $stmtBeasiswa = $db->prepare("
                SELECT * 
                FROM riwayat_beasiswa
                WHERE siswa_id = :siswa_id AND tenant_id = :tenant_id
                ORDER BY tahun_menerima DESC
            ");
            $stmtBeasiswa->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $beasiswa = $stmtBeasiswa->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['beasiswa'] = $beasiswa;

        $this->jsonResponse($siswa);
    }

    public function printRapot(): void {
        $id = $_GET['id'] ?? '';
        $tempat = $_GET['tempat'] ?? 'Jombang';
        $tanggal = $_GET['tanggal'] ?? '';

        if (empty($id)) {
            die("<h1>Bad Request</h1><p>ID siswa tidak valid.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $siswaModel = new \App\Models\Siswa($tenantId);
        $siswa = $siswaModel->findFullById($id);

        if (!$siswa) {
            die("<h1>Not Found</h1><p>Data siswa tidak ditemukan.</p>");
        }

        // Dapatkan nama-nama wilayah (Provinsi, Kota, Kecamatan, Kelurahan) secara human readable jika ada
        if (!empty($siswa['id_kelurahan'])) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("
                    SELECT kl.nama_kelurahan, kc.nama_kecamatan, kt.nama_kota, pr.nama_provinsi
                    FROM kelurahan kl
                    JOIN kecamatan kc ON kl.id_kecamatan = kc.id_kecamatan
                    JOIN kota kt ON kc.id_kota = kt.id_kota
                    JOIN provinsi pr ON kt.id_provinsi = pr.id_provinsi
                    WHERE kl.id_kelurahan = ?
                    LIMIT 1
                ");
                $stmt->execute([$siswa['id_kelurahan']]);
                $wilayahNames = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($wilayahNames) {
                    $siswa['nama_kelurahan'] = $wilayahNames['nama_kelurahan'];
                    $siswa['nama_kecamatan'] = $wilayahNames['nama_kecamatan'];
                    $siswa['nama_kota'] = $wilayahNames['nama_kota'];
                    $siswa['nama_provinsi'] = $wilayahNames['nama_provinsi'];
                }
            } catch (\Throwable $e) {
                // Ignore errors
            }
        }

        // Dapatkan nama kelas
        if (!empty($siswa['id_kelas'])) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
                $stmtKelas->execute([$siswa['id_kelas']]);
                $siswa['nama_kelas'] = $stmtKelas->fetchColumn() ?: '-';
            } catch (\Throwable $e) {
                $siswa['nama_kelas'] = '-';
            }
        } else {
            $siswa['nama_kelas'] = '-';
        }

        // Dapatkan data headmaster & sekolah (npsn, wilayah) dari tenants
        try {
            $db = \App\Config\Database::getConnection();
            $stmtTenant = $db->prepare("SELECT nama_sekolah, nama_kepsek, nip_kepsek, pangkat_kepsek, npsn, kecamatan, kabupaten_kota, provinsi FROM tenants WHERE id = ?");
            $stmtTenant->execute([$siswa['tenant_id']]);
            $tenantInfo = $stmtTenant->fetch(PDO::FETCH_ASSOC);
            $siswa['nama_sekolah'] = $tenantInfo['nama_sekolah'] ?? '-';
            $siswa['nama_kepsek'] = $tenantInfo['nama_kepsek'] ?? '-';
            $siswa['nip_kepsek'] = $tenantInfo['nip_kepsek'] ?? '-';
            $siswa['pangkat_kepsek'] = $tenantInfo['pangkat_kepsek'] ?? '';
            $siswa['npsn'] = $tenantInfo['npsn'] ?? '';
            $siswa['sekolah_kecamatan'] = $tenantInfo['kecamatan'] ?? '';
            $siswa['sekolah_kabupaten'] = $tenantInfo['kabupaten_kota'] ?? '';
            $siswa['sekolah_provinsi'] = $tenantInfo['provinsi'] ?? '';
        } catch (\Throwable $e) {
            $siswa['nama_kepsek'] = '-';
            $siswa['nip_kepsek'] = '-';
            $siswa['pangkat_kepsek'] = '';
        }

        // Fetch Prestasi
        $prestasi = [];
        try {
            $db = \App\Config\Database::getConnection();
            $stmtPrestasi = $db->prepare("
                SELECT ps.* 
                FROM prestasi_siswa ps
                JOIN prestasi_siswa_anggota psa ON ps.id = psa.id_prestasi
                WHERE psa.id_siswa = :siswa_id AND ps.tenant_id = :tenant_id AND ps.deleted_at IS NULL
                ORDER BY ps.tanggal_lomba DESC
            ");
            $stmtPrestasi->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $prestasi = $stmtPrestasi->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['prestasi'] = $prestasi;

        // Fetch Beasiswa
        $beasiswa = [];
        try {
            $stmtBeasiswa = $db->prepare("
                SELECT * 
                FROM riwayat_beasiswa
                WHERE siswa_id = :siswa_id AND tenant_id = :tenant_id
                ORDER BY tahun_menerima DESC
            ");
            $stmtBeasiswa->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $beasiswa = $stmtBeasiswa->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['beasiswa'] = $beasiswa;

        // QR Code options and parameters
        $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $domainName = $_SERVER['HTTP_HOST'];
        $baseFolder = '';
        if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
            $baseFolder = '/SINTA-SaaS';
        }
        $urlVerifikasi = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=" . $siswa['id'];

        $archiveFilename = "identitas_rapor.html";
        $this->renderOrGetArchive($siswa['id'], $siswa['tenant_id'], $archiveFilename, function() use ($siswa, $showQrCode, $urlVerifikasi) {
            // Load the print view directly (no layout wrapper)
            require __DIR__ . '/../../views/print_rapot.php';
        });
        exit;
    }

    public function printBukuInduk(): void {
        $id = $_GET['id'] ?? '';
        $tempat = $_GET['tempat'] ?? 'Jombang';
        $tanggal = $_GET['tanggal'] ?? '';

        if (empty($id)) {
            die("<h1>Bad Request</h1><p>ID siswa tidak valid.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $siswaModel = new \App\Models\Siswa($tenantId);
        $siswa = $siswaModel->findFullById($id);

        if (!$siswa) {
            die("<h1>Not Found</h1><p>Data siswa tidak ditemukan.</p>");
        }

        // Dapatkan nama-nama wilayah (Provinsi, Kota, Kecamatan, Kelurahan) secara human readable jika ada
        if (!empty($siswa['id_kelurahan'])) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("
                    SELECT kl.nama_kelurahan, kc.nama_kecamatan, kt.nama_kota, pr.nama_provinsi
                    FROM kelurahan kl
                    JOIN kecamatan kc ON kl.id_kecamatan = kc.id_kecamatan
                    JOIN kota kt ON kc.id_kota = kt.id_kota
                    JOIN provinsi pr ON kt.id_provinsi = pr.id_provinsi
                    WHERE kl.id_kelurahan = ?
                    LIMIT 1
                ");
                $stmt->execute([$siswa['id_kelurahan']]);
                $wilayahNames = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($wilayahNames) {
                    $siswa['nama_kelurahan'] = $wilayahNames['nama_kelurahan'];
                    $siswa['nama_kecamatan'] = $wilayahNames['nama_kecamatan'];
                    $siswa['nama_kota'] = $wilayahNames['nama_kota'];
                    $siswa['nama_provinsi'] = $wilayahNames['nama_provinsi'];
                }
            } catch (\Throwable $e) {
                // Ignore errors
            }
        }

        // Dapatkan nama kelas
        if (!empty($siswa['id_kelas'])) {
            try {
                $db = \App\Config\Database::getConnection();
                $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
                $stmtKelas->execute([$siswa['id_kelas']]);
                $siswa['nama_kelas'] = $stmtKelas->fetchColumn() ?: '-';
            } catch (\Throwable $e) {
                $siswa['nama_kelas'] = '-';
            }
        } else {
            $siswa['nama_kelas'] = '-';
        }

        // Dapatkan data headmaster & sekolah (npsn, wilayah) dari tenants
        try {
            $db = \App\Config\Database::getConnection();
            $stmtTenant = $db->prepare("SELECT nama_sekolah, nama_kepsek, nip_kepsek, pangkat_kepsek, npsn, kecamatan, kabupaten_kota, provinsi FROM tenants WHERE id = ?");
            $stmtTenant->execute([$siswa['tenant_id']]);
            $tenantInfo = $stmtTenant->fetch(PDO::FETCH_ASSOC);
            $siswa['nama_sekolah'] = $tenantInfo['nama_sekolah'] ?? '-';
            $siswa['nama_kepsek'] = $tenantInfo['nama_kepsek'] ?? '-';
            $siswa['nip_kepsek'] = $tenantInfo['nip_kepsek'] ?? '-';
            $siswa['pangkat_kepsek'] = $tenantInfo['pangkat_kepsek'] ?? '';
            $siswa['npsn'] = $tenantInfo['npsn'] ?? '';
            $siswa['sekolah_kecamatan'] = $tenantInfo['kecamatan'] ?? '';
            $siswa['sekolah_kabupaten'] = $tenantInfo['kabupaten_kota'] ?? '';
            $siswa['sekolah_provinsi'] = $tenantInfo['provinsi'] ?? '';
        } catch (\Throwable $e) {
            $siswa['nama_kepsek'] = '-';
            $siswa['nip_kepsek'] = '-';
            $siswa['pangkat_kepsek'] = '';
        }

        // Determine signature date and who is the principal
        $tanggalCetak = $_GET['tanggal_cetak'] ?? date('Y-m-d');
        $historicalKepsek = $this->getKepsekAtDate($siswa['tenant_id'], $tanggalCetak);
        if (!empty($historicalKepsek)) {
            $siswa['nama_kepsek'] = $historicalKepsek['nama_kepsek'];
            $siswa['nip_kepsek'] = $historicalKepsek['nip_kepsek'];
        }

        // Fetch Prestasi
        $prestasi = [];
        try {
            $db = \App\Config\Database::getConnection();
            $stmtPrestasi = $db->prepare("
                SELECT ps.* 
                FROM prestasi_siswa ps
                JOIN prestasi_siswa_anggota psa ON ps.id = psa.id_prestasi
                WHERE psa.id_siswa = :siswa_id AND ps.tenant_id = :tenant_id AND ps.deleted_at IS NULL
                ORDER BY ps.tanggal_lomba DESC
            ");
            $stmtPrestasi->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $prestasi = $stmtPrestasi->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['prestasi'] = $prestasi;

        // Fetch Beasiswa
        $beasiswa = [];
        try {
            $stmtBeasiswa = $db->prepare("
                SELECT * 
                FROM riwayat_beasiswa
                WHERE siswa_id = :siswa_id AND tenant_id = :tenant_id
                ORDER BY tahun_menerima DESC
            ");
            $stmtBeasiswa->execute([
                'siswa_id' => $id,
                'tenant_id' => $siswa['tenant_id']
            ]);
            $beasiswa = $stmtBeasiswa->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['beasiswa'] = $beasiswa;

        // Fetch Kesehatan
        $kesehatan = [];
        try {
            $kesehatan = $siswaModel->getKesehatanSiswa($id);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['kesehatan'] = $kesehatan;

        // QR Code options and parameters
        $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $domainName = $_SERVER['HTTP_HOST'];
        $baseFolder = '';
        if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
            $baseFolder = '/SINTA-SaaS';
        }
        $urlVerifikasi = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=" . $siswa['id'];

        $archiveFilename = "buku_induk.html";
        $this->renderOrGetArchive($siswa['id'], $siswa['tenant_id'], $archiveFilename, function() use ($siswa, $showQrCode, $urlVerifikasi, $tempat, $tanggal) {
            // Load the print view directly
            require __DIR__ . '/../../views/print_buku_induk.php';
        });
        exit;
    }

    // --- API Riwayat Kepala Sekolah ---
    public function getRiwayatKepsek(): void {
        header('Content-Type: application/json');
        try {
            $db = \App\Config\Database::getConnection();
            $tenantId = SessionManager::getTenantId();
            if (!$tenantId && isset($_GET['filter_tenant_id'])) {
                $tenantId = $_GET['filter_tenant_id'];
            }
            if (!$tenantId) throw new \Exception("Sekolah belum dipilih.");
            
            $stmt = $db->prepare("SELECT * FROM riwayat_kepala_sekolah WHERE tenant_id = ? ORDER BY tanggal_mulai DESC");
            $stmt->execute([$tenantId]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function storeRiwayatKepsek(): void {
        header('Content-Type: application/json');
        try {
            $db = \App\Config\Database::getConnection();
            $tenantId = SessionManager::getTenantId();
            if (!$tenantId && isset($_POST['filter_tenant_id'])) {
                $tenantId = $_POST['filter_tenant_id'];
            }
            if (!$tenantId) throw new \Exception("Sekolah belum dipilih.");
            
            $namaKepsek = $_POST['nama_kepsek'] ?? '';
            $nipKepsek = $_POST['nip_kepsek'] ?? '';
            $tanggalMulai = $_POST['tanggal_mulai'] ?? '';
            $tanggalSelesai = !empty($_POST['tanggal_selesai']) ? $_POST['tanggal_selesai'] : null;
            $statusPlt = isset($_POST['status_plt']) ? (int)$_POST['status_plt'] : 0;
            $id = $_POST['id'] ?? null;

            if (empty($namaKepsek) || empty($tanggalMulai)) {
                echo json_encode(['success' => false, 'error' => 'Nama Kepsek dan Tanggal Mulai wajib diisi.']);
                return;
            }

            if ($id) {
                // Update
                $stmt = $db->prepare("UPDATE riwayat_kepala_sekolah SET nama_kepsek = ?, nip_kepsek = ?, tanggal_mulai = ?, tanggal_selesai = ?, status_plt = ? WHERE id = ? AND tenant_id = ?");
                $stmt->execute([$namaKepsek, $nipKepsek, $tanggalMulai, $tanggalSelesai, $statusPlt, $id, $tenantId]);
            } else {
                // Insert
                $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
                $stmt = $db->prepare("INSERT INTO riwayat_kepala_sekolah (id, tenant_id, nama_kepsek, nip_kepsek, tanggal_mulai, tanggal_selesai, status_plt) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $tenantId, $namaKepsek, $nipKepsek, $tanggalMulai, $tanggalSelesai, $statusPlt]);
            }

            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function deleteRiwayatKepsek(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $id = $input['id'] ?? '';
            if (!$id) throw new \Exception("ID tidak valid");

            $db = \App\Config\Database::getConnection();
            $tenantId = SessionManager::getTenantId();
            if (!$tenantId && isset($input['filter_tenant_id'])) {
                $tenantId = $input['filter_tenant_id'];
            }
            if (!$tenantId) throw new \Exception("Sekolah belum dipilih.");
            
            $stmt = $db->prepare("DELETE FROM riwayat_kepala_sekolah WHERE id = ? AND tenant_id = ?");
            $stmt->execute([$id, $tenantId]);
            
            echo json_encode(['success' => true]);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getKepsekAtDate(string $tenantId, string $tanggalCetak): array {
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("
                SELECT nama_kepsek, nip_kepsek 
                FROM riwayat_kepala_sekolah 
                WHERE tenant_id = ? 
                  AND tanggal_mulai <= ? 
                  AND (tanggal_selesai IS NULL OR tanggal_selesai >= ?)
                ORDER BY tanggal_mulai DESC LIMIT 1
            ");
            $stmt->execute([$tenantId, $tanggalCetak, $tanggalCetak]);
            $kepsek = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($kepsek) {
                return $kepsek;
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return [];
    }

    public function printRapotSemester(): void {
        $id = $_GET['id'] ?? '';
        $semester = $_GET['semester'] ?? 'Ganjil';
        $ta = $_GET['ta'] ?? '';

        if (empty($id) || empty($semester) || empty($ta)) {
            die("<h1>Bad Request</h1><p>Parameter tidak lengkap.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $siswaModel = new \App\Models\Siswa($tenantId);
        $siswa = $siswaModel->findFullById($id);

        if (!$siswa) {
            die("<h1>Not Found</h1><p>Data siswa tidak ditemukan.</p>");
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmtTenant = $db->prepare("SELECT * FROM tenants WHERE id = ?");
            $stmtTenant->execute([$siswa['tenant_id']]);
            $tenantInfo = $stmtTenant->fetch(PDO::FETCH_ASSOC);
            $kurikulum = $tenantInfo['kurikulum'] ?? 'Merdeka';
        } catch (\Throwable $e) {
            $kurikulum = 'Merdeka';
            $tenantInfo = [];
        }
        $siswa['tenant_info'] = $tenantInfo;

        // Determine signature date and who is the principal
        $tanggalCetak = $_GET['tanggal_cetak'] ?? date('Y-m-d');
        $historicalKepsek = $this->getKepsekAtDate($siswa['tenant_id'], $tanggalCetak);
        if (!empty($historicalKepsek)) {
            $siswa['tenant_info']['nama_kepsek'] = $historicalKepsek['nama_kepsek'];
            $siswa['tenant_info']['nip_kepsek'] = $historicalKepsek['nip_kepsek'];
        }

        // Fetch grades and historical class name
        $grades = [];
        $historicalKelas = null;
        try {
            $stmtGrades = $db->prepare("
                SELECT d.*, m.nama_mapel, k.nama_kelas 
                FROM detail_nilai_rapor d 
                JOIN mata_pelajaran m ON d.mapel_id = m.id 
                LEFT JOIN kelas k ON d.kelas_id = k.id
                WHERE d.siswa_id = ? AND d.semester = ? AND d.tahun_ajaran = ? 
                AND d.deleted_at IS NULL 
                ORDER BY m.nama_mapel ASC
            ");
            $stmtGrades->execute([$id, $semester, $ta]);
            $grades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($grades) > 0 && !empty($grades[0]['nama_kelas'])) {
                $historicalKelas = $grades[0]['nama_kelas'];
            }
        } catch (\Throwable $e) {
            $grades = [];
        }
        
        if ($historicalKelas) {
            $siswa['nama_kelas'] = $historicalKelas;
        }

        // Determine dynamic curriculum
        $tipePenilaian = 'sederhana'; // default
        $namaKurikulum = 'Kurikulum Merdeka';
        try {
            $kelasIdSiswa = null;
            if (count($grades) > 0 && !empty($grades[0]['kelas_id'])) {
                $kelasIdSiswa = $grades[0]['kelas_id'];
            } else {
                $kelasIdSiswa = $siswa['id_kelas'];
            }

            if ($kelasIdSiswa) {
                $qKelasKur = "SELECT r.nama_kurikulum, r.tipe_penilaian 
                              FROM kelas_kurikulum kk
                              JOIN ref_kurikulum r ON kk.kurikulum_id = r.id
                              WHERE kk.kelas_id = :kelas_id AND kk.tahun_ajaran = :tahun_ajaran AND kk.tenant_id = :tenant_id
                              LIMIT 1";
                $stmtKelasKur = $db->prepare($qKelasKur);
                $stmtKelasKur->execute([
                    'kelas_id' => $kelasIdSiswa,
                    'tahun_ajaran' => $ta,
                    'tenant_id' => $siswa['tenant_id']
                ]);
                $kelasKurInfo = $stmtKelasKur->fetch(PDO::FETCH_ASSOC);
                if ($kelasKurInfo) {
                    $namaKurikulum = $kelasKurInfo['nama_kurikulum'];
                    $tipePenilaian = $kelasKurInfo['tipe_penilaian'];
                } else {
                    $kurikulumTenant = $tenantInfo['kurikulum'] ?? 'Merdeka';
                    if (stripos($kurikulumTenant, '13') !== false) {
                        $tipePenilaian = 'kompleks';
                        $namaKurikulum = 'Kurikulum 2013 (K-13)';
                    } elseif (stripos($kurikulumTenant, 'KTSP') !== false || stripos($kurikulumTenant, 'KBK') !== false) {
                        $tipePenilaian = 'klasik';
                        $namaKurikulum = 'KTSP';
                    }
                }
            }
        } catch (\Throwable $e) {
            // fallback
        }

        // Fetch K-13 attitude if complex
        $sikapK13 = [];
        if ($tipePenilaian === 'kompleks') {
            try {
                $qSikap = "SELECT * FROM nilai_sikap_k13 WHERE siswa_id = ? AND tahun_ajaran = ? AND semester = ? LIMIT 1";
                $stmtSikap = $db->prepare($qSikap);
                $stmtSikap->execute([$id, $ta, $semester]);
                $sikapRow = $stmtSikap->fetch(PDO::FETCH_ASSOC);
                if ($sikapRow) {
                    $sikapK13[$id] = $sikapRow;
                }
            } catch (\Throwable $e) {
                // skip
            }
        }

        // QR Code options and parameters
        $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $domainName = $_SERVER['HTTP_HOST'];
        $baseFolder = '';
        if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
            $baseFolder = '/SINTA-SaaS';
        }
        $urlVerifikasi = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=" . $siswa['id'];

        $cleanTa = str_replace(['/', '\\'], '-', $ta);
        $archiveFilename = "rapor_{$semester}_{$cleanTa}.html";

        $this->renderOrGetArchive($siswa['id'], $siswa['tenant_id'], $archiveFilename, function() use ($siswa, $grades, $tipePenilaian, $namaKurikulum, $sikapK13, $showQrCode, $urlVerifikasi) {
            // Load correct layout
            if ($tipePenilaian === 'klasik') {
                require __DIR__ . '/../../views/print_rapot_ktsp.php';
            } elseif ($tipePenilaian === 'kompleks') {
                require __DIR__ . '/../../views/print_rapot_k13.php';
            } else {
                require __DIR__ . '/../../views/print_rapot_merdeka.php';
            }
        });
        exit;
    }

    public function printTranskripNilai(): void {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            die("<h1>Bad Request</h1><p>ID siswa tidak valid.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $siswaModel = new \App\Models\Siswa($tenantId);
        $siswa = $siswaModel->findFullById($id);

        if (!$siswa) {
            die("<h1>Not Found</h1><p>Data siswa tidak ditemukan.</p>");
        }

        try {
            $db = \App\Config\Database::getConnection();
            $stmtTenant = $db->prepare("SELECT * FROM tenants WHERE id = ?");
            $stmtTenant->execute([$siswa['tenant_id']]);
            $tenantInfo = $stmtTenant->fetch(PDO::FETCH_ASSOC);
            $kurikulum = $tenantInfo['kurikulum'] ?? 'Merdeka';
        } catch (\Throwable $e) {
            $kurikulum = 'Merdeka';
            $tenantInfo = [];
        }
        $siswa['tenant_info'] = $tenantInfo;

        // Determine signature date and who is the principal
        $tanggalCetak = $_GET['tanggal_cetak'] ?? date('Y-m-d');
        $historicalKepsek = $this->getKepsekAtDate($siswa['tenant_id'], $tanggalCetak);
        if (!empty($historicalKepsek)) {
            $siswa['tenant_info']['nama_kepsek'] = $historicalKepsek['nama_kepsek'];
            $siswa['tenant_info']['nip_kepsek'] = $historicalKepsek['nip_kepsek'];
        }

        // Fetch all grades
        $gradesRaw = [];
        try {
            $stmt = $db->prepare("
                SELECT d.*, m.nama_mapel, m.kelompok 
                FROM detail_nilai_rapor d 
                JOIN mata_pelajaran m ON d.mapel_id = m.id 
                WHERE d.siswa_id = ? AND d.deleted_at IS NULL 
                ORDER BY m.kelompok ASC, m.nama_mapel ASC, d.tahun_ajaran ASC, d.semester ASC
            ");
            $stmt->execute([$id]);
            $gradesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        
        $siswa['transkrip_grades'] = $gradesRaw;

        // QR Code options and parameters
        $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $domainName = $_SERVER['HTTP_HOST'];
        $baseFolder = '';
        if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
            $baseFolder = '/SINTA-SaaS';
        }
        $urlVerifikasi = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=" . $siswa['id'];

        $archiveFilename = "transkrip.html";
        $this->renderOrGetArchive($siswa['id'], $siswa['tenant_id'], $archiveFilename, function() use ($siswa, $kurikulum, $showQrCode, $urlVerifikasi) {
            if (stripos($kurikulum, 'Merdeka') !== false) {
                require __DIR__ . '/../../views/print_transkrip_merdeka.php';
            } else {
                require __DIR__ . '/../../views/print_transkrip_standar.php';
            }
        });
        exit;
    }

    public function printRapotKelas(): void {
        $kelasId = $_GET['kelas_id'] ?? '';
        $tempat = $_GET['tempat'] ?? 'Jombang';
        $tanggal = $_GET['tanggal'] ?? '';

        if (empty($kelasId)) {
            die("<h1>Bad Request</h1><p>Kelas tidak valid.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $db = \App\Config\Database::getConnection();

        // Ambil daftar siswa aktif di kelas ini
        $query = "SELECT id FROM siswa WHERE id_kelas = :kelas_id AND deleted_at IS NULL AND status = 'Aktif'";
        $params = ['kelas_id' => $kelasId];
        
        if ($tenantId !== null) {
            $query .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $tenantId;
        }
        
        $query .= " ORDER BY nama_lengkap ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($studentIds)) {
            die("<h1>Not Found</h1><p>Tidak ada siswa aktif di kelas yang dipilih.</p>");
        }

        $siswaModel = new \App\Models\Siswa($tenantId);
        $studentsData = [];

        foreach ($studentIds as $id) {
            $siswa = $siswaModel->findFullById($id);
            if (!$siswa) continue;

            // Dapatkan nama-nama wilayah (Provinsi, Kota, Kecamatan, Kelurahan) secara human readable jika ada
            if (!empty($siswa['id_kelurahan'])) {
                try {
                    $stmtWilayah = $db->prepare("
                        SELECT kl.nama_kelurahan, kc.nama_kecamatan, kt.nama_kota, pr.nama_provinsi
                        FROM kelurahan kl
                        JOIN kecamatan kc ON kl.id_kecamatan = kc.id_kecamatan
                        JOIN kota kt ON kc.id_kota = kt.id_kota
                        JOIN provinsi pr ON kt.id_provinsi = pr.id_provinsi
                        WHERE kl.id_kelurahan = ?
                        LIMIT 1
                    ");
                    $stmtWilayah->execute([$siswa['id_kelurahan']]);
                    $wilayahNames = $stmtWilayah->fetch(PDO::FETCH_ASSOC);
                    if ($wilayahNames) {
                        $siswa['nama_kelurahan'] = $wilayahNames['nama_kelurahan'];
                        $siswa['nama_kecamatan'] = $wilayahNames['nama_kecamatan'];
                        $siswa['nama_kota'] = $wilayahNames['nama_kota'];
                        $siswa['nama_provinsi'] = $wilayahNames['nama_provinsi'];
                    }
                } catch (\Throwable $e) {
                    // Ignore errors
                }
            }

            // Dapatkan nama kelas
            if (!empty($siswa['id_kelas'])) {
                try {
                    $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
                    $stmtKelas->execute([$siswa['id_kelas']]);
                    $siswa['nama_kelas'] = $stmtKelas->fetchColumn() ?: '-';
                } catch (\Throwable $e) {
                    $siswa['nama_kelas'] = '-';
                }
            } else {
                $siswa['nama_kelas'] = '-';
            }

            // Dapatkan data headmaster (nama_kepsek & nip_kepsek) dari tenants
            try {
                $stmtTenant = $db->prepare("SELECT nama_sekolah, nama_kepsek, nip_kepsek, pangkat_kepsek FROM tenants WHERE id = ?");
                $stmtTenant->execute([$siswa['tenant_id']]);
                $tenantInfo = $stmtTenant->fetch(PDO::FETCH_ASSOC);
                $siswa['nama_sekolah'] = $tenantInfo['nama_sekolah'] ?? '-';
                $siswa['nama_kepsek'] = $tenantInfo['nama_kepsek'] ?? '-';
                $siswa['nip_kepsek'] = $tenantInfo['nip_kepsek'] ?? '-';
                $siswa['pangkat_kepsek'] = $tenantInfo['pangkat_kepsek'] ?? '';
            } catch (\Throwable $e) {
                $siswa['nama_kepsek'] = '-';
                $siswa['nip_kepsek'] = '-';
                $siswa['pangkat_kepsek'] = '';
            }

            $studentsData[] = $siswa;
        }

        // QR Code options and parameters
        $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
        $domainName = $_SERVER['HTTP_HOST'];
        $baseFolder = '';
        if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
            $baseFolder = '/SINTA-SaaS';
        }
        $baseVerifyUrl = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=";

        // Load bulk print view directly
        require __DIR__ . '/../../views/print_rapot_bulk.php';
        exit;
    }
    public function fetchCetakMatrixApi(): void {
        $db = \App\Config\Database::getConnection();
        $tenantId = SessionManager::getTenantId();
        
        $filterTenant = $_GET['filter_tenant_id'] ?? '';
        $filterKelas = $_GET['kelas_id'] ?? '';
        $filterStatus = $_GET['status'] ?? '';
        $filterTahunAjaran = $_GET['tahun_ajaran'] ?? '';
        
        $where = ["s.deleted_at IS NULL"];
        $params = [];

        if ($tenantId) {
            $where[] = "s.tenant_id = :tenant_id";
            $params['tenant_id'] = $tenantId;
        } else if ($filterTenant !== '') {
            $where[] = "s.tenant_id = :filter_tenant_id";
            $params['filter_tenant_id'] = $filterTenant;
        }

        // Dapatkan tahun ajaran aktif terlebih dahulu
        $stmtTa = $db->prepare("SELECT tahun_ajaran FROM tahun_ajaran WHERE is_active = 1 " . ($tenantId ? "AND tenant_id = ?" : "") . " ORDER BY tahun_ajaran DESC LIMIT 1");
        $stmtTa->execute($tenantId ? [$tenantId] : []);
        $activeTahunAjaran = $stmtTa->fetchColumn();

        if ($filterKelas !== '' && $filterTahunAjaran !== '') {
            $where[] = "(
                s.id IN (SELECT siswa_id FROM detail_nilai_rapor WHERE kelas_id = :kelas_id1 AND tahun_ajaran = :tahun_ajaran1 AND deleted_at IS NULL)
                OR s.id IN (SELECT siswa_id FROM riwayat_kenaikan_kelas WHERE id_kelas_tujuan = :kelas_id2 AND tahun_ajaran = :tahun_ajaran2)
                OR (s.id_kelas = :kelas_id3 AND " . ($filterTahunAjaran === $activeTahunAjaran ? "s.status = 'Aktif'" : "1=0") . ")
                OR (s.id_kelas = :kelas_id4 AND s.id_tahun_ajaran IN (SELECT id FROM tahun_ajaran WHERE tahun_ajaran = :tahun_ajaran3))
            )";
            $params['kelas_id1'] = $filterKelas;
            $params['kelas_id2'] = $filterKelas;
            $params['kelas_id3'] = $filterKelas;
            $params['kelas_id4'] = $filterKelas;
            $params['tahun_ajaran1'] = $filterTahunAjaran;
            $params['tahun_ajaran2'] = $filterTahunAjaran;
            $params['tahun_ajaran3'] = $filterTahunAjaran;
        } else if ($filterKelas !== '') {
            $where[] = "(
                s.id_kelas = :kelas_id1
                OR s.id IN (SELECT siswa_id FROM detail_nilai_rapor WHERE kelas_id = :kelas_id2 AND deleted_at IS NULL)
                OR s.id IN (SELECT siswa_id FROM riwayat_kenaikan_kelas WHERE id_kelas_tujuan = :kelas_id3 OR id_kelas_asal = :kelas_id4)
            )";
            $params['kelas_id1'] = $filterKelas;
            $params['kelas_id2'] = $filterKelas;
            $params['kelas_id3'] = $filterKelas;
            $params['kelas_id4'] = $filterKelas;
        } else if ($filterTahunAjaran !== '') {
            $where[] = "(
                s.id IN (SELECT siswa_id FROM detail_nilai_rapor WHERE tahun_ajaran = :tahun_ajaran1 AND deleted_at IS NULL)
                OR s.id IN (SELECT siswa_id FROM riwayat_kenaikan_kelas WHERE tahun_ajaran = :tahun_ajaran2)
                OR s.id_tahun_ajaran IN (SELECT id FROM tahun_ajaran WHERE tahun_ajaran = :tahun_ajaran3)
                " . ($filterTahunAjaran === $activeTahunAjaran ? " OR s.status = 'Aktif' " : "") . "
            )";
            $params['tahun_ajaran1'] = $filterTahunAjaran;
            $params['tahun_ajaran2'] = $filterTahunAjaran;
            $params['tahun_ajaran3'] = $filterTahunAjaran;
        }
        if ($filterStatus !== '') {
            $where[] = "s.status = :status";
            $params['status'] = $filterStatus;
        }

        $whereClause = implode(" AND ", $where);
        
        // Dapatkan data siswa dasar
        $sql = "SELECT s.id, s.nisn, s.nis, s.nama_lengkap, ta.tahun_ajaran as tahun_masuk, k.nama_kelas as kelas_aktif, s.id_kelas, s.status 
                FROM siswa s 
                LEFT JOIN kelas k ON s.id_kelas = k.id 
                LEFT JOIN tahun_ajaran ta ON s.id_tahun_ajaran = ta.id
                WHERE $whereClause 
                ORDER BY s.nama_lengkap ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Kumpulkan ID siswa untuk query history
        $studentIds = array_column($students, 'id');
        
        $histories = [];
        if (!empty($studentIds)) {
            $inQuery = implode(',', array_fill(0, count($studentIds), '?'));
            $historyParams = $studentIds;
            
            $activeTenantId = $tenantId ?: ($filterTenant !== '' ? $filterTenant : null);
            if ($activeTenantId) {
                $historyParams[] = $activeTenantId;
            }
            
            // Ambil histori dari rapor (sebagai proxy kelas apa mereka di masa lalu)
            $qHistory = "SELECT dnr.siswa_id, dnr.tahun_ajaran, dnr.semester, k.nama_kelas, k.id as kelas_id
                         FROM detail_nilai_rapor dnr
                         JOIN kelas k ON dnr.kelas_id = k.id
                         WHERE dnr.siswa_id IN ($inQuery) AND dnr.deleted_at IS NULL " . ($activeTenantId ? " AND dnr.tenant_id = ? " : "") . "
                         GROUP BY dnr.siswa_id, dnr.tahun_ajaran, dnr.semester, k.nama_kelas, k.id
                         ORDER BY dnr.tahun_ajaran ASC, dnr.semester ASC";
            
            $stmtHist = $db->prepare($qHistory);
            $stmtHist->execute($historyParams);
            
            foreach ($stmtHist->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $histories[$row['siswa_id']][$row['tahun_ajaran']][$row['semester']] = [
                    'kelas_id' => $row['kelas_id'],
                    'nama_kelas' => $row['nama_kelas']
                ];
            }
        }

        $maxYears = 3; // Default
        if ($tenantId || $filterTenant !== '') {
            $activeTenantId = $tenantId ?: $filterTenant;
            $stmtT = $db->prepare("SELECT bentuk_pendidikan FROM tenants WHERE id = ?");
            $stmtT->execute([$activeTenantId]);
            $bentukPendidikan = $stmtT->fetchColumn();
            if ($bentukPendidikan) {
                $bp = strtoupper(trim($bentukPendidikan));
                if (strpos($bp, 'SD') !== false || $bp === 'MI') {
                    $maxYears = 6;
                } elseif (strpos($bp, 'SMP') !== false || $bp === 'MTS') {
                    $maxYears = 3;
                } elseif (strpos($bp, 'SMK') !== false) {
                    $maxYears = 3; // SMK can be 3 or 4, but let it scale dynamically if data exists
                }
            }
        }
        $matrixData = [];

        foreach ($students as $siswa) {
            $sid = $siswa['id'];
            $studentHistory = $histories[$sid] ?? [];
            
            // Susun berdasarkan tahun ajaran secara urut
            $sortedTahun = array_keys($studentHistory);
            sort($sortedTahun);
            
            $yearsData = [];
            foreach ($sortedTahun as $ta) {
                $semesters = $studentHistory[$ta];
                // Asumsi kelas dalam 1 tahun ajaran sama (diambil dari Ganjil atau Genap)
                $kelasName = '';
                $kelasId = '';
                if (isset($semesters['Ganjil'])) {
                    $kelasName = $semesters['Ganjil']['nama_kelas'] . " (" . $ta . ")";
                    $kelasId = $semesters['Ganjil']['kelas_id'];
                } else if (isset($semesters['Genap'])) {
                    $kelasName = $semesters['Genap']['nama_kelas'] . " (" . $ta . ")";
                    $kelasId = $semesters['Genap']['kelas_id'];
                }
                
                $yearsData[] = [
                    'tahun_ajaran' => $ta,
                    'kelas_id' => $kelasId,
                    'nama_kelas' => $kelasName,
                    'has_ganjil' => isset($semesters['Ganjil']),
                    'has_genap' => isset($semesters['Genap'])
                ];
            }
            
            $isJustPromoted = false;
            if (!empty($yearsData) && $activeTahunAjaran) {
                $lastYear = end($yearsData)['tahun_ajaran'];
                if (strlen($lastYear) === 9 && strlen($activeTahunAjaran) === 9) {
                    $lastYearStart = (int) substr($lastYear, 0, 4);
                    $activeYearStart = (int) substr($activeTahunAjaran, 0, 4);
                    if ($activeYearStart - $lastYearStart === 1) {
                        $isJustPromoted = true;
                    }
                }
            }

            // Tambahkan kelas saat ini ke histori jika belum ada dan siswa masih aktif (tanpa gap tahun)
            if ($activeTahunAjaran && $siswa['status'] === 'Aktif' && !isset($studentHistory[$activeTahunAjaran]) && !empty($siswa['id_kelas']) && (empty($yearsData) || $isJustPromoted)) {
                $yearsData[] = [
                    'tahun_ajaran' => $activeTahunAjaran,
                    'kelas_id' => $siswa['id_kelas'],
                    'nama_kelas' => ($siswa['kelas_aktif'] ?: '-') . " (" . $activeTahunAjaran . ")",
                    'has_ganjil' => false,
                    'has_genap' => false
                ];
            }

            // Jika siswa tidak punya histori sama sekali, pastikan array tidak kosong
            if (empty($yearsData)) {
                $yearsData[] = [
                    'tahun_ajaran' => $activeTahunAjaran ?: '-', // Bisa diset ke tahun ajaran aktif kalau ada
                    'kelas_id' => $siswa['id_kelas'] ?: '', // Ganti id_kelas kalau perlu
                    'nama_kelas' => ($siswa['kelas_aktif'] ?: '-') . ($activeTahunAjaran ? " (" . $activeTahunAjaran . ")" : ""),
                    'has_ganjil' => false,
                    'has_genap' => false
                ];
            }

            if (count($yearsData) > $maxYears) {
                $maxYears = count($yearsData);
            }

            $siswa['years'] = $yearsData;
            $matrixData[] = $siswa;
        }

        $this->jsonResponse([
            'max_years' => $maxYears,
            'data' => $matrixData
        ]);
    }

    public function deleteSiswaApi(): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Metode request tidak diizinkan.");
            }

            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                throw new \Exception("ID Siswa wajib diisi.");
            }

            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'super_admin' && $role !== 'admin') {
                throw new \Exception("Anda tidak memiliki akses untuk menghapus data siswa.");
            }

            $db = \App\Config\Database::getConnection();
            
            // Check existence
            $stmtCheck = $db->prepare("SELECT id, nama_lengkap, tenant_id FROM siswa WHERE id = ? AND deleted_at IS NULL");
            $stmtCheck->execute([$id]);
            $siswa = $stmtCheck->fetch(PDO::FETCH_ASSOC);

            if (!$siswa) {
                throw new \Exception("Data siswa tidak ditemukan atau sudah dihapus.");
            }
            
            // Verify tenant access for Admin
            $tenantId = \App\Core\SessionManager::getTenantId();
            if ($role === 'Admin' && $siswa['tenant_id'] !== $tenantId) {
                throw new \Exception("Akses ditolak. Siswa berada di sekolah lain.");
            }

            $db->beginTransaction();

            // Soft delete the student
            $stmtDel = $db->prepare("UPDATE siswa SET deleted_at = NOW() WHERE id = ?");
            $stmtDel->execute([$id]);

            // Soft delete detail nilai rapor
            $stmtDelRapor = $db->prepare("UPDATE detail_nilai_rapor SET deleted_at = NOW() WHERE siswa_id = ?");
            $stmtDelRapor->execute([$id]);
            
            // Soft delete prestasi
            $stmtDelPrestasi = $db->prepare("
                UPDATE prestasi_siswa ps
                JOIN prestasi_siswa_anggota psa ON ps.id = psa.id_prestasi
                SET ps.deleted_at = NOW()
                WHERE psa.id_siswa = ?
            ");
            $stmtDelPrestasi->execute([$id]);

            // Soft delete pelanggaran
            $stmtDelPelanggaran = $db->prepare("UPDATE catatan_pelanggaran_siswa SET deleted_at = NOW() WHERE siswa_id = ?");
            $stmtDelPelanggaran->execute([$id]);

            // Record activity
            \App\Helpers\ActivityLogger::record('DELETE', 'siswa', $id, $siswa, null);

            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => "Data siswa '{$siswa['nama_lengkap']}' berhasil dihapus."
            ]);
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function printRapotSemesterBulk(): void {
        $kelasId = $_GET['kelas_id'] ?? '';
        $semester = $_GET['semester'] ?? '';
        $ta = $_GET['ta'] ?? '';
        $tempat = $_GET['tempat'] ?? 'Jombang';
        $tanggal = $_GET['tanggal'] ?? '';

        if (empty($kelasId) || empty($semester) || empty($ta)) {
            die("<h1>Bad Request</h1><p>Parameter tidak lengkap.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $db = \App\Config\Database::getConnection();

        // Ambil daftar siswa aktif di kelas ini
        $query = "SELECT id FROM siswa WHERE id_kelas = :kelas_id AND deleted_at IS NULL AND status = 'Aktif'";
        $params = ['kelas_id' => $kelasId];
        if ($tenantId !== null) {
            $query .= " AND tenant_id = :tenant_id";
            $params['tenant_id'] = $tenantId;
        }
        $query .= " ORDER BY nama_lengkap ASC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($studentIds)) {
            die("<h1>Not Found</h1><p>Tidak ada siswa aktif di kelas yang dipilih.</p>");
        }

        // Ambil info tenant / sekolah
        try {
            $stmtTenant = $db->prepare("SELECT * FROM tenants WHERE id = ?");
            $stmtTenant->execute([$tenantId]);
            $tenantInfo = $stmtTenant->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $tenantInfo = [];
        }

        // Ambil data Kepsek saat ini
        $tanggalCetak = $tanggal ?: date('Y-m-d');
        $historicalKepsek = $this->getKepsekAtDate($tenantId, $tanggalCetak);
        if (!empty($historicalKepsek)) {
            $tenantInfo['nama_kepsek'] = $historicalKepsek['nama_kepsek'];
            $tenantInfo['nip_kepsek'] = $historicalKepsek['nip_kepsek'];
        }

        // Dapatkan nama kelas
        try {
            $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = ?");
            $stmtKelas->execute([$kelasId]);
            $kelasName = $stmtKelas->fetchColumn() ?: '-';
        } catch (\Throwable $e) {
            $kelasName = '-';
        }

        // Tipe kurikulum kelas
        $tipePenilaian = 'sederhana';
        $namaKurikulum = 'Kurikulum Merdeka';
        try {
            $qKelasKur = "SELECT r.nama_kurikulum, r.tipe_penilaian 
                          FROM kelas_kurikulum kk
                          JOIN ref_kurikulum r ON kk.kurikulum_id = r.id
                          WHERE kk.kelas_id = :kelas_id AND kk.tahun_ajaran = :tahun_ajaran AND kk.tenant_id = :tenant_id
                          LIMIT 1";
            $stmtKelasKur = $db->prepare($qKelasKur);
            $stmtKelasKur->execute([
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $ta,
                'tenant_id' => $tenantId
            ]);
            $kelasKurInfo = $stmtKelasKur->fetch(PDO::FETCH_ASSOC);
            if ($kelasKurInfo) {
                $namaKurikulum = $kelasKurInfo['nama_kurikulum'];
                $tipePenilaian = $kelasKurInfo['tipe_penilaian'];
            }
        } catch (\Throwable $e) {}

        $siswaModel = new \App\Models\Siswa($tenantId);
        $studentsData = [];

        foreach ($studentIds as $id) {
            $siswa = $siswaModel->findFullById($id);
            if (!$siswa) continue;
            $siswa['tenant_info'] = $tenantInfo;
            $siswa['nama_kelas'] = $kelasName;

            // Fetch grades
            $grades = [];
            try {
                $stmtGrades = $db->prepare("
                    SELECT d.*, m.nama_mapel, k.nama_kelas 
                    FROM detail_nilai_rapor d 
                    JOIN mata_pelajaran m ON d.mapel_id = m.id 
                    LEFT JOIN kelas k ON d.kelas_id = k.id
                    WHERE d.siswa_id = ? AND d.semester = ? AND d.tahun_ajaran = ? 
                    AND d.deleted_at IS NULL 
                    ORDER BY m.nama_mapel ASC
                ");
                $stmtGrades->execute([$id, $semester, $ta]);
                $grades = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Throwable $e) {}

            $siswa['grades'] = $grades;
            $studentsData[] = $siswa;
        }

        // Load correct bulk layout
        if ($tipePenilaian === 'klasik') {
            require __DIR__ . '/../../views/print_rapot_bulk_ktsp.php';
        } elseif ($tipePenilaian === 'kompleks') {
            // Ambil data sikap K-13
            $sikapK13 = [];
            foreach ($studentIds as $id) {
                try {
                    $stmtSikap = $db->prepare("SELECT * FROM nilai_sikap_k13 WHERE siswa_id = ? AND tahun_ajaran = ? AND semester = ? LIMIT 1");
                    $stmtSikap->execute([$id, $ta, $semester]);
                    $sikapRow = $stmtSikap->fetch(PDO::FETCH_ASSOC);
                    if ($sikapRow) {
                        $sikapK13[$id] = $sikapRow;
                    }
                } catch (\Throwable $e) {}
            }
            // QR Code options and parameters
            $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
            $domainName = $_SERVER['HTTP_HOST'];
            $baseFolder = '';
            if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
                $baseFolder = '/SINTA-SaaS';
            }
            $baseVerifyUrl = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=";

            require __DIR__ . '/../../views/print_rapot_bulk_k13.php';
        } else {
            // QR Code options and parameters
            $showQrCode = isset($_GET['show_qrcode']) && $_GET['show_qrcode'] == '1';
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
            $domainName = $_SERVER['HTTP_HOST'];
            $baseFolder = '';
            if (strpos($_SERVER['REQUEST_URI'], '/SINTA-SaaS') !== false) {
                $baseFolder = '/SINTA-SaaS';
            }
            $baseVerifyUrl = $protocol . "://" . $domainName . $baseFolder . "/verify-transkrip?id=";

            require __DIR__ . '/../../views/print_rapot_bulk_merdeka.php';
        }
        exit;
    }

    public function exportPdssSnbp(): void {
        $kelasId = $_GET['kelas_id'] ?? '';
        
        if (empty($kelasId)) {
            die("<h1>Bad Request</h1><p>Parameter kelas_id wajib diisi.</p>");
        }

        $tenantId = SessionManager::getTenantId();
        $db = \App\Config\Database::getConnection();

        // 1. Get Kelas Name
        $stmtKelas = $db->prepare("SELECT nama_kelas FROM kelas WHERE id = :id LIMIT 1");
        $stmtKelas->execute(['id' => $kelasId]);
        $kelasName = $stmtKelas->fetchColumn() ?: 'Kelas';

        // 2. Get students in this class
        $qSiswa = "SELECT id, nama_lengkap, nisn, nis FROM siswa 
                   WHERE id_kelas = :kelas_id AND tenant_id = :tenant_id AND status = 'Aktif' AND deleted_at IS NULL 
                   ORDER BY nama_lengkap ASC";
        $stmtSiswa = $db->prepare($qSiswa);
        $stmtSiswa->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $students = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            die("<h1>Not Found</h1><p>Tidak ada siswa aktif di kelas ini.</p>");
        }

        // 3. Get all subjects mapped to this class across historical years
        $qMapel = "SELECT DISTINCT m.id, m.nama_mapel 
                   FROM detail_nilai_rapor d
                   JOIN mata_pelajaran m ON d.mapel_id = m.id
                   WHERE d.kelas_id = :kelas_id AND d.tenant_id = :tenant_id AND d.deleted_at IS NULL
                   ORDER BY m.nama_mapel ASC";
        $stmtMapel = $db->prepare($qMapel);
        $stmtMapel->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $subjects = $stmtMapel->fetchAll(PDO::FETCH_ASSOC);

        // 4. Get all grades for these students for semesters 1-5 (Ganjil/Genap)
        $qGrades = "SELECT d.siswa_id, d.mapel_id, d.semester, d.tahun_ajaran, d.nilai_akhir 
                    FROM detail_nilai_rapor d
                    WHERE d.kelas_id = :kelas_id AND d.tenant_id = :tenant_id AND d.deleted_at IS NULL 
                      AND d.semester IN ('Ganjil', 'Genap')";
        $stmtGrades = $db->prepare($qGrades);
        $stmtGrades->execute(['kelas_id' => $kelasId, 'tenant_id' => $tenantId]);
        $gradesList = $stmtGrades->fetchAll(PDO::FETCH_ASSOC);

        // Group grades
        $tas = array_unique(array_column($gradesList, 'tahun_ajaran'));
        sort($tas);
        $taMap = [];
        foreach ($tas as $idx => $t) {
            $taMap[$t] = ($idx * 2) + 1; // 1, 3, 5
        }

        $gradesMatrix = [];
        foreach ($gradesList as $row) {
            $ta = $row['tahun_ajaran'];
            $sem = strtolower($row['semester']);
            $baseSem = $taMap[$ta] ?? 1;
            $smtIdx = (strpos($sem, 'genap') !== false) ? $baseSem + 1 : $baseSem;
            
            if ($smtIdx >= 1 && $smtIdx <= 5) {
                $gradesMatrix[$row['siswa_id']][$row['mapel_id']][$smtIdx] = $row['nilai_akhir'];
            }
        }

        // Build Excel Sheet
        $excelData = [];
        $header = ['NISN', 'Nama Siswa'];
        foreach ($subjects as $sub) {
            for ($i = 1; $i <= 5; $i++) {
                $header[] = $sub['nama_mapel'] . " (S$i)";
            }
        }
        $excelData[] = $header;

        foreach ($students as $stu) {
            $row = [
                (string)($stu['nisn'] ?: $stu['nis'] ?: '-'),
                (string)$stu['nama_lengkap']
            ];
            foreach ($subjects as $sub) {
                for ($i = 1; $i <= 5; $i++) {
                    $val = $gradesMatrix[$stu['id']][$sub['id']][$i] ?? '';
                    $row[] = $val !== '' ? (float)$val : '';
                }
            }
            $excelData[] = $row;
        }

        $cleanKelasName = str_replace(' ', '_', $kelasName);
        $filename = "PDSS_SNBP_{$cleanKelasName}_" . date('Y-m-d') . ".xlsx";

        \Shuchkin\SimpleXLSXGen::fromArray($excelData)->downloadAs($filename);
        exit;
    }

    public function toggleLockKelasApi(): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Method not allowed.");
            }
            $input = json_decode(file_get_contents('php://input'), true);
            $kelasId = $input['kelas_id'] ?? '';
            $tahunAjaran = $input['tahun_ajaran'] ?? '';
            $lockStatus = isset($input['lock']) ? (int)$input['lock'] : 0;
            
            if (!$kelasId || !$tahunAjaran) {
                throw new \Exception("Parameter tidak lengkap.");
            }
            
            $role = $_SESSION['role_name'] ?? '';
            if ($role !== 'super_admin' && $role !== 'admin') {
                throw new \Exception("Anda tidak memiliki akses.");
            }
            
            $tenantId = SessionManager::getTenantId() ?: ($input['tenant_id'] ?? null);
            if (!$tenantId) {
                throw new \Exception("Sekolah tidak terdeteksi.");
            }
            
            $db = \App\Config\Database::getConnection();
            
            $stmt = $db->prepare("
                UPDATE kelas_kurikulum 
                SET is_locked = :is_locked 
                WHERE tenant_id = :tenant_id AND kelas_id = :kelas_id AND tahun_ajaran = :tahun_ajaran
            ");
            $stmt->execute([
                'is_locked' => $lockStatus,
                'tenant_id' => $tenantId,
                'kelas_id' => $kelasId,
                'tahun_ajaran' => $tahunAjaran
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => $lockStatus ? 'Rombel kelas berhasil dikunci.' : 'Kunci rombel kelas berhasil dibuka.'
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function verifyTranskrip(): void {
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            die("<h1>Bad Request</h1><p>ID siswa tidak valid.</p>");
        }

        $db = \App\Config\Database::getConnection();
        
        $siswaModel = new \App\Models\Siswa(null);
        $siswa = $siswaModel->findFullById($id);

        if (!$siswa) {
            die("<h1>Not Found</h1><p>Data siswa tidak ditemukan.</p>");
        }

        try {
            $stmtTenant = $db->prepare("SELECT nama_sekolah, npsn, alamat, kecamatan, kabupaten_kota, provinsi, nama_kepsek, nip_kepsek FROM tenants WHERE id = ?");
            $stmtTenant->execute([$siswa['tenant_id']]);
            $siswa['tenant_info'] = $stmtTenant->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            $siswa['tenant_info'] = [];
        }

        $gradesRaw = [];
        try {
            $stmt = $db->prepare("
                SELECT d.*, m.nama_mapel, m.kelompok 
                FROM detail_nilai_rapor d 
                JOIN mata_pelajaran m ON d.mapel_id = m.id 
                WHERE d.siswa_id = ? AND d.deleted_at IS NULL 
                ORDER BY m.kelompok ASC, m.nama_mapel ASC, d.tahun_ajaran ASC, d.semester ASC
            ");
            $stmt->execute([$id]);
            $gradesRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            // Ignore
        }
        $siswa['transkrip_grades'] = $gradesRaw;

        require __DIR__ . '/../../views/verify_transkrip.php';
        exit;
    }

    private function renderOrGetArchive(string $siswaId, string $tenantId, string $filename, callable $renderCallback): void {
        $archiveDir = __DIR__ . "/../../storage/archive/{$tenantId}/{$siswaId}";
        if (!is_dir($archiveDir)) {
            mkdir($archiveDir, 0777, true);
        }
        $filepath = "{$archiveDir}/{$filename}";

        // If archive exists and we are not forcing re-generation, output it directly
        if (file_exists($filepath) && (!isset($_GET['re_generate']) || $_GET['re_generate'] != '1')) {
            readfile($filepath);
            exit;
        }

        // Otherwise, run the callback to buffer HTML, save it, and output it
        ob_start();
        $renderCallback();
        $html = ob_get_clean();

        // Save to archive
        file_put_contents($filepath, $html);

        // Output HTML
        echo $html;
        exit;
    }

    public function clearArchiveApi(): void {
        header('Content-Type: application/json');
        try {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $siswaId = $input['siswa_id'] ?? '';
            $semester = $input['semester'] ?? '';
            $ta = $input['ta'] ?? '';
            $type = $input['type'] ?? 'rapor'; // 'rapor', 'transkrip', 'buku_induk'

            if (empty($siswaId)) {
                throw new \Exception("ID Siswa wajib diisi.");
            }

            $tenantId = SessionManager::getTenantId();
            if (!$tenantId) {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT tenant_id FROM siswa WHERE id = ?");
                $stmt->execute([$siswaId]);
                $tenantId = $stmt->fetchColumn() ?: null;
            }

            if (!$tenantId) {
                throw new \Exception("Sekolah tidak terdeteksi.");
            }

            $cleanTa = str_replace(['/', '\\'], '-', $ta);
            $archiveDir = __DIR__ . "/../../storage/archive/{$tenantId}/{$siswaId}";
            
            if ($type === 'rapor') {
                $filename = "rapor_{$semester}_{$cleanTa}.html";
            } elseif ($type === 'transkrip') {
                $filename = "transkrip.html";
            } else {
                $filename = "buku_induk.html";
            }

            $filepath = "{$archiveDir}/{$filename}";
            if (file_exists($filepath)) {
                unlink($filepath);
            }

            echo json_encode(['success' => true, 'message' => 'Arsip berhasil dihapus. Rapor akan di-render ulang pada cetak berikutnya.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function compileImagesToPdfApi(): void {
        header('Content-Type: application/json');
        try {
            $siswaId = $_POST['siswa_id'] ?? '';
            $jenisDokumen = $_POST['jenis_dokumen'] ?? '';
            $keterangan = $_POST['keterangan'] ?? '';

            if (empty($siswaId) || empty($jenisDokumen)) {
                throw new \Exception("Data wajib diisi (Siswa ID dan Jenis Dokumen).");
            }

            $db = \App\Config\Database::getConnection();
            
            // Get tenant_id of student
            $stmtSiswa = $db->prepare("SELECT tenant_id FROM siswa WHERE id = ?");
            $stmtSiswa->execute([$siswaId]);
            $tenantId = $stmtSiswa->fetchColumn();
            if (!$tenantId) {
                throw new \Exception("Siswa tidak ditemukan.");
            }

            // Ensure archive directory exists
            $archiveDir = realpath(__DIR__ . '/../../storage') . "/archive/{$tenantId}/{$siswaId}";
            if (!is_dir($archiveDir)) {
                mkdir($archiveDir, 0755, true);
            }

            $pdfPath = '';
            $fileSize = 0;

            // Handle direct PDF upload
            if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['pdf_file'];
                if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
                    throw new \Exception("Format file harus PDF.");
                }
                
                $uuid = $this->generateUuid();
                $cleanType = str_replace(' ', '_', strtolower($jenisDokumen));
                $pdfFilename = "{$cleanType}_{$uuid}.pdf";
                $targetFile = "{$archiveDir}/{$pdfFilename}";
                
                if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
                    throw new \Exception("Gagal menyimpan file PDF.");
                }
                
                $pdfPath = "storage/archive/{$tenantId}/{$siswaId}/{$pdfFilename}";
                $fileSize = filesize($targetFile);
            } 
            // Handle images upload and compile to PDF
            elseif (isset($_FILES['images']) && !empty($_FILES['images']['tmp_name'])) {
                $files = $_FILES['images'];
                $tmpNames = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
                $errors = is_array($files['error']) ? $files['error'] : [$files['error']];
                
                // Load FPDF
                require_once __DIR__ . '/../Libraries/fpdf.php';
                $pdf = new \FPDF();
                
                $validImagesCount = 0;
                for ($i = 0; $i < count($tmpNames); $i++) {
                    if ($errors[$i] === UPLOAD_ERR_OK && !empty($tmpNames[$i])) {
                        $tmpPath = $tmpNames[$i];
                        $size = getimagesize($tmpPath);
                        if ($size !== false) {
                            $mime = $size['mime'];
                            $type = strtoupper(substr(strstr($mime, '/'), 1));
                            if ($type === 'JPEG') {
                                $type = 'JPG';
                            }
                            
                            $pdf->AddPage('P', 'A4');
                            // Fit exactly on A4 page (210mm x 297mm)
                            $pdf->Image($tmpPath, 0, 0, 210, 297, $type);
                            $validImagesCount++;
                        }
                    }
                }
                
                if ($validImagesCount === 0) {
                    throw new \Exception("Tidak ada gambar valid yang diunggah.");
                }
                
                $uuid = $this->generateUuid();
                $cleanType = str_replace(' ', '_', strtolower($jenisDokumen));
                $pdfFilename = "{$cleanType}_{$uuid}.pdf";
                $targetFile = "{$archiveDir}/{$pdfFilename}";
                
                $pdf->Output('F', $targetFile);
                
                $pdfPath = "storage/archive/{$tenantId}/{$siswaId}/{$pdfFilename}";
                $fileSize = filesize($targetFile);
            } else {
                throw new \Exception("Unggah file PDF atau foto HP terlebih dahulu.");
            }

            // Save metadata to database
            $uuidDoc = $this->generateUuid();
            $userId = $_SESSION['user_id'] ?? 'SYSTEM';
            $stmtInsert = $db->prepare("INSERT INTO arsip_dokumen_alumni (id, siswa_id, tenant_id, jenis_dokumen, file_path, file_size, keterangan, uploaded_by) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtInsert->execute([$uuidDoc, $siswaId, $tenantId, $jenisDokumen, $pdfPath, $fileSize, $keterangan, $userId]);

            echo json_encode(['success' => true, 'message' => 'Berkas berhasil diarsipkan sebagai PDF.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function fetchDocumentsApi(): void {
        header('Content-Type: application/json');
        try {
            $siswaId = $_GET['siswa_id'] ?? '';
            if (empty($siswaId)) {
                throw new \Exception("Siswa ID wajib diisi.");
            }
            $db = \App\Config\Database::getConnection();
            
            // 1. Fetch archived documents from vault
            $stmt = $db->prepare("SELECT id, jenis_dokumen, file_size, keterangan, created_at 
                                  FROM arsip_dokumen_alumni 
                                  WHERE siswa_id = ? 
                                  ORDER BY created_at DESC");
            $stmt->execute([$siswaId]);
            $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 2. Fetch supporting documents from "dokumen" table (Step 5 uploads)
            $stmtDok = $db->prepare("SELECT * FROM dokumen WHERE id_siswa = ? LIMIT 1");
            $stmtDok->execute([$siswaId]);
            $dok = $stmtDok->fetch(PDO::FETCH_ASSOC);

            if ($dok) {
                $sizes = [];
                if (!empty($dok['file_sizes'])) {
                    $sizes = json_decode($dok['file_sizes'], true) ?: [];
                }

                $columns = [
                    'berkas_kk' => 'Kartu Keluarga (KK)',
                    'berkas_akta' => 'Akta Kelahiran',
                    'berkas_ijazah_sd' => 'Ijazah SD',
                    'berkas_ijazah_smp' => 'Ijazah SMP',
                    'berkas_ijazah_sma' => 'Ijazah SMA',
                    'berkas_mutasi_masuk' => 'Berkas Mutasi Masuk',
                    'berkas_mutasi_keluar' => 'Berkas Mutasi Keluar',
                    'berkas_kip' => 'Berkas KIP / KPS',
                    'berkas_pernyataan_baru' => 'Surat Pernyataan Baru',
                    'berkas_pernyataan_tka' => 'Surat Pernyataan TKA'
                ];

                foreach ($columns as $col => $label) {
                    if (!empty($dok[$col])) {
                        // Insert as virtual document
                        $docs[] = [
                            'id' => "virtual_{$col}_{$siswaId}",
                            'jenis_dokumen' => $label,
                            'file_size' => $sizes[$col] ?? 0,
                            'keterangan' => 'Dokumen dari Pendaftaran/Profil Siswa',
                            'created_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }

            echo json_encode($docs);
        } catch (\Throwable $e) {
            echo json_encode([]);
        }
        exit;
    }

    public function deleteDocumentApi(): void {
        header('Content-Type: application/json');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new \Exception("Metode request tidak diizinkan.");
            }
            $id = $_POST['id'] ?? '';
            if (empty($id)) {
                throw new \Exception("ID Dokumen wajib diisi.");
            }
            
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("SELECT file_path, tenant_id FROM arsip_dokumen_alumni WHERE id = ?");
            $stmt->execute([$id]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$doc) {
                throw new \Exception("Dokumen tidak ditemukan.");
            }
            
            // Delete record
            $stmtDel = $db->prepare("DELETE FROM arsip_dokumen_alumni WHERE id = ?");
            $stmtDel->execute([$id]);
            
            // Delete file
            $fullPath = realpath(__DIR__ . '/../../') . '/' . $doc['file_path'];
            if (file_exists($fullPath)) {
                @unlink($fullPath);
            }
            
            echo json_encode(['success' => true, 'message' => 'Dokumen berhasil dihapus.']);
        } catch (\Throwable $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    public function viewDocumentApi(): void {
        try {
            $id = $_GET['id'] ?? '';
            if (empty($id)) {
                throw new \Exception("ID Dokumen wajib diisi.");
            }
            
            $db = \App\Config\Database::getConnection();
            $doc = null;

            // Check if it's a virtual document
            if (strpos($id, 'virtual_') === 0) {
                $parts = explode('_', $id);
                if (count($parts) >= 3) {
                    $colName = $parts[1] . '_' . $parts[2]; // e.g. berkas_kk
                    $siswaId = implode('_', array_slice($parts, 3)); // e.g. uuid
                    
                    $allowedCols = [
                        'berkas_kk', 'berkas_akta', 'berkas_ijazah_sd', 
                        'berkas_ijazah_smp', 'berkas_ijazah_sma', 'berkas_mutasi_masuk', 
                        'berkas_mutasi_keluar', 'berkas_kip', 'berkas_pernyataan_baru', 
                        'berkas_pernyataan_tka'
                    ];
                    
                    if (in_array($colName, $allowedCols)) {
                        $stmtDok = $db->prepare("
                            SELECT d.{$colName} as file_path, s.tenant_id, s.nama_lengkap 
                            FROM dokumen d 
                            JOIN siswa s ON d.id_siswa = s.id 
                            WHERE d.id_siswa = ? LIMIT 1
                        ");
                        $stmtDok->execute([$siswaId]);
                        $docInfo = $stmtDok->fetch(PDO::FETCH_ASSOC);
                        
                        if ($docInfo && !empty($docInfo['file_path'])) {
                            $doc = [
                                'file_path' => $docInfo['file_path'],
                                'tenant_id' => $docInfo['tenant_id'],
                                'jenis_dokumen' => str_replace('berkas_', '', $colName),
                                'siswa_id' => $siswaId
                            ];
                        }
                    }
                }
            } else {
                // Fetch standard document from vault
                $stmt = $db->prepare("SELECT * FROM arsip_dokumen_alumni WHERE id = ?");
                $stmt->execute([$id]);
                $doc = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if (!$doc) {
                http_response_code(404);
                die("Dokumen tidak ditemukan.");
            }

            // Check security permissions
            $tenantId = SessionManager::getTenantId();
            if ($tenantId && $tenantId !== $doc['tenant_id']) {
                http_response_code(403);
                die("Forbidden: Anda tidak memiliki hak akses ke dokumen sekolah ini.");
            }
            
            // Audit Log
            $userId = $_SESSION['user_id'] ?? 'SYSTEM';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $stmtLog = $db->prepare("INSERT INTO log_akses_arsip (user_id, tenant_id, siswa_id, aktivitas, ip_address, user_agent) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
            $stmtLog->execute([$userId, $doc['tenant_id'], $doc['siswa_id'], "View File {$doc['jenis_dokumen']}", $ip, $ua]);

            $fullPath = realpath(__DIR__ . '/../../') . '/' . $doc['file_path'];
            if (!file_exists($fullPath)) {
                http_response_code(404);
                die("Berkas fisik dokumen tidak ditemukan.");
            }
            
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            if ($ext === 'pdf') {
                header('Content-Type: application/pdf');
            } elseif (in_array($ext, ['png', 'jpg', 'jpeg', 'gif', 'webp'])) {
                header('Content-Type: image/' . ($ext === 'jpg' ? 'jpeg' : $ext));
            } else {
                header('Content-Type: application/octet-stream');
            }
            
            header('Content-Disposition: inline; filename="' . basename($doc['file_path']) . '"');
            header('Content-Length: ' . filesize($fullPath));
            readfile($fullPath);
            exit;
        } catch (\Throwable $e) {
            http_response_code(500);
            die("Error: " . $e->getMessage());
        }
    }

    private function generateUuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
