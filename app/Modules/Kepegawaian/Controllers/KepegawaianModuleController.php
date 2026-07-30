<?php

namespace App\Modules\Kepegawaian\Controllers;

use App\Controllers\BaseController;
use App\Modules\Kepegawaian\Models\PtkIdentitasModel;
use App\Modules\Kepegawaian\Models\DokumenPtkModel;

class KepegawaianModuleController extends BaseController {
    public function getDaftarPtk(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

        $ptk = PtkIdentitasModel::getActivePtk($this->tenantId, $limit, $offset);
        $this->jsonResponse(true, $ptk);
    }

    public function getDokumenPtk(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $ptkId = $_GET['ptk_id'] ?? '';
        if (empty($ptkId)) {
            $this->jsonResponse(false, null, 'Parameter ptk_id wajib diisi', 422);
            return;
        }

        $dokumen = DokumenPtkModel::getDokumenByPtk($this->tenantId, $ptkId);
        $this->jsonResponse(true, $dokumen);
    }
}
