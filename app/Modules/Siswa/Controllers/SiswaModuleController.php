<?php

namespace App\Modules\Siswa\Controllers;

use App\Controllers\BaseController;
use App\Modules\Siswa\Services\SiswaService;

class SiswaModuleController extends BaseController {
    private SiswaService $siswaService;

    public function __construct() {
        parent::__construct();
        $this->siswaService = new SiswaService();
    }

    public function getActiveSiswa(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $siswa = $this->siswaService->getDaftarSiswaAktif($this->tenantId, $limit, $offset);
        $this->jsonResponse(true, $siswa);
    }
}
