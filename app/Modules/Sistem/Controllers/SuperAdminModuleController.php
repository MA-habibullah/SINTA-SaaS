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
     * GET /super-admin/tenant-menus
     */
    public function tenantMenusView(): void {
        $data = [
            'title' => 'Pengaturan Hak Akses Fitur Sekolah (Tenant Level)',
            'userRole' => $_SESSION['role_name'] ?? '',
            'baseUrl' => $this->getBaseUrl()
        ];
        $this->render('super_admin_tenant_menus', $data);
    }

    /**
     * GET /api/v1/super-admin/tenant-menus/fetch
     */
    public function fetchTenantMenus(): void {
        $tenantId = $_GET['tenant_id'] ?? null;
        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID wajib diisi.', 400);
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id, parent_id, nama_menu, icon, urutan FROM core.menus ORDER BY urutan ASC");
            $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtAccess = $db->prepare("SELECT menu_id FROM core.tenant_menu_access WHERE tenant_id = ?");
            $stmtAccess->execute([$tenantId]);
            $activeMenuIds = $stmtAccess->fetchAll(PDO::FETCH_COLUMN);

            $this->jsonResponse(true, [
                'menus' => $menus,
                'active_menu_ids' => $activeMenuIds
            ]);
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

        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID wajib dipilih.', 400);
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM core.tenant_menu_access WHERE tenant_id = ?");
            $stmtDel->execute([$tenantId]);

            $stmtIns = $db->prepare("INSERT INTO core.tenant_menu_access (tenant_id, menu_id) VALUES (?, ?)");
            foreach ($menuIds as $mId) {
                $stmtIns->execute([$tenantId, $mId]);
            }

            $db->commit();
            $this->jsonResponse(true, null, 'Akses menu sekolah berhasil disimpan.');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}
