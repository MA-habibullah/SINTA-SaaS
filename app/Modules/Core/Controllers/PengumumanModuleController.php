<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Core\Models\PengumumanModel;
use App\Modules\Core\Models\KategoriPengumumanModel;
use App\Config\Database;
use Exception;
use PDO;

class PengumumanModuleController extends BaseController {
    private PengumumanModel $model;
    private KategoriPengumumanModel $kategoriModel;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $tenantId = $this->getSecureTenantId();
        $this->model = new PengumumanModel($tenantId);
        $this->kategoriModel = new KategoriPengumumanModel($tenantId);
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
            if (isset($_GET['tenant_id']) && $_GET['tenant_id'] !== '') {
                return $_GET['tenant_id'];
            }
            if (isset($_POST['tenant_id']) && $_POST['tenant_id'] !== '') {
                return $_POST['tenant_id'];
            }
            return $_SESSION['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? null;
        }
        return $_SESSION['tenant_id'] ?? $_SESSION['user']['tenant_id'] ?? $this->tenantId;
    }

    public function index(): void {
        $tenantId = $this->getSecureTenantId();
        $roleName = $_SESSION['role_name'] ?? $_SESSION['user']['role'] ?? 'guest';
        $isSuperAdmin = $this->isUserSuperAdmin();

        $db = Database::getConnection();
        $tenants = [];
        if ($isSuperAdmin) {
            $stmt = $db->query("SELECT id, nama_sekolah, npsn, subdomain FROM core.tenants WHERE (subdomain != 'admin' AND nama_sekolah NOT ILIKE '%pusat kendali%') AND (status = 'active' OR status IS NULL) ORDER BY nama_sekolah ASC");
            $tenants = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            // Jika tenant_id adalah superadmin global id dan tidak dispesifikasikan di URL, gunakan string kosong agar terpilih opsi 'Semua Sekolah / Tenant'
            if ($tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' && empty($_GET['tenant_id'])) {
                $tenantId = '';
            }
        }

        $data = [
            "title" => "Manajemen Pengumuman & Informasi Sekolah",
            "isSuperAdmin" => $isSuperAdmin,
            "tenants" => $tenants,
            "selectedTenantId" => $tenantId,
            "currentRole" => $roleName
        ];
        
        $this->render("humas/pengumuman", $data);
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: OPTIONS & STATS SUMMARY
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetOptions(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $db = Database::getConnection();

            $kategoriModel = new KategoriPengumumanModel();
            // Fetch all categories so frontend modals can filter options by target tenant
            $kategoriList = $kategoriModel->getAll([]);

            // Fetch available roles for target audience selection
            $stmtRoles = $db->query("SELECT id, nama_role FROM core.roles ORDER BY nama_role ASC");
            $rolesList = $stmtRoles->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $isSuperAdmin = $this->isUserSuperAdmin();
            $tenants = [];
            if ($isSuperAdmin) {
                $stmtTenants = $db->query("SELECT id, nama_sekolah, subdomain FROM core.tenants WHERE (subdomain != 'admin' AND nama_sekolah NOT ILIKE '%pusat kendali%') AND (status = 'active' OR status IS NULL) ORDER BY nama_sekolah ASC");
                $tenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $model = new PengumumanModel($tenantId);
            $stats = $model->getStatsSummary($tenantId);

            $this->jsonResponse([
                'success' => true,
                'data' => [
                    'kategori' => $kategoriList,
                    'roles' => $rolesList,
                    'tenants' => $tenants,
                    'stats' => $stats
                ]
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiGetSummary(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $stats = $this->model->getStatsSummary($tenantId);
            $this->jsonResponse(['success' => true, 'data' => $stats], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: PENGUMUMAN CRUD
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetPengumuman(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $filters = [
                'search' => $_GET['search'] ?? '',
                'kategori_id' => $_GET['kategori_id'] ?? '',
                'visibilitas' => $_GET['visibilitas'] ?? '',
                'is_active' => $_GET['is_active'] ?? '',
                'tanggal' => $_GET['tanggal'] ?? '',
                'tenant_id' => $_GET['tenant_id'] ?? $tenantId
            ];

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $model = new PengumumanModel($tenantId);
            $data = $model->getAll($filters, $limit, $offset);
            $total = $model->countAll($filters);

            $this->jsonResponse([
                'success' => true,
                'data' => $data,
                'total' => $total
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSavePengumuman(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();

            $judul = trim($input['judul'] ?? '');
            $deskripsi = trim($input['deskripsi'] ?? ($input['isi_pengumuman'] ?? ''));
            $kategoriId = !empty($input['kategori_id']) ? trim($input['kategori_id']) : null;
            $visibilitas = !empty($input['visibilitas']) ? trim($input['visibilitas']) : 'public';
            $targetRoles = !empty($input['target_roles']) ? $input['target_roles'] : null;
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
            $id = !empty($input['id']) ? trim($input['id']) : null;

            if (empty($judul)) {
                $this->jsonResponse(['success' => false, 'error' => 'Judul pengumuman wajib diisi.'], 422);
                return;
            }

            $currentUserId = $_SESSION['user']['id'] ?? null;
            $isSuperAdmin = $this->isUserSuperAdmin();
            $effectiveTenant = $isSuperAdmin ? null : $tenantId;
            $model = new PengumumanModel($effectiveTenant);

            $payload = [
                'tenant_id' => array_key_exists('tenant_id', $input) ? ($input['tenant_id'] === 'global' ? null : $input['tenant_id']) : $tenantId,
                'kategori_id' => $kategoriId,
                'created_by' => $currentUserId,
                'judul' => $judul,
                'deskripsi' => $deskripsi,
                'visibilitas' => $visibilitas,
                'target_roles' => $targetRoles,
                'is_active' => $isActive
            ];

            if ($id) {
                $success = $model->update($id, $payload);
                $this->jsonResponse([
                    'success' => $success,
                    'message' => $success ? 'Pengumuman berhasil diperbarui.' : 'Gagal memperbarui pengumuman.'
                ], $success ? 200 : 400);
            } else {
                $newId = $model->create($payload);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Pengumuman baru berhasil diterbitkan.',
                    'id' => $newId
                ], 200);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiToggleStatus(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? '';

            if (empty($id)) {
                $this->jsonResponse(['success' => false, 'error' => 'ID pengumuman tidak valid.'], 422);
                return;
            }

            $model = new PengumumanModel($tenantId);
            $newStatus = $model->toggleActive($id);

            $this->jsonResponse([
                'success' => true,
                'message' => $newStatus ? 'Pengumuman diaktifkan.' : 'Pengumuman dinonaktifkan.',
                'is_active' => $newStatus
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiDeletePengumuman(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_GET['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(['success' => false, 'error' => 'ID pengumuman tidak valid.'], 422);
                return;
            }

            $isSuperAdmin = $this->isUserSuperAdmin();
            $effectiveTenant = $isSuperAdmin ? null : $tenantId;
            $model = new PengumumanModel($effectiveTenant);
            $deleted = $model->delete($id);

            $this->jsonResponse([
                'success' => $deleted,
                'message' => $deleted ? 'Pengumuman berhasil dihapus.' : 'Gagal menghapus pengumuman.'
            ], $deleted ? 200 : 400);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: KATEGORI PENGUMUMAN
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetKategori(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $filters = [
                'search' => $_GET['search'] ?? '',
                'tenant_id' => $_GET['tenant_id'] ?? $tenantId
            ];
            $kategoriModel = new KategoriPengumumanModel($tenantId);
            $data = $kategoriModel->getAll($filters);

            $this->jsonResponse(['success' => true, 'data' => $data], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiSaveKategori(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();

            $namaKategori = trim($input['nama_kategori'] ?? '');
            $id = !empty($input['id']) ? trim($input['id']) : null;

            if (empty($namaKategori)) {
                $this->jsonResponse(['success' => false, 'error' => 'Nama kategori pengumuman wajib diisi.'], 422);
                return;
            }

            $kategoriModel = new KategoriPengumumanModel($tenantId);
            if ($id) {
                $success = $kategoriModel->update($id, $namaKategori);
                $this->jsonResponse([
                    'success' => $success,
                    'message' => $success ? 'Kategori berhasil diperbarui.' : 'Gagal memperbarui kategori.'
                ], $success ? 200 : 400);
            } else {
                $newId = $kategoriModel->create([
                    'tenant_id' => array_key_exists('tenant_id', $input) ? ($input['tenant_id'] === 'global' ? null : $input['tenant_id']) : $tenantId,
                    'nama_kategori' => $namaKategori
                ]);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Kategori baru berhasil ditambahkan.',
                    'id' => $newId
                ], 200);
            }
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiDeleteKategori(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_GET['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(['success' => false, 'error' => 'ID kategori tidak valid.'], 422);
                return;
            }

            $kategoriModel = new KategoriPengumumanModel($tenantId);
            $deleted = $kategoriModel->delete($id);

            $this->jsonResponse([
                'success' => $deleted,
                'message' => $deleted ? 'Kategori berhasil dihapus.' : 'Gagal menghapus kategori.'
            ], $deleted ? 200 : 400);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /* ═══════════════════════════════════════════════════════════════════════
       BACKWARD COMPATIBILITY HTTP FORM HANDLERS
       ═══════════════════════════════════════════════════════════════════════ */

    public function store(): void {
        $this->apiSavePengumuman();
    }

    public function update(): void {
        $this->apiSavePengumuman();
    }

    public function delete(): void {
        $this->apiDeletePengumuman();
    }

    public function storeKategori(): void {
        $this->apiSaveKategori();
    }

    public function updateKategori(): void {
        $this->apiSaveKategori();
    }

    public function deleteKategori(): void {
        $this->apiDeleteKategori();
    }
}
