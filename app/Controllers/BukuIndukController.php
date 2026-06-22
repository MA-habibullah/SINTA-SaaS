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
        $q = "SELECT id, nama_kelas FROM kelas WHERE is_active = 1";
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

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $filterKelas = isset($_GET['kelas_id']) ? trim($_GET['kelas_id']) : '';
        $filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
        $filterTenant = isset($_GET['filter_tenant_id']) ? trim($_GET['filter_tenant_id']) : '';

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
            $where[] = "(s.nama_lengkap LIKE :search OR s.nisn LIKE :search OR s.nis LIKE :search)";
            $params['search'] = "%$search%";
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
        
        $this->jsonResponse($siswa);
    }
}
