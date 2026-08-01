<?php

namespace App\Modules\Smk\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Smk\Models\PklModel;

class SmkModuleController extends BaseController {

    private PklModel $pklModel;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->pklModel = new PklModel();
    }

    /**
     * GET /smk/pkl
     */
    public function indexView(): void {
        $data = [
            'title' => 'Manajemen Praktik Kerja Lapangan (PKL / Prakerin)',
            'user_role' => $_SESSION['role_name'] ?? ''
        ];
        $this->render('smk/pkl_index', $data);
    }

    /**
     * GET /api/v1/smk/pkl
     */
    public function getPklApi(): void {
        $tenantId = SessionManager::getTenantId();
        try {
            $data = $this->pklModel->getAllPkl($tenantId);
            $this->jsonResponse(true, $data);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }
}
