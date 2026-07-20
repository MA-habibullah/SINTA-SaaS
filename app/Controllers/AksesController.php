<?php

namespace App\Controllers;

use App\Models\Menu;
use App\Core\SessionManager;
use PDO;

class AksesController extends BaseController {

    private Menu $menuModel;

    public function __construct() {
        parent::__construct();

        // 1. Amankan Operasi: Pastikan user sudah login
        SessionManager::requireLogin();

        // 2. Proteksi Otoritas: Super Admin atau Operator Sekolah (SaaS Tenant-Level Access)
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin' && $roleName !== 'operator_sekolah') {
            header('Location: /SINTA-SaaS/dashboard?error=' . urlencode('Akses ditolak. Anda tidak memiliki wewenang untuk mengelola akses menu sidebar.'));
            exit;
        }

        // Inisialisasi Model Menu dengan tenant_id aktif
        $tenantId = SessionManager::getTenantId();
        $this->menuModel = new Menu($tenantId);
    }

    /**
     * Ambil semua tenant (untuk dropdown super admin)
     */
    private function getAllTenants(): array {
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM tenants WHERE status = 'active' ORDER BY nama_sekolah ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * AJAX: Ambil access map untuk tenant tertentu
     * GET /api/v1/akses/fetch?tenant_id=X
     */
    public function fetchAccessMap(): void {
        SessionManager::requireLogin();
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin' && $roleName !== 'operator_sekolah') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
            exit;
        }

        $tenantId = $_GET['tenant_id'] ?? null;
        if (empty($tenantId)) {
            echo json_encode(['success' => false, 'error' => 'tenant_id wajib diisi.']);
            exit;
        }

        try {
            $db = \App\Config\Database::getConnection();
            // Cek apakah tenant ini punya kustomisasi; kalau belum, fallback ke global
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM role_menu_access WHERE tenant_id = ?");
            $stmtCount->execute([$tenantId]);
            $targetTenant = (int)$stmtCount->fetchColumn() > 0 ? $tenantId : '00000000-0000-0000-0000-000000000000';

            $stmt = $db->prepare("SELECT role_id, menu_id FROM role_menu_access WHERE tenant_id = ?");
            $stmt->execute([$targetTenant]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $map = [];
            foreach ($rows as $row) {
                $map[$row['role_id'] . '-' . $row['menu_id']] = true;
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'access_map' => $map, 'is_custom' => ($targetTenant !== '00000000-0000-0000-0000-000000000000')]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Tampilkan Halaman Matriks Kelola Akses Menu
     * GET /konfigurasi/akses
     */
    public function index(): void {
        $menus = $this->menuModel->getAllMenus();
        $roles = $this->menuModel->getAllRoles();
        $accessMap = $this->menuModel->getAccessMap();

        $roleName = $_SESSION['role_name'] ?? '';
        $tenantId = SessionManager::getTenantId();

        // Jika peran adalah operator sekolah, saring data peran & menu secara ketat
        if ($roleName === 'operator_sekolah') {
            // A. Peran bawahan: Guru (3), Siswa (4), Karyawan (6)
            $roles = array_filter($roles, function($r) {
                return in_array((int)$r['id'], [3, 4, 6]);
            });

            // B. Menu yang diizinkan untuk sekolah (tenant) ini oleh Super Admin
            try {
                $db = \App\Config\Database::getConnection();
                $stmt = $db->prepare("SELECT menu_id FROM tenant_menu_access WHERE tenant_id = :tenant_id");
                $stmt->execute(['tenant_id' => $tenantId]);
                $activeMenuIds = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

                $menus = array_filter($menus, function($m) use ($activeMenuIds) {
                    return in_array((int)$m['id'], $activeMenuIds);
                });
            } catch (\Throwable $e) {
                error_log("Failed to load active tenant menus: " . $e->getMessage());
                $menus = [];
            }
        }

        $data = [
            'title'      => ($roleName === 'super_admin') ? 'Kelola Akses Menu Sidebar (Global)' : 'Kelola Akses Menu Sekolah',
            'menus'      => $menus,
            'roles'      => $roles,
            'access_map' => $accessMap,
            'user_nama'  => $_SESSION['nama_lengkap'],
            'user_role'  => $_SESSION['role_name'],
            'tenants'    => ($roleName === 'super_admin') ? $this->getAllTenants() : [],
        ];

        $this->render('kelola_akses', $data);
    }

    /**
     * Simpan Perubahan Matriks Akses Menu (POST)
     * POST /konfigurasi/akses/simpan
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /SINTA-SaaS/konfigurasi/akses?error=' . urlencode('Metode request tidak diizinkan.'));
            exit;
        }

        $roleName = $_SESSION['role_name'] ?? '';

        // Jika super_admin, gunakan tenant_id dari POST (bisa atur untuk sekolah manapun)
        // Jika operator_sekolah, gunakan tenant_id dari session (terkunci ke sekolahnya)
        if ($roleName === 'super_admin') {
            $targetTenantId = $_POST['target_tenant_id'] ?? null;
            if (empty($targetTenantId)) {
                // Tidak ada tenant dipilih = atur global default
                $targetTenantId = '00000000-0000-0000-0000-000000000000';
            }
            // Re-inisialisasi model dengan tenant yang dipilih
            $this->menuModel = new Menu($targetTenantId === '00000000-0000-0000-0000-000000000000' ? null : $targetTenantId);
        }

        // Ambil input checklist akses
        $accessInput = $_POST['access'] ?? [];

        // Sanitasi data masukan secara ketat (Secure by Design)
        $cleanedAccess = [];
        foreach ($accessInput as $roleId => $menus) {
            if (!is_numeric($roleId)) {
                continue;
            }
            
            // Otorisasi Sisi Server: Operator sekolah tidak boleh mengubah akses role super_admin (1) atau operator_sekolah (2)
            if ($roleName === 'operator_sekolah' && in_array((int)$roleId, [1, 2])) {
                continue;
            }

            $cleanedAccess[(int)$roleId] = [];
            if (is_array($menus)) {
                foreach ($menus as $menuId) {
                    if (is_numeric($menuId)) {
                        $cleanedAccess[(int)$roleId][] = (int)$menuId;
                    }
                }
            }
        }

        // Jika operator sekolah menyimpan akses, pastikan data yang tersimpan di database hanya mencakup role bawahan
        if ($roleName === 'operator_sekolah') {
            // Tetap pertahankan akses peran lain yang sudah ada sebelumnya di database untuk tenant ini
            // Agar tidak menimpa peran yang di luar wewenang operator
            $currentAccessMap = $this->menuModel->getAccessMap();
            
            // Rekonstruksi accessData lengkap: gabung wewenang lama + modifikasi baru
            $finalAccess = [];
            
            // Baca wewenang yang ada saat ini
            foreach ($currentAccessMap as $key => $val) {
                list($rId, $mId) = explode('-', $key);
                $rId = (int)$rId;
                $mId = (int)$mId;
                
                // Jika role berada diluar wewenang operator, pertahankan datanya
                if (!in_array($rId, [3, 4, 6])) {
                    if (!isset($finalAccess[$rId])) {
                        $finalAccess[$rId] = [];
                    }
                    $finalAccess[$rId][] = $mId;
                }
            }
            
            // Gabung dengan modifikasi baru dari form operator
            foreach ($cleanedAccess as $rId => $mIds) {
                $finalAccess[$rId] = $mIds;
            }
            
            $cleanedAccess = $finalAccess;
        }

        // Proses penyimpanan lewat transaksi aman di Model
        $success = $this->menuModel->saveAccessMap($cleanedAccess);

        if ($success) {
            header('Location: /SINTA-SaaS/konfigurasi/akses?success=' . urlencode('Matriks hak akses menu berhasil diperbarui.'));
        } else {
            header('Location: /SINTA-SaaS/konfigurasi/akses?error=' . urlencode('Terjadi kesalahan sistem saat memperbarui matriks hak akses.'));
        }
        exit;
    }

    /**
     * API: Ambil semua menu & menu tercentang khusus untuk seorang user
     * GET /api/v1/akses/user-override?user_id=X
     */
    public function fetchUserAccessOverrides(): void {
        header('Content-Type: application/json');
        
        // Pastikan super_admin atau operator_sekolah
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin' && $roleName !== 'operator_sekolah') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
            exit;
        }

        $userId = $_GET['user_id'] ?? '';
        $tenantId = \App\Core\SessionManager::getTenantId();
        if (empty($userId)) {
            echo json_encode(['success' => false, 'error' => 'user_id wajib diisi.']);
            exit;
        }

        try {
            $db = \App\Config\Database::getConnection();
            
            // 1. Ambil semua menu yang didukung oleh tenant ini
            $stmtMenus = $db->prepare("
                SELECT m.id, m.nama_menu, m.parent_id 
                FROM menus m 
                JOIN tenant_menu_access tma ON m.id = tma.menu_id 
                WHERE tma.tenant_id = ? 
                ORDER BY m.parent_id ASC, m.urutan ASC
            ");
            $stmtMenus->execute([$tenantId]);
            $menus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);

            // 2. Ambil menu ter-override untuk user ini
            $stmtChecked = $db->prepare("SELECT menu_id FROM user_menu_access WHERE user_id = ? AND tenant_id = ?");
            $stmtChecked->execute([$userId, $tenantId]);
            $checkedIds = $stmtChecked->fetchAll(PDO::FETCH_COLUMN) ?: [];

            echo json_encode([
                'success' => true, 
                'menus' => $menus, 
                'checked_ids' => array_map('intval', $checkedIds)
            ]);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: Simpan ceklis override menu untuk user
     * POST /api/v1/akses/user-override/simpan
     */
    public function saveUserAccessOverrides(): void {
        header('Content-Type: application/json');

        // Pastikan super_admin atau operator_sekolah
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin' && $roleName !== 'operator_sekolah') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Akses ditolak.']);
            exit;
        }

        $tenantId = \App\Core\SessionManager::getTenantId();
        $userId = $_POST['user_id'] ?? '';
        $menuIds = $_POST['menu_ids'] ?? []; // Array ID menu

        if (empty($userId)) {
            echo json_encode(['success' => false, 'error' => 'user_id wajib diisi.']);
            exit;
        }

        try {
            $db = \App\Config\Database::getConnection();
            $db->beginTransaction();

            // Hapus semua override lama untuk user ini di tenant terkait
            $stmtDel = $db->prepare("DELETE FROM user_menu_access WHERE user_id = ? AND tenant_id = ?");
            $stmtDel->execute([$userId, $tenantId]);

            // Insert baru
            if (!empty($menuIds)) {
                $stmtIns = $db->prepare("INSERT INTO user_menu_access (tenant_id, user_id, menu_id) VALUES (?, ?, ?)");
                foreach ($menuIds as $mid) {
                    $stmtIns->execute([$tenantId, $userId, (int)$mid]);
                }
            }

            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Hak akses khusus pengguna berhasil diperbarui.']);
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
