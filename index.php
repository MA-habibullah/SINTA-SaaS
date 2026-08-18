<?php
// Secure by Design: Global Security Headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: blob: https:; font-src 'self' https: data:; connect-src 'self' data: blob: https:; worker-src 'self' blob:;");

// Load Composer Autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 1. Definisikan Autoloader Kelas PSR-4 Sederhana
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Load Environment Configuration (.env)
App\Core\Env::load(__DIR__);

// Dynamic Error Display based on APP_DEBUG environment
$appDebug = getenv('APP_DEBUG') ?: ($_ENV['APP_DEBUG'] ?? 'false');
if ($appDebug === 'true') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

// 2. Mulai Session Aman
use App\Core\SessionManager;
SessionManager::start();

// Lacak Sesi Aktif Pengguna
App\Helpers\SessionTracker::track();


// ✅ Global Error Handler: Tangkap semua PHP/SQL error & simpan ke DB system_errors
App\Helpers\ErrorTracker::register(true);

// 3. Routing Sederhana Dinamis
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH) ?? '/';

// Deteksi Base URL secara otomatis dari Script Name
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = rtrim(dirname($scriptName), '/\\');

// Bersihkan path dari base URL folder (misal: /sinta, /SINTA, /SINTA-SaaS)
if (!empty($baseUrl) && $baseUrl !== '/' && strncasecmp($path, $baseUrl, strlen($baseUrl)) === 0) {
    $path = substr($path, strlen($baseUrl));
}

// Standardisasi path kosong, null, atau slash saja
if (empty($path) || $path === '/') {
    $path = '/login';
}

// Ensure trailing slash removal except for root
$path = rtrim($path, '/');
if (empty($path)) {
    $path = '/login';
}

// 4. Map Halaman & Endpoint API (Dilindungi Try-Catch untuk Response API yang Konsisten)
try {
    // First Login Guard untuk Siswa
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['role_name'] ?? '') === 'siswa') {
        $isFirstLogin = $_SESSION['is_first_login'] ?? false;
        if ($isFirstLogin && !in_array($path, ['/siswa/ubah-password', '/siswa/logout', '/api/v1/siswa/ubah-password'])) {
            header('Location: ' . $baseUrl . '/siswa/ubah-password');
            exit;
        }
    }

    // Global Route Guard: Cegah bypass url fitur yang dinonaktifkan oleh Super Admin (Tenant-Level Menu Management)
    if (isset($_SESSION['logged_in']) && !str_starts_with($path, '/api/')) {
        $tenantId = $_SESSION['tenant_id'] ?? null;
        $roleName = $_SESSION['roles'] ?? ($_SESSION['role_name'] ?? '');
        if (!App\Core\RouteGuard::check($path, $tenantId, $roleName)) {
            http_response_code(403);
            echo "<div style='font-family: sans-serif; text-align: center; padding: 50px;'>";
            echo "<h1 style='color: #dc3545; font-size: 2.5rem; margin-bottom: 15px;'>403 Fitur Belum Aktif / Silakan Upgrade Paket</h1>";
            echo "<p style='color: #6c757d; font-size: 1.1rem; line-height: 1.6;'>Menu fitur ini dinonaktifkan untuk sekolah Anda oleh Super Admin Platform. Silakan hubungi Administrator untuk upgrade paket fitur.</p>";
            echo "<a href='/SINTA-SaaS/dashboard' style='display: inline-block; margin-top: 25px; padding: 10px 20px; background-color: #0d6efd; color: #fff; text-decoration: none; border-radius: 5px; font-weight: 600;'>Kembali ke Dashboard</a>";
            echo "</div>";
            exit;
        }
    }

    switch ($path) {
        // ─── Secure File Serve (wajib login, anti path traversal, whitelist MIME) ────
        case '/api/v1/file/serve':
            (new \App\Modules\Core\Controllers\SecureFileController())->serve();
            break;

        case '/api/v1/log-js-error':
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data) {
                \App\Helpers\ErrorTracker::logToDatabase(
                    'JS ' . ($data['type'] ?? 'Error'),
                    $data['message'] ?? 'Unknown JS Error',
                    $data['source'] ?? 'ClientBrowser',
                    $data['lineno'] ?? 0,
                    $data['stack'] ?? 'No stack trace'
                );
            }
            header('Content-Type: application/json');
            echo json_encode(['success' => true]);
            exit;

        case '/login':
            // Jika sudah login sebagai siswa, langsung lempar ke dashboard
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['role_name'] ?? '') === 'siswa') {
                header('Location: ' . $baseUrl . '/dashboard');
                exit;
            }
            $controller = new App\Modules\Siswa\Controllers\SiswaAuthModuleController();
            $controller->loginView();
            break;

        case '/siswa/login':
            header('Location: ' . $baseUrl . '/login');
            exit;

        case '/admin':
            // Jika sudah login sebagai admin/super_admin, langsung lempar ke dashboard
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['role_name'] ?? '') !== 'siswa') {
                header('Location: ' . $baseUrl . '/dashboard');
                exit;
            }
            $controller = new App\Modules\Core\Controllers\AuthModuleController();
            $controller->adminLoginView();
            break;

        case '/api/v1/siswa/login':
            $controller = new App\Modules\Siswa\Controllers\SiswaAuthModuleController();
            $controller->loginApi();
            break;

        case '/bk':
            $controller = new App\Modules\Bk\Controllers\BkModuleController();
            $controller->indexView();
            break;

        case '/kesiswaan/ekskul':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->ekskulIndex();
            break;

        case '/api/v1/kesiswaan/ekskul':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->getEkskulApi();
            break;

        case '/api/v1/nilai-rapor/siswa':
            $controller = new App\Modules\Akademik\Controllers\RaporModuleController();
            $controller->getNilaiRaporSiswa();
            break;

        case '/kepegawaian/ptk':
            $controller = new App\Modules\Kepegawaian\Controllers\KepegawaianModuleController();
            $controller->getDaftarPtk();
            break;

        case '/keuangan/tagihan':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

