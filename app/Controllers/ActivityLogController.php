<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

class ActivityLogController extends BaseController {

    public function __construct() {
        parent::__construct();
        
        // 1. Wajib Login (Security Gate)
        SessionManager::requireLogin();
        
        // 2. Hak Akses: Hanya Super Admin & Operator Sekolah yang berwenang membuka Audit Trail
        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
            echo "<h1 style='color: #dc3545;'>403 Akses Ditolak</h1>";
            echo "<p style='color: #6c757d;'>Anda tidak memiliki wewenang untuk mengakses log aktivitas sistem.</p>";
            echo "<a href='/SINTA-SaaS/dashboard'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    /**
     * Tampilkan Halaman Utama Log Aktivitas (Audit Trail)
     * GET /utilitas/log-aktivitas
     */
    public function index(): void {
        $data = [
            'title'     => 'Log Aktivitas Sistem',
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'User',
            'user_role' => $_SESSION['role_name'] ?? '',
        ];
        
        $this->render('activity_logs', $data);
    }

    /**
     * API: Ambil opsi filter (Daftar Sekolah & Daftar Role) untuk dropdown
     * GET /api/v1/activity-logs/filters
     */
    public function fetchFiltersApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        
        try {
            $db = Database::getConnection();
            
            $tenants = [];
            if ($role === 'super_admin') {
                // Ambil daftar sekolah aktif untuk dropdown filter Super Admin
                $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
                $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
            
            // Ambil daftar role unik yang ada di log aktivitas untuk dropdown filter
            $stmtRoles = $db->query("SELECT DISTINCT user_role FROM activity_logs ORDER BY user_role ASC");
            $roles = $stmtRoles->fetchAll(PDO::FETCH_COLUMN);
            
            $this->jsonResponse([
                'success' => true,
                'tenants' => $tenants,
                'roles'   => $roles
            ]);
        } catch (\Throwable $e) {
            error_log("Failed to fetch activity log filters: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memuat filter.'], 500);
        }
    }

    /**
     * API: Ambil data log aktivitas terpaginasi & tersaring secara RBAC (Multi-tenant)
     * GET /api/v1/activity-logs?page=...&per_page=...&search=...&tenant_filter=...&role_filter=...
     */
    public function fetchApi(): void {
        $role = $_SESSION['role_name'] ?? '';
        $tenantId = $_SESSION['tenant_id'] ?? null;

        $page    = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 15;
        $search  = isset($_GET['search']) ? trim($_GET['search']) : '';
        $tenantFilter = isset($_GET['tenant_filter']) ? trim($_GET['tenant_filter']) : '';
        $roleFilter   = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : '';

        if ($page < 1) $page = 1;
        if ($perPage < 1 || $perPage > 100) $perPage = 15;
        $offset = ($page - 1) * $perPage;

        try {
            $db = Database::getConnection();

            // 1. Bangun klausa filter keamanan berbasis Peran (RBAC) dan Filter Kustom
            $whereClauses = [];
            $params = [];

            if ($role === 'super_admin') {
                // Super Admin: Dapat memonitoring log seluruh tenant secara global
                if ($tenantFilter === 'system') {
                    $whereClauses[] = "l.tenant_id IS NULL";
                } elseif (!empty($tenantFilter)) {
                    $whereClauses[] = "l.tenant_id = :tenant_filter";
                    $params['tenant_filter'] = $tenantFilter;
                }
            } else {
                // Operator Sekolah: Hanya boleh memantau log sekolahnya sendiri
                // DAN sama sekali dilarang melihat aksi dari Super Admin demi kerahasiaan platform
                $whereClauses[] = "l.tenant_id = :tenant_id";
                $whereClauses[] = "l.user_role != 'super_admin'";
                $params['tenant_id'] = $tenantId;
            }

            // Filter Role (Dapat digunakan oleh Super Admin atau Operator Sekolah)
            if (!empty($roleFilter)) {
                $whereClauses[] = "l.user_role = :role_filter";
                $params['role_filter'] = $roleFilter;
            }

            // Filter Pencarian
            if (!empty($search)) {
                $whereClauses[] = "(
                    l.action LIKE :search_action OR 
                    l.table_name LIKE :search_table OR 
                    l.user_role LIKE :search_role OR
                    u.nama_lengkap LIKE :search_user OR
                    l.ip_address LIKE :search_ip
                )";
                $searchVal = '%' . $search . '%';
                $params['search_action'] = $searchVal;
                $params['search_table']  = $searchVal;
                $params['search_role']   = $searchVal;
                $params['search_user']   = $searchVal;
                $params['search_ip']     = $searchVal;
            }

            $whereSql = '';
            if (!empty($whereClauses)) {
                $whereSql = 'WHERE ' . implode(' AND ', $whereClauses);
            }

            // 2. Query daftar log terpaginasi
            $sql = "
                SELECT l.*, u.nama_lengkap AS actor_name, t.nama_sekolah
                FROM activity_logs l
                LEFT JOIN users u ON l.user_id = u.id
                LEFT JOIN tenants t ON l.tenant_id = t.id
                {$whereSql}
                ORDER BY l.created_at DESC
                LIMIT :limit OFFSET :offset
            ";

            $stmt = $db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Resolve database IDs in log data to human-readable names
            $this->resolveLogDataIds($logs, $db);

            // 3. Hitung total data terfilter
            $countSql = "
                SELECT COUNT(*) 
                FROM activity_logs l
                LEFT JOIN users u ON l.user_id = u.id
                {$whereSql}
            ";
            $countStmt = $db->prepare($countSql);
            foreach ($params as $key => $val) {
                $countStmt->bindValue(':' . $key, $val, PDO::PARAM_STR);
            }
            $countStmt->execute();
            $total = (int)$countStmt->fetchColumn();

            $this->jsonResponse([
                'success' => true,
                'data'    => $logs,
                'pagination' => [
                    'page'     => $page,
                    'per_page' => $perPage,
                    'total'    => $total,
                    'pages'    => ceil($total / $perPage)
                ]
            ]);

        } catch (\Throwable $e) {
            error_log("Audit log fetch error: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memuat data log.'], 500);
        }
    }

    /**
     * API: Menghapus log aktivitas berdasarkan filter
     * POST /api/v1/activity-logs/delete
     */
    public function deleteLogsApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['error' => 'Metode request tidak diizinkan.'], 405);
        }

