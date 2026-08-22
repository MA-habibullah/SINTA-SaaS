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
     * Helper to load access map dictionary for a tenant ID
     */
    private function loadAccessMap(PDO $db, string $targetTenantId): array {
        $accessMap = [];
        $isCustom = false;
        $globalTenantId = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';

        // Check if there are custom role_menu_access entries for this targetTenantId
        if (!empty($targetTenantId) && $targetTenantId !== $globalTenantId) {
            $stmt = $db->prepare("SELECT role_id, menu_id FROM core.role_menu_access WHERE tenant_id = ?");
            $stmt->execute([$targetTenantId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                $isCustom = true;
                foreach ($rows as $r) {
                    $key = $r['role_id'] . '-' . $r['menu_id'];
                    $accessMap[$key] = true;
                }
                return ['access_map' => $accessMap, 'is_custom' => true];
            }
        }

        // Fallback to global default
        $stmt = $db->prepare("SELECT role_id, menu_id FROM core.role_menu_access WHERE tenant_id = ? OR tenant_id IS NULL");
        $stmt->execute([$globalTenantId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $key = $r['role_id'] . '-' . $r['menu_id'];
            $accessMap[$key] = true;
        }

        return ['access_map' => $accessMap, 'is_custom' => false];
    }

    /**
     * GET /konfigurasi/akses
     */
    public function index(): void {
        $userRole = $_SESSION['role_name'] ?? '';
        $tenantId = SessionManager::getTenantId();

        $tenants = [];
        $roles = [];
        $menus = [];
        $accessMap = [];

        try {
            $db = Database::getConnection();

            if ($userRole === 'super_admin') {
                $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM core.tenants ORDER BY nama_sekolah ASC");
                $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $stmtRoles = $db->query("SELECT id, nama_role, deskripsi FROM core.roles ORDER BY id ASC");
            $roles = $stmtRoles->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $stmtMenus = $db->query("SELECT id, parent_id, nama_menu, url, icon, urutan FROM core.menus WHERE is_active = true ORDER BY urutan ASC");
            $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $mapResult = $this->loadAccessMap($db, (string)$tenantId);
            $accessMap = $mapResult['access_map'];
        } catch (\Throwable $e) {
            error_log("Failed to load access control data: " . $e->getMessage());
        }

        $data = [
            'title'      => 'Manajemen User & Hak Akses (RBAC)',
            'user_role'  => $userRole,
            'userRole'   => $userRole,
            'tenantId'   => $tenantId,
            'tenants'    => $tenants,
            'roles'      => $roles,
            'menus'      => $menus,
            'access_map' => $accessMap,
            'baseUrl'    => $this->getBaseUrl()
        ];
        $this->render('sistem/kelola_akses', $data);
    }

    /**
     * GET /api/v1/akses/fetch
     */
    public function fetchAccessMap(): void {
        $userRole = $_SESSION['role_name'] ?? '';
        $sessionTenantId = SessionManager::getTenantId();
        $targetTenantId = $_GET['tenant_id'] ?? $sessionTenantId;

        if ($userRole !== 'super_admin') {
            $targetTenantId = $sessionTenantId;
        }

        try {
            $db = Database::getConnection();
            $result = $this->loadAccessMap($db, (string)$targetTenantId);

            header('Content-Type: application/json');
            echo json_encode([
                'success'    => true,
                'access_map' => $result['access_map'],
                'is_custom'  => $result['is_custom']
            ]);
            exit;
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /konfigurasi/akses/simpan
     */
    public function saveAccessMatrix(): void {
        $userRole = $_SESSION['role_name'] ?? '';
        $sessionTenantId = SessionManager::getTenantId();
        $targetTenantId = $_POST['target_tenant_id'] ?? $sessionTenantId;

        if ($userRole !== 'super_admin' || empty($targetTenantId)) {
            $targetTenantId = $sessionTenantId;
        }

        if (empty($targetTenantId)) {
            $targetTenantId = 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12';
        }

        $access = $_POST['access'] ?? [];

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM core.role_menu_access WHERE tenant_id = ?");
            $stmtDel->execute([$targetTenantId]);

            $stmtIns = $db->prepare("INSERT INTO core.role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");
            foreach ($access as $roleId => $menuIds) {
                if (is_array($menuIds)) {
                    foreach ($menuIds as $menuId) {
                        $stmtIns->execute([$targetTenantId, $roleId, $menuId]);
                    }
                }
            }

            $db->commit();

            \App\Helpers\ActivityLogger::log(
                'UPDATE',
                'core.role_menu_access',
                null,
                ['target_tenant_id' => $targetTenantId, 'roles_updated' => count($access)],
                $targetTenantId
            );

            header('Location: ' . $this->getBaseUrl() . '/konfigurasi/akses?success=' . urlencode('Matriks hak akses berhasil disimpan.'));
            exit;
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            header('Location: ' . $this->getBaseUrl() . '/konfigurasi/akses?error=' . urlencode('Gagal menyimpan hak akses: ' . $e->getMessage()));
            exit;
        }
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
        $tenantId = SessionManager::getTenantId();

        if (!$roleId) {
            $this->jsonResponse(false, null, 'Role ID wajib dipilih.', 400);
            return;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM core.role_menu_access WHERE role_id = ? AND tenant_id = ?");
            $stmtDel->execute([$roleId, $tenantId]);

            $stmtIns = $db->prepare("INSERT INTO core.role_menu_access (tenant_id, role_id, menu_id) VALUES (?, ?, ?)");
            foreach ($menuIds as $mId) {
                $stmtIns->execute([$tenantId, $roleId, $mId]);
            }

            $db->commit();
            $this->jsonResponse(true, null, 'Hak akses role berhasil diperbarui.');
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/akses/user-override
     */
    public function fetchUserAccessOverrides(): void {
        $userId = $_GET['user_id'] ?? null;
        if (!$userId) {
            $this->jsonResponse(false, null, 'User ID wajib diisi.', 400);
            return;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT menu_id::text, is_allowed AS access_grant FROM sistem.user_access_overrides WHERE user_id::text = ?");
            $stmt->execute([$userId]);
            $overrides = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $this->jsonResponse(true, ['overrides' => $overrides]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/akses/user-override/simpan
     */
    public function saveUserAccessOverrides(): void {
        $input = $this->getJsonInput();
        $userId = $input['user_id'] ?? null;
        $overrides = $input['overrides'] ?? [];

        if (!$userId) {
            $this->jsonResponse(false, null, 'User ID wajib diisi.', 400);
            return;
        }

        try {
            $db = Database::getConnection();
            $db->beginTransaction();

            $stmtDel = $db->prepare("DELETE FROM sistem.user_access_overrides WHERE user_id::text = ?");
            $stmtDel->execute([$userId]);

            $stmtIns = $db->prepare("INSERT INTO sistem.user_access_overrides (id, user_id, menu_id, is_allowed, created_at) VALUES (gen_random_uuid(), :user_id::uuid, :menu_id::uuid, :is_allowed, NOW())");
            foreach ($overrides as $ov) {
                if (!empty($ov['menu_id']) && isset($ov['access_grant'])) {
                    $grantVal = filter_var($ov['access_grant'], FILTER_VALIDATE_BOOLEAN);
                    $stmtIns->bindValue(':user_id', $userId);
                    $stmtIns->bindValue(':menu_id', $ov['menu_id']);
                    $stmtIns->bindValue(':is_allowed', $grantVal ? 'true' : 'false', PDO::PARAM_STR);
                    $stmtIns->execute();
                }
            }

            $db->commit();
            $this->jsonResponse(true, null, 'Override hak akses user berhasil disimpan.');
        } catch (\Throwable $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}
