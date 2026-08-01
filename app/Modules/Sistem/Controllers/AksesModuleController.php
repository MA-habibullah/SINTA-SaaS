<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Config\Database;
use PDO;

class AksesModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    /**
     * GET /konfigurasi/akses
     */
    public function index(): void {
        $tenantId = SessionManager::getTenantId();
        $data = [
            'title' => 'Manajemen User & Hak Akses (RBAC)',
            'userRole' => $_SESSION['role_name'] ?? '',
            'tenantId' => $tenantId,
            'baseUrl' => $this->getBaseUrl()
        ];
        $this->render('konfigurasi_akses', $data);
    }

    /**
     * GET /api/v1/sistem/akses
     */
    public function fetchApi(): void {
        $tenantId = SessionManager::getTenantId();
        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id, nama_role, deskripsi FROM core.roles ORDER BY id ASC");
            $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtMenus = $db->query("SELECT id, parent_id, nama_menu, icon, urutan FROM core.menus ORDER BY urutan ASC");
            $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);

            $this->jsonResponse(true, [
                'roles' => $roles,
                'menus' => $menus
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/sistem/akses/simpan
     */
    public function saveApi(): void {
        $input = $this->getJsonInput();
        $roleId = $input['role_id'] ?? null;
        $menuIds = $input['menu_ids'] ?? [];

        if (!$roleId) {
            $this->jsonResponse(false, null, 'Role ID wajib dipilih.', 400);
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM core.role_menu_access WHERE role_id = ?");
            $stmtDel->execute([$roleId]);

            $stmtIns = $db->prepare("INSERT INTO core.role_menu_access (role_id, menu_id) VALUES (?, ?)");
            foreach ($menuIds as $mId) {
                $stmtIns->execute([$roleId, $mId]);
            }

            $db->commit();
            $this->jsonResponse(true, null, 'Hak akses role berhasil diperbarui.');
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}
