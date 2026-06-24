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
        $siswa['riwayat_kelas'] = $riwayatKelas;

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

        // Dapatkan data headmaster (nama_kepsek & nip_kepsek) dari tenants
        try {
            $db = \App\Config\Database::getConnection();
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

        // Load the print view directly (no layout wrapper)
        require __DIR__ . '/../../views/print_rapot.php';
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
}
