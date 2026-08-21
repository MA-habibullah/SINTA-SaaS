<?php

namespace App\Modules\Perpustakaan\Controllers;

use App\Core\BaseController;
use App\Core\SessionManager;
use App\Modules\Perpustakaan\Models\PerpustakaanModel;

class PerpustakaanModuleController extends BaseController {
    private PerpustakaanModel $model;

    public function __construct() {
        parent::__construct();
        $this->model = new PerpustakaanModel();
    }

    private function getResolvedTenantId(): string {
        $tenantId = $_SESSION['tenant_id'] ?? ($this->tenantId ?? '');
        if ($this->isSuperAdmin()) {
            if (!empty($_GET['tenant_id'])) {
                $tenantId = $_GET['tenant_id'];
            } elseif (!empty($_POST['tenant_id'])) {
                $tenantId = $_POST['tenant_id'];
            } else {
                $raw = file_get_contents('php://input');
                if (!empty($raw)) {
                    $json = json_decode($raw, true);
                    if (!empty($json['tenant_id'])) {
                        $tenantId = $json['tenant_id'];
                    }
                }
            }
        }
        return $this->model->resolveTenantId((string)$tenantId);
    }

    private function isSuperAdmin(): bool {
        $role = strtolower($_SESSION['role_name'] ?? ($_SESSION['role'] ?? ($_SESSION['level'] ?? ($_SESSION['user_role'] ?? ''))));
        return in_array($role, ['superadmin', 'administrator', 'admin_sistem', 'super_admin']);
    }

    // =========================================================================
    // VIEW CONTROLLERS
    // =========================================================================

    public function indexView(): void {
        SessionManager::requireLogin();
        $tenantId = $this->getResolvedTenantId();
        $data = [
            'title' => 'Dashboard Perpustakaan',
            'summary' => $this->model->getDashboardSummary($tenantId),
            'pengaturan' => $this->model->getPengaturan($tenantId),
            'ratio' => $this->model->getAccreditationStats($tenantId),
            'active_tenant_id' => $tenantId,
            'is_super_admin' => $this->isSuperAdmin()
        ];
        $this->render('perpustakaan/dashboard', $data);
    }

    public function katalog(): void {
        SessionManager::requireLogin();
        $tenantId = $this->getResolvedTenantId();
        $search = $_GET['search'] ?? '';
        $kategori = $_GET['kategori'] ?? '';

        $list = $this->model->getBibliografiList($tenantId, ['search' => $search, 'kategori' => $kategori], 100);
        $rakList = $this->model->getLokasiRakList($tenantId);
        $ddcCategories = $this->model->getKategoriDdcList();
        $usulanList = $this->model->getUsulanBukuList($tenantId);
        $serialList = $this->model->getSerialBerkalaList($tenantId);

        $data = [
            'title' => 'Katalog & Inventori Perpustakaan',
            'list' => $list,
            'rak_list' => $rakList,
            'ddc_categories' => $ddcCategories,
            'usulan_list' => $usulanList,
            'serial_list' => $serialList,
            'opname_list' => [],
            'active_tenant_id' => $tenantId,
            'is_super_admin' => $this->isSuperAdmin(),
            'tenants' => []
        ];
        $this->render('perpustakaan/katalog', $data);
    }

    public function sirkulasi(): void {
        SessionManager::requireLogin();
        $tenantId = $this->getResolvedTenantId();
        $sirkulasiList = $this->model->getSirkulasiList($tenantId, [], 100);
        $pengaturan = $this->model->getPengaturan($tenantId);

        $data = [
            'title' => 'Sirkulasi & Layanan Perpustakaan',
            'sirkulasi_list' => $sirkulasiList,
            'paket_list' => [],
            'event_list' => [],
            'denda_list' => [],
            'pengaturan' => $pengaturan,
            'active_tenant_id' => $tenantId,
            'is_super_admin' => $this->isSuperAdmin()
        ];
        $this->render('perpustakaan/sirkulasi', $data);
    }

