<?php

namespace App\Modules\Keuangan\Controllers;

use App\Controllers\BaseController;
use App\Modules\Keuangan\Models\TagihanSiswaModel;
use App\Modules\Keuangan\Models\TransaksiPembayaranModel;

class KeuanganModuleController extends BaseController {
    public function getTagihanSiswa(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $siswaId = $_GET['siswa_id'] ?? '';
        if (empty($siswaId)) {
            $this->jsonResponse(false, null, 'Parameter siswa_id wajib diisi', 422);
            return;
        }

        $tagihan = TagihanSiswaModel::getTagihanUnpaid($this->tenantId, $siswaId);
        $this->jsonResponse(true, $tagihan);
    }

    public function getRiwayatPembayaran(): void {
        if (!$this->tenantId) {
            $this->jsonResponse(false, null, 'Tenant ID tidak terdeteksi', 400);
            return;
        }

        $tagihanId = $_GET['tagihan_id'] ?? '';
        if (empty($tagihanId)) {
            $this->jsonResponse(false, null, 'Parameter tagihan_id wajib diisi', 422);
            return;
        }

        $transaksi = TransaksiPembayaranModel::getTransaksiByTagihan($this->tenantId, $tagihanId);
        $this->jsonResponse(true, $transaksi);
    }
}
