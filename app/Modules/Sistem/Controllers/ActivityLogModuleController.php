<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Sistem\Models\AuditLogModel;
use App\Helpers\ActivityLogger;
use Exception;
use Throwable;

class ActivityLogModuleController extends BaseController {
    private AuditLogModel $model;

    public function __construct() {
        parent::__construct();
        
        // 1. Wajib Login (Security Gate)
        SessionManager::requireLogin();
        
        // 2. Hak Akses: Super Admin, Admin Sekolah, & Operator Sekolah
        if (!\App\Core\RouteGuard::checkCurrent(['super_admin', 'admin_sekolah', 'operator_sekolah'])) {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
            echo "<h1 style='color: #dc3545;'>403 Akses Ditolak</h1>";
            echo "<p style='color: #6c757d;'>Anda tidak memiliki wewenang untuk mengakses log aktivitas sistem.</p>";
            echo "<a href='/sinta/dashboard'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }

        $this->model = new AuditLogModel();
    }

    protected function isUserSuperAdmin(): bool {
        $role = $_SESSION['user']['role'] ?? $_SESSION['role_name'] ?? '';
        if (in_array(strtolower($role), ['super_admin', 'super admin', 'superadmin', 'admin platform'])) {
            return true;
        }
        $roles = $_SESSION['roles'] ?? [];
        foreach ($roles as $r) {
            if (in_array(strtolower($r), ['super_admin', 'super admin', 'superadmin', 'admin platform'])) {
                return true;
            }
        }
        return false;
    }

    protected function getSecureTenantId(): ?string {
        if ($this->isUserSuperAdmin()) {
            if (isset($_GET['tenant_id']) && !empty($_GET['tenant_id'])) {
                return $_GET['tenant_id'] === 'global' || $_GET['tenant_id'] === 'system' ? null : $_GET['tenant_id'];
            }
            if (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                return $_POST['tenant_id'] === 'global' || $_POST['tenant_id'] === 'system' ? null : $_POST['tenant_id'];
            }
            return $_SESSION['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? null;
        }
        return $_SESSION['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? $this->tenantId;
    }

    /**
     * Tampilkan Halaman Utama Log Aktivitas (Audit Trail)
     * GET /utilitas/log-aktivitas
     */
    public function index(): void {
        $isSuperAdmin = $this->isUserSuperAdmin();
        $tenantId = $this->getSecureTenantId();
        $roleName = $_SESSION['role_name'] ?? $_SESSION['user']['role'] ?? 'guest';

        $filterOptions = $this->model->getFiltersOptions($tenantId, $isSuperAdmin);

        $data = [
            'title'            => 'Audit Trail & Log Aktivitas Sistem',
            'user_nama'        => $_SESSION['nama_lengkap'] ?? $_SESSION['user']['nama_lengkap'] ?? 'User',
            'user_role'        => $roleName,
            'isSuperAdmin'     => $isSuperAdmin,
            'tenants'          => $filterOptions['tenants'] ?? [],
            'selectedTenantId' => $tenantId
        ];
        
        $this->render('sistem/activity_logs', $data);
    }

    /**
     * API: Ambil opsi filter (Daftar Sekolah, Daftar Role, Aksi, dan Modul/Tabel)
     * GET /api/v1/activity-logs/filters
     */
    public function fetchFiltersApi(): void {
        try {
            $isSuperAdmin = $this->isUserSuperAdmin();
            $tenantId = $this->getSecureTenantId();

            $options = $this->model->getFiltersOptions($tenantId, $isSuperAdmin);

            $this->jsonResponse([
                'success' => true,
                'data'    => $options
            ], 200);
        } catch (Throwable $e) {
            error_log("Failed to fetch activity log filters: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Terjadi kesalahan sistem saat memuat filter.'
            ], 500);
        }
    }

    /**
     * API: Ambil ringkasan 4 statistik metrik log aktivitas
     * GET /api/v1/activity-logs/stats
     */
    public function fetchStatsApi(): void {
        try {
            $isSuperAdmin = $this->isUserSuperAdmin();
            $tenantFilter = $_GET['tenant_filter'] ?? $this->getSecureTenantId();

            $stats = $this->model->getStatsSummary($tenantFilter, $isSuperAdmin);

            $this->jsonResponse([
                'success' => true,
                'data'    => $stats
            ], 200);
        } catch (Throwable $e) {
            error_log("Failed to fetch activity log stats: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Gagal memuat statistik audit trail.'
            ], 500);
        }
    }

    /**
     * API: Ambil data log aktivitas terpaginasi & tersaring secara RBAC
     * GET /api/v1/activity-logs?page=...&per_page=...&search=...&tenant_filter=...&role_filter=...&action_filter=...&table_filter=...&start_date=...&end_date=...
     */
    public function fetchApi(): void {
        $isSuperAdmin = $this->isUserSuperAdmin();
        $sessionTenantId = $this->getSecureTenantId();

        $page         = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage      = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 15;
        $search       = isset($_GET['search']) ? trim($_GET['search']) : '';
        $tenantFilter = isset($_GET['tenant_filter']) ? trim($_GET['tenant_filter']) : '';
        $roleFilter   = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : '';
        $actionFilter = isset($_GET['action_filter']) ? trim($_GET['action_filter']) : '';
        $tableFilter  = isset($_GET['table_filter']) ? trim($_GET['table_filter']) : '';
        $startDate    = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
        $endDate      = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

        try {
            $filters = [
                'is_super_admin' => $isSuperAdmin,
                'tenant_id'      => $sessionTenantId,
                'tenant_filter'  => $tenantFilter,
                'role_filter'    => $roleFilter,
                'action_filter'  => $actionFilter,
                'table_filter'   => $tableFilter,
                'search'         => $search,
                'start_date'     => $startDate,
                'end_date'       => $endDate
            ];

            $result = $this->model->getPaginatedLogs($filters, $page, $perPage);

            // Decode old_data & new_data secara konsisten
            foreach ($result['logs'] as &$log) {
                if (!empty($log['old_data']) && is_string($log['old_data'])) {
                    $decoded = json_decode($log['old_data'], true);
                    $log['old_data'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['detail' => $log['old_data']];
                }
                if (!empty($log['new_data']) && is_string($log['new_data'])) {
                    $decoded = json_decode($log['new_data'], true);
                    $log['new_data'] = (json_last_error() === JSON_ERROR_NONE) ? $decoded : ['detail' => $log['new_data']];
                }
            }

            $this->jsonResponse([
                'success'    => true,
                'data'       => $result['logs'],
                'pagination' => $result['pagination']
            ], 200);

        } catch (Throwable $e) {
            error_log("Audit log fetch error: " . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'error'   => 'Terjadi kesalahan sistem saat memuat data log: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * API: Menghapus log aktivitas berdasarkan rentang tanggal
     * POST /api/v1/activity-logs/delete
     */
    public function deleteLogsApi(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'error' => 'Metode request tidak diizinkan.'], 405);
        }

        $isSuperAdmin = $this->isUserSuperAdmin();
        $role = $_SESSION['role_name'] ?? $_SESSION['user']['role'] ?? '';

        if (!$isSuperAdmin && $role !== 'operator_sekolah' && $role !== 'admin_sekolah') {
            $this->jsonResponse(['success' => false, 'error' => 'Akses ditolak.'], 403);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            $this->jsonResponse(['success' => false, 'error' => 'Data input tidak valid.'], 400);
        }

        $startDate = $input['startDate'] ?? $input['start_date'] ?? '';
        $endDate   = $input['endDate'] ?? $input['end_date'] ?? '';
        $targetTenant = $input['tenantId'] ?? $input['tenant_id'] ?? '';

        if (empty($startDate) || empty($endDate)) {
            $this->jsonResponse(['success' => false, 'error' => 'Rentang tanggal harus diisi.'], 400);
        }

        $sessionTenantId = $this->getSecureTenantId();
        if (!$isSuperAdmin) {
            $targetTenant = $sessionTenantId;
        }

        try {
            $deletedRows = $this->model->deleteLogs($startDate, $endDate, $targetTenant, $isSuperAdmin);

            $infoTarget = ($targetTenant === 'all') ? "Semua Sekolah & Sistem" : (($targetTenant === 'system') ? "Sistem (Global)" : "Sekolah ID: $targetTenant");
            if (!$isSuperAdmin) {
                $infoTarget = "Sekolah Sendiri";
            }
            
            ActivityLogger::record(
                'DELETE',
                'sistem.activity_logs',
                null,
                ['deleted_rows' => $deletedRows, 'start_date' => $startDate, 'end_date' => $endDate, 'target' => $infoTarget],
                $sessionTenantId
            );

            $this->jsonResponse([
                'success' => true,
                'message' => "Berhasil menghapus $deletedRows baris log aktivitas."
            ], 200);

        } catch (Throwable $e) {
            error_log("Failed to delete activity logs: " . $e->getMessage());
            $this->jsonResponse(['success' => false, 'error' => 'Gagal menghapus log aktivitas.'], 500);
        }
    }
}