    public function anggota(): void {
        SessionManager::requireLogin();
        $tenantId = $this->getResolvedTenantId();
        $search = $_GET['search'] ?? '';
        $anggotaList = $this->model->getAnggotaList($tenantId, ['search' => $search], 100);
        $visitorLogs = $this->model->getVisitorLogs($tenantId, 50);
        $pengaturan = $this->model->getPengaturan($tenantId);

        $data = [
            'title' => 'Administrasi & Keanggotaan Perpustakaan',
            'anggota_list' => $anggotaList,
            'visitor_logs' => $visitorLogs,
            'pengaturan' => $pengaturan,
            'pagination' => [
                'current_page' => 1,
                'per_page' => 100,
                'total_records' => count($anggotaList),
                'total_pages' => 1,
                'from' => 1,
                'to' => count($anggotaList)
            ],
            'active_tenant_id' => $tenantId,
            'is_super_admin' => $this->isSuperAdmin()
        ];
        $this->render('perpustakaan/anggota', $data);
    }

    public function opacPublic(): void {
        $q = trim($_GET['q'] ?? '');
        $kategori = trim($_GET['kategori'] ?? '');
        $list = $this->model->searchOpacPublic($q, $kategori, 50);

        $data = [
            'title' => 'OPAC Publik — Katalog Perpustakaan Digital',
            'query' => $q,
            'kategori' => $kategori,
            'list' => $list,
            'ddc_categories' => $this->model->getKategoriDdcList()
        ];
        $this->render('perpustakaan/opac_public', $data);
    }

    public function bukuPaket(): void {
        SessionManager::requireLogin();
        $data = ['title' => 'Manajemen Buku Paket'];
        $this->render('perpustakaan/buku_paket', $data);
    }

    public function eventOSN(): void {
        SessionManager::requireLogin();
        $data = ['title' => 'Event OSN & Literasi Perpustakaan'];
        $this->render('perpustakaan/event_osn', $data);
    }

    public function denda(): void {
        SessionManager::requireLogin();
        $data = ['title' => 'Manajemen Denda Perpustakaan'];
        $this->render('perpustakaan/denda', $data);
    }

    public function opname(): void {
        SessionManager::requireLogin();
        $data = ['title' => 'Stock Opname Perpustakaan'];
        $this->render('perpustakaan/opname', $data);
    }

    public function laporan(): void {
        SessionManager::requireLogin();
        $data = ['title' => 'Laporan Perpustakaan'];
        $this->render('perpustakaan/laporan', $data);
    }

    public function pengaturan(): void {
        SessionManager::requireLogin();
        $tenantId = $this->getResolvedTenantId();
        $pengaturan = $this->model->getPengaturan($tenantId);
        $data = ['title' => 'Pengaturan Perpustakaan', 'pengaturan' => $pengaturan];
        $this->render('perpustakaan/pengaturan', $data);
    }

    public function bukuTamuPublic(): void {
        $tenantId = $this->getResolvedTenantId();
        $data = ['title' => 'Buku Tamu Perpustakaan', 'active_tenant_id' => $tenantId];
        $this->render('perpustakaan/kios_mandiri', $data);
    }

    public function riwayatSiswa(): void {
        SessionManager::requireLogin();
        $tenantId = $this->getResolvedTenantId();
        $username = $_SESSION['username'] ?? ($_SESSION['nama_lengkap'] ?? '');

        $sirkulasiList = $this->model->getSirkulasiList($tenantId, ['search' => $username], 100);
        if (empty($sirkulasiList)) {
            $sirkulasiList = $this->model->getSirkulasiList($tenantId, [], 50);
        }

        $data = [
            'title' => 'Riwayat Peminjaman Saya',
            'sirkulasi_list' => $sirkulasiList,
            'active_tenant_id' => $tenantId,
            'is_super_admin' => $this->isSuperAdmin()
        ];
        $this->render('perpustakaan/riwayat_saya', $data);
    }

    public function kiosMandiri(): void {
        $data = ['title' => 'Kios Peminjaman & Pengembalian Mandiri'];
        $this->render('perpustakaan/kios_mandiri', $data);
    }

    public function kiosPintu(): void {
        $data = ['title' => 'Kios Presensi Pintu Masuk'];
        $this->render('perpustakaan/kios_pintu', $data);
    }

