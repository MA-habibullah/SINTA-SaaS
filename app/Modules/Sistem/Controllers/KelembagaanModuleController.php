<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Sistem\Models\KelembagaanModel;

class KelembagaanModuleController extends BaseController {

    private KelembagaanModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->model = new KelembagaanModel();
    }

    /**
     * GET /master-data
     */
    public function index(): void {
        $tenantId = SessionManager::getTenantId();
        $data = [
            'title' => 'Master Data Kelembagaan & Akademik',
            'userRole' => $_SESSION['role_name'] ?? '',
            'tenantId' => $tenantId,
            'baseUrl' => $this->getBaseUrl()
        ];
        $this->render('master_kelembagaan', $data);
    }

    /**
     * GET /sekolah/identitas
     */
    public function identitasView(): void {
        $tenantId = SessionManager::getTenantId();
        $data = [
            'title' => 'Identitas Profil Sekolah',
            'userRole' => $_SESSION['role_name'] ?? '',
            'tenantId' => $tenantId,
            'baseUrl' => $this->getBaseUrl()
        ];
        $this->render('sekolah_identitas', $data);
    }

    /**
     * GET /api/v1/kelembagaan
     */
    public function fetchApi(): void {
        $tenantId = SessionManager::getTenantId();
        $type = $_GET['type'] ?? 'kelas';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);

        try {
            $result = $this->model->getDataList($tenantId, $type, $page, $limit);
            $this->jsonResponse(true, $result);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/kelembagaan/options
     */
    public function getOptionsApi(): void {
        $tenantId = SessionManager::getTenantId();
        try {
            $options = $this->model->getOptions($tenantId);
            $this->jsonResponse(true, $options);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/kelembagaan/simpan
     */
    public function storeApi(): void {
        $tenantId = SessionManager::getTenantId();
        $input = $this->getJsonInput();

        try {
            $saved = $this->model->saveData($tenantId, $input);
            $this->jsonResponse(true, $saved, 'Data kelembagaan berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/kelembagaan/hapus
     */
    public function deleteApi(): void {
        $tenantId = SessionManager::getTenantId();
        $input = $this->getJsonInput();
        $id = $input['id'] ?? null;
        $type = $input['type'] ?? 'kelas';

        if (!$id) {
            $this->jsonResponse(false, null, 'ID data wajib disertakan.', 400);
        }

        try {
            $this->model->deleteData($tenantId, $type, $id);
            $this->jsonResponse(true, null, 'Data berhasil dihapus.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }
}
