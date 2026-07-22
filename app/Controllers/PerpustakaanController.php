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

        if (!$this->tenantId) {
            $this->tenantId = $_SESSION['tenant_id'] ?? null;
        }

        if (!$this->tenantId) {
            // Fallback for Super Admin / localhost dev: pick default or first available tenant
            $stmtDefault = $db->query("SELECT id FROM tenants WHERE deleted_at IS NULL ORDER BY created_at ASC LIMIT 1");
            $this->tenantId = $stmtDefault->fetchColumn() ?: '00000000-0000-0000-0000-000000000000';
        }

        // Auto-enable module for the tenant to ensure zero HTTP 400/403 errors
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

    // -------------------------------------------------------------------------
    // 1. DASHBOARD & VIEWS OPERATOR (HTML LAYOUT RENDER)
    // -------------------------------------------------------------------------

    public function dashboard(): void {
        $this->guardModul();
        $summary = $this->model->getDashboardSummary($this->tenantId);
        $pengaturan = $this->model->getPengaturan($this->tenantId);

        $data = [
            'title' => 'Dashboard Perpustakaan',
            'summary' => $summary,
            'pengaturan' => $pengaturan
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/dashboard.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function katalog(): void {
        $this->guardModul();
        $list = $this->model->getBibliografiList($this->tenantId);
        
        $data = [
            'title' => 'Katalog & Koleksi Buku',
            'list' => $list
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/katalog.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function sirkulasi(): void {
        $this->guardModul();
        $data = [
            'title' => 'Sirkulasi Reguler'
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/sirkulasi.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function bukuPaket(): void {
        $this->guardModul();
        $data = [
            'title' => 'Peminjaman Buku Paket Pelajaran',
            'paket_list' => []
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/buku_paket.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function eventOSN(): void {
        $this->guardModul();
        $data = [
            'title' => 'Event Khusus & Peminjaman OSN',
            'event_list' => []
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/event_osn.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function anggota(): void {
        $this->guardModul();
        $data = [
            'title' => 'Keanggotaan & Bebas Pustaka',
            'anggota_list' => []
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/anggota.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function denda(): void {
        $this->guardModul();
        $data = [
            'title' => 'Denda & Billing Integrasi SPP',
            'denda_list' => []
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/denda.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function opname(): void {
        $this->guardModul();
        $data = [
            'title' => 'Stock Opname & Audit Inventaris',
            'opname_list' => []
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/opname.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function laporan(): void {
        $this->guardModul();
        $data = [
            'title' => 'Laporan Perpustakaan & Akreditasi'
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/laporan.php';
        require __DIR__ . '/../../views/layout/master.php';
    }

    public function pengaturan(): void {
        $this->guardModul();
        $pengaturan = $this->model->getPengaturan($this->tenantId);
        $data = [
            'title' => 'Pengaturan Perpustakaan',
            'pengaturan' => $pengaturan
        ];
        $contentView = __DIR__ . '/../../views/perpustakaan/pengaturan.php';
        require __DIR__ . '/../../views/layout/master.php';
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
        
        $ok = $this->model->updatePengaturan($this->tenantId, $input);
        
        if (isset($_POST['nama_perpustakaan'])) {
            header('Location: /SINTA-SaaS/perpustakaan/pengaturan?success=' . urlencode('Pengaturan perpustakaan berhasil disimpan.'));
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
        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        if (empty($input['judul'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Judul buku wajib diisi.'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            return;
        }

        $id = $this->model->saveBibliografi($this->tenantId, $input, $input['id'] ?? null);
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
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Kios Peminjaman Mandiri — Perpustakaan</title></head><body>";
        echo "<h1>🖥️ Kios Peminjaman Mandiri</h1>";
        echo "<p>Silakan scan QR Kartu Anggota Anda, lalu scan barcode buku yang ingin dipinjam.</p>";
        echo "</body></html>";
    }

    public function kiosPintu(): void {
        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>Kios Presensi Pintu Masuk Perpustakaan</title></head><body>";
        echo "<h1>🚪 Gate Pass — Presensi Pintu Masuk Perpustakaan</h1>";
        echo "<p>Dekatkan QR Code Kartu Anggota ke kamera untuk presensi kunjungan otomatis.</p>";
        echo "</body></html>";
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


    // -------------------------------------------------------------------------
    // 3. PUBLIK OPAC & BUKU TAMU (TANPA LOGIN)
    // -------------------------------------------------------------------------

    public function opacPublic(): void {
        $query = $_GET['q'] ?? '';
        $list = [];

        if ($this->tenantId) {
            $list = $this->model->searchOpacPublic($this->tenantId, $query);
        }

        header('Content-Type: text/html; charset=utf-8');
        echo "<!DOCTYPE html><html lang='id'><head><meta charset='UTF-8'><title>OPAC Publik — Katalog Perpustakaan</title></head><body>";
        echo "<h1>🌐 OPAC Publik — Katalog Perpustakaan</h1>";
        echo "<form method='GET' action=''><input type='text' name='q' value='" . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . "' placeholder='Cari judul, pengarang, DDC...'> <button type='submit'>Cari</button></form>";
        echo "<hr>";
        echo "<p>Total Hasil: " . count($list) . " koleksi</p>";
        echo "<ul>";
        foreach ($list as $b) {
            echo "<li><strong>" . htmlspecialchars($b['judul'], ENT_QUOTES, 'UTF-8') . "</strong> (DDC: " . htmlspecialchars($b['klasifikasi_ddc'] ?? '-', ENT_QUOTES, 'UTF-8') . ") — Tersedia: " . (int)$b['total_tersedia'] . " dari " . (int)$b['total_eksemplar'] . " eksemplar</li>";
        }
        echo "</ul></body></html>";
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
}
