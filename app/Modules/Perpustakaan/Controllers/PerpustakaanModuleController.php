<?php

namespace App\Modules\Perpustakaan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Perpustakaan\Models\BibliografiModel;

class PerpustakaanModuleController extends BaseController {

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
    }

    /**
     * GET /perpustakaan
     */
    public function indexView(): void {
        require_once __DIR__ . '/../../../../views/perpustakaan/index.php';
    }

    /**
     * API: Ambil katalog buku/bibliografi
     * GET /api/v1/perpustakaan/katalog
     */
    public function getKatalogApi(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $katalog = BibliografiModel::getKatalog($this->tenantId, $limit, $offset);
        $this->jsonResponse(true, $katalog);
    }
}
