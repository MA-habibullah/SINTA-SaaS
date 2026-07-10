<?php

namespace App\Controllers;

use App\Core\SessionManager;
use PDO;

class BukuIndukController extends BaseController {

    public function __construct() {
        parent::__construct();
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
        $q = "SELECT id, nama_kelas FROM kelas WHERE is_active = 1 AND deleted_at IS NULL";
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

        // Load the print view directly (no layout wrapper)
        require __DIR__ . '/../../views/print_rapot.php';
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

        // Load the print view directly
        require __DIR__ . '/../../views/print_buku_induk.php';
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

        if (stripos($kurikulum, 'Merdeka') !== false) {
            require __DIR__ . '/../../views/print_rapot_merdeka.php';
        } else {
            require __DIR__ . '/../../views/print_rapot_standar.php';
        }
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

        if (stripos($kurikulum, 'Merdeka') !== false) {
            require __DIR__ . '/../../views/print_transkrip_merdeka.php';
        } else {
            require __DIR__ . '/../../views/print_transkrip_standar.php';
        }
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
}