        $role = $_SESSION['role_name'] ?? '';
        if ($role !== 'super_admin' && $role !== 'operator_sekolah') {
            $this->jsonResponse(['error' => 'Akses ditolak.'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->jsonResponse(['error' => 'Data tidak valid.'], 400);
        }

        $startDate = $input['startDate'] ?? '';
        $endDate = $input['endDate'] ?? '';
        $targetTenant = $input['tenantId'] ?? '';

        if (!$startDate || !$endDate) {
            $this->jsonResponse(['error' => 'Rentang tanggal harus diisi.'], 400);
        }

        // RBAC Check for tenant isolation
        $sessionTenantId = $_SESSION['tenant_id'] ?? null;
        if ($role === 'operator_sekolah') {
            $targetTenant = $sessionTenantId; // Force self tenant
        } else if ($targetTenant === 'self') {
            $targetTenant = $sessionTenantId;
        }

        try {
            $db = Database::getConnection();

            $sql = "DELETE FROM activity_logs WHERE DATE(created_at) BETWEEN ? AND ?";
            $params = [$startDate, $endDate];

            if ($targetTenant === 'all' && $role === 'super_admin') {
                // Delete everything in range
            } elseif ($targetTenant === 'system' && $role === 'super_admin') {
                $sql .= " AND tenant_id IS NULL";
            } elseif ($targetTenant !== 'all' && $targetTenant !== '') {
                // For a specific tenant (including operator_sekolah)
                $sql .= " AND tenant_id = ?";
                $params[] = $targetTenant;
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            
            $deletedRows = $stmt->rowCount();

            // Integritas Audit: Catat tindakan penghapusan ini
            $infoTarget = ($targetTenant === 'all') ? "Semua Sekolah & Sistem" : (($targetTenant === 'system') ? "Sistem (Global)" : "Sekolah ID: $targetTenant");
            if ($role === 'operator_sekolah') {
                $infoTarget = "Sekolah Sendiri";
            }
            
            // Catat log
            $aktorId = $_SESSION['user_id'] ?? null;
            $aktorNama = $_SESSION['nama_lengkap'] ?? 'System';
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            $logSql = "INSERT INTO activity_logs (tenant_id, actor_id, actor_name, user_role, action, table_name, record_id, old_data, new_data, ip_address, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $logStmt = $db->prepare($logSql);
            $logStmt->execute([
                $sessionTenantId,
                $aktorId,
                $aktorNama,
                $role,
                'DELETE',
                'activity_logs',
                null,
                json_encode(['deleted_rows' => $deletedRows, 'start_date' => $startDate, 'end_date' => $endDate, 'target' => $infoTarget]),
                null,
                $ipAddress
            ]);

            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil menghapus $deletedRows baris log aktivitas."
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to delete activity logs: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal menghapus log aktivitas.'], 500);
        }
    }

}

