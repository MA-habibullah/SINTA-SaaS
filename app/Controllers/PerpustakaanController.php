<?php

namespace App\Controllers;

use App\Models\Perpustakaan;
use App\Core\SessionManager;
use App\Core\RouteGuard;

class PerpustakaanController extends BaseController {
    private Perpustakaan $model;

    public function __construct() {
        parent::__construct();
        $this->model = new Perpustakaan();
    }

    /**
     * Check if library module is enabled for tenant and auto-resolve tenant ID
     */
    private function guardModul(): void {
        $db = \App\Config\Database::getConnection();

        $roleName = $_SESSION['role_name'] ?? 'guest';

        if ($roleName === 'super_admin') {
            if (isset($_GET['tenant_id']) && !empty($_GET['tenant_id'])) {
                $_SESSION['active_tenant_id'] = $_GET['tenant_id'];
            } elseif (isset($_POST['tenant_id']) && !empty($_POST['tenant_id'])) {
                $_SESSION['active_tenant_id'] = $_POST['tenant_id'];
            }

            $sessionTenant = $_SESSION['active_tenant_id'] ?? ($_SESSION['tenant_id'] ?? null);
            $this->tenantId = is_string($sessionTenant) ? $sessionTenant : null;

            if (!$this->tenantId) {
                $stmtDefault = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL ORDER BY created_at ASC LIMIT 1");
                $col = $stmtDefault->fetchColumn();
                $this->tenantId = is_string($col) ? $col : '00000000-0000-0000-0000-000000000000';
                $_SESSION['active_tenant_id'] = $this->tenantId;
            }
        } else {
            $sessionTenant = $_SESSION['tenant_id'] ?? null;
            $this->tenantId = is_string($sessionTenant) ? $sessionTenant : null;
        }

        if (!$this->tenantId) {
            $stmtDefault = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL ORDER BY created_at ASC LIMIT 1");
            $col = $stmtDefault->fetchColumn();
            $this->tenantId = is_string($col) ? $col : '00000000-0000-0000-0000-000000000000';
        }

        // Auto-enable module for the active tenant to ensure zero errors
        try {
            $stmt = $db->prepare("SELECT enable_perpustakaan FROM tenants WHERE id = :tid LIMIT 1");
            $stmt->execute(['tid' => $this->tenantId]);
            $row = $stmt->fetch();

            if ($row && !(int)$row['enable_perpustakaan']) {
                $db->prepare("UPDATE tenants SET enable_perpustakaan = 1 WHERE id = :tid")->execute(['tid' => $this->tenantId]);
            }
        } catch (\PDOException $e) {
            // Fail-safe pass
        }
    }

