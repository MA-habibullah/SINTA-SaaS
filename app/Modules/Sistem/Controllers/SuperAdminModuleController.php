<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class SuperAdminModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();

        $roleName = $_SESSION['role_name'] ?? '';
        if (!in_array($roleName, ['super_admin', 'superadmin'], true)) {
            if ($this->isJsonRequest()) {
                $this->jsonResponse(false, null, 'Akses ditolak. Fitur ini khusus Super Admin Platform.', 403);
            }
            header('Location: ' . $this->getBaseUrl() . '/dashboard');
            exit;
        }
    }

    /**
     * GET /super-admin/tenant-menus (Alias for index)
     */
    public function index(): void {
        $this->tenantMenusView();
    }

    /**
     * GET /super-admin/tenant-menus
     */
    public function tenantMenusView(): void {
        $data = [
            'title'    => 'Pengaturan Ketersediaan Fitur Sekolah (Tenant Level)',
            'userRole' => $_SESSION['role_name'] ?? '',
            'baseUrl'  => $this->getBaseUrl()
        ];
        $this->render('sistem/tenant_menus', $data);
    }

    /**
     * GET /api/v1/super-admin/tenant-menus/fetch
     */
    public function fetchTenantMenus(): void {
        $tenantId = $_GET['tenant_id'] ?? null;

        try {
            $db = Database::getConnection();

            $stmtTenants = $db->query("SELECT id, nama_sekolah, npsn, subdomain FROM core.tenants ORDER BY nama_sekolah ASC");
            $tenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Audit Note: core.menus adalah katalog menu master global platform (tanpa tenant_id & deleted_at)
            $stmtMenus = $db->query("SELECT id, parent_id, nama_menu, icon, urutan, url FROM core.menus WHERE is_active = true ORDER BY urutan ASC");
            $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $checkedMenuIds = [];
            if ($tenantId && preg_match('/^[a-f0-9\-]{36}$/i', (string)$tenantId)) {
                $stmtAccess = $db->prepare("SELECT menu_id FROM core.tenant_menu_access WHERE tenant_id = :tenant_id::uuid");
                $stmtAccess->execute(['tenant_id' => $tenantId]);
                $checkedMenuIds = $stmtAccess->fetchAll(PDO::FETCH_COLUMN) ?: [];

                // Jika tenant belum memiliki record kustomisasi khusus di tenant_menu_access, default-kan seluruh menu aktif
                if (empty($checkedMenuIds)) {
                    $checkedMenuIds = array_column($menus, 'id');
                }
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success'         => true,
                'data'            => [
                    'tenants'         => $tenants,
                    'menus'           => $menus,
                    'checkedMenuIds'  => $checkedMenuIds,
                    'active_menu_ids' => $checkedMenuIds
                ],
                'tenants'         => $tenants,
                'menus'           => $menus,
                'checkedMenuIds'  => $checkedMenuIds,
                'active_menu_ids' => $checkedMenuIds
            ]);
            exit;
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/super-admin/tenant-menus/save
     */
    public function saveTenantMenuAccess(): void {
        $input = $this->getJsonInput();
        $tenantId = $input['tenant_id'] ?? null;
        $menuIds = $input['menu_ids'] ?? [];

        if (!$tenantId || !preg_match('/^[a-f0-9\-]{36}$/i', (string)$tenantId)) {
            $this->jsonResponse(false, null, 'Tenant ID wajib dipilih dengan format valid.', 400);
            return;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM core.tenant_menu_access WHERE tenant_id = :tenant_id::uuid");
            $stmtDel->execute(['tenant_id' => $tenantId]);

            $stmtIns = $db->prepare("INSERT INTO core.tenant_menu_access (tenant_id, menu_id) VALUES (:tenant_id::uuid, :menu_id::uuid)");
            foreach ($menuIds as $mId) {
                if (!empty($mId) && preg_match('/^[a-f0-9\-]{36}$/i', (string)$mId)) {
                    $stmtIns->execute(['tenant_id' => $tenantId, 'menu_id' => $mId]);
                }
            }

            $db->commit();

            \App\Helpers\ActivityLogger::log(
                'UPDATE',
                'core.tenant_menu_access',
                null,
                ['tenant_id' => $tenantId, 'total_menu_ids' => count($menuIds)],
                $tenantId
            );

            $this->jsonResponse(true, null, 'Ketersediaan modul fitur sekolah berhasil disimpan.');
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * GET /super-admin/server-monitor
     */
    public function serverMonitorView(): void {
        $controller = new ServerMonitorModuleController();
        $controller->index();
    }

    /**
     * GET /api/v1/super-admin/server-monitor/fetch
     */
    public function fetchServerMonitorApi(): void {
        $controller = new ServerMonitorModuleController();
        $controller->fetchApi();
    }

    /**
     * POST /api/v1/super-admin/server-monitor/save-network
     */
    public function saveNetworkConfigApi(): void {
        $controller = new ServerMonitorModuleController();
        $controller->saveNetworkConfig();
    }

    /**
     * POST /api/v1/super-admin/server-monitor/update-server
     */
    public function updateServerApi(): void {
        $controller = new ServerMonitorModuleController();
        $controller->updateServer();
    }
}
