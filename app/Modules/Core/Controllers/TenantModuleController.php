<?php

namespace App\Modules\Core\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Core\Models\TenantModel;

class TenantModuleController extends BaseController {

    private TenantModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->model = new TenantModel();
    }

    /**
     * GET /api/v1/tenant/info
     */
    public function getInfoApi(): void {
        $tenantId = SessionManager::getTenantId();
        try {
            $info = $this->model->getTenantById($tenantId);
            $this->jsonResponse(true, $info);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}
