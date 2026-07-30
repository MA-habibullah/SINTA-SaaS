<?php

namespace App\Modules\Kesiswaan\Controllers;

use App\Controllers\BaseController;
use App\Modules\Kesiswaan\Models\EkskulModel;
use App\Modules\Kesiswaan\Models\PrestasiSiswaModel;

class KesiswaanModuleController extends BaseController {
    public function getEkskul(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $ekskul = EkskulModel::getActiveEkskul($this->tenantId);
        $this->jsonResponse(true, $ekskul);
    }

    public function getPrestasiSiswa(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $siswaId = $_GET['siswa_id'] ?? '';
        if (empty($siswaId)) {
            $this->jsonResponse(false, null, 'Parameter siswa_id wajib diisi', 422);
            return;
        }

        $prestasi = PrestasiSiswaModel::getPrestasiBySiswa($this->tenantId, $siswaId);
        $this->jsonResponse(true, $prestasi);
    }
}