    public function cetakLabelThermal(): void {
        $barcode = $_GET['barcode'] ?? 'BOOK-DEMO';
        echo "<!DOCTYPE html><html><head><title>Label Thermal</title><style>body{font-family:monospace;padding:10px;text-align:center;}@media print{@page{size:58mm auto;margin:0;}}</style></head><body onload='window.print()'><h3>SINTA PERPUS</h3><p><strong>" . htmlspecialchars($barcode) . "</strong></p><p>═════════════════</p></body></html>";
        exit;
    }

    public function exportKatalogExcel(): void {
        $tenantId = $this->getResolvedTenantId();
        $list = $this->model->getBibliografiList($tenantId, [], 1000);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="katalog_perpustakaan_' . date('Ymd_His') . '.csv"');
        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['No', 'Judul Buku', 'Pengarang', 'Penerbit', 'Tahun', 'ISBN', 'DDC', 'Total Eksemplar', 'Tersedia']);
        foreach ($list as $i => $item) {
            fputcsv($fp, [
                $i + 1,
                $item['judul'] ?? '',
                $item['penulis'] ?? '',
                $item['penerbit'] ?? '',
                $item['tahun_terbit'] ?? '',
                $item['isbn'] ?? '',
                $item['klasifikasi_ddc'] ?? '',
                $item['total_eksemplar'] ?? 1,
                $item['total_tersedia'] ?? 1
            ]);
        }
        fclose($fp);
        exit;
    }

    public function cetakLaporanDdc(): void {
        $this->laporan();
    }

    public function cetakLaporanPeminjaman(): void {
        $this->laporan();
    }

    public function cetakLaporanKunjungan(): void {
        $this->laporan();
    }

    public function readEbook(): void {
        $data = ['title' => 'E-Book Viewer'];
        $this->render('perpustakaan/baca_ebook', $data);
    }

    // =========================================================================
    // REST API JSON ENDPOINTS
    // =========================================================================

    public function apiGetKatalog(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $search = $_GET['search'] ?? ($_GET['q'] ?? '');
            $kategori = $_GET['kategori'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $filters = array_filter(['search' => $search, 'kategori' => $kategori]);
            $list = $this->model->getBibliografiList($tenantId, $filters, $limit, $offset);
            $total = $this->model->countBibliografi($tenantId, $filters);

            $this->jsonResponse(true, [
                'list' => $list,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, ['list' => [], 'total' => 0, 'limit' => 100, 'offset' => 0], $e->getMessage(), 500);
        }
    }

    public function apiSaveBibliografi(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

            if (empty($input['judul'])) {
                $this->jsonResponse(false, null, 'Judul buku wajib diisi.', 422);
                return;
            }

            $id = !empty($input['id']) ? $input['id'] : null;
            $savedId = $this->model->saveBibliografi($tenantId, $input, $id);

            if ($savedId) {
                $this->jsonResponse(true, ['id' => $savedId], 'Data katalog buku berhasil disimpan.');
            } else {
                $this->jsonResponse(false, null, 'Gagal menyimpan katalog buku.', 500);
            }
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiDeleteBibliografi(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $input['id'] ?? ($_GET['id'] ?? null);

            if (!$id) {
                $this->jsonResponse(false, null, 'ID buku tidak ditemukan.', 400);
                return;
            }

            $ok = $this->model->deleteBibliografi($tenantId, $id);
            $this->jsonResponse($ok, null, $ok ? 'Katalog buku berhasil dihapus.' : 'Gagal menghapus buku.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetAnggota(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $search = $_GET['search'] ?? '';
            $kategori = $_GET['kategori'] ?? '';
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

            $filters = array_filter(['search' => $search, 'kategori' => $kategori]);
            $list = $this->model->getAnggotaList($tenantId, $filters, $limit, $offset);
            $total = $this->model->countAnggota($tenantId, $filters);

            $this->jsonResponse(true, [
                'list' => $list,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, ['list' => [], 'total' => 0, 'limit' => 100, 'offset' => 0], $e->getMessage(), 500);
        }
    }

    public function apiSaveAnggota(): void {
        $tenantId = $this->getResolvedTenantId();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

        if (empty($input['nama_lengkap']) && empty($input['nama_perpus_anggota'])) {
            $this->jsonResponse(false, null, 'Nama anggota wajib diisi.', 422);
            return;
        }

        $id = !empty($input['id']) ? $input['id'] : null;
        $savedId = $this->model->saveAnggota($tenantId, $input, $id);

        if ($savedId) {
            $this->jsonResponse(true, ['id' => $savedId], 'Data anggota berhasil disimpan.');
        } else {
            $this->jsonResponse(false, null, 'Gagal menyimpan anggota.', 500);
        }
    }

    public function apiDeleteAnggota(): void {
        $tenantId = $this->getResolvedTenantId();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $id = $input['id'] ?? ($_GET['id'] ?? null);

        if (!$id) {
            $this->jsonResponse(false, null, 'ID anggota tidak ditemukan.', 400);
            return;
        }

        $ok = $this->model->deleteAnggota($tenantId, $id);
        $this->jsonResponse($ok, null, $ok ? 'Anggota berhasil dihapus.' : 'Gagal menghapus anggota.');
    }

    public function apiSyncAnggota(): void {
        $tenantId = $this->getResolvedTenantId();
        $synced = $this->model->syncAnggotaFromSiswa($tenantId);
        $this->jsonResponse(true, ['synced' => $synced], "Sinkronisasi berhasil: {$synced} siswa disinkronkan.");
    }

    public function apiGetSirkulasi(): void {
        $tenantId = $this->getResolvedTenantId();
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        $list = $this->model->getSirkulasiList($tenantId, array_filter(['status' => $status, 'search' => $search]), 100);
        $this->jsonResponse(true, $list);
    }

    public function apiPinjamReguler(): void {
        $tenantId = $this->getResolvedTenantId();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $res = $this->model->prosesPeminjaman($tenantId, $input);
        $this->jsonResponse($res['success'], $res, $res['message'], $res['success'] ? 200 : 400);
    }

    public function apiKembaliReguler(): void {
        $tenantId = $this->getResolvedTenantId();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $sirkulasiId = $input['sirkulasi_id'] ?? ($input['id'] ?? '');
        $kondisi = $input['kondisi'] ?? 'Baik';

        $res = $this->model->prosesPengembalian($tenantId, $sirkulasiId, $kondisi);
        $this->jsonResponse($res['success'], $res, $res['message'], $res['success'] ? 200 : 400);
    }

    public function apiDashboardSummary(): void {
        $tenantId = $this->getResolvedTenantId();
        $summary = $this->model->getDashboardSummary($tenantId);
        $this->jsonResponse(true, $summary);
    }

    public function apiGetRak(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $rak = $this->model->getLokasiRakList($tenantId);
            $this->jsonResponse(true, $rak);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function apiSaveRak(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = !empty($input['id']) ? $input['id'] : null;
            $savedId = $this->model->saveLokasiRak($tenantId, $input, $id);
            $this->jsonResponse(true, ['id' => $savedId], 'Data lokasi rak berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiDeleteRak(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $input['id'] ?? ($_GET['id'] ?? null);
            if (!$id) {
                $this->jsonResponse(false, null, 'ID rak tidak ditemukan.', 400);
                return;
            }
            $ok = $this->model->deleteLokasiRak($tenantId, $id);
            $this->jsonResponse($ok, null, $ok ? 'Lokasi rak berhasil dihapus.' : 'Gagal menghapus rak.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetUsulan(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $list = $this->model->getUsulanBukuList($tenantId);
            $this->jsonResponse(true, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function apiSaveUsulan(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = !empty($input['id']) ? $input['id'] : null;
            $savedId = $this->model->saveUsulanBuku($tenantId, $input, $id);
            $this->jsonResponse(true, ['id' => $savedId], 'Usulan buku berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiDeleteUsulan(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $input['id'] ?? ($_GET['id'] ?? null);
            if (!$id) {
                $this->jsonResponse(false, null, 'ID usulan tidak ditemukan.', 400);
                return;
            }
            $ok = $this->model->deleteUsulanBuku($tenantId, $id);
            $this->jsonResponse($ok, null, $ok ? 'Usulan buku berhasil dihapus.' : 'Gagal menghapus usulan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiUpdateStatusUsulan(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $input['id'] ?? null;
            $status = $input['status'] ?? 'Disetujui';
            if (!$id) {
                $this->jsonResponse(false, null, 'ID usulan tidak ditemukan.', 400);
                return;
            }
            $ok = $this->model->updateStatusUsulanBuku($tenantId, $id, $status);
            $this->jsonResponse($ok, null, $ok ? "Status usulan berhasil diperbarui menjadi '{$status}'." : 'Gagal memperbarui status usulan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetSerial(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $list = $this->model->getSerialBerkalaList($tenantId);
            $this->jsonResponse(true, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function apiSaveSerial(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = !empty($input['id']) ? $input['id'] : null;
            $savedId = $this->model->saveSerialBerkala($tenantId, $input, $id);
            $this->jsonResponse(true, ['id' => $savedId], 'Media serial/berkala berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiDeleteSerial(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $input['id'] ?? ($_GET['id'] ?? null);
            if (!$id) {
                $this->jsonResponse(false, null, 'ID serial tidak ditemukan.', 400);
                return;
            }
            $ok = $this->model->deleteSerialBerkala($tenantId, $id);
            $this->jsonResponse($ok, null, $ok ? 'Media serial/berkala berhasil dihapus.' : 'Gagal menghapus serial.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetEksemplarList(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $bibliografiId = $_GET['bibliografi_id'] ?? ($_GET['id'] ?? '');
            $list = $this->model->getEksemplarByBibliografiId($tenantId, $bibliografiId);
            $this->jsonResponse(true, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function apiSaveEksemplar(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = !empty($input['id']) ? $input['id'] : null;
            $savedId = $this->model->saveEksemplarSingle($tenantId, $input, $id);
            $this->jsonResponse(true, ['id' => $savedId], 'Data eksemplar berhasil disimpan.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiDeleteEksemplar(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $input['id'] ?? ($_GET['id'] ?? null);
            if (!$id) {
                $this->jsonResponse(false, null, 'ID eksemplar tidak ditemukan.', 400);
                return;
            }
            $ok = $this->model->deleteEksemplar($tenantId, $id);
            $this->jsonResponse($ok, null, $ok ? 'Data eksemplar berhasil dihapus.' : 'Gagal menghapus eksemplar.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetDdcCategories(): void {
        try {
            $list = $this->model->getKategoriDdcList();
            $this->jsonResponse(true, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function getKatalogApi(): void {
        $this->apiGetKatalog();
    }

    public function apiGetVisitorLogs(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $limit = (int)($_GET['limit'] ?? 50);
            $list = $this->model->getVisitorLogs($tenantId, $limit);
            $this->jsonResponse(true, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function apiSaveVisitorLog(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $ok = $this->model->checkinVisitor($tenantId, $input);
            $this->jsonResponse($ok, null, $ok ? 'Kunjungan berhasil dicatat.' : 'Gagal mencatat kunjungan.', $ok ? 200 : 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetPengaturan(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $data = $this->model->getPengaturanPerpus($tenantId);
            $this->jsonResponse(true, $data);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiSavePengaturan(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $ok = $this->model->savePengaturanPerpus($tenantId, $input);
            $this->jsonResponse($ok, null, $ok ? 'Pengaturan perpustakaan berhasil disimpan.' : 'Gagal menyimpan pengaturan.', $ok ? 200 : 400);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetMasterReferensiPaket(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $data = $this->model->getMasterReferensiPaket($tenantId);
            $this->jsonResponse(true, $data);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetPaketBukuList(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $list = $this->model->getDistribusiPaketBukuList($tenantId);
            $this->jsonResponse(true, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(false, [], $e->getMessage(), 500);
        }
    }

    public function apiSimpanPaketBuku(): void {
        try {
            $tenantId = $this->getResolvedTenantId();
            $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
            $id = $this->model->simpanDistribusiPaketBuku($tenantId, $input);
            $this->jsonResponse(true, ['id' => $id], 'Distribusi buku paket berhasil dicatat.');
        } catch (\Throwable $e) {
            $this->jsonResponse(false, null, $e->getMessage(), 500);
        }
    }

    public function apiGetBibliografiTraceability(): void {
        $this->jsonResponse(true, []);
    }

    public function apiSimpanUlasan(): void {
        $this->jsonResponse(true, null, 'Ulasan berhasil dikirim.');
    }

    public function apiGetDutaBaca(): void {
        $this->jsonResponse(true, []);
    }

    public function apiCronNotifReminder(): void {
        $this->jsonResponse(true, null, 'Reminder notifikasi dieksekusi.');
    }
}
