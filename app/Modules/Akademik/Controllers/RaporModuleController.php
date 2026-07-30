<?php

namespace App\Modules\Akademik\Controllers;

use App\Core\BaseController;
use App\Modules\Akademik\Services\RaporService;

class RaporModuleController extends BaseController {
    private RaporService $raporService;

    public function __construct() {
        parent::__construct();
        $this->raporService = new RaporService();
    }

    public function getNilaiRaporSiswa(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $siswaId = $_GET['siswa_id'] ?? '';
        $semester = $_GET['semester'] ?? '';

        if (empty($siswaId) || empty($semester)) {
            $this->jsonResponse(false, null, 'Parameter siswa_id dan semester wajib diisi', 422);
            return;
        }

        $rapor = $this->raporService->getRaporSiswa($this->tenantId, $siswaId, $semester);
        $this->jsonResponse(true, $rapor);
    }
}
