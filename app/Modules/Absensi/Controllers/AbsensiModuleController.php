<?php

namespace App\Modules\Absensi\Controllers;

use App\Controllers\BaseController;
use App\Modules\Absensi\Models\PresensiSiswaModel;
use App\Modules\Absensi\Models\PresensiPtkModel;

class AbsensiModuleController extends BaseController {
    public function getPresensiSiswa(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $presensi = PresensiSiswaModel::getPresensiByTanggal($this->tenantId, $tanggal);
        $this->jsonResponse(true, $presensi);
    }

    public function getPresensiPtk(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');
        $presensi = PresensiPtkModel::getPresensiByTanggal($this->tenantId, $tanggal);
        $this->jsonResponse(true, $presensi);
    }
}
