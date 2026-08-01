<?php

namespace App\Modules\Perpustakaan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Perpustakaan\Models\BibliografiModel;
use App\Modules\Perpustakaan\Models\PerpustakaanModel;

class PerpustakaanModuleController extends BaseController {
    private PerpustakaanModel $model;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->model = new PerpustakaanModel();
    }

    /**
     * GET /perpustakaan
     */
    public function indexView(): void {
        $tenantId = $_SESSION['tenant_id'] ?? $this->tenantId;
        $summary = $this->model->getDashboardSummary($tenantId ?: '');
        $pengaturan = $this->model->getPengaturan($tenantId ?: '');
        $ratio = $this->model->getAccreditationStats($tenantId ?: '');

        $data = [
            'title' => 'Dashboard Perpustakaan',
            'summary' => $summary,
            'pengaturan' => $pengaturan,
            'ratio' => $ratio
        ];
        $this->render('perpustakaan/dashboard', $data);
    }

    /**
     * API: Ambil katalog buku/bibliografi
     * GET /api/v1/perpustakaan/katalog
     */
    public function getKatalogApi(): void {
        $tenantId = $_SESSION['tenant_id'] ?? $this->tenantId;
        if (!$tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $katalog = $this->model->getBibliografiList($tenantId, [], $limit, $offset);
        $this->jsonResponse(true, $katalog);
    }
}
