<?php

namespace App\Modules\Sistem\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Sistem\Models\AuditLogModel;

class SistemModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    public function getAuditLogApi(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $logs = AuditLogModel::getLogs($this->tenantId, $limit, $offset);
        $this->jsonResponse(true, $logs);
    }
}