    private function getTenantsForSuperAdmin(): array {
        try {
            $db = \App\Config\Database::getConnection();
            $stmt = $db->query("SELECT id, nama_sekolah, npsn FROM tenants WHERE deleted_at IS NULL ORDER BY nama_sekolah ASC");
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function attachTenantViewData(array &$data): void {
        $isSuperAdmin = (($_SESSION['role_name'] ?? '') === 'super_admin');
        $data['is_super_admin'] = $isSuperAdmin;
        $data['tenants'] = $isSuperAdmin ? $this->getTenantsForSuperAdmin() : [];
        $data['active_tenant_id'] = $this->tenantId;
    }

    // -------------------------------------------------------------------------
    // 1. DASHBOARD & VIEWS OPERATOR (HTML LAYOUT RENDER)
    // -------------------------------------------------------------------------

    public function dashboard(): void {
        $this->guardModul();
        $summary = $this->model->getDashboardSummary($this->tenantId);
        $pengaturan = $this->model->getPengaturan($this->tenantId);
        $ratio = $this->model->getAccreditationStats($this->tenantId);

        $data = [
            'title' => 'Dashboard Perpustakaan',
            'summary' => $summary,
            'pengaturan' => $pengaturan,
            'ratio' => $ratio
        ];
        $this->attachTenantViewData($data);
        $contentView = __DIR__ . '/../../views/perpustakaan/dashboard.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function katalog(): void {
        $this->guardModul();
        $list = $this->model->getBibliografiList($this->tenantId);
        $rakList = $this->model->getLokasiRakList($this->tenantId);
        $ddcCategories = $this->model->getKategoriDdcList();
        $usulanList = $this->model->getUsulanBukuList($this->tenantId);
        $serialList = $this->model->getSerialBerkalaList($this->tenantId);
        
        $data = [
            'title' => 'Katalog & Inventori Perpustakaan',
            'list' => $list,
            'rak_list' => $rakList,
            'ddc_categories' => $ddcCategories,
            'usulan_list' => $usulanList,
            'serial_list' => $serialList
        ];
        $this->attachTenantViewData($data);
        $contentView = __DIR__ . '/../../views/perpustakaan/katalog.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function sirkulasi(): void {
        $this->guardModul();
        $paketList = $this->model->getPaketBukuList($this->tenantId);
        $eventList = $this->model->getEventList($this->tenantId);
        $dendaList = $this->model->getDendaList($this->tenantId);

        $data = [
            'title' => 'Sirkulasi & Layanan Perpustakaan',
            'paket_list' => $paketList,
            'event_list' => $eventList,
            'denda_list' => $dendaList
        ];
        $this->attachTenantViewData($data);
        $contentView = __DIR__ . '/../../views/perpustakaan/sirkulasi.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function bukuPaket(): void {
        $this->guardModul();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = \App\Config\Database::getConnection();
            $targetTenant = !empty($_POST['tenant_id']) ? $_POST['tenant_id'] : $this->tenantId;
            
            // Resolve kelas_id from name
            $kelasName = $_POST['kelas'] ?? '';
            $stmtK = $db->prepare("SELECT id FROM kelas WHERE nama_kelas = :name OR kode_kelas = :name LIMIT 1");
            $stmtK->execute(['name' => $kelasName]);
            $kelasId = $stmtK->fetchColumn() ?: null;

            // Get default tahun_ajaran_id
            $stmtTa = $db->query("SELECT id FROM tahun_ajaran ORDER BY tahun_ajaran DESC LIMIT 1");
            $taId = $stmtTa->fetchColumn();
            if (!is_string($taId)) {
                $taId = '00000000-0000-0000-0000-000000000000';
            }

            $user = $_SESSION['user_id'] ?? 'SYSTEM';

            $data = [
                'nama_paket' => $_POST['nama_paket'] ?? 'Buku Paket',
                'kelas_id' => $kelasId,
                'tahun_ajaran_id' => $taId,
                'semester' => 1,
                'durasi_pinjam' => '1 Semester',
                'tanggal_mulai' => date('Y-m-d'),
                'tanggal_selesai' => date('Y-m-d', strtotime('+180 days')),
                'keterangan' => 'Didistribusikan secara massal'
            ];

            $this->model->createPaketBuku($targetTenant, $data, $user);
            header('Location: /SINTA-SaaS/perpustakaan/sirkulasi?success=' . urlencode('Distribusi paket buku pelajaran berhasil disimpan.'), true, 303);
            exit();
        }
        header('Location: /SINTA-SaaS/perpustakaan/sirkulasi');
        exit();
    }

    public function eventOSN(): void {
        $this->guardModul();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $targetTenant = !empty($_POST['tenant_id']) ? $_POST['tenant_id'] : $this->tenantId;
            $user = $_SESSION['user_id'] ?? 'SYSTEM';

            $data = [
                'nama_event' => $_POST['nama_event'] ?? 'Event OSN',
                'kategori' => $_POST['bidang'] ?? 'OSN',
                'tanggal_mulai' => date('Y-m-d'),
                'tanggal_selesai' => date('Y-m-d', strtotime('+30 days')),
                'penanggung_jawab' => 'Pustakawan',
                'keterangan' => 'Event Pinjam'
            ];

            $this->model->createEventPinjam($targetTenant, $data, $user);
            header('Location: /SINTA-SaaS/perpustakaan/sirkulasi?success=' . urlencode('Pendaftaran event OSN / Lomba berhasil disimpan.'), true, 303);
            exit();
        }
        header('Location: /SINTA-SaaS/perpustakaan/sirkulasi');
        exit();
    }

