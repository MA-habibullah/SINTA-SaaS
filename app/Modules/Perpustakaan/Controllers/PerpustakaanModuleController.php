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

    /**
     * GET /perpustakaan/katalog
     */
    public function katalog(): void {
        $data = ['title' => 'Katalog Perpustakaan'];
        $this->render('perpustakaan/katalog', $data);
    }

    /**
     * GET /perpustakaan/sirkulasi
     */
    public function sirkulasi(): void {
        $data = ['title' => 'Sirkulasi Peminjaman Buku'];
        $this->render('perpustakaan/sirkulasi', $data);
    }

    /**
     * GET /perpustakaan/buku-paket
     */
    public function bukuPaket(): void {
        $data = ['title' => 'Manajemen Buku Paket'];
        $this->render('perpustakaan/buku_paket', $data);
    }

    /**
     * GET /perpustakaan/event
     */
    public function eventOSN(): void {
        $data = ['title' => 'Event OSN & Literasi Perpustakaan'];
        $this->render('perpustakaan/event', $data);
    }

    /**
     * GET /perpustakaan/anggota
     */
    public function anggota(): void {
        $data = ['title' => 'Manajemen Anggota Perpustakaan'];
        $this->render('perpustakaan/anggota', $data);
    }

    /**
     * GET /perpustakaan/denda
     */
    public function denda(): void {
        $data = ['title' => 'Manajemen Denda Perpustakaan'];
        $this->render('perpustakaan/denda', $data);
    }

    /**
     * GET /perpustakaan/opname
     */
    public function opname(): void {
        $data = ['title' => 'Stock Opname Perpustakaan'];
        $this->render('perpustakaan/opname', $data);
    }

    /**
     * GET /perpustakaan/laporan
     */
    public function laporan(): void {
        $data = ['title' => 'Laporan Perpustakaan'];
        $this->render('perpustakaan/laporan', $data);
    }

    /**
     * GET /perpustakaan/pengaturan
     */
    public function pengaturan(): void {
        $data = ['title' => 'Pengaturan Perpustakaan'];
        $this->render('perpustakaan/pengaturan', $data);
    }

    /**
     * GET /perpustakaan/opac
     */
    public function opacPublic(): void {
        $data = ['title' => 'OPAC - Katalog Perpustakaan Publik'];
        $this->render('perpustakaan/opac', $data);
    }

    /**
     * GET /perpustakaan/buku-tamu
     */
    public function bukuTamuPublic(): void {
        $data = ['title' => 'Buku Tamu Perpustakaan'];
        $this->render('perpustakaan/buku_tamu', $data);
    }

    /**
     * GET /perpustakaan/riwayat-saya
     */
    public function riwayatSiswa(): void {
        $data = ['title' => 'Riwayat Peminjaman Saya'];
        $this->render('perpustakaan/riwayat_siswa', $data);
    }

    /**
     * POST /api/v1/perpustakaan/sirkulasi/pinjam
     */
    public function apiPinjamReguler(): void {
        $this->jsonResponse(false, null, 'Fitur belum tersedia.', 501);
    }

    /**
     * POST /api/v1/perpustakaan/sirkulasi/kembali
     */
    public function apiKembaliReguler(): void {
        $this->jsonResponse(false, null, 'Fitur belum tersedia.', 501);
    }

    /**
     * GET /api/v1/perpustakaan/bebas-pustaka/cek
     */
    public function apiCekBebasPustaka(): void {
        $this->jsonResponse(true, ['bebas_pustaka' => true]);
    }

    /**
     * POST /api/v1/perpustakaan/anggota/sync
     */
    public function apiSyncAnggota(): void {
        $this->jsonResponse(false, null, 'Fitur belum tersedia.', 501);
    }
}
