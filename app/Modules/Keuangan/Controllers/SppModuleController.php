<?php

namespace App\Modules\Keuangan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Keuangan\Models\TagihanSiswaModel;
use App\Modules\Keuangan\Models\TransaksiPembayaranModel;

class SppModuleController extends BaseController {

    private TagihanSiswaModel $tagihanModel;
    private TransaksiPembayaranModel $transaksiModel;

    public function __construct() {
        parent::__construct();
        SessionManager::requireLogin();
        $this->tagihanModel = new TagihanSiswaModel();
        $this->transaksiModel = new TransaksiPembayaranModel();
    }

    /**
     * GET /keuangan/tagihan
     */
    public function tagihanView(): void {
        $tenantId = SessionManager::getTenantId();
        $data = [
            'title' => 'Tagihan & Pembayaran SPP Siswa',
            'userRole' => $_SESSION['role_name'] ?? '',
            'tenantId' => $tenantId,
            'baseUrl' => $this->getBaseUrl()
        ];
        $this->render('keuangan_tagihan', $data);
    }

    /**
     * GET /api/v1/keuangan/spp
     */
    public function getTagihanApi(): void {
        $tenantId = SessionManager::getTenantId();
        $siswaId = $_GET['siswa_id'] ?? null;
        try {
            $data = $this->tagihanModel->getTagihanList($tenantId, $siswaId);
            $this->jsonResponse(true, $data);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/v1/keuangan/bayar
     */
    public function bayarApi(): void {
        $tenantId = SessionManager::getTenantId();
        $input = $this->getJsonInput();
        try {
            $result = $this->transaksiModel->prosesPembayaran($tenantId, $input);
            $this->jsonResponse(true, $result, 'Pembayaran SPP berhasil dicatat.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 400);
        }
    }
}
