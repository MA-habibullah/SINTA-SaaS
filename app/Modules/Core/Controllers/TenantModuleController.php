<?php

namespace App\Modules\Core\Controllers;

use App\Controllers\BaseController;
use App\Modules\Core\Services\TenantService;

class TenantModuleController extends BaseController {
    private TenantService $tenantService;

    public function __construct() {
        parent::__construct();
        $this->tenantService = new TenantService();
    }

    public function getProfile(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $profile = $this->tenantService->getTenantProfile($this->tenantId);
        if (!$profile) {
            $this->jsonResponse(false, null, 'Data tenant tidak ditemukan', 44);
            return;
        }

        $this->jsonResponse(true, $profile);
    }
}
