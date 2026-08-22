<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Core\Models\AgendaModel;
use App\Config\Database;
use Exception;
use PDO;

class AgendaModuleController extends BaseController {
    private AgendaModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $tenantId = $this->getSecureTenantId();
        $this->model = new AgendaModel($tenantId);
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
                return $_GET['tenant_id'] === 'global' ? null : $_GET['tenant_id'];
            }
            if (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                return $_POST['tenant_id'] === 'global' ? null : $_POST['tenant_id'];
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
            if ($tenantId === 'e8b1d4c2-9f3a-4e78-b125-6c7d8e9f0a12' && empty($_GET['tenant_id'])) {
                $tenantId = '';
            }
        }

        $data = [
            "title" => "Agenda & Timeline Kegiatan Sekolah",
            "isSuperAdmin" => $isSuperAdmin,
            "tenants" => $tenants,
            "selectedTenantId" => $tenantId,
            "currentRole" => $roleName
        ];
        
        $this->render("humas/agenda", $data);
    }

    /* ═══════════════════════════════════════════════════════════════════════
       API: OPTIONS & SUMMARY
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetOptions(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $db = Database::getConnection();

            $kategoriList = $this->model->getKategoriList($tenantId);

            // Fetch available roles
            $stmtRoles = $db->query("SELECT id, nama_role FROM core.roles ORDER BY nama_role ASC");
            $rolesList = $stmtRoles->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $isSuperAdmin = $this->isUserSuperAdmin();
            $tenants = [];
            if ($isSuperAdmin) {
                $stmtTenants = $db->query("SELECT id, nama_sekolah, subdomain FROM core.tenants WHERE status = 'active' OR status IS NULL ORDER BY nama_sekolah ASC");
                $tenants = $stmtTenants->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $stats = $this->model->getStatsSummary($tenantId);

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
       API: AGENDA CRUD
       ═══════════════════════════════════════════════════════════════════════ */

    public function apiGetAgenda(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $filters = [
                'search' => $_GET['search'] ?? '',
                'kategori' => $_GET['kategori'] ?? '',
                'visibilitas' => $_GET['visibilitas'] ?? '',
                'month' => $_GET['month'] ?? '',
                'is_active' => $_GET['is_active'] ?? '',
                'tenant_id' => $_GET['tenant_id'] ?? $tenantId
            ];

            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $model = new AgendaModel($tenantId);
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

    public function apiSaveAgenda(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();

            $judul = trim($input['nama_agenda_sekolah'] ?? ($input['judul'] ?? ''));
            $kategori = trim($input['kategori'] ?? 'Akademik');
            $deskripsi = trim($input['deskripsi'] ?? ($input['isi'] ?? ''));
            $tglMulai = !empty($input['tanggal_mulai']) ? trim($input['tanggal_mulai']) : date('Y-m-d');
            $tglSelesai = !empty($input['tanggal_selesai']) ? trim($input['tanggal_selesai']) : $tglMulai;
            $waktuMulai = !empty($input['waktu_mulai']) ? trim($input['waktu_mulai']) : '07:30';
            $waktuSelesai = !empty($input['waktu_selesai']) ? trim($input['waktu_selesai']) : '15:00';
            $lokasi = !empty($input['lokasi']) ? trim($input['lokasi']) : 'Kampus Sekolah';
            $pj = !empty($input['penanggung_jawab']) ? trim($input['penanggung_jawab']) : 'Panitia Acara';
            $visibilitas = !empty($input['visibilitas']) ? trim($input['visibilitas']) : 'public';
            $targetRoles = !empty($input['target_roles']) ? $input['target_roles'] : [];
            $isActive = isset($input['is_active']) ? (bool)$input['is_active'] : true;
            $id = !empty($input['id']) ? trim($input['id']) : null;

            if (empty($judul)) {
                $this->jsonResponse(['success' => false, 'error' => 'Nama kegiatan agenda wajib diisi.'], 422);
                return;
            }

            $model = new AgendaModel($tenantId);

            $payload = [
                'tenant_id' => array_key_exists('tenant_id', $input) ? ($input['tenant_id'] === 'global' ? null : $input['tenant_id']) : $tenantId,
                'nama_agenda_sekolah' => $judul,
                'judul' => $judul,
                'kategori' => $kategori,
                'deskripsi' => $deskripsi,
                'tanggal_mulai' => $tglMulai,
                'tanggal_selesai' => $tglSelesai,
                'waktu_mulai' => $waktuMulai,
                'waktu_selesai' => $waktuSelesai,
                'lokasi' => $lokasi,
                'penanggung_jawab' => $pj,
                'visibilitas' => $visibilitas,
                'target_roles' => $targetRoles,
                'is_active' => $isActive
            ];

            if ($id) {
                $success = $model->update($id, $payload);
                $this->jsonResponse([
                    'success' => $success,
                    'message' => $success ? 'Agenda kegiatan berhasil diperbarui.' : 'Gagal memperbarui agenda.'
                ], $success ? 200 : 400);
            } else {
                $newId = $model->create($payload);
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Agenda baru berhasil dijadwalkan.',
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
                $this->jsonResponse(['success' => false, 'error' => 'ID agenda tidak valid.'], 422);
                return;
            }

            $model = new AgendaModel($tenantId);
            $newStatus = $model->toggleActive($id);

            $this->jsonResponse([
                'success' => true,
                'message' => $newStatus ? 'Agenda diaktifkan.' : 'Agenda dinonaktifkan.',
                'is_active' => $newStatus
            ], 200);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function apiDeleteAgenda(): void {
        try {
            $tenantId = $this->getSecureTenantId();
            $input = $this->getJsonInput();
            $id = $input['id'] ?? ($_GET['id'] ?? '');

            if (empty($id)) {
                $this->jsonResponse(['success' => false, 'error' => 'ID agenda tidak valid.'], 422);
                return;
            }

            $model = new AgendaModel($tenantId);
            $deleted = $model->delete($id);

            $this->jsonResponse([
                'success' => $deleted,
                'message' => $deleted ? 'Agenda kegiatan berhasil dihapus.' : 'Gagal menghapus agenda.'
            ], $deleted ? 200 : 400);
        } catch (Exception $e) {
            $this->jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
