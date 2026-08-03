<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Core\Models\TenantModel;

class TenantManagementModuleController extends BaseController {

    private TenantModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->model = new TenantModel();
    }

    /**
     * GET /super-admin/tenants
     */
    public function index(): void {
        $data = [
            'title' => 'Kelola Sekolah (SaaS Tenant Management)',
            'userRole' => $_SESSION['role_name'] ?? '',
            'baseUrl' => $this->getBaseUrl()
        ];
        $this->render('core/tenants_index', $data);
    }

    /**
     * GET /api/v1/super-admin/tenants
     */
    public function fetchApi(): void {
        try {
            $tenants = $this->model->getAllTenants();
            $this->jsonResponse(true, $tenants);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/super-admin/tenants/simpan
     */
    public function storeApi(): void {
        $input = $this->getJsonInput();
        try {
            $isUpdate = !empty($input['id']);
            $saved = $this->model->saveTenant($input);
            
            \App\Helpers\ActivityLogger::log(
                $isUpdate ? 'UPDATE' : 'INSERT',
                'core.tenants',
                $isUpdate ? ['id' => $input['id']] : null,
                $saved ?: $input
            );

            $this->jsonResponse(true, $saved, 'Data sekolah berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/super-admin/tenants/hapus
     */
    public function deleteApi(): void {
        $input = $this->getJsonInput();
        $id = $input['id'] ?? null;

        if (!$id) {
            $this->jsonResponse(false, null, 'ID tenant wajib disertakan.', 400);
        }

        try {
            $this->model->deleteTenant($id);

            \App\Helpers\ActivityLogger::log(
                'DELETE',
                'core.tenants',
                ['id' => $id],
                null
            );

            $this->jsonResponse(true, null, 'Sekolah berhasil dinonaktifkan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }

    /**
     * POST /api/v1/super-admin/tenants/toggle-status
     */
    public function toggleStatusApi(): void {
        $input = $this->getJsonInput();
        $id = $input['id'] ?? null;
        $status = $input['status'] ?? 'active';

        if (!$id) {
            $this->jsonResponse(false, null, 'ID tenant wajib disertakan.', 400);
        }

        try {
            $this->model->updateStatus($id, $status);

            \App\Helpers\ActivityLogger::log(
                'UPDATE',
                'core.tenants',
                ['id' => $id],
                ['status' => $status]
            );

            $this->jsonResponse(true, null, 'Status sekolah berhasil diperbarui.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }
}
