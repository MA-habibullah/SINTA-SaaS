<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\FileStorage;
use App\Core\SessionManager;
use App\Config\Database;
use App\Modules\Sistem\Models\KelembagaanModel;
use PDO;

class KelembagaanModuleController extends BaseController {

    private KelembagaanModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $tenantId = SessionManager::getTenantId();
        $this->model = new KelembagaanModel($tenantId);
    }

    /**
     * GET /master-data
     */
    public function index(): void {
        $tenantId = SessionManager::getTenantId();
        $userRole = $_SESSION['role_name'] ?? '';
        $tenantList = [];
        if ($userRole === 'super_admin') {
            $tenantList = $this->model->getTenants();
        }
        $data = [
            'title'       => 'Master Data Kelembagaan & Akademik',
            'userRole'    => $userRole,
            'user_role'   => $userRole,
            'tenantId'    => $tenantId,
            'tenant_id'   => $tenantId,
            'tenantList'  => $tenantList,
            'tenant_list' => $tenantList,
            'baseUrl'     => $this->getBaseUrl()
        ];
        $this->render('sistem/master_kelembagaan', $data);
    }

    /**
     * GET /sekolah/identitas
     */
    public function identitasView(): void {
        $userRole = $_SESSION['role_name'] ?? '';
        $tenantId = SessionManager::getTenantId();
        
        $tenantsList = [];
        if ($userRole === 'super_admin') {
            $tenantsList = $this->model->getTenants();
        }

        $selectedTenantId = $_GET['tenant_id'] ?? $tenantId;
        if ($userRole !== 'super_admin' || empty($selectedTenantId)) {
            $selectedTenantId = $tenantId;
        }

        // Handle AJAX request for profile detail
        if (isset($_GET['ajax']) && $_GET['ajax'] == '1' && ($_GET['action'] ?? '') === 'get_profile_detail') {
            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT * FROM core.tenants WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $selectedTenantId]);
                $tenantData = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
                if (!empty($tenantData)) {
                    $tenantData['kurikulum'] = $tenantData['kurikulum_terapan'] ?? ($tenantData['kurikulum'] ?? '');
                    $tenantData['alamat_sekolah'] = $tenantData['alamat'] ?? '';
                    $tenantData['no_telp'] = $tenantData['telepon'] ?? '';
                    $tenantData['email_sekolah'] = $tenantData['email'] ?? '';
                }
                $this->jsonResponse(true, $tenantData);
            } catch (\Throwable $e) {
                $this->jsonResponse(false, null, $e->getMessage(), 500);
            }
            return;
        }

        $data = [
            'title'       => 'Identitas Profil Sekolah',
            'user_role'   => $userRole,
            'userRole'    => $userRole,
            'tenantId'    => $selectedTenantId,
            'tenantsList' => $tenantsList,
            'baseUrl'     => $this->getBaseUrl()
        ];
        $this->render('sistem/sekolah_profil', $data);
    }

    /**
     * POST /api/v1/sekolah/update
     */
    public function updateProfile(): void {
        $userRole = $_SESSION['role_name'] ?? '';
        $sessionTenantId = SessionManager::getTenantId();
        $targetTenantId = $_POST['tenant_id'] ?? $sessionTenantId;

        if ($userRole !== 'super_admin' || empty($targetTenantId)) {
            $targetTenantId = $sessionTenantId;
        }

        if (empty($targetTenantId)) {
            $this->jsonResponse(false, null, 'ID Sekolah/Tenant tidak valid.', 400);
            return;
        }

        try {
            $db = Database::getConnection();

            // Fetch old state for audit log
            $oldStmt = $db->prepare("SELECT * FROM core.tenants WHERE id = :id LIMIT 1");
            $oldStmt->execute(['id' => $targetTenantId]);
            $oldTenantData = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            // Prepare update fields (support both column name variants)
            $fields = [
                'alamat'            => $_POST['alamat'] ?? ($_POST['alamat_sekolah'] ?? ''),
                'rt_rw'             => $_POST['rt_rw'] ?? '',
                'kode_pos'          => $_POST['kode_pos'] ?? '',
                'kelurahan'         => $_POST['kelurahan'] ?? '',
                'kecamatan'         => $_POST['kecamatan'] ?? '',
                'kabupaten_kota'    => $_POST['kabupaten_kota'] ?? '',
                'provinsi'          => $_POST['provinsi'] ?? '',
                'telepon'           => $_POST['telepon'] ?? ($_POST['no_telp'] ?? ''),
                'email'             => $_POST['email'] ?? ($_POST['email_sekolah'] ?? ''),
                'website'           => $_POST['website'] ?? '',
                'nama_kepsek'       => $_POST['nama_kepsek'] ?? '',
                'pangkat_kepsek'    => $_POST['pangkat_kepsek'] ?? '',
                'nip_kepsek'        => $_POST['nip_kepsek'] ?? '',
                'nama_operator'     => $_POST['nama_operator'] ?? '',
                'email_operator'    => $_POST['email_operator'] ?? '',
                'akreditasi'        => $_POST['akreditasi'] ?? 'A (Unggul)',
                'updated_at'        => date('Y-m-d H:i:s')
            ];

            // Kurikulum can be edited by both Super Admin and Admin Sekolah
            if (isset($_POST['kurikulum'])) {
                $fields['kurikulum_terapan'] = $_POST['kurikulum'];
            } elseif (isset($_POST['kurikulum_terapan'])) {
                $fields['kurikulum_terapan'] = $_POST['kurikulum_terapan'];
            }

            // Data Identitas Pokok can ONLY be edited by Super Admin
            if ($userRole === 'super_admin') {
                if (isset($_POST['nama_sekolah']) && trim($_POST['nama_sekolah']) !== '') {
                    $fields['nama_sekolah'] = trim($_POST['nama_sekolah']);
                }
                if (isset($_POST['npsn']) && trim($_POST['npsn']) !== '') {
                    $fields['npsn'] = trim($_POST['npsn']);
                }
                if (isset($_POST['bentuk_pendidikan']) && trim($_POST['bentuk_pendidikan']) !== '') {
                    $fields['bentuk_pendidikan'] = trim($_POST['bentuk_pendidikan']);
                }
                if (isset($_POST['status_sekolah']) && trim($_POST['status_sekolah']) !== '') {
                    $fields['status_sekolah'] = trim($_POST['status_sekolah']);
                }
            }

            // File Upload: Logo — format: logos/{tenant_id}/{sha1}.ext
            if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
                $newLogoPath = FileStorage::store(
                    $_FILES['logo']['tmp_name'],
                    'logos',
                    $targetTenantId,
                    null,
                    'image_only'
                );
                if ($newLogoPath !== null) {
                    // Hapus logo lama
                    if (!empty($oldTenantData['logo'])) {
                        FileStorage::deleteOld('storage/app/public/' . $oldTenantData['logo'], $targetTenantId);
                    }
                    $fields['logo'] = str_replace('storage/app/public/', '', $newLogoPath);
                }
            }

            // File Upload: Sertifikat Akreditasi — format: sertifikat/{tenant_id}/{sha1}.pdf
            if (!empty($_FILES['sertifikat_akreditasi']['tmp_name']) && $_FILES['sertifikat_akreditasi']['error'] === UPLOAD_ERR_OK) {
                $newCertPath = FileStorage::store(
                    $_FILES['sertifikat_akreditasi']['tmp_name'],
                    'sertifikat',
                    $targetTenantId,
                    null,
                    'default'
                );
                if ($newCertPath !== null) {
                    // Hapus sertifikat lama
                    if (!empty($oldTenantData['sertifikat_akreditasi'])) {
                        FileStorage::deleteOld('storage/app/public/' . $oldTenantData['sertifikat_akreditasi'], $targetTenantId);
                    }
                    $fields['sertifikat_akreditasi'] = str_replace('storage/app/public/', '', $newCertPath);
                }
            }

            $setClause = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($fields)));
            $fields['id'] = $targetTenantId;

            $stmt = $db->prepare("UPDATE core.tenants SET $setClause WHERE id = :id");
            $stmt->execute($fields);

            // Audit Trail Activity Logger
            \App\Helpers\ActivityLogger::log('UPDATE', 'core.tenants', $oldTenantData, $fields, $targetTenantId);

            $this->jsonResponse(true, null, 'Profil identitas sekolah berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, 'Gagal menyimpan profil: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/kelembagaan
     */
    public function fetchApi(): void {
        $module = $_GET['module'] ?? ($_GET['type'] ?? 'kelas');
        $role = $_SESSION['role_name'] ?? '';

        if ($role === 'super_admin') {
            $filterTenantId = $_GET['filter_tenant_id'] ?? null;
            if ($filterTenantId !== null) {
                $this->model->setTenantId($filterTenantId !== '' ? $filterTenantId : null);
            }
        }

        try {
            $filters = [
                'search'   => $_GET['search'] ?? '',
                'page'     => (int)($_GET['page'] ?? 1),
                'per_page' => (int)($_GET['per_page'] ?? ($_GET['limit'] ?? 10)),
                'trash'    => $_GET['trash'] ?? 'false'
            ];
            $result = $this->model->getPaginated($module, $filters);
            $this->jsonResponse(true, $result);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/kelembagaan/options
     */
    public function getOptionsApi(): void {
        $module = $_GET['module'] ?? 'jenjang';
        $role = $_SESSION['role_name'] ?? '';

        if ($role === 'super_admin' && !empty($_GET['tenant_id'])) {
            $this->model->setTenantId($_GET['tenant_id']);
        }

        try {
            $options = $this->model->getOptions($module);
            $this->jsonResponse(true, $options);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/kelembagaan/simpan
     */
    public function storeApi(): void {
        $input = $this->getJsonInput();
        $module = $input['module'] ?? 'kelas';
        $id = $input['id'] ?? null;
        $role = $_SESSION['role_name'] ?? '';

        if ($role === 'super_admin' && !empty($input['tenant_id'])) {
            $this->model->setTenantId($input['tenant_id']);
        } elseif ($role !== 'super_admin') {
            $this->model->setTenantId(SessionManager::getTenantId());
        }

        try {
            if ($id) {
                $this->model->update($module, $id, $input);
                $this->jsonResponse(true, ['id' => $id], 'Data kelembagaan berhasil diperbarui.');
            } else {
                $newId = $this->model->create($module, $input);
                $this->jsonResponse(true, ['id' => $newId], 'Data kelembagaan berhasil ditambahkan.');
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/kelembagaan/hapus
     */
    public function deleteApi(): void {
        $input = $this->getJsonInput();
        $id = $input['id'] ?? null;
        $module = $input['module'] ?? ($input['type'] ?? 'kelas');

        if (!$id) {
            $this->jsonResponse(false, null, 'ID data wajib disertakan.', 400);
            return;
        }

        try {
            $this->model->delete($module, $id);
            $this->jsonResponse(true, null, 'Data berhasil dipindahkan ke tong sampah.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/kelembagaan/restore
     */
    public function restoreApi(): void {
        $input = $this->getJsonInput();
        $id = $input['id'] ?? null;
        $module = $input['module'] ?? 'kelas';

        if (!$id) {
            $this->jsonResponse(false, null, 'ID data wajib disertakan.', 400);
            return;
        }

        try {
            $this->model->restore($module, $id);
            $this->jsonResponse(true, null, 'Data berhasil dipulihkan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/kelembagaan/toggle-status
     */
    public function toggleStatusApi(): void {
        $input = $this->getJsonInput();
        $id = $input['id'] ?? null;
        $module = $input['module'] ?? 'kelas';

        if (!$id) {
            $this->jsonResponse(false, null, 'ID data wajib disertakan.', 400);
            return;
        }

        try {
            $this->model->toggleStatus($module, $id);
            $this->jsonResponse(true, null, 'Status keaktifan berhasil diubah.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * GET /api/v1/kelembagaan/tenants
     */
    public function getTenantsApi(): void {
        try {
            $tenants = $this->model->getTenants();
            $this->jsonResponse(true, $tenants);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/riwayat-kepsek
     */
    public function getRiwayatKepsekApi(): void {
        $tenantId = $_GET['filter_tenant_id'] ?? SessionManager::getTenantId();
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM core.tenants WHERE id = ? LIMIT 1");
            $stmt->execute([$tenantId]);
            $tenant = $stmt->fetch(\PDO::FETCH_ASSOC);

            $this->jsonResponse(true, [
                'nama_kepsek' => $tenant['nama_kepsek'] ?? '-',
                'nip_kepsek' => $tenant['nip_kepsek'] ?? '-',
                'riwayat' => []
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(true, ['nama_kepsek' => '-', 'nip_kepsek' => '-', 'riwayat' => []]);
        }
    }
}
