<?php

namespace App\Controllers;

use App\Config\Database;
use App\Core\SessionManager;
use PDO;

class SuperAdminController extends BaseController {

    public function __construct() {
        parent::__construct();

        // 1. Gate Keamanan: Wajib Login
        SessionManager::requireLogin();

        // 2. Middleware khusus CheckIsSuperAdmin (Cybersecurity Enforcement)
        $roleName = $_SESSION['role_name'] ?? '';
        if ($roleName !== 'super_admin') {
            $isApi = str_starts_with($_SERVER['REQUEST_URI'], '/dapodik-spmb/api/');
            if ($isApi) {
                $this->jsonResponse(['error' => 'Akses ditolak. Fitur ini memerlukan wewenang Super Admin Platform.'], 403);
            } else {
                header('Location: /dapodik-spmb/dashboard?error=' . urlencode('Akses ditolak. Halaman tersebut khusus untuk Super Admin Platform.'));
                exit;
            }
        }
    }

    /**
     * Tampilkan halaman manajemen akses menu per tenant
     * GET /super-admin/tenant-menus
     */
    public function index(): void {
        $data = [
            'title' => 'Kelola Akses Menu Per Sekolah (SaaS Tenant)',
            'user_nama' => $_SESSION['nama_lengkap'] ?? 'Super Admin',
            'user_role' => $_SESSION['role_name'] ?? 'super_admin'
        ];

        $this->render('tenant_menus', $data);
    }

    /**
     * API: Ambil daftar seluruh tenant & struktur menu dengan status centang aktif untuk tenant terpilih
     * GET /api/v1/super-admin/tenant-menus/fetch?tenant_id=...
     */
    public function fetchTenantMenus(): void {
        $tenantId = $_GET['tenant_id'] ?? '';

        try {
            $db = Database::getConnection();

            // 1. Ambil semua daftar sekolah (tenants) untuk pilihan dropdown
            $stmtTenants = $db->query("SELECT id, nama_sekolah, npsn, subdomain FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
            $tenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC);

            // 2. Ambil seluruh master menu (Parent & Child)
            $stmtMenus = $db->query("SELECT id, nama_menu, url, icon, parent_id, urutan FROM menus ORDER BY parent_id ASC, urutan ASC");
            $allMenus = $stmtMenus->fetchAll(PDO::FETCH_ASSOC);

            // 3. Ambil daftar menu_id yang saat ini dicentang untuk tenant terpilih
            $checkedMenuIds = [];
            if (!empty($tenantId)) {
                $stmtAccess = $db->prepare("SELECT menu_id FROM tenant_menu_access WHERE tenant_id = :tenant_id");
                $stmtAccess->execute(['tenant_id' => $tenantId]);
                $checkedMenuIds = $stmtAccess->fetchAll(PDO::FETCH_COLUMN) ?: [];
            }

            $this->jsonResponse([
                'success' => true,
                'tenants' => $tenants,
                'menus' => $allMenus,
                'checkedMenuIds' => array_map('intval', $checkedMenuIds)
            ]);

        } catch (\Throwable $e) {
            error_log("Failed to fetch tenant menus access data: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Gagal mengambil data akses menu dari server.'], 500);
        }
    }

    /**
     * API: Simpan/update konfigurasi menu per-sekolah (Tenant)
     * POST /api/v1/super-admin/tenant-menus/save
     */
    public function saveTenantMenuAccess(): void {
        $input = $this->getJsonInput();
        $tenantId = isset($input['tenant_id']) ? trim($input['tenant_id']) : '';
        $menuIds = isset($input['menu_ids']) ? $input['menu_ids'] : [];

        // 1. Validasi Input
        if (empty($tenantId)) {
            $this->jsonResponse(['error' => 'Sekolah (Tenant) wajib dipilih.'], 422);
        }

        if (!is_array($menuIds)) {
            $this->jsonResponse(['error' => 'Format daftar menu tidak valid.'], 422);
        }

        try {
            $db = Database::getConnection();

            // Cek apakah tenant valid di database
            $stmtTenantCheck = $db->prepare("SELECT COUNT(*) FROM tenants WHERE id = :id AND deleted_at IS NULL");
            $stmtTenantCheck->execute(['id' => $tenantId]);
            if ((int)$stmtTenantCheck->fetchColumn() === 0) {
                $this->jsonResponse(['error' => 'Sekolah (Tenant) yang dipilih tidak terdaftar di sistem.'], 404);
            }

            // 2. Eksekusi Transaksi Database (Atomic Operation - DB::beginTransaction() equivalent)
            $db->beginTransaction();

            // A. Hapus seluruh relasi menu lama untuk tenant terpilih
            $stmtDelete = $db->prepare("DELETE FROM tenant_menu_access WHERE tenant_id = :tenant_id");
            $stmtDelete->execute(['tenant_id' => $tenantId]);

            // B. Mass insert relasi menu baru yang dicentang
            if (!empty($menuIds)) {
                // Sanitasi dan persiapkan data batch insert
                $placeholders = [];
                $params = [];
                $i = 0;
                foreach ($menuIds as $menuId) {
                    $placeholders[] = "(:tenant_id_{$i}, :menu_id_{$i})";
                    $params["tenant_id_{$i}"] = $tenantId;
                    $params["menu_id_{$i}"] = (int)$menuId;
                    $i++;
                }

                $sqlInsert = "INSERT INTO tenant_menu_access (tenant_id, menu_id) VALUES " . implode(", ", $placeholders);
                $stmtInsert = $db->prepare($sqlInsert);
                $stmtInsert->execute($params);
            }

            // C. Commit transaksi jika semuanya sukses
            $db->commit();

            $this->jsonResponse([
                'success' => true,
                'message' => 'Akses fitur dan menu sidebar sekolah berhasil diperbarui.'
            ]);

        } catch (\Throwable $e) {
            // Rollback jika terjadi kegagalan sistem
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Failed to save tenant menu access: " . $e->getMessage());
            $this->jsonResponse(['error' => 'Terjadi kesalahan sistem saat memperbarui akses fitur sekolah.'], 500);
        }
    }
}