case '/perpustakaan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->indexView();
            break;

        case '/api/v1/perpustakaan/katalog':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->getKatalogApi();
            break;

        case '/api/v1/sarpras/inventaris':
            $controller = new App\Modules\Sarpras\Controllers\SarprasModuleController();
            $controller->getInventarisApi();
            break;

        case '/api/v1/persuratan/surat-masuk':
            $controller = new App\Modules\Persuratan\Controllers\PersuratanModuleController();
            $controller->getSuratMasukApi();
            break;

        case '/api/v1/sistem/audit-log':
            $controller = new App\Modules\Sistem\Controllers\SistemModuleController();
            $controller->getAuditLogApi();
            break;

        case '/api/v1/cms/landing-sections':
            $controller = new App\Modules\Cms\Controllers\CmsModuleController();
            $controller->getLandingSectionsApi();
            break;

        case '/siswa/edit':
            $controller = new App\Modules\Siswa\Controllers\SiswaModuleController();
            $controller->edit();
            break;

        case '/siswa/tambah':
            $controller = new App\Modules\Siswa\Controllers\SiswaModuleController();
            $controller->tambah();
            break;

        case '/siswa/simpan':
            $controller = new App\Modules\Siswa\Controllers\SiswaModuleController();
            $controller->store();
            break;

        case '/siswa/update':
            $controller = new App\Modules\Siswa\Controllers\SiswaModuleController();
            $controller->update();
            break;


        case '/kesiswaan/ekskul':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->index();
            break;
            
        case '/informasi/pengumuman':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->index();
            break;
            
        case '/informasi/pengumuman/kategori/store':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->storeKategori();
            break;
            
        case '/informasi/pengumuman/kategori/update':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->updateKategori();
            break;
            
        case '/informasi/pengumuman/kategori/delete':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->deleteKategori();
            break;
            
        case '/pengumuman/arsip':
            $controller = new App\Modules\Core\Controllers\DashboardModuleController();
            $controller->pengumumanArsip();
            break;
            
        case '/informasi/pengumuman/store':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->store();
            break;
            
        case '/informasi/pengumuman/update':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->update();
            break;
            
        case '/informasi/pengumuman/delete':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->delete();
            break;
            
        case '/pembinaan':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;
        case '/pembinaan_old':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->index();
            break; 
            
        case '/pembinaan/store':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->store();
            break; 
            
        case '/pembinaan/jadwal':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->jadwalkan_sesi();
            break; 
            
        case '/pembinaan/sesi':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->sesi();
            break; 
            
        case '/pembinaan/sesi/simpan':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->simpan_sesi();
            break; 
            
        case '/pembinaan/evaluasi':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->evaluasi();
            break; 
            
        case '/pembinaan/cetak':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->cetak();
            break; 

        case '/konseling':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->index();
            break;
            
        case '/konseling/store':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->store();
            break;

        case '/informasi/agenda':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;
        case '/informasi/agenda_old':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->index();
            break;
            
        case '/informasi/agenda/store':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->store();
            break;
            
        case '/informasi/agenda/update':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->update();
            break;
            
        case '/informasi/agenda/delete':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->delete();
            break;

        case '/informasi/kategori-agenda/store':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->store();
            break;
            
        case '/informasi/kategori-agenda/update':
            $controller = new App\Modules\Core\Controllers\PengumumanModuleController();
            $controller->update();
            break;

        // MODUL PERPUSTAKAAN (INTEGRATED LIBRARY SYSTEM)
        case '/perpustakaan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->indexView();
            break;

        case '/perpustakaan/katalog':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->katalog();
            break;

        case '/perpustakaan/sirkulasi':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->sirkulasi();
            break;

        case '/perpustakaan/buku-paket':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->bukuPaket();
            break;

        case '/perpustakaan/event':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->eventOSN();
            break;

        case '/perpustakaan/anggota':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->anggota();
            break;

        case '/perpustakaan/denda':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->denda();
            break;

        case '/perpustakaan/opname':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->opname();
            break;

        case '/perpustakaan/laporan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->laporan();
            break;

        case '/perpustakaan/pengaturan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->pengaturan();
            break;

        case '/perpustakaan/opac':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->opacPublic();
            break;

        case '/perpustakaan/buku-tamu':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->bukuTamuPublic();
            break;

        case '/perpustakaan/riwayat-saya':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->riwayatSiswa();
            break;

        case '/api/v1/perpustakaan/sirkulasi/pinjam':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiPinjamReguler();
            break;

        case '/api/v1/perpustakaan/sirkulasi/kembali':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiKembaliReguler();
            break;

        case '/api/v1/perpustakaan/bebas-pustaka/cek':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiCekBebasPustaka();
            break;

        case '/api/v1/perpustakaan/anggota/sync':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiSyncAnggota();
            break;

        case '/api/v1/perpustakaan/summary':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiDashboardSummary();
            break;

        case '/api/v1/perpustakaan/rak':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveRak();
            } else {
                $controller->apiGetRak();
            }
            break;

        case '/api/v1/perpustakaan/katalog':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiGetKatalog();
            break;

        case '/api/v1/perpustakaan/katalog/simpan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiSaveBibliografi();
            break;

        case '/api/v1/perpustakaan/katalog/traceability':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiGetBibliografiTraceability();
            break;

        case '/api/v1/perpustakaan/eksemplar/simpan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiSaveEksemplar();
            break;

        case '/api/v1/perpustakaan/pengaturan':
        case '/api/v1/perpustakaan/pengaturan/simpan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiSavePengaturan();
            break;

        case '/api/v1/perpustakaan/denda':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiGetDenda();
            break;

        case '/api/v1/perpustakaan/denda/bayar':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiBayarDenda();
            break;

        case '/api/v1/perpustakaan/usulan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveUsulan();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $controller->apiDeleteUsulan();
            } else {
                $controller->apiGetUsulan();
            }
            break;

        case '/api/v1/perpustakaan/serial':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveSerial();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $controller->apiDeleteSerial();
            } else {
                $controller->apiGetSerial();
            }
            break;

        case '/api/v1/perpustakaan/kompetensi':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveKompetensi();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $controller->apiDeleteKompetensi();
            } else {
                $controller->apiGetKompetensi();
            }
            break;

        case '/api/v1/perpustakaan/visitor-logs':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiGetVisitorLogs();
            break;

        case '/api/v1/perpustakaan/ddc-categories':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiGetDdcCategories();
            break;

        case '/perpustakaan/baca-ebook':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->readEbook();
            break;

        case '/perpustakaan/kios-mandiri':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->kiosMandiri();
            break;

        case '/perpustakaan/kios-pintu':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->kiosPintu();
            break;

        case '/perpustakaan/cetak-label-thermal':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->cetakLabelThermal();
            break;

        case '/perpustakaan/katalog/export-excel':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->exportKatalogExcel();
            break;

        case '/perpustakaan/cetak-laporan-ddc':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->cetakLaporanDdc();
            break;

        case '/perpustakaan/cetak-laporan-peminjaman':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->cetakLaporanPeminjaman();
            break;

        case '/perpustakaan/cetak-laporan-kunjungan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->cetakLaporanKunjungan();
            break;

        case '/api/v1/perpustakaan/ulasan/simpan':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiSimpanUlasan();
            break;

        case '/api/v1/perpustakaan/duta-baca':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiGetDutaBaca();
            break;

        case '/api/v1/perpustakaan/cron/reminder':
            $controller = new App\Modules\Perpustakaan\Controllers\PerpustakaanModuleController();
            $controller->apiCronNotifReminder();
            break;

        case '/api/v1/ekskul/tambah':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->store();
            break;

        case '/api/v1/ekskul/pembina/tambah':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->storePembina();
            break;

        case '/api/v1/ekskul/edit':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->update();
            break;

        case '/api/v1/ekskul/toggle-status':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->toggleStatus();
            break;

        case '/api/v1/ekskul/pembina/edit':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->updatePembina();
            break;

        case '/api/v1/ekskul/pembina/toggle-status':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->togglePembinaStatus();
            break;

        case '/api/v1/ekskul/anggota/tambah':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->addMembers();
            break;

        case '/api/v1/ekskul/anggota/hapus':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->removeMember();
            break;

        case '/api/v1/ekskul/nilai/simpan':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->saveGrades();
            break;

        case '/api/v1/ekskul/kunci/anggota':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->toggleLockAnggota();
            break;

        case '/api/v1/ekskul/kunci/nilai':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->toggleLockNilai();
            break;

        case '/api/v1/ekskul/nilai/export':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->exportGrades();
            break;

        case '/api/v1/ekskul/nilai/import':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->importGrades();
            break;

        case '/api/v1/ekskul/anggota/export':
            $controller = new App\Modules\Kesiswaan\Controllers\KesiswaanModuleController();
            $controller->exportMembers();
            break;

        case '/dashboard':
            // Panggil DashboardController
            $controller = new App\Modules\Core\Controllers\DashboardModuleController();
            $controller->index();
            break;

        case '/utility/document-scanner':
            // Pastikan session sudah login
            if (!isset($_SESSION['user_id'])) {
                header('Location: /SINTA-SaaS/login');
                exit;
            }
            $controller = new App\Modules\Sistem\Controllers\DocumentScannerModuleController();
            $controller->index();
            break;

        // Rute Pusat Bantuan (Ticketing System)
        case '/bantuan':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->index();
            break; 
        case '/api/v1/bantuan/buat':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiCreateTicket();
            break; 
        case '/api/v1/bantuan/list':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiListTickets();
            break; 
        case '/api/v1/bantuan/detail':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiGetTicketDetail();
            break; 
        case '/api/v1/bantuan/balas':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiReplyTicket();
            break; 
        case '/api/v1/bantuan/update-status':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiUpdateStatus();
            break; 
        case '/api/v1/bantuan/faq-lookup':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiFaqLookup();
            break; 
        case '/api/v1/bantuan/canned-responses':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiGetCannedResponses();
            break; 
        case '/api/v1/bantuan/unread-count':
            (new \App\Modules\Core\Controllers\BantuanModuleController())->apiGetUnreadCount();
            break; 

        // Rute Modul Keuangan & SPP Dinamis
        case '/keuangan/dashboard':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