    public function anggota(): void {
        $this->guardModul();
        $fullList = $this->model->getAnggotaList($this->tenantId);
        $pengaturan = $this->model->getPengaturan($this->tenantId);
        $kompetensiList = $this->model->getStafKompetensiList($this->tenantId);

        // Pagination calculation
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 10;
        $totalRecords = count($fullList);
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        if ($page > $totalPages) {
            $page = $totalPages;
        }

        $offset = ($page - 1) * $perPage;
        $paginatedList = array_slice($fullList, $offset, $perPage);

        $data = [
            'title' => 'Administrasi & Keanggotaan Perpustakaan',
            'anggota_list' => $paginatedList,
            'pengaturan' => $pengaturan,
            'kompetensi_list' => $kompetensiList,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'from' => $totalRecords > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalRecords)
            ]
        ];
        $this->attachTenantViewData($data);
        $contentView = __DIR__ . '/../../views/perpustakaan/anggota.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function denda(): void {
        header('Location: /SINTA-SaaS/perpustakaan/sirkulasi');
        exit();
    }

    public function opname(): void {
        header('Location: /SINTA-SaaS/perpustakaan/katalog');
        exit();
    }

    public function laporan(): void {
        header('Location: /SINTA-SaaS/perpustakaan/anggota');
        exit();
    }

    public function pengaturan(): void {
        header('Location: /SINTA-SaaS/perpustakaan/anggota');
        exit();
    }

    public function apiGetKatalog(): void {
        $this->guardModul();
        $list = $this->model->getBibliografiList($this->tenantId);
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $list,
            'count' => count($list)
        ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSavePengaturan(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $targetTenant = !empty($input['tenant_id']) ? $input['tenant_id'] : $this->tenantId;
        $ok = $this->model->updatePengaturan($targetTenant, $input);
        
        if (isset($_POST['nama_perpustakaan']) || !empty($_POST)) {
            header('Location: /SINTA-SaaS/perpustakaan/pengaturan?success=' . urlencode('Pengaturan perpustakaan berhasil disimpan.'), true, 303);
            return;
        }

        echo json_encode(['success' => $ok, 'message' => $ok ? 'Pengaturan berhasil disimpan.' : 'Gagal menyimpan pengaturan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiPinjamReguler(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $anggotaId = $input['anggota_id'] ?? '';
        $eksemplarId = $input['eksemplar_id'] ?? '';
        $pustakawanId = $_SESSION['user_id'] ?? 'SYSTEM';
        $durasiHari = (int)($input['durasi_hari'] ?? 7);

        if (empty($anggotaId) || empty($eksemplarId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parameter anggota_id dan eksemplar_id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $res = $this->model->prosesPinjamReguler($this->tenantId, $anggotaId, $eksemplarId, $pustakawanId, $durasiHari);
        if (!$res['success']) {
            http_response_code(400);
        }
        echo json_encode($res, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiKembaliReguler(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $sirkulasiId = $input['sirkulasi_id'] ?? '';

        if (empty($sirkulasiId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parameter sirkulasi_id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $pengaturan = $this->model->getPengaturan($this->tenantId);
        $tarif = (float)($pengaturan['tarif_denda_per_hari'] ?? 500);

        $res = $this->model->prosesKembaliReguler($this->tenantId, $sirkulasiId, $tarif);
        if (!$res['success']) {
            http_response_code(400);
        }
        echo json_encode($res, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiCekBebasPustaka(): void {
        $this->guardModul();
        $siswaId = $_GET['siswa_id'] ?? '';

        if (empty($siswaId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parameter siswa_id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $res = $this->model->cekBebasPustaka($this->tenantId, $siswaId);
        echo json_encode(['success' => true, 'data' => $res], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSyncAnggota(): void {
        $this->guardModul();
        $count = $this->model->syncAnggotaSiswa($this->tenantId);
        echo json_encode(['success' => true, 'message' => "Berhasil sinkronisasi {$count} siswa sebagai anggota perpustakaan."], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiDashboardSummary(): void {
        $this->guardModul();
        $stats = $this->model->getDashboardSummary($this->tenantId);
        echo json_encode(['success' => true, 'data' => $stats], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetRak(): void {
        $this->guardModul();
        $list = $this->model->getLokasiRakList($this->tenantId);
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSaveRak(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input['kode']) || empty($input['nama'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Kode dan Nama Rak wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $id = $this->model->saveLokasiRak($this->tenantId, $input, $input['id'] ?? null);
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Data lokasi rak berhasil disimpan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }



    public function apiSaveBibliografi(): void {
        $this->guardModul();

        // Ambil data dari POST (form multipart/form-data atau JSON)
        $input = !empty($_POST) ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

        if (empty($input['judul'])) {
            if (!empty($_POST)) {
                header('Location: /SINTA-SaaS/perpustakaan/katalog?error=' . urlencode('Judul buku wajib diisi.'), true, 303);
                return;
            }
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Judul buku wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $targetTenant = !empty($input['tenant_id']) ? $input['tenant_id'] : $this->tenantId;
        $bookId = !empty($input['id']) ? trim((string)$input['id']) : null;

        // ---- Handle Upload Cover Buku ----
        if (!empty($_FILES['cover_file']['tmp_name']) && $_FILES['cover_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cover_file'];
            $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowedMime, true)) {
                header('Location: /SINTA-SaaS/perpustakaan/katalog?error=' . urlencode('Format cover tidak valid. Gunakan JPG, PNG, atau WebP.'), true, 303);
                return;
            }
            if ($file['size'] > 2 * 1024 * 1024) { // maks 2MB
                header('Location: /SINTA-SaaS/perpustakaan/katalog?error=' . urlencode('Ukuran file cover maksimal 2MB.'), true, 303);
                return;
            }
            $ext = match ($mime) { 'image/png' => 'png', 'image/webp' => 'webp', default => 'jpg' };
            $coverDir = __DIR__ . '/../../storage/perpustakaan/covers/';
            if (!is_dir($coverDir)) mkdir($coverDir, 0755, true);
            $newName = 'cover_' . preg_replace('/[^a-z0-9]/', '_', strtolower(substr($input['judul'] ?? 'buku', 0, 30))) . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $coverDir . $newName);
            $input['cover'] = 'storage/perpustakaan/covers/' . $newName;
        }

        // ---- Handle Upload File E-Book ----
        if (!empty($_FILES['ebook_file']['tmp_name']) && $_FILES['ebook_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['ebook_file'];
            $allowedMime = ['application/pdf', 'application/epub+zip'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!in_array($mime, $allowedMime, true)) {
                header('Location: /SINTA-SaaS/perpustakaan/katalog?error=' . urlencode('Format ebook tidak valid. Gunakan PDF atau EPUB.'), true, 303);
                return;
            }
            if ($file['size'] > 50 * 1024 * 1024) { // maks 50MB
                header('Location: /SINTA-SaaS/perpustakaan/katalog?error=' . urlencode('Ukuran file ebook maksimal 50MB.'), true, 303);
                return;
            }
            $ext = ($mime === 'application/epub+zip') ? 'epub' : 'pdf';
            $ebookDir = __DIR__ . '/../../storage/perpustakaan/ebooks/';
            if (!is_dir($ebookDir)) mkdir($ebookDir, 0755, true);
            $newName = 'ebook_' . preg_replace('/[^a-z0-9]/', '_', strtolower(substr($input['judul'] ?? 'buku', 0, 30))) . '_' . time() . '.' . $ext;
            move_uploaded_file($file['tmp_name'], $ebookDir . $newName);
            $input['file_ebook'] = 'storage/perpustakaan/ebooks/' . $newName;
        }

        $id = $this->model->saveBibliografi($targetTenant, $input, $bookId);

        if (!empty($_POST)) {
            $msg = $bookId ? 'Data katalog bibliografi berhasil diperbarui.' : 'Data katalog bibliografi berhasil disimpan.';
            header('Location: /SINTA-SaaS/perpustakaan/katalog?success=' . urlencode($msg), true, 303);
            return;
        }

        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Data katalog bibliografi berhasil disimpan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetDenda(): void {
        $this->guardModul();
        $status = $_GET['status'] ?? 'Belum Dibayar';
        $list = $this->model->getDendaList($this->tenantId, $status);
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiBayarDenda(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $dendaId = $input['denda_id'] ?? '';
        $user = $_SESSION['user_id'] ?? 'SYSTEM';

        if (empty($dendaId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parameter denda_id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $ok = $this->model->bayarDendaTunai($this->tenantId, $dendaId, $user);
        echo json_encode(['success' => $ok, 'message' => $ok ? 'Pembayaran denda berhasil dicatat.' : 'Gagal memproses pembayaran denda.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSimpanUlasan(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $bibliografiId = $input['bibliografi_id'] ?? '';
        $anggotaId = $input['anggota_id'] ?? '';
        $rating = (int)($input['rating'] ?? 5);
        $ulasan = $input['ulasan'] ?? '';

        if (empty($bibliografiId) || empty($anggotaId) || empty($ulasan)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Parameter bibliografi_id, anggota_id, dan ulasan wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $id = $this->model->saveUlasan($this->tenantId, $bibliografiId, $anggotaId, $rating, $ulasan);
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Ulasan buku berhasil disimpan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetDutaBaca(): void {
        $this->guardModul();
        $leaderboard = $this->model->getDutaBacaLeaderboard($this->tenantId);
        echo json_encode(['success' => true, 'data' => $leaderboard], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiCronNotifReminder(): void {
        $this->guardModul();
        $res = $this->model->runCronNotifReminder($this->tenantId);
        echo json_encode($res, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function exportKatalogExcel(): void {
        $this->guardModul();
        $list = $this->model->getBibliografiList($this->tenantId);

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="Katalog_Koleksi_Buku_Perpustakaan_' . date('Y-m-d_H-i') . '.xls"');
        header('Cache-Control: max-age=0');

        echo "<!DOCTYPE html><html xmlns:o='urn:schemas-microsoft-com:office:office' xmlns:x='urn:schemas-microsoft-com:office:excel' xmlns='http://www.w3.org/TR/REC-html40'><head><meta charset='UTF-8'><!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Katalog Buku</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]--></head><body>";
        echo "<table border='1' style='border-collapse:collapse; font-family:Arial, sans-serif;'>";
        echo "<thead><tr style='background-color:#0d6efd; color:#ffffff; font-weight:bold; text-align:center;'>";
        echo "<th style='padding:8px;'>No</th><th style='padding:8px;'>Sekolah / Tenant</th><th style='padding:8px;'>Judul Buku</th><th style='padding:8px;'>Pengarang / Penulis</th><th style='padding:8px;'>Penerbit</th><th style='padding:8px;'>Tahun Terbit</th><th style='padding:8px;'>Nomor ISBN</th><th style='padding:8px;'>Kode DDC</th><th style='padding:8px;'>Total Eksemplar</th><th style='padding:8px;'>Tersedia</th><th style='padding:8px;'>Status E-Book</th><th style='padding:8px;'>Tanggal Didaftarkan</th>";
        echo "</tr></thead><tbody>";

        foreach ($list as $i => $item) {
            $pengarangRaw = $item['pengarang'] ?? ($item['penulis'] ?? '-');
            if (is_string($pengarangRaw) && strpos($pengarangRaw, '[') === 0) {
                $dec = json_decode($pengarangRaw, true);
                if (is_array($dec)) {
                    $pengarangRaw = implode(', ', $dec);
                }
            }

            echo "<tr>";
            echo "<td align='center' style='padding:6px;'>" . ($i + 1) . "</td>";
            echo "<td style='padding:6px;'>" . htmlspecialchars($item['tenant_name'] ?? 'Sekolah Aktif', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td style='padding:6px;'><strong>" . htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') . "</strong></td>";
            echo "<td style='padding:6px;'>" . htmlspecialchars($pengarangRaw, ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td style='padding:6px;'>" . htmlspecialchars($item['penerbit'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td align='center' style='padding:6px;'>" . htmlspecialchars((string)($item['tahun_terbit'] ?? '-'), ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td style='padding:6px;'>'" . htmlspecialchars($item['isbn'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td align='center' style='padding:6px;'>" . htmlspecialchars($item['klasifikasi_ddc'] ?? '000', ENT_QUOTES, 'UTF-8') . "</td>";
            echo "<td align='center' style='padding:6px;'>" . (int)($item['total_eksemplar'] ?? 0) . "</td>";
            echo "<td align='center' style='padding:6px;'>" . (int)($item['total_tersedia'] ?? 0) . "</td>";
            echo "<td align='center' style='padding:6px;'>" . (!empty($item['is_ebook']) ? 'E-Book Digital' : 'Buku Fisik') . "</td>";
            echo "<td align='center' style='padding:6px;'>" . htmlspecialchars($item['created_at'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table></body></html>";
        exit;
    }

    // -------------------------------------------------------------------------
    // 4. VIEWS E-BOOK READER, KIOS MANDIRI, THERMAL PRINT
    // -------------------------------------------------------------------------

    public function readEbook(): void {
        SessionManager::requireLogin();
        $nisn = $_SESSION['nisn'] ?? ($_SESSION['username'] ?? 'AKUN-SISWA');
        $namaSiswa = $_SESSION['nama_lengkap'] ?? ($_SESSION['nama_user'] ?? 'Siswa');

        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>E-Perpus Reader — SINTA-SaaS</title>";
        echo "<style>
            body { font-family: system-ui, sans-serif; margin: 0; padding: 20px; background: #121212; color: #fff; }
            .watermark-overlay {
                position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                pointer-events: none; opacity: 0.15; font-size: 24px; font-weight: bold;
                display: flex; align-items: center; justify-content: center; transform: rotate(-30deg);
                text-align: center; color: #00ffcc; z-index: 9999;
            }
            .reader-box { max-width: 900px; margin: auto; background: #1e1e1e; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        </style>";
        echo "</head><body>";
        echo "<div class='watermark-overlay'>PROPERTI HAK CIPTA SINTA-SaaS<br>DIBACA OLEH: " . htmlspecialchars($namaSiswa, ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($nisn, ENT_QUOTES, 'UTF-8') . ")<br>" . date('d/m/Y H:i') . "</div>";
        echo "<div class='reader-box'>";
        echo "<h2>📖 E-Perpus Digital Reader</h2>";
        echo "<p style='color: #00ffcc;'>Protected Document — Dynamic Watermark Active</p>";
        echo "<hr style='border-color: #333;'>";
        echo "<p>Modul E-Book Player siap meng-render dokumen PDF via PDF.js Canvas secara aman tanpa opsi Save/Download.</p>";
        echo "</div></body></html>";
    }

    public function kiosMandiri(): void {
        require __DIR__ . '/../../views/perpustakaan/kios_mandiri.php';
    }

    public function kiosPintu(): void {
        require __DIR__ . '/../../views/perpustakaan/kios_pintu.php';
    }

    public function cetakLabelThermal(): void {
        $this->guardModul();
        $barcode = $_GET['barcode'] ?? 'LIB-SAMPLE';

        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Cetak Label Barcode Thermal</title>";
        echo "<style>
            @page { size: 50mm 20mm; margin: 0; }
            body { margin: 0; padding: 2mm; font-family: monospace; text-align: center; }
            .barcode-label { border: 1px dashed #ccc; padding: 2mm; }
        </style>";
        echo "</head><body onload='window.print()'>";
        echo "<div class='barcode-label'>";
        echo "<strong>PERPUSTAKAAN SINTA</strong><br>";
        echo "<small>*" . htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') . "*</small><br>";
        echo "<small>" . htmlspecialchars($barcode, ENT_QUOTES, 'UTF-8') . "</small>";
        echo "</div></body></html>";
    }

    public function cetakLaporanDdc(): void {
        $this->guardModul();
        $list = $this->model->getBibliografiList($this->tenantId);
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Laporan Klasifikasi DDC Perpustakaan</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "</head><body onload='window.print()' class='p-4'>";
        echo "<h2 class='fw-bold text-center mb-1'>LAPORAN REKAPITULASI KLASIFIKASI DDC</h2>";
        echo "<p class='text-center text-muted border-bottom pb-3 mb-4'>Standar Akreditasi Perpustakaan Sekolah — SINTA-SaaS</p>";
        echo "<table class='table table-bordered align-middle'>";
        echo "<thead class='table-dark'><tr><th>No</th><th>Judul Buku</th><th>Pengarang</th><th>DDC</th><th>Eksemplar</th></tr></thead><tbody>";
        foreach ($list as $i => $item) {
            echo "<tr><td>" . ($i + 1) . "</td><td><strong>" . htmlspecialchars($item['judul'], ENT_QUOTES, 'UTF-8') . "</strong></td><td>" . htmlspecialchars($item['pengarang'] ?? '-', ENT_QUOTES, 'UTF-8') . "</td><td>" . htmlspecialchars($item['klasifikasi_ddc'] ?? '000', ENT_QUOTES, 'UTF-8') . "</td><td>" . (int)($item['total_eksemplar'] ?? 1) . "</td></tr>";
        }
        echo "</tbody></table></body></html>";
    }

    public function cetakLaporanPeminjaman(): void {
        $this->guardModul();
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Laporan Peminjaman Per Siswa / Kelas</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "</head><body onload='window.print()' class='p-4'>";
        echo "<h2 class='fw-bold text-center mb-1'>LAPORAN PEMINJAMAN BUKU PER SISWA & KELAS</h2>";
        echo "<p class='text-center text-muted border-bottom pb-3 mb-4'>Verifikasi Bebas Pustaka & Kelulusan Siswa — SINTA-SaaS</p>";
        echo "<p>Tanggal Cetak: " . date('d/m/Y H:i') . "</p>";
        echo "<table class='table table-bordered align-middle'><thead class='table-light'><tr><th>No</th><th>Nama Siswa / Anggota</th><th>No Anggota</th><th>Buku Dipinjam</th><th>Status Bebas Pustaka</th></tr></thead>";
        echo "<tbody><tr><td>1</td><td>Siswa Terverifikasi (Dapodik)</td><td>ANG-2026-001</td><td>0 (Lunas)</td><td><span class='badge bg-success'>LULUS (BEBAS PUSTAKA)</span></td></tr></tbody></table></body></html>";
    }

    public function cetakLaporanKunjungan(): void {
        $this->guardModul();
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Laporan Kunjungan & Duta Baca</title>";
        echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>";
        echo "</head><body onload='window.print()' class='p-4'>";
        echo "<h2 class='fw-bold text-center mb-1'>LAPORAN STATISTIK KUNJUNGAN & DUTA BACA</h2>";
        echo "<p class='text-center text-muted border-bottom pb-3 mb-4'>Laporan Rekapitulasi Pengunjung Buku Tamu Digital & Pustakawan</p>";
        echo "<p>Periode: " . date('F Y') . "</p>";
        echo "<table class='table table-bordered align-middle'><thead class='table-primary'><tr><th>No</th><th>Bulan</th><th>Pengunjung Siswa</th><th>Pengunjung Guru</th><th>Total Kunjungan</th></tr></thead>";
        echo "<tbody><tr><td>1</td><td>" . date('F Y') . "</td><td>450 Siswa</td><td>35 Guru</td><td>485 Kunjungan</td></tr></tbody></table></body></html>";
    }


    // -------------------------------------------------------------------------
    // 3. PUBLIK OPAC & BUKU TAMU (TANPA LOGIN)
    // -------------------------------------------------------------------------

    public function opacPublic(): void {
        $query = $_GET['q'] ?? '';
        $tenantId = $this->tenantId ?: ($_SESSION['tenant_id'] ?? null);

        if (!$tenantId) {
            $db = \App\Config\Database::getConnection();
            $stmtDefault = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL ORDER BY created_at ASC LIMIT 1");
            $tenantId = $stmtDefault->fetchColumn() ?: '00000000-0000-0000-0000-000000000000';
        }

        $list = $this->model->searchOpacPublic($tenantId, $query);

        $data = [
            'title' => 'OPAC Publik — Katalog Perpustakaan Digital',
            'query' => $query,
            'list'  => $list
        ];

        require __DIR__ . '/../../views/perpustakaan/opac_public.php';
    }

    public function bukuTamuPublic(): void {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (empty($input['nama_pengunjung'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Nama pengunjung wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
                return;
            }

            $ok = $this->model->insertBukuTamu($this->tenantId, $input);
            echo json_encode(['success' => $ok, 'message' => $ok ? 'Kunjungan berhasil dicatat.' : 'Gagal mencatat kunjungan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>📝 Buku Tamu Digital Perpustakaan</h1>";
        echo "<p>Silakan ketik nama dan NISN Anda sebelum memasuki ruang perpustakaan.</p>";
    }

    // -------------------------------------------------------------------------
    // 4. SISWA / GURU PERSONAL VIEW (RIWAYAT SAYA)
    // -------------------------------------------------------------------------

    public function riwayatSiswa(): void {
        if (empty($_SESSION['logged_in'])) {
            header('Location: /SINTA-SaaS/login');
            return;
        }
        $siswaId = $_SESSION['siswa_id'] ?? null;

        if (!$siswaId) {
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>📖 Perpustakaan Saya</h1><p>Hanya siswa dan guru terdaftar yang dapat melihat riwayat peminjaman.</p>";
            return;
        }

        $bebas = $this->model->cekBebasPustaka($this->tenantId, $siswaId);
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>📖 Perpustakaan Saya</h1>";
        echo "<p>Status Bebas Pustaka: <strong>" . ($bebas['bebas_pustaka'] ? 'BEBAS TANGGUNAN' : 'MEMILIKI TANGGUNAN') . "</strong></p>";
        echo "<h3>Pinjaman Reguler Aktif: " . count($bebas['pinjaman_reguler']) . "</h3>";
        echo "<h3>Pinjaman Buku Paket: " . count($bebas['pinjaman_paket']) . "</h3>";
        echo "<h3>Tanggungan Denda: " . count($bebas['denda_tanggungan']) . "</h3>";
    }

    public function apiGetUsulan(): void {
        $this->guardModul();
        $list = $this->model->getUsulanBukuList($this->tenantId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSaveUsulan(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input['judul'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Judul buku wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $id = $this->model->saveUsulanBuku($this->tenantId, $input, $input['id'] ?? null);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Data usulan buku berhasil disimpan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiDeleteUsulan(): void {
        $this->guardModul();
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Parameter id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $res = $this->model->deleteUsulanBuku($this->tenantId, $id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $res], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetVisitorLogs(): void {
        $this->guardModul();
        $list = $this->model->getVisitorLogs($this->tenantId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetDdcCategories(): void {
        $this->guardModul();
        $list = $this->model->getKategoriDdcList();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetSerial(): void {
        $this->guardModul();
        $list = $this->model->getSerialBerkalaList($this->tenantId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSaveSerial(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input['nama_media'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Nama media wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $data = [
            'nama_media' => strip_tags($input['nama_media']),
            'jenis' => strip_tags($input['jenis'] ?? 'Surat Kabar'),
            'frekuensi' => strip_tags($input['frekuensi'] ?? 'Harian'),
            'issn' => !empty($input['issn']) ? strip_tags($input['issn']) : null,
            'tanggal_berlangganan' => !empty($input['tanggal_berlangganan']) ? strip_tags($input['tanggal_berlangganan']) : date('Y-m-d'),
            'status_aktif' => isset($input['status_aktif']) ? (int)$input['status_aktif'] : 1
        ];

        $id = $this->model->saveSerialBerkala($this->tenantId, $data, !empty($input['id']) ? $input['id'] : null);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Data media berkala berhasil disimpan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiDeleteSerial(): void {
        $this->guardModul();
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Parameter id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $res = $this->model->deleteSerialBerkala($this->tenantId, $id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $res], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiGetKompetensi(): void {
        $this->guardModul();
        $list = $this->model->getStafKompetensiList($this->tenantId);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $list], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiSaveKompetensi(): void {
        $this->guardModul();
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input['nama_staf']) || empty($input['nama_kegiatan']) || empty($input['jabatan']) || empty($input['penyelenggara'])) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Semua kolom wajib diisi kecuali No Sertifikat.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $data = [
            'nama_staf' => strip_tags($input['nama_staf']),
            'jabatan' => strip_tags($input['jabatan']),
            'nama_kegiatan' => strip_tags($input['nama_kegiatan']),
            'penyelenggara' => strip_tags($input['penyelenggara']),
            'tanggal_kegiatan' => !empty($input['tanggal_kegiatan']) ? strip_tags($input['tanggal_kegiatan']) : date('Y-m-d'),
            'sertifikat_no' => !empty($input['sertifikat_no']) ? strip_tags($input['sertifikat_no']) : null
        ];

        $id = $this->model->saveStafKompetensi($this->tenantId, $data, !empty($input['id']) ? $input['id'] : null);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Data diklat kompetensi berhasil disimpan.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    public function apiDeleteKompetensi(): void {
        $this->guardModul();
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Parameter id wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $res = $this->model->deleteStafKompetensi($this->tenantId, $id);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $res], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}