case '/keuangan/master':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

case '/keuangan/keringanan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->keringanan();
            break; 
        case '/keuangan/generate':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->generate();
            break; 
        case '/keuangan/kasir':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

case '/keuangan/laporan':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;
        case '/keuangan/laporan_old':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->laporan();
            break; 
        case '/keuangan/pengaturan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->pengaturan();
            break; 
        case '/keuangan/tagihan-saya':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

case '/keuangan/audit-log':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

case '/api/v1/keuangan/dashboard-metrics':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiDashboardMetrics();
            break; 
        case '/api/v1/keuangan/komponen':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiKomponen();
            break; 
        case '/api/v1/keuangan/tarif':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiTarif();
            break; 
        case '/api/v1/keuangan/keringanan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiKeringanan();
            break; 
        case '/api/v1/keuangan/generate-tagihan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiGenerateTagihan();
            break; 
        case '/api/v1/keuangan/preview-generate':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiPreviewGenerate();
            break; 
        case '/api/v1/keuangan/daftar-tagihan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiDaftarTagihan();
            break; 
        case '/api/v1/keuangan/tahun-ajaran':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiTahunAjaran();
            break; 
        case '/api/v1/keuangan/kelas':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiKelas();
            break; 
        case '/api/v1/keuangan/jenjang':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiJenjang();
            break; 
        case '/api/v1/keuangan/export-tagihan-excel':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiExportTagihanExcel();
            break; 
        case '/api/v1/keuangan/edit-tagihan-nominal':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiEditTagihanNominal();
            break; 
        case '/api/v1/keuangan/hapus-tagihan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiHapusTagihan();
            break; 
        case '/api/v1/keuangan/cari-siswa':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiCariSiswa();
            break; 
        case '/api/v1/keuangan/tagihan-siswa':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiGetTagihanSiswa();
            break; 
        case '/api/v1/keuangan/bayar':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiBayarTagihan();
            break; 
        case '/api/v1/keuangan/batal-pembayaran':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiBatalPembayaran();
            break; 
        case '/api/v1/keuangan/laporan-rekap':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiLaporanRekap();
            break; 
        case '/api/v1/keuangan/pengaturan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiGetPengaturan();
            break; 
        case '/api/v1/keuangan/tenants':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiTenants();
            break; 
        case '/api/v1/keuangan/komponen/toggle':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiToggleKomponen();
            break; 
        case '/api/v1/keuangan/save-pengaturan':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiSavePengaturan();
            break; 
        case '/api/v1/keuangan/tagihan-saya':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiGetTagihanSaya();
            break; 
        case '/api/v1/keuangan/audit-log':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiAuditLog();
            break; 
        case '/api/v1/keuangan/buat-tagihan-ppdb':
            (new \App\Modules\Keuangan\Controllers\SppModuleController())->apiCreatePpdbInvoice();
            break; 



        case '/buku-induk':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->index();
            break;

        case '/api/v1/nilai-rapor/grid':
            $controller = new App\Modules\Akademik\Controllers\NilaiRaporModuleController();
            $controller->getGrid();
            break;

        case '/api/v1/nilai-rapor/save':
            $controller = new App\Modules\Akademik\Controllers\NilaiRaporModuleController();
            $controller->save();
            break;

        case '/api/v1/nilai-rapor/export':
            $controller = new App\Modules\Akademik\Controllers\NilaiRaporModuleController();
            $controller->export();
            break;

        case '/api/v1/nilai-rapor/import':
        case '/api/v1/nilai-rapor/import-validate':
            $controller = new App\Modules\Akademik\Controllers\NilaiRaporModuleController();
            $controller->import();
            break;

        case '/api/v1/nilai-rapor/delete-siswa':
            // API: Hapus nilai siswa dari tabel matriks nilai rapor
            $controller = new App\Modules\Akademik\Controllers\NilaiRaporModuleController();
            $controller->deleteSiswaGradesApi();
            break;

        case '/api/v1/verify-transkrip/data':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->verifyTranskripApi();
            break;

        case '/api/v1/cetak/request-token':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->requestCetakToken();
            break;

        case '/api/v1/buku-induk':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/buku-induk/detail':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->fetchDetailApi();
            break;

        case '/api/v1/buku-induk/archive/upload':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->compileImagesToPdfApi();
            break;

        case '/api/v1/buku-induk/archive/list':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->fetchDocumentsApi();
            break;

        case '/api/v1/buku-induk/archive/delete':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->deleteDocumentApi();
            break;

        case '/api/v1/buku-induk/archive/view':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->viewDocumentApi();
            break;

        case '/api/v1/buku_induk/matrix_cetak':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->fetchCetakMatrixApi();
            break;

        case '/api/v1/kunci_akademik':
            $controller = new App\Modules\Akademik\Controllers\KurikulumModuleController();
            $controller->getStatus();
            break;

        case '/api/v1/kunci_akademik/toggle':
            $controller = new App\Modules\Akademik\Controllers\KurikulumModuleController();
            $controller->toggle();
            break;


        case '/api/v1/buku-induk/beasiswa':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storeBeasiswaApi();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $controller->deleteBeasiswaApi();
            } else {
                $controller->getBeasiswaApi();
            }
            break;

        case '/cetak-rapot':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->printRapot();
            break;

        case '/cetak-buku-induk':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->printBukuInduk();
            break;

        case '/cetak-rapot-semester':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->printRapotSemester();
            break;

        case '/cetak-transkrip-nilai':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->printTranskripNilai();
            break;

        case '/verify-transkrip':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->verifyTranskrip();
            break;

        case '/cetak-rapot-kelas':
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->printRapotKelas();
            break;


        case '/api/v1/siswa/delete':
            // API: Hapus Siswa (Soft Delete) via AJAX
            $controller = new App\Modules\Siswa\Controllers\BukuIndukModuleController();
            $controller->deleteSiswaApi();
            break;

        // ================================================================
        // TRACER STUDY ROUTES
        // ================================================================
        case '/tracer-study':
            // Halaman Tracer Study / Portofolio Alumni
            $controller = new App\Modules\Alumni\Controllers\TracerModuleController();
            $controller->index();
            break;

        case '/api/v1/tracer/kuliah':
            $controller = new App\Modules\Alumni\Controllers\TracerModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storeKuliah();
            } else {
                $controller->apiGetKuliah();
            }
            break;

        case '/api/v1/tracer/pekerjaan':
            $controller = new App\Modules\Alumni\Controllers\TracerModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storePekerjaan();
            } else {
                $controller->apiGetPekerjaan();
            }
            break;

        case '/api/v1/tracer/kuliah/delete':
            // API: Hapus riwayat kuliah (hanya admin/guru_bk)
            $controller = new App\Modules\Alumni\Controllers\TracerModuleController();
            $controller->deleteKuliah();
            break;

        case '/api/v1/tracer/pekerjaan/delete':
            // API: Hapus riwayat pekerjaan (hanya admin/guru_bk)
            $controller = new App\Modules\Alumni\Controllers\TracerModuleController();
            $controller->deletePekerjaan();
            break;

        case '/api/v1/pdss/students/search':
            // API: Pencarian siswa alumni untuk form tracer study admin
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiSearchStudents();
            break;

        // ================================================================
        // BIMBINGAN KONSELING (BK) ROUTES
        // Role Guard: super_admin, operator_sekolah, guru_bk (enforced in BKController)
        // ================================================================
        case '/bk/layanan':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->layanan();
            break;

        case '/bk/kedisiplinan':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->kedisiplinan();
            break;

        case '/bk/akademik':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->akademik();
            break;

        case '/bk/alumni':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->alumni();
            break;

        case '/api/v1/bk/dashboard':
            // API: KPI Dashboard Monitoring BK (Tab 1)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDashboard();
            break;

        case '/api/v1/bk/pelanggaran/dashboard':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiGetPelanggaranDashboard();
            break;

        case '/api/v1/bk/pelanggaran/master':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStoreMasterPelanggaran();
            } else {
                $controller->apiGetMasterPelanggaran();
            }
            break;

        case '/api/v1/bk/pelanggaran/master/update':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiUpdateMasterPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/master/delete':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDeleteMasterPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/catatan':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStoreCatatanPelanggaran();
            } else {
                $controller->apiGetCatatanPelanggaran();
            }
            break;

        case '/api/v1/bk/pelanggaran/catatan/update':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiUpdateCatatanPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/catatan/delete':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDeleteCatatanPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/sanksi':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiGetSanksiBuku();
            break;

        case '/api/v1/bk/pelanggaran/sanksi/detail':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiGetSanksiDetail();
            break;

        case '/api/v1/bk/pelanggaran/sanksi/tindak-lanjut':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiStoreTindakLanjutSanksi();
            break;

        case '/api/v1/bk/absensi-semester':
            // API: GET list absensi / POST simpan bulk absensi semester (Tab Kehadiran)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveAbsensiSemesterBulk();
            } else {
                $controller->apiGetAbsensiSemester();
            }
            break;

        case '/api/v1/bk/absensi-semester/export':
            // API: GET download Excel (.xls) absensi kelas per semester
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiExportAbsensiSemester();
            break;

        case '/api/v1/bk/absensi-semester/import':
            // API: POST upload file CSV absensi kelas per semester
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiImportAbsensiSemester();
            break;

        case '/api/v1/bk/absensi-semester/toggle-lock':
            // API: POST toggle kunci/buka kunci data absensi semester
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiToggleLockAbsensiSemester();
            break;

        case '/api/v1/bk/kasus':
            // API: GET list kasus / POST simpan kasus baru (Tab 5)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStoreKasus();
            } else {
                $controller->apiListKasus();
            }
            break;

        case '/api/v1/bk/kasus/update-status':
            // API: POST update status riwayat kasus BK + log
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiUpdateStatus();
            break;

        case '/api/v1/bk/kasus/logs':
            // API: GET list log riwayat penanganan kasus BK
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiGetLogs();
            break;

        case '/api/v1/bk/prestasi':
            // API: GET list prestasi / POST simpan prestasi baru
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStorePrestasi();
            } else {
                $controller->apiListPrestasi();
            }
            break;

        case '/api/v1/bk/prestasi/update':
            // API: POST update prestasi
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiUpdatePrestasi();
            break;

        case '/api/v1/bk/prestasi/delete':
            // API: POST delete (soft-delete) prestasi
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDeletePrestasi();
            break;

        case '/api/v1/bk/guru':
            // API: GET list guru pendamping
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiGetGuruList();
            break;

        case '/api/v1/bk/tracer':
            // API: Ringkasan Tracer Study alumni (Tab 3)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiTracerSummary();
            break;

        case '/bk/kesiapan-pdss':
            header('Location: /SINTA-SaaS/pdss/kesiapan');
            exit;

        case '/pdss/kesiapan':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->index();
            break;

        case '/api/v1/pdss/kesiapan':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiGetKesiapan();
            break;

        case '/api/v1/pdss/config-mapel':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSavePdssMapels();
            } else {
                $controller->apiGetPdssMapels();
            }
            break;

        case '/api/v1/pdss/manual-eligible':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiSaveManualEligible();
            break;

        case '/api/v1/pdss/lock':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiToggleLock();
            break;

        case '/api/v1/pdss/student-grades':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiGetStudentGrades();
            break;

        case '/api/v1/pdss/download-leger':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiDownloadLeger();
            break;

        case '/api/v1/pdss/students/search':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiSearchStudents();
            break;

        case '/api/v1/pdss/alumni-tracks':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveAlumniTrack();
            } else {
                $controller->apiGetAlumniTracks();
            }
            break;

        case '/api/v1/pdss/alumni-tracks/delete':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiDeleteAlumniTrack();
            break;

        case '/api/v1/pdss/target-kampus':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveTargetKampus();
            } else {
                $controller->apiGetTargetKampus();
            }
            break;

        case '/api/v1/pdss/target-kampus/delete':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiDeleteTargetKampus();
            break;

        // MASTER KAMPUS & PRODI ROUTES
        case '/api/v1/kampus/template':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDownloadTemplate();
            break;

        case '/api/v1/kampus/import':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiImportExcel();
            break;

        case '/api/v1/kampus':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveKampus();
            } else {
                $controller->apiGetKampus();
            }
            break;

        case '/api/v1/kampus/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteKampus();
            break;

        case '/api/v1/kampus/prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveProdi();
            } else {
                $controller->apiGetProdi();
            }
            break;

        case '/api/v1/kampus/prodi/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteProdi();
            break;

        case '/api/v1/kampus/prodi/riwayat':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveRiwayat();
            } else {
                $controller->apiGetRiwayat();
            }
            break;

        case '/api/v1/kampus/prodi/riwayat/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteRiwayat();
            break;

        case '/api/v1/kampus/jalur':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveJalurMasuk();
            } else {
                $controller->apiGetJalurMasuk();
            }
            break;

        case '/api/v1/kampus/jalur/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteJalurMasuk();
            break;

        case '/api/v1/kampus/all-prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiGetAllKampusProdi();
            break;

        case '/api/v1/kampus/flat-list':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiGetMasterKampusProdiFlat();
            break;

        case '/api/v1/kampus/export-daya-tampung':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiExportDayaTampung();
            break;

        case '/api/v1/kampus/import-daya-tampung':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiImportDayaTampung();
            break;

        case '/api/v1/kampus/bulk-delete-riwayat':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiBulkDeleteRiwayat();
            break;

        case '/api/v1/kampus/export-kampus-prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiExportKampusProdi();
            break;

        case '/api/v1/kampus/import-kampus-prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiImportKampusProdi();
            break;


        case '/api/v1/pdss/target-kampus/seed':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiSeedTargetKampus();
            break;

        case '/api/v1/bk/pdss':
            // API: Daftar siswa eligible SNBP / PDSS (Tab 4) - Deprecated / Scoped to PDSS Module
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiGetKesiapan();
            break;

        case '/api/v1/bk/penjurusan':
            // API: GET list pilihan penjurusan (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiListPenjurusan();
            break;

        case '/api/v1/bk/penjurusan/verifikasi':
            // API: POST verifikasi atau tolak pilihan penjurusan (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiVerifikasiPenjurusan();
            break;

        case '/api/v1/bk/penjurusan/override':
            // API: POST override jurusan siswa dengan ACID transaction (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiOverridePenjurusan();
            break;

        case '/api/v1/bk/penjurusan/kunci':
            // API: POST toggle kunci pilihan penjurusan (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiToggleKunci();
            break;

        case '/api/v1/bk/siswa':
            // API: GET search siswa dengan kelas, NIS, NISN (untuk Rekam Kasus Tab 5)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiSiswaSearch();
            break;

        case '/api/v1/bk/beasiswa/list':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiBeasiswaList();
            break;

        case '/api/v1/bk/beasiswa/export':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiExportBeasiswa();
            break;

            $controller->apiGetStudentGrades();
            break;

        case '/api/v1/pdss/download-leger':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiDownloadLeger();
            break;

        case '/api/v1/pdss/students/search':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiSearchStudents();
            break;

        case '/api/v1/pdss/alumni-tracks':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveAlumniTrack();
            } else {
                $controller->apiGetAlumniTracks();
            }
            break;

        case '/api/v1/pdss/alumni-tracks/delete':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiDeleteAlumniTrack();
            break;

        case '/api/v1/pdss/target-kampus':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveTargetKampus();
            } else {
                $controller->apiGetTargetKampus();
            }
            break;

        case '/api/v1/pdss/target-kampus/delete':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiDeleteTargetKampus();
            break;

        // MASTER KAMPUS & PRODI ROUTES
        case '/api/v1/kampus/template':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDownloadTemplate();
            break;

        case '/api/v1/kampus/import':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiImportExcel();
            break;

        case '/api/v1/kampus':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveKampus();
            } else {
                $controller->apiGetKampus();
            }
            break;

        case '/api/v1/kampus/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteKampus();
            break;

        case '/api/v1/kampus/prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveProdi();
            } else {
                $controller->apiGetProdi();
            }
            break;

        case '/api/v1/kampus/prodi/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteProdi();
            break;

        case '/api/v1/kampus/prodi/riwayat':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveRiwayat();
            } else {
                $controller->apiGetRiwayat();
            }
            break;

        case '/api/v1/kampus/prodi/riwayat/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteRiwayat();
            break;

        case '/api/v1/kampus/jalur':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveJalurMasuk();
            } else {
                $controller->apiGetJalurMasuk();
            }
            break;

        case '/api/v1/kampus/jalur/delete':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiDeleteJalurMasuk();
            break;

        case '/api/v1/kampus/all-prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiGetAllKampusProdi();
            break;

        case '/api/v1/kampus/flat-list':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiGetMasterKampusProdiFlat();
            break;

        case '/api/v1/kampus/export-daya-tampung':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiExportDayaTampung();
            break;

        case '/api/v1/kampus/import-daya-tampung':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiImportDayaTampung();
            break;

        case '/api/v1/kampus/bulk-delete-riwayat':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiBulkDeleteRiwayat();
            break;

        case '/api/v1/kampus/export-kampus-prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiExportKampusProdi();
            break;

        case '/api/v1/kampus/import-kampus-prodi':
            $controller = new App\Modules\Alumni\Controllers\KampusModuleController();
            $controller->apiImportKampusProdi();
            break;


        case '/api/v1/pdss/target-kampus/seed':
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiSeedTargetKampus();
            break;

        case '/api/v1/bk/pdss':
            // API: Daftar siswa eligible SNBP / PDSS (Tab 4) - Deprecated / Scoped to PDSS Module
            $controller = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $controller->apiGetKesiapan();
            break;

        case '/api/v1/bk/penjurusan':
            // API: GET list pilihan penjurusan (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiListPenjurusan();
            break;

        case '/api/v1/bk/penjurusan/verifikasi':
            // API: POST verifikasi atau tolak pilihan penjurusan (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiVerifikasiPenjurusan();
            break;

        case '/api/v1/bk/penjurusan/override':
            // API: POST override jurusan siswa dengan ACID transaction (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiOverridePenjurusan();
            break;

        case '/api/v1/bk/penjurusan/kunci':
            // API: POST toggle kunci pilihan penjurusan (Tab 2)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiToggleKunci();
            break;

        case '/api/v1/bk/siswa':
            // API: GET search siswa dengan kelas, NIS, NISN (untuk Rekam Kasus Tab 5)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiSiswaSearch();
            break;

        case '/api/v1/bk/beasiswa/list':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiBeasiswaList();
            break;

        case '/api/v1/bk/beasiswa/export':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiExportBeasiswa();
            break;

        // ─── PDSS: KESIAPAN & ELIGIBILITAS ────────────────────
        case '/api/v1/bk/kesiapan/list':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKesiapanList();
            break;

        case '/api/v1/bk/kesiapan/toggle-eligible':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiToggleEligible();
            break;

        case '/api/v1/bk/kesiapan/auto-calculate':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKesiapanAutoCalculate();
            break;

        case '/api/v1/bk/kesiapan/detail-nilai':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKesiapanDetailNilai();
            break;

        // ─── PDSS: SIMULASI PILIHAN KAMPUS ────────────────────
        case '/api/v1/bk/simulasi/list':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiSimulasiList();
            break;

        case '/api/v1/bk/simulasi/setting':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiGetSimulasiSetting();
            break;

        case '/api/v1/bk/simulasi/toggle-setting':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiToggleSimulasiSetting();
            break;

        // ─── MASTER KAMPUS & PRODI ────────────────────────────
        case '/api/v1/bk/kampus/list':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKampusList();
            break;

        case '/api/v1/bk/kampus/create':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKampusCreate();
            break;

        case '/api/v1/bk/kampus/update':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKampusUpdate();
            break;

        case '/api/v1/bk/kampus/delete':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKampusDelete();
            break;

        // ─── MASTER JALUR MASUK ───────────────────────────────
        case '/api/v1/bk/jalur-masuk/list':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiJalurList();
            break;

        case '/api/v1/bk/jalur-masuk/create':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiJalurCreate();
            break;

        case '/api/v1/bk/jalur-masuk/update':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiJalurUpdate();
            break;

        case '/api/v1/bk/jalur-masuk/delete':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiJalurDelete();
            break;

        // ─── ALUMNI: TRACKING & TRACER STUDY ─────────────────
        case '/api/v1/bk/alumni/tracking':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiAlumniTracking();
            break;

        case '/api/v1/bk/prestasi/delete':
            // API: POST delete (soft-delete) prestasi
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDeletePrestasi();
            break;

        case '/api/v1/bk/prestasi/export':
            // API: GET export Excel prestasi siswa
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiExportPrestasi();
            break;

        case '/api/v1/bk/prestasi/export':
            // API: GET export Excel prestasi siswa
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiExportPrestasi();
            break;

        case '/api/v1/bk/alumni/riwayat-kuliah':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiAlumniRiwayatKuliah();
            break;

        case '/api/v1/bk/alumni/riwayat-kuliah/delete':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDeleteRiwayatKuliah();
            break;

        case '/api/v1/bk/alumni/riwayat-pekerjaan':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiAlumniRiwayatPekerjaan();
            break;

        case '/api/v1/bk/alumni/riwayat-pekerjaan/delete':
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiDeleteRiwayatPekerjaan();
            break;

        case '/api/v1/bk/kelas':
            // API: GET daftar kelas aktif untuk filter Rekam Kasus (Tab 5)
            $controller = new App\Modules\Bk\Controllers\BkDetailModuleController();
            $controller->apiKelasList();
            break;

        case '/konfigurasi/akses':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->index();
            break;

        case '/konfigurasi/akses/simpan':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->saveAccessMatrix();
            break;

        case '/api/v1/akses/fetch':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->fetchAccessMap();
            break;

        case '/api/v1/akses/user-override':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->fetchUserAccessOverrides();
            break;

        case '/api/v1/akses/user-override/simpan':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->saveUserAccessOverrides();
            break;

        case '/utilitas/log-aktivitas':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->index();
            break;

        case '/api/v1/activity-logs/filters':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->fetchFiltersApi();
            break;

        case '/api/v1/activity-logs':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/activity-logs/delete':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->deleteLogsApi();
            break;

        case '/utilitas/sesi-aktif':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->index();
            break;

        case '/api/v1/sessions/data':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->fetchDataApi();
            break;

        case '/api/v1/sessions/audit':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->fetchAuditApi();
            break;

        case '/api/v1/sessions/audit/retention':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->deleteAuditRetentionApi();
            break;

        case '/api/v1/sessions/retention':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->deleteRetentionApi();
            break;

        case '/master-data':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->index();
            break;

        case '/api/v1/kelembagaan':
            // API: Ambil data terpaginasi
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/kelembagaan/options':
            // API: Ambil data opsi relasi
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getOptionsApi();
            break;

        case '/api/v1/kelembagaan/tenants':
            // API: Ambil daftar tenant/sekolah untuk Super Admin
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/kelembagaan/simpan':
            // API: Simpan/Update data
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/kelembagaan/hapus':
            // API: Soft Delete
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/kelembagaan/restore':
            // API: Restore
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->restoreApi();
            break;

            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->store();
            break;

        case '/api/v1/akses/fetch':
            // AJAX: Ambil access map untuk tenant tertentu (Super Admin)
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->fetchAccessMap();
            break;

        case '/api/v1/akses/user-override':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->fetchUserAccessOverrides();
            break;

        case '/api/v1/akses/user-override/simpan':
            $controller = new App\Modules\Sistem\Controllers\AksesModuleController();
            $controller->saveUserAccessOverrides();
            break;

        case '/utilitas/log-aktivitas':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->index();
            break;

        case '/api/v1/activity-logs/filters':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->fetchFiltersApi();
            break;

        case '/api/v1/activity-logs':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/activity-logs/delete':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->deleteLogsApi();
            break;

        case '/utilitas/sesi-aktif':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->index();
            break;

        case '/api/v1/sessions/data':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->fetchDataApi();
            break;

        case '/api/v1/sessions/audit':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->fetchAuditApi();
            break;

        case '/api/v1/sessions/audit/retention':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->deleteAuditRetentionApi();
            break;

        case '/api/v1/sessions/retention':
            $controller = new App\Modules\Sistem\Controllers\ActiveSessionModuleController();
            $controller->deleteRetentionApi();
            break;

        case '/master-data':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->index();
            break;

        case '/api/v1/kelembagaan':
            // API: Ambil data terpaginasi
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/kelembagaan/options':
            // API: Ambil data opsi relasi
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getOptionsApi();
            break;

        case '/api/v1/kelembagaan/tenants':
            // API: Ambil daftar tenant/sekolah untuk Super Admin
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/kelembagaan/simpan':
            // API: Simpan/Update data
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/kelembagaan/hapus':
            // API: Soft Delete
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/kelembagaan/restore':
            // API: Restore
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->restoreApi();
            break;

        case '/api/v1/kelembagaan/toggle-status':
            // API: Toggle Status Aktif
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->toggleStatusApi();
            break;
        case '/sekolah/identitas':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->identitasView();
            break;

        case '/api/v1/sekolah/update':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->updateProfile();
            break;

        case '/utilitas/antrean':
            $controller = new App\Modules\Sistem\Controllers\QueueModuleController();
            $controller->index();
            break;

        case '/utilitas/log-aktivitas':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->index();
            break;

        case '/utility/document-scanner':
        case '/utilitas/document-scanner':
        case '/utilitas/pemindai-dokumen':
            $controller = new App\Modules\Sistem\Controllers\DocumentScannerModuleController();
            $controller->index();
            break;

        case '/api/v1/activity-logs':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/activity-logs/filters':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->fetchFiltersApi();
            break;

        case '/api/v1/activity-logs/delete':
            $controller = new App\Modules\Sistem\Controllers\ActivityLogModuleController();
            $controller->deleteLogsApi();
            break;

        case '/api/v1/queue/data':
            $controller = new App\Modules\Sistem\Controllers\QueueModuleController();
            $controller->fetchDataApi();
            break;

        case '/api/v1/queue/dispatch':
            $controller = new App\Modules\Sistem\Controllers\QueueModuleController();
            $controller->dispatchDemoJobApi();
            break;

        case '/api/v1/queue/retry':
            $controller = new App\Modules\Sistem\Controllers\QueueModuleController();
            $controller->retryJobApi();
            break;

        case '/api/v1/queue/delete':
            $controller = new App\Modules\Sistem\Controllers\QueueModuleController();
            $controller->deleteJobApi();
            break;

        case '/api/v1/queue/run-worker':
            $controller = new App\Modules\Sistem\Controllers\QueueModuleController();
            $controller->runWorkerApi();
            break;

        case '/api/v1/tenant/lookup':
            // API: Cari tenant berdasarkan NPSN
            $controller = new App\Modules\Core\Controllers\TenantModuleController();
            $controller->lookup();
            break;

        case '/api/v1/tenant/search':
            // API: Cari tenant/sekolah aktif
            $controller = new App\Modules\Core\Controllers\TenantModuleController();
            $controller->searchActiveTenants();
            break;

        case '/api/v1/tenant/check-npsn':
            // API: Cek keunikan NPSN secara real-time
            $controller = new App\Modules\Core\Controllers\TenantModuleController();
            $controller->checkUnique();
            break;

        case '/api/v1/tenant/update':
            // API: Update data profil sekolah (Tenant)
            $controller = new App\Modules\Core\Controllers\TenantModuleController();
            $controller->update();
            break;

        case '/super-admin/tenant-menus':
            // Halaman: Kelola Akses Menu Per Sekolah (Tenant)
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->index();
            break;

        case '/super-admin/tenants':
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->index();
            break;

        // ================================================================
        // ERROR MONITOR ROUTES — SUPER ADMIN ONLY
        // ================================================================
        case '/super-admin/error-monitor':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->index();
            break;

        case '/api/v1/error-monitor':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->fetchApi();
            break;

            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->fetchTenantMenus();
            break;

        case '/api/v1/super-admin/tenant-menus/save':
            // API: Simpan/update menu per-sekolah
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->saveTenantMenuAccess();
            break;

        case '/api/v1/super-admin/tenants':
            // API: Ambil daftar seluruh sekolah (tenants)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/super-admin/tenants/simpan':
            // API: Simpan (Tambah Baru / Update) Sekolah (SaaS Tenant)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/super-admin/tenants/hapus':
            // API: Hapus Sekolah (Soft Delete)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/super-admin/tenants/toggle-status':
            // API: Ubah Status Akses Tenant (active / inactive / suspended)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->toggleStatusApi();
            break;

        case '/pengguna':
            // Panggil PenggunaController - Halaman Sentral Pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->index();
            break;

        case '/api/v1/pengguna':
            // API: Ambil data terpaginasi pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/pengguna/tenants':
            // API: Ambil daftar tenant/sekolah untuk Super Admin
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/pengguna/kelas':
            // API: Ambil daftar kelas/rombel untuk dropdown filter
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getKelasApi();
            break;

        case '/api/v1/pengguna/simpan':
            // API: Simpan/Update data pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/pengguna/quick-add-siswa':
            // API: Registrasi Cepat Siswa
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->quickStoreSiswaApi();
            break;

        case '/api/v1/pengguna/hapus':
            // API: Soft Delete pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/pengguna/restore':
            // API: Restore pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->restoreApi();
            break;

        case '/api/v1/pengguna/toggle-status':
            // API: Toggle Status Aktif pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->toggleStatusApi();
            break;

        case '/api/v1/pengguna/aksi/kelas':
            // API: Ambil daftar kelas untuk dropdown panel Naikkan Kelas & Luluskan Siswa
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getKelasAksiApi();
            break;
        case '/super-admin/tenants_old':
            // Halaman: Kelola Sekolah (SaaS Tenant Management)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->index();
            break;

        // ================================================================
        // ERROR MONITOR ROUTES — SUPER ADMIN ONLY
        // ================================================================
        case '/super-admin/error-monitor':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->index();
            break;

        case '/api/v1/error-monitor':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/error-monitor/clear':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->clearAll();
            break;

        case '/api/v1/error-monitor/delete':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->deleteOne();
            break;

        case '/api/v1/error-monitor/log-client':
            $controller = new App\Modules\Core\Controllers\ErrorMonitorModuleController();
            $controller->logClientErrorApi();
            break;


        case '/api/v1/super-admin/tenant-menus/fetch':
            // API: Ambil menu & status centang per-sekolah
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->fetchTenantMenus();
            break;

        case '/api/v1/super-admin/tenant-menus/save':
            // API: Simpan/update menu per-sekolah
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->saveTenantMenuAccess();
            break;

        case '/api/v1/super-admin/tenants':
            // API: Ambil daftar seluruh sekolah (tenants)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/super-admin/tenants/simpan':
            // API: Simpan (Tambah Baru / Update) Sekolah (SaaS Tenant)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/super-admin/tenants/hapus':
            // API: Hapus Sekolah (Soft Delete)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/super-admin/tenants/toggle-status':
            // API: Ubah Status Akses Tenant (active / inactive / suspended)
            $controller = new App\Modules\Core\Controllers\TenantManagementModuleController();
            $controller->toggleStatusApi();
            break;

        case '/smk/pkl':
            $controller = new App\Modules\Smk\Controllers\SmkModuleController();
            $controller->indexView();
            break;

        case '/api/v1/smk/pkl':
            $controller = new App\Modules\Smk\Controllers\SmkModuleController();
            $controller->getPklApi();
            break;

        case '/pengguna':
            // Panggil PenggunaController - Halaman Sentral Pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->index();
            break;

        case '/api/v1/pengguna':
            // API: Ambil data terpaginasi pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/pengguna/export-excel':
            // API: Export CSV/Excel data pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->exportExcelApi();
            break;

        case '/api/v1/pengguna/tenants':
            // API: Ambil daftar tenant/sekolah untuk Super Admin
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/pengguna/kelas':
            // API: Ambil daftar kelas/rombel untuk dropdown filter
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getKelasApi();
            break;

        case '/api/v1/pengguna/simpan':
            // API: Simpan/Update data pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/pengguna/quick-add-siswa':
            // API: Registrasi Cepat Siswa
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->quickStoreSiswaApi();
            break;

        case '/api/v1/pengguna/hapus':
            // API: Soft Delete pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/pengguna/restore':
            // API: Restore pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->restoreApi();
            break;

        case '/api/v1/pengguna/toggle-status':
            // API: Toggle Status Aktif pengguna
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->toggleStatusApi();
            break;

        case '/api/v1/pengguna/aksi/kelas':
            // API: Ambil daftar kelas untuk dropdown panel Naikkan Kelas & Luluskan Siswa
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getKelasAksiApi();
            break;

        case '/api/v1/pengguna/tahun-ajaran':
            // API: Ambil daftar tahun ajaran untuk filter aksi
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->apiTahunAjaran();
            break;
        case '/api/v1/pengguna/aksi/siswa':
            // API: Ambil daftar siswa aktif berdasarkan kelas (checklist panel aksi)
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getSiswaUntukAksiApi();
            break;

        case '/api/v1/pengguna/aksi/naikkan-kelas':
            // API: Eksekusi kenaikan kelas massal
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->naikkanKelasApi();
            break;

        case '/api/v1/pengguna/aksi/tinggal-kelas':
            // API: Eksekusi tinggal kelas siswa massal
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->tinggalKelasApi();
            break;

        case '/api/v1/pengguna/aksi/luluskan':
            // API: Eksekusi kelulusan siswa massal
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->luluskanSiswaApi();
            break;

        case '/api/v1/auth/login':
        case '/admin/api/v1/auth/login':
            $controller = new App\Modules\Core\Controllers\AuthModuleController();
            $controller->loginAdminApi();
            break;

        case '/api/v1/auth/logout':
        case '/admin/api/v1/auth/logout':
        case '/logout':
        case '/admin/logout':
            $controller = new App\Modules\Core\Controllers\AuthModuleController();
            $controller->logout();
            break;

        case '/api/v1/pdss/simulasi/setting':
            $ctrl = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? $ctrl->apiToggleSimulasiSetting()
                : $ctrl->apiGetSimulasiSetting();
            break;

        case '/api/v1/pdss/simulasi/delete':
            (new App\Modules\Pdss\Controllers\PdssDetailModuleController())->apiDeleteSimulasi();
            break;

        case '/api/v1/pdss/simulasi/upload-bukti':
            (new App\Modules\Pdss\Controllers\PdssDetailModuleController())->apiUploadBuktiSimulasi();
            break;

        case '/api/v1/pdss/simulasi/export':
            (new App\Modules\Pdss\Controllers\PdssDetailModuleController())->apiExportSimulasi();
            break;

        case '/api/v1/pdss/simulasi':
            $ctrl = new App\Modules\Pdss\Controllers\PdssDetailModuleController();
            $_SERVER['REQUEST_METHOD'] === 'POST'
                ? $ctrl->apiSaveSimulasi()
                : $ctrl->apiGetSimulasi();
            break;

                case '/ppdb/verifikasi':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

                case '/ppdb/calon-siswa':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

                case '/ppdb/riwayat':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

                case '/akademik/jadwal':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

                case '/akademik/mapel':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

                case '/akademik/kelas':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

                case '/akademik/rapor':
            $controller = new App\Modules\Core\Controllers\UnderConstructionController();
            $controller->index();
            break;

        // MODUL MASTER DATA & KELEMBAGAAN
        case '/master-data':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->index();
            break;

        case '/sekolah/identitas':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->identitasView();
            break;

        case '/api/v1/kelembagaan':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/kelembagaan/options':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getOptionsApi();
            break;

        case '/api/v1/kelembagaan/simpan':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/kelembagaan/hapus':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->deleteApi();
            break;

        case '/api/v1/kelembagaan/restore':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->restoreApi();
            break;

        case '/api/v1/kelembagaan/toggle-status':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->toggleStatusApi();
            break;

        case '/api/v1/kelembagaan/tenants':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/riwayat-kepsek':
            $controller = new App\Modules\Sistem\Controllers\KelembagaanModuleController();
            $controller->getRiwayatKepsekApi();
            break;

        // MODUL KURIKULUM
        case '/api/v1/kurikulum':
            $controller = new App\Modules\Akademik\Controllers\KurikulumModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->store();
            } else {
                $controller->index();
            }
            break;

        case '/api/v1/kurikulum/mapel':
            $controller = new App\Modules\Akademik\Controllers\KurikulumModuleController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storeMapel();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $controller->deleteMapel();
            } else {
                http_response_code(405);
                echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
            }
            break;

        case '/api/v1/kurikulum/copy':
            $controller = new App\Modules\Akademik\Controllers\KurikulumModuleController();
            $controller->copyCurriculum();
            break;

        case '/api/v1/kurikulum/save':
            $controller = new App\Modules\Akademik\Controllers\KurikulumModuleController();
            $controller->store();
            break;

        // MODUL SUPER ADMIN
        case '/super-admin/tenant-menus':
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->tenantMenusView();
            break;

        case '/api/v1/super-admin/tenant-menus/fetch':
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->fetchTenantMenus();
            break;

        case '/api/v1/super-admin/tenant-menus/save':
            $controller = new App\Modules\Sistem\Controllers\SuperAdminModuleController();
            $controller->saveTenantMenuAccess();
            break;

        case '/super-admin/server-monitor':
            $controller = new App\Modules\Sistem\Controllers\ServerMonitorModuleController();
            $controller->index();
            break;

        case '/api/v1/super-admin/server-monitor/fetch':
            $controller = new App\Modules\Sistem\Controllers\ServerMonitorModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/super-admin/server-monitor/save-network':
            $controller = new App\Modules\Sistem\Controllers\ServerMonitorModuleController();
            $controller->saveNetworkConfig();
            break;

        case '/api/v1/super-admin/server-monitor/update-server':
            $controller = new App\Modules\Sistem\Controllers\ServerMonitorModuleController();
            $controller->updateServer();
            break;

        // MODUL PENGGUNA
        case '/pengguna':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->index();
            break;

        case '/api/v1/pengguna':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->fetchApi();
            break;

        case '/api/v1/pengguna/export-excel':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->exportExcelApi();
            break;

        case '/api/v1/pengguna/tenants':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/pengguna/kelas':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->getKelasApi();
            break;

        case '/api/v1/pengguna/simpan':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->storeApi();
            break;

        case '/api/v1/pengguna/quick-add-siswa':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->quickStoreSiswaApi();
            break;

        case '/api/v1/pengguna/hapus':
            $controller = new App\Modules\Sistem\Controllers\PenggunaModuleController();
            $controller->deleteApi();
            break;

        default:

            // Jika halaman tidak ditemukan, tampilkan 404
            http_response_code(404);
            if (str_starts_with($path, '/api/')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Endpoint tidak ditemukan']);
            } else {
                $controller = new App\Modules\Core\Controllers\UnderConstructionController();
                $controller->index();
            }
            break;
    }
} catch (\Throwable $e) {
    // Tangkap semua error PHP 8 & exception database, log ke DB, tampilkan pesan aman ke user
    App\Helpers\ErrorTracker::handleException($e);
    // handleException() sudah memanggil exit() di dalamnya — baris di bawah tidak akan dieksekusi
}
