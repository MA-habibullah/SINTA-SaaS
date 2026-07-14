<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load Composer Autoloader if available
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Front Controller & Router
 * Entry point untuk semua request aplikasi SINTA-SaaS
 */

// 1. Definisikan Autoloader Kelas PSR-4 Sederhana (Tanpa Composer)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Bukan namespace kita
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// 2. Mulai Session Aman
use App\Core\SessionManager;
SessionManager::start();

// Lacak Sesi Aktif Pengguna
App\Helpers\SessionTracker::track();

// ✅ Global Error Handler: Tangkap semua PHP/SQL error & simpan ke DB system_errors
App\Helpers\ErrorTracker::register(true);

// 3. Routing Sederhana
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Bersihkan path dari sub-folder XAMPP (case-insensitive, e.g. /sinta-saas/login -> /login)
$project_folder = '/SINTA-SaaS';
if (strncasecmp($path, $project_folder, strlen($project_folder)) === 0) {
    $path = substr($path, strlen($project_folder));
}

if (str_starts_with($path, '/api/')) {
    $logMsg = date('Y-m-d H:i:s') . " - " . $_SERVER['REQUEST_METHOD'] . " - " . $path . " - GET: " . json_encode($_GET) . " - POST: " . json_encode($_POST) . " - INPUT: " . file_get_contents('php://input') . "\n";
    @file_put_contents(__DIR__ . '/scratch/api_requests.log', $logMsg, FILE_APPEND);
}

// Standardisasi path kosong atau slash saja
if (empty($path) || $path === '/') {
    $path = '/login';
}

// 4. Map Halaman & Endpoint API (Dilindungi Try-Catch untuk Response API yang Konsisten)
try {
    // First Login Guard untuk Siswa
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['role_name'] ?? '') === 'siswa') {
        $isFirstLogin = $_SESSION['is_first_login'] ?? false;
        if ($isFirstLogin && !in_array($path, ['/siswa/ubah-password', '/siswa/logout', '/api/v1/siswa/ubah-password'])) {
            header('Location: /SINTA-SaaS/siswa/ubah-password');
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
                header('Location: /SINTA-SaaS/dashboard');
                exit;
            }
            $controller = new App\Controllers\AuthSiswaController();
            $controller->loginView();
            break;

        case '/siswa/login':
            header('Location: /SINTA-SaaS/login');
            exit;

        case '/admin':
            // Jika sudah login sebagai admin/super_admin, langsung lempar ke dashboard
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && ($_SESSION['role_name'] ?? '') !== 'siswa') {
                header('Location: /SINTA-SaaS/dashboard');
                exit;
            }
            $controller = new App\Controllers\AuthAdminController();
            $controller->loginView();
            break;

        case '/api/v1/siswa/login':
            $controller = new App\Controllers\AuthSiswaController();
            $controller->loginApi();
            break;

        case '/siswa/ubah-password':
            $controller = new App\Controllers\AuthSiswaController();
            $controller->changePasswordView();
            break;

        case '/api/v1/siswa/ubah-password':
            $controller = new App\Controllers\AuthSiswaController();
            $controller->changePasswordApi();
            break;

        case '/siswa/logout':
            $controller = new App\Controllers\AuthSiswaController();
            $controller->logout();
            break;

        case '/api/v1/siswa/import':
            $controller = new App\Controllers\ImportController();
            $controller->import();
            break;

        case '/api/v1/siswa/import/template':
            $controller = new App\Controllers\ImportController();
            $controller->downloadTemplate();
            break;

        case '/api/v1/siswa/bulk-photo':
            $controller = new App\Controllers\BulkPhotoController();
            $controller->uploadZip();
            break;

        case '/pengguna/download-excel':
            $controller = new App\Controllers\PenggunaController();
            $controller->downloadExcel();
            break;

        case '/kesiswaan/ekskul':
            $controller = new App\Controllers\EkskulController();
            $controller->index();
            break;
            
        case '/informasi/pengumuman':
            $controller = new App\Controllers\PengumumanController();
            $controller->index();
            break;
            
        case '/informasi/pengumuman/kategori/store':
            $controller = new App\Controllers\PengumumanController();
            $controller->storeKategori();
            break;
            
        case '/informasi/pengumuman/kategori/update':
            $controller = new App\Controllers\PengumumanController();
            $controller->updateKategori();
            break;
            
        case '/informasi/pengumuman/kategori/delete':
            $controller = new App\Controllers\PengumumanController();
            $controller->deleteKategori();
            break;
            
        case '/pengumuman/arsip':
            $controller = new App\Controllers\DashboardController();
            $controller->pengumumanArsip();
            break;
            
        case '/informasi/pengumuman/store':
            $controller = new App\Controllers\PengumumanController();
            $controller->store();
            break;
            
        case '/informasi/pengumuman/update':
            $controller = new App\Controllers\PengumumanController();
            $controller->update();
            break;
            
        case '/informasi/pengumuman/delete':
            $controller = new App\Controllers\PengumumanController();
            $controller->delete();
            break;
            
        case '/pembinaan':
            $controller = new App\Controllers\PembinaanController();
            $controller->index();
            break;
            
        case '/pembinaan/store':
            $controller = new App\Controllers\PembinaanController();
            $controller->store();
            break;
            
        case '/pembinaan/jadwal':
            $controller = new App\Controllers\PembinaanController();
            $controller->jadwalkan_sesi();
            break;
            
        case '/pembinaan/sesi':
            $controller = new App\Controllers\PembinaanController();
            $controller->sesi();
            break;
            
        case '/pembinaan/sesi/simpan':
            $controller = new App\Controllers\PembinaanController();
            $controller->simpan_sesi();
            break;
            
        case '/pembinaan/evaluasi':
            $controller = new App\Controllers\PembinaanController();
            $controller->evaluasi();
            break;
            
        case '/pembinaan/cetak':
            $controller = new App\Controllers\PembinaanController();
            $controller->cetak();
            break;

        case '/konseling':
            $controller = new App\Controllers\GuruKonselingController();
            $controller->index();
            break;
            
        case '/konseling/store':
            $controller = new App\Controllers\GuruKonselingController();
            $controller->store();
            break;

        case '/informasi/agenda':
            $controller = new App\Controllers\AgendaController();
            $controller->index();
            break;
            
        case '/informasi/agenda/store':
            $controller = new App\Controllers\AgendaController();
            $controller->store();
            break;
            
        case '/informasi/agenda/update':
            $controller = new App\Controllers\AgendaController();
            $controller->update();
            break;
            
        case '/informasi/agenda/delete':
            $controller = new App\Controllers\AgendaController();
            $controller->delete();
            break;

        case '/informasi/kategori-agenda/store':
            $controller = new App\Controllers\KategoriAgendaController();
            $controller->store();
            break;
            
        case '/informasi/kategori-agenda/update':
            $controller = new App\Controllers\KategoriAgendaController();
            $controller->update();
            break;
            
        case '/informasi/kategori-agenda/delete':
            $controller = new App\Controllers\KategoriAgendaController();
            $controller->delete();
            break;

        case '/api/v1/ekskul/tambah':
            $controller = new App\Controllers\EkskulController();
            $controller->store();
            break;

        case '/api/v1/ekskul/pembina/tambah':
            $controller = new App\Controllers\EkskulController();
            $controller->storePembina();
            break;

        case '/api/v1/ekskul/edit':
            $controller = new App\Controllers\EkskulController();
            $controller->update();
            break;

        case '/api/v1/ekskul/toggle-status':
            $controller = new App\Controllers\EkskulController();
            $controller->toggleStatus();
            break;

        case '/api/v1/ekskul/pembina/edit':
            $controller = new App\Controllers\EkskulController();
            $controller->updatePembina();
            break;

        case '/api/v1/ekskul/pembina/toggle-status':
            $controller = new App\Controllers\EkskulController();
            $controller->togglePembinaStatus();
            break;

        case '/api/v1/ekskul/anggota/tambah':
            $controller = new App\Controllers\EkskulController();
            $controller->addMembers();
            break;

        case '/api/v1/ekskul/anggota/hapus':
            $controller = new App\Controllers\EkskulController();
            $controller->removeMember();
            break;

        case '/api/v1/ekskul/nilai/simpan':
            $controller = new App\Controllers\EkskulController();
            $controller->saveGrades();
            break;

        case '/api/v1/ekskul/kunci/anggota':
            $controller = new App\Controllers\EkskulController();
            $controller->toggleLockAnggota();
            break;

        case '/api/v1/ekskul/kunci/nilai':
            $controller = new App\Controllers\EkskulController();
            $controller->toggleLockNilai();
            break;

        case '/api/v1/ekskul/nilai/export':
            $controller = new App\Controllers\EkskulController();
            $controller->exportGrades();
            break;

        case '/api/v1/ekskul/nilai/import':
            $controller = new App\Controllers\EkskulController();
            $controller->importGrades();
            break;

        case '/api/v1/ekskul/anggota/export':
            $controller = new App\Controllers\EkskulController();
            $controller->exportMembers();
            break;

        case '/dashboard':
            // Panggil DashboardController
            $controller = new App\Controllers\DashboardController();
            $controller->index();
            break;

        case '/buku-induk':
            $controller = new App\Controllers\BukuIndukController();
            $controller->index();
            break;

        case '/api/v1/nilai-rapor/delete-siswa':
            // API: Hapus nilai siswa dari tabel matriks nilai rapor
            $controller = new App\Controllers\NilaiRaporController();
            $controller->deleteSiswaGradesApi();
            break;

        case '/api/v1/buku-induk':
            $controller = new App\Controllers\BukuIndukController();
            $controller->fetchApi();
            break;

        case '/api/v1/buku-induk/detail':
            $controller = new App\Controllers\BukuIndukController();
            $controller->fetchDetailApi();
            break;

        case '/api/v1/buku_induk/matrix_cetak':
            $controller = new App\Controllers\BukuIndukController();
            $controller->fetchCetakMatrixApi();
            break;

        case '/api/v1/kunci_akademik':
            $controller = new App\Controllers\KunciAkademikController();
            $controller->getStatus();
            break;

        case '/api/v1/kunci_akademik/toggle':
            $controller = new App\Controllers\KunciAkademikController();
            $controller->toggle();
            break;

        case '/api/v1/kurikulum':
            $controller = new App\Controllers\KurikulumController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->store();
            } else {
                $controller->index();
            }
            break;

        case '/api/v1/kurikulum/copy':
            $controller = new App\Controllers\KurikulumController();
            $controller->copyCurriculum();
            break;

        case '/api/v1/nilai-rapor/grid':
            $controller = new App\Controllers\NilaiRaporController();
            $controller->getGrid();
            break;

        case '/api/v1/nilai-rapor/save':
            $controller = new App\Controllers\NilaiRaporController();
            $controller->save();
            break;

        case '/api/v1/nilai-rapor/export':
            $controller = new App\Controllers\NilaiRaporController();
            $controller->export();
            break;

        case '/api/v1/nilai-rapor/import':
            $controller = new App\Controllers\NilaiRaporController();
            $controller->import();
            break;

        case '/api/v1/riwayat-kepsek':
            $controller = new App\Controllers\BukuIndukController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storeRiwayatKepsek();
            } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
                $controller->deleteRiwayatKepsek();
            } else {
                $controller->getRiwayatKepsek();
            }
            break;

        case '/cetak-rapot':
            $controller = new App\Controllers\BukuIndukController();
            $controller->printRapot();
            break;

        case '/cetak-buku-induk':
            $controller = new App\Controllers\BukuIndukController();
            $controller->printBukuInduk();
            break;

        case '/cetak-rapot-semester':
            $controller = new App\Controllers\BukuIndukController();
            $controller->printRapotSemester();
            break;

        case '/cetak-transkrip-nilai':
            $controller = new App\Controllers\BukuIndukController();
            $controller->printTranskripNilai();
            break;

        case '/cetak-rapot-kelas':
            $controller = new App\Controllers\BukuIndukController();
            $controller->printRapotKelas();
            break;

        case '/siswa/tambah':
            // Panggil SiswaController - Render Form Tambah
            $controller = new App\Controllers\SiswaController();
            $controller->tambah();
            break;

        case '/siswa/simpan':
            // Panggil SiswaController - Proses Simpan Baru
            $controller = new App\Controllers\SiswaController();
            $controller->store();
            break;

        case '/siswa/save-draft':
            // Panggil SiswaController - Simpan Draft
            $controller = new App\Controllers\SiswaController();
            $controller->saveDraft();
            break;

        case '/siswa/edit':
            // Panggil SiswaController - Render Form Edit
            $controller = new App\Controllers\SiswaController();
            $controller->edit();
            break;

        case '/siswa/update':
            // Panggil SiswaController - Proses Update
            $controller = new App\Controllers\SiswaController();
            $controller->update();
            break;

        case '/siswa/hapus':
            // Panggil SiswaController - Proses Hapus (Soft Delete)
            $controller = new App\Controllers\SiswaController();
            $controller->delete();
            break;

        case '/api/v1/siswa/delete':
            // API: Hapus Siswa (Soft Delete) via AJAX
            $controller = new App\Controllers\BukuIndukController();
            $controller->deleteSiswaApi();
            break;

        // ================================================================
        // TRACER STUDY ROUTES
        // ================================================================
        case '/tracer-study':
            // Halaman Tracer Study / Portofolio Alumni
            $controller = new App\Controllers\TracerController();
            $controller->index();
            break;

        case '/api/v1/tracer/kuliah':
            $controller = new App\Controllers\TracerController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storeKuliah();
            } else {
                $controller->apiGetKuliah();
            }
            break;

        case '/api/v1/tracer/pekerjaan':
            $controller = new App\Controllers\TracerController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->storePekerjaan();
            } else {
                $controller->apiGetPekerjaan();
            }
            break;

        // ================================================================
        // BIMBINGAN KONSELING (BK) ROUTES
        // Role Guard: super_admin, operator_sekolah, guru_bk (enforced in BKController)
        // ================================================================
        case '/bk/layanan':
            $controller = new App\Controllers\BKController();
            $controller->layanan();
            break;

        case '/bk/akademik':
            $controller = new App\Controllers\BKController();
            $controller->akademik();
            break;

        case '/bk/alumni':
            $controller = new App\Controllers\BKController();
            $controller->alumni();
            break;

        case '/api/v1/bk/dashboard':
            // API: KPI Dashboard Monitoring BK (Tab 1)
            $controller = new App\Controllers\BKController();
            $controller->apiDashboard();
            break;

        case '/api/v1/bk/pelanggaran/dashboard':
            $controller = new App\Controllers\BKController();
            $controller->apiGetPelanggaranDashboard();
            break;

        case '/api/v1/bk/pelanggaran/master':
            $controller = new App\Controllers\BKController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStoreMasterPelanggaran();
            } else {
                $controller->apiGetMasterPelanggaran();
            }
            break;

        case '/api/v1/bk/pelanggaran/master/update':
            $controller = new App\Controllers\BKController();
            $controller->apiUpdateMasterPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/master/delete':
            $controller = new App\Controllers\BKController();
            $controller->apiDeleteMasterPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/catatan':
            $controller = new App\Controllers\BKController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStoreCatatanPelanggaran();
            } else {
                $controller->apiGetCatatanPelanggaran();
            }
            break;

        case '/api/v1/bk/pelanggaran/catatan/update':
            $controller = new App\Controllers\BKController();
            $controller->apiUpdateCatatanPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/catatan/delete':
            $controller = new App\Controllers\BKController();
            $controller->apiDeleteCatatanPelanggaran();
            break;

        case '/api/v1/bk/pelanggaran/sanksi':
            $controller = new App\Controllers\BKController();
            $controller->apiGetSanksiBuku();
            break;

        case '/api/v1/bk/pelanggaran/sanksi/detail':
            $controller = new App\Controllers\BKController();
            $controller->apiGetSanksiDetail();
            break;

        case '/api/v1/bk/pelanggaran/sanksi/tindak-lanjut':
            $controller = new App\Controllers\BKController();
            $controller->apiStoreTindakLanjutSanksi();
            break;

        case '/api/v1/bk/absensi-semester':
            // API: GET list absensi / POST simpan bulk absensi semester (Tab Kehadiran)
            $controller = new App\Controllers\BKController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveAbsensiSemesterBulk();
            } else {
                $controller->apiGetAbsensiSemester();
            }
            break;

        case '/api/v1/bk/absensi-semester/export':
            // API: GET download Excel (.xls) absensi kelas per semester
            $controller = new App\Controllers\BKController();
            $controller->apiExportAbsensiSemester();
            break;

        case '/api/v1/bk/absensi-semester/import':
            // API: POST upload file CSV absensi kelas per semester
            $controller = new App\Controllers\BKController();
            $controller->apiImportAbsensiSemester();
            break;

        case '/api/v1/bk/kasus':
            // API: GET list kasus / POST simpan kasus baru (Tab 5)
            $controller = new App\Controllers\BKController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStoreKasus();
            } else {
                $controller->apiListKasus();
            }
            break;

        case '/api/v1/bk/kasus/update-status':
            // API: POST update status riwayat kasus BK + log
            $controller = new App\Controllers\BKController();
            $controller->apiUpdateStatus();
            break;

        case '/api/v1/bk/kasus/logs':
            // API: GET list log riwayat penanganan kasus BK
            $controller = new App\Controllers\BKController();
            $controller->apiGetLogs();
            break;

        case '/api/v1/bk/prestasi':
            // API: GET list prestasi / POST simpan prestasi baru
            $controller = new App\Controllers\BKController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiStorePrestasi();
            } else {
                $controller->apiListPrestasi();
            }
            break;

        case '/api/v1/bk/prestasi/update':
            // API: POST update prestasi
            $controller = new App\Controllers\BKController();
            $controller->apiUpdatePrestasi();
            break;

        case '/api/v1/bk/prestasi/delete':
            // API: POST delete (soft-delete) prestasi
            $controller = new App\Controllers\BKController();
            $controller->apiDeletePrestasi();
            break;

        case '/api/v1/bk/guru':
            // API: GET list guru pendamping
            $controller = new App\Controllers\BKController();
            $controller->apiGetGuruList();
            break;

        case '/api/v1/bk/tracer':
            // API: Ringkasan Tracer Study alumni (Tab 3)
            $controller = new App\Controllers\BKController();
            $controller->apiTracerSummary();
            break;

        case '/bk/kesiapan-pdss':
            header('Location: /SINTA-SaaS/pdss/kesiapan');
            exit;

        case '/pdss/kesiapan':
            $controller = new App\Controllers\PDSSController();
            $controller->index();
            break;

        case '/api/v1/pdss/kesiapan':
            $controller = new App\Controllers\PDSSController();
            $controller->apiGetKesiapan();
            break;

        case '/api/v1/pdss/config-mapel':
            $controller = new App\Controllers\PDSSController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSavePdssMapels();
            } else {
                $controller->apiGetPdssMapels();
            }
            break;

        case '/api/v1/pdss/manual-eligible':
            $controller = new App\Controllers\PDSSController();
            $controller->apiSaveManualEligible();
            break;

        case '/api/v1/pdss/lock':
            $controller = new App\Controllers\PDSSController();
            $controller->apiToggleLock();
            break;

        case '/api/v1/pdss/student-grades':
            $controller = new App\Controllers\PDSSController();
            $controller->apiGetStudentGrades();
            break;

        case '/api/v1/pdss/download-leger':
            $controller = new App\Controllers\PDSSController();
            $controller->apiDownloadLeger();
            break;

        case '/api/v1/pdss/students/search':
            $controller = new App\Controllers\PDSSController();
            $controller->apiSearchStudents();
            break;

        case '/api/v1/pdss/alumni-tracks':
            $controller = new App\Controllers\PDSSController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveAlumniTrack();
            } else {
                $controller->apiGetAlumniTracks();
            }
            break;

        case '/api/v1/pdss/alumni-tracks/delete':
            $controller = new App\Controllers\PDSSController();
            $controller->apiDeleteAlumniTrack();
            break;

        case '/api/v1/pdss/target-kampus':
            $controller = new App\Controllers\PDSSController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveTargetKampus();
            } else {
                $controller->apiGetTargetKampus();
            }
            break;

        case '/api/v1/pdss/target-kampus/delete':
            $controller = new App\Controllers\PDSSController();
            $controller->apiDeleteTargetKampus();
            break;

        // MASTER KAMPUS & PRODI ROUTES
        case '/api/v1/kampus/template':
            $controller = new App\Controllers\KampusController();
            $controller->apiDownloadTemplate();
            break;

        case '/api/v1/kampus/import':
            $controller = new App\Controllers\KampusController();
            $controller->apiImportExcel();
            break;

        case '/api/v1/kampus':
            $controller = new App\Controllers\KampusController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveKampus();
            } else {
                $controller->apiGetKampus();
            }
            break;

        case '/api/v1/kampus/delete':
            $controller = new App\Controllers\KampusController();
            $controller->apiDeleteKampus();
            break;

        case '/api/v1/kampus/prodi':
            $controller = new App\Controllers\KampusController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveProdi();
            } else {
                $controller->apiGetProdi();
            }
            break;

        case '/api/v1/kampus/prodi/delete':
            $controller = new App\Controllers\KampusController();
            $controller->apiDeleteProdi();
            break;

        case '/api/v1/kampus/prodi/riwayat':
            $controller = new App\Controllers\KampusController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveRiwayat();
            } else {
                $controller->apiGetRiwayat();
            }
            break;

        case '/api/v1/kampus/prodi/riwayat/delete':
            $controller = new App\Controllers\KampusController();
            $controller->apiDeleteRiwayat();
            break;

        case '/api/v1/kampus/jalur':
            $controller = new App\Controllers\KampusController();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $controller->apiSaveJalurMasuk();
            } else {
                $controller->apiGetJalurMasuk();
            }
            break;

        case '/api/v1/kampus/jalur/delete':
            $controller = new App\Controllers\KampusController();
            $controller->apiDeleteJalurMasuk();
            break;

        case '/api/v1/kampus/all-prodi':
            $controller = new App\Controllers\KampusController();
            $controller->apiGetAllKampusProdi();
            break;

        case '/api/v1/kampus/flat-list':
            $controller = new App\Controllers\KampusController();
            $controller->apiGetMasterKampusProdiFlat();
            break;

        case '/api/v1/kampus/export-daya-tampung':
            $controller = new App\Controllers\KampusController();
            $controller->apiExportDayaTampung();
            break;

        case '/api/v1/kampus/import-daya-tampung':
            $controller = new App\Controllers\KampusController();
            $controller->apiImportDayaTampung();
            break;

        case '/api/v1/kampus/bulk-delete-riwayat':
            $controller = new App\Controllers\KampusController();
            $controller->apiBulkDeleteRiwayat();
            break;

        case '/api/v1/kampus/export-kampus-prodi':
            $controller = new App\Controllers\KampusController();
            $controller->apiExportKampusProdi();
            break;

        case '/api/v1/kampus/import-kampus-prodi':
            $controller = new App\Controllers\KampusController();
            $controller->apiImportKampusProdi();
            break;


        case '/api/v1/pdss/target-kampus/seed':
            $controller = new App\Controllers\PDSSController();
            $controller->apiSeedTargetKampus();
            break;

        case '/api/v1/bk/pdss':
            // API: Daftar siswa eligible SNBP / PDSS (Tab 4) - Deprecated / Scoped to PDSS Module
            $controller = new App\Controllers\PDSSController();
            $controller->apiGetKesiapan();
            break;

        case '/api/v1/bk/penjurusan':
            // API: GET list pilihan penjurusan (Tab 2)
            $controller = new App\Controllers\BKController();
            $controller->apiListPenjurusan();
            break;

        case '/api/v1/bk/penjurusan/verifikasi':
            // API: POST verifikasi atau tolak pilihan penjurusan (Tab 2)
            $controller = new App\Controllers\BKController();
            $controller->apiVerifikasiPenjurusan();
            break;

        case '/api/v1/bk/penjurusan/override':
            // API: POST override jurusan siswa dengan ACID transaction (Tab 2)
            $controller = new App\Controllers\BKController();
            $controller->apiOverridePenjurusan();
            break;

        case '/api/v1/bk/penjurusan/kunci':
            // API: POST toggle kunci pilihan penjurusan (Tab 2)
            $controller = new App\Controllers\BKController();
            $controller->apiToggleKunci();
            break;

        case '/api/v1/bk/siswa':
            // API: GET search siswa dengan kelas, NIS, NISN (untuk Rekam Kasus Tab 5)
            $controller = new App\Controllers\BKController();
            $controller->apiSiswaSearch();
            break;

        case '/api/v1/bk/kelas':
            // API: GET daftar kelas aktif untuk filter Rekam Kasus (Tab 5)
            $controller = new App\Controllers\BKController();
            $controller->apiKelasList();
            break;

        case '/konfigurasi/akses':
            // Panggil AksesController - Tampilkan Matriks Akses
            $controller = new App\Controllers\AksesController();
            $controller->index();
            break;

        case '/konfigurasi/akses/simpan':
            // Panggil AksesController - Proses Simpan Matriks Akses
            $controller = new App\Controllers\AksesController();
            $controller->store();
            break;

        case '/api/v1/akses/fetch':
            // AJAX: Ambil access map untuk tenant tertentu (Super Admin)
            $controller = new App\Controllers\AksesController();
            $controller->fetchAccessMap();
            break;

        case '/utilitas/log-aktivitas':
            $controller = new App\Controllers\ActivityLogController();
            $controller->index();
            break;

        case '/api/v1/activity-logs/filters':
            $controller = new App\Controllers\ActivityLogController();
            $controller->fetchFiltersApi();
            break;

        case '/api/v1/activity-logs':
            $controller = new App\Controllers\ActivityLogController();
            $controller->fetchApi();
            break;

        case '/api/v1/activity-logs/delete':
            $controller = new App\Controllers\ActivityLogController();
            $controller->deleteLogsApi();
            break;

        case '/utilitas/sesi-aktif':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->index();
            break;

        case '/api/v1/sessions/data':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->fetchDataApi();
            break;

        case '/api/v1/sessions/audit':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->fetchAuditApi();
            break;

        case '/api/v1/sessions/audit/retention':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->deleteAuditRetentionApi();
            break;

        case '/api/v1/sessions/retention':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->deleteRetentionApi();
            break;

        case '/master-data':
            // Panggil KelembagaanController - Halaman Sentral Kelembagaan
            $controller = new App\Controllers\KelembagaanController();
            $controller->index();
            break;

        case '/api/v1/kelembagaan':
            // API: Ambil data terpaginasi
            $controller = new App\Controllers\KelembagaanController();
            $controller->fetchApi();
            break;

        case '/api/v1/kelembagaan/options':
            // API: Ambil data opsi relasi
            $controller = new App\Controllers\KelembagaanController();
            $controller->getOptionsApi();
            break;

        case '/api/v1/kelembagaan/tenants':
            // API: Ambil daftar tenant/sekolah untuk Super Admin
            $controller = new App\Controllers\KelembagaanController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/kelembagaan/simpan':
            // API: Simpan/Update data
            $controller = new App\Controllers\KelembagaanController();
            $controller->storeApi();
            break;

        case '/api/v1/kelembagaan/hapus':
            // API: Soft Delete
            $controller = new App\Controllers\KelembagaanController();
            $controller->deleteApi();
            break;

        case '/api/v1/kelembagaan/restore':
            // API: Restore
            $controller = new App\Controllers\KelembagaanController();
            $controller->restoreApi();
            break;

        case '/api/v1/kelembagaan/toggle-status':
            // API: Toggle Status Aktif
            $controller = new App\Controllers\KelembagaanController();
            $controller->toggleStatusApi();
            break;

        case '/sekolah/identitas':
            $controller = new App\Controllers\SekolahController();
            $controller->showProfile();
            break;

        case '/api/v1/sekolah/update':
            $controller = new App\Controllers\SekolahController();
            $controller->updateProfile();
            break;

        case '/utilitas/antrean':
            $controller = new App\Controllers\QueueController();
            $controller->index();
            break;

        case '/api/v1/queue/data':
            $controller = new App\Controllers\QueueController();
            $controller->fetchDataApi();
            break;

        case '/api/v1/queue/dispatch':
            $controller = new App\Controllers\QueueController();
            $controller->dispatchDemoJobApi();
            break;

        case '/api/v1/queue/retry':
            $controller = new App\Controllers\QueueController();
            $controller->retryJobApi();
            break;

        case '/api/v1/queue/delete':
            $controller = new App\Controllers\QueueController();
            $controller->deleteJobApi();
            break;

        case '/api/v1/queue/run-worker':
            $controller = new App\Controllers\QueueController();
            $controller->runWorkerApi();
            break;

        case '/api/v1/tenant/lookup':
            // API: Cari tenant berdasarkan NPSN
            $controller = new App\Controllers\TenantController();
            $controller->lookup();
            break;

        case '/api/v1/tenant/search':
            // API: Cari tenant/sekolah aktif
            $controller = new App\Controllers\TenantController();
            $controller->searchActiveTenants();
            break;

        case '/api/v1/tenant/check-npsn':
            // API: Cek keunikan NPSN secara real-time
            $controller = new App\Controllers\TenantController();
            $controller->checkUnique();
            break;

        case '/api/v1/tenant/update':
            // API: Update data profil sekolah (Tenant)
            $controller = new App\Controllers\TenantController();
            $controller->update();
            break;

        case '/super-admin/tenant-menus':
            // Halaman: Kelola Akses Menu Per Sekolah (Tenant)
            $controller = new App\Controllers\SuperAdminController();
            $controller->index();
            break;

        case '/super-admin/tenants':
            // Halaman: Kelola Sekolah (SaaS Tenant Management)
            $controller = new App\Controllers\TenantManagementController();
            $controller->index();
            break;

        // ================================================================
        // ERROR MONITOR ROUTES — SUPER ADMIN ONLY
        // ================================================================
        case '/super-admin/error-monitor':
            $controller = new App\Controllers\ErrorMonitorController();
            $controller->index();
            break;

        case '/api/v1/error-monitor':
            $controller = new App\Controllers\ErrorMonitorController();
            $controller->fetchApi();
            break;

        case '/api/v1/error-monitor/clear':
            $controller = new App\Controllers\ErrorMonitorController();
            $controller->clearAll();
            break;

        case '/api/v1/error-monitor/delete':
            $controller = new App\Controllers\ErrorMonitorController();
            $controller->deleteOne();
            break;

        case '/api/v1/error-monitor/log-client':
            $controller = new App\Controllers\ErrorMonitorController();
            $controller->logClientErrorApi();
            break;

        // ================================================================
        // SERVER MONITOR ROUTES — SUPER ADMIN ONLY
        // ================================================================
        case '/super-admin/server-monitor':
            // Halaman: Server & Tenant Resource Monitor
            $controller = new App\Controllers\ServerMonitorController();
            $controller->index();
            break;

        case '/api/v1/super-admin/server-monitor/fetch':
            // API: Ambil metrik global server & resource per-tenant
            $controller = new App\Controllers\ServerMonitorController();
            $controller->fetchApi();
            break;

        case '/api/v1/super-admin/server-monitor/save-network':
            // API: Simpan konfigurasi IP Address (Netplan)
            $controller = new App\Controllers\ServerMonitorController();
            $controller->saveNetworkConfig();
            break;

        case '/api/v1/super-admin/server-monitor/update-server':
            // API: Jalankan deploy.sh
            $controller = new App\Controllers\ServerMonitorController();
            $controller->updateServer();
            break;


        case '/api/v1/super-admin/tenant-menus/fetch':
            // API: Ambil menu & status centang per-sekolah
            $controller = new App\Controllers\SuperAdminController();
            $controller->fetchTenantMenus();
            break;

        case '/api/v1/super-admin/tenant-menus/save':
            // API: Simpan/update menu per-sekolah
            $controller = new App\Controllers\SuperAdminController();
            $controller->saveTenantMenuAccess();
            break;

        case '/api/v1/super-admin/tenants':
            // API: Ambil daftar seluruh sekolah (tenants)
            $controller = new App\Controllers\TenantManagementController();
            $controller->fetchApi();
            break;

        case '/api/v1/super-admin/tenants/simpan':
            // API: Simpan (Tambah Baru / Update) Sekolah (SaaS Tenant)
            $controller = new App\Controllers\TenantManagementController();
            $controller->storeApi();
            break;

        case '/api/v1/super-admin/tenants/hapus':
            // API: Hapus Sekolah (Soft Delete)
            $controller = new App\Controllers\TenantManagementController();
            $controller->deleteApi();
            break;

        case '/api/v1/super-admin/tenants/toggle-status':
            // API: Ubah Status Akses Tenant (active / inactive / suspended)
            $controller = new App\Controllers\TenantManagementController();
            $controller->toggleStatusApi();
            break;

        case '/pengguna':
            // Panggil PenggunaController - Halaman Sentral Pengguna
            $controller = new App\Controllers\PenggunaController();
            $controller->index();
            break;

        case '/api/v1/pengguna':
            // API: Ambil data terpaginasi pengguna
            $controller = new App\Controllers\PenggunaController();
            $controller->fetchApi();
            break;

        case '/api/v1/pengguna/tenants':
            // API: Ambil daftar tenant/sekolah untuk Super Admin
            $controller = new App\Controllers\PenggunaController();
            $controller->getTenantsApi();
            break;

        case '/api/v1/pengguna/kelas':
            // API: Ambil daftar kelas/rombel untuk dropdown filter
            $controller = new App\Controllers\PenggunaController();
            $controller->getKelasApi();
            break;

        case '/api/v1/pengguna/simpan':
            // API: Simpan/Update data pengguna
            $controller = new App\Controllers\PenggunaController();
            $controller->storeApi();
            break;

        case '/api/v1/pengguna/quick-add-siswa':
            // API: Registrasi Cepat Siswa
            $controller = new App\Controllers\PenggunaController();
            $controller->quickStoreSiswaApi();
            break;

        case '/api/v1/pengguna/hapus':
            // API: Soft Delete pengguna
            $controller = new App\Controllers\PenggunaController();
            $controller->deleteApi();
            break;

        case '/api/v1/pengguna/restore':
            // API: Restore pengguna
            $controller = new App\Controllers\PenggunaController();
            $controller->restoreApi();
            break;

        case '/api/v1/pengguna/toggle-status':
            // API: Toggle Status Aktif pengguna
            $controller = new App\Controllers\PenggunaController();
            $controller->toggleStatusApi();
            break;

        case '/api/v1/pengguna/aksi/kelas':
            // API: Ambil daftar kelas untuk dropdown panel Naikkan Kelas & Luluskan Siswa
            $controller = new App\Controllers\PenggunaController();
            $controller->getKelasAksiApi();
            break;

        case '/api/v1/pengguna/tahun-ajaran':
            // API: Ambil daftar tahun ajaran untuk filter aksi
            $controller = new App\Controllers\PenggunaController();
            $controller->apiTahunAjaran();
            break;

        case '/api/v1/pengguna/aksi/siswa':
            // API: Ambil daftar siswa aktif berdasarkan kelas (checklist panel aksi)
            $controller = new App\Controllers\PenggunaController();
            $controller->getSiswaUntukAksiApi();
            break;

        case '/api/v1/pengguna/aksi/naikkan-kelas':
            // API: Eksekusi kenaikan kelas massal
            $controller = new App\Controllers\PenggunaController();
            $controller->naikkanKelasApi();
            break;

        case '/api/v1/pengguna/aksi/tinggal-kelas':
            // API: Eksekusi tinggal kelas siswa massal
            $controller = new App\Controllers\PenggunaController();
            $controller->tinggalKelasApi();
            break;

        case '/api/v1/pengguna/aksi/luluskan':
            // API: Eksekusi kelulusan siswa massal
            $controller = new App\Controllers\PenggunaController();
            $controller->luluskanSiswaApi();
            break;

        case '/api/v1/auth/login':
            // Panggil API Login Admin
            $controller = new App\Controllers\AuthAdminController();
            $controller->login();
            break;

        case '/api/v1/auth/logout':
            // Panggil API Logout Admin
            $controller = new App\Controllers\AuthAdminController();
            $controller->logout();
            break;

        default:
            // Jika halaman tidak ditemukan, tampilkan 404
            http_response_code(404);
            if (str_starts_with($path, '/api/')) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'Endpoint tidak ditemukan']);
            } else {
                echo "<h1>404 Halaman Tidak Ditemukan</h1>";
                echo "<p>Halaman yang Anda cari tidak ada pada server ini.</p>";
            }
            break;
    }
} catch (\Throwable $e) {
    // Tangkap semua error PHP 8 & exception database, log ke DB, tampilkan pesan aman ke user
    App\Helpers\ErrorTracker::handleException($e);
    // handleException() sudah memanggil exit() di dalamnya — baris di bawah tidak akan dieksekusi
}
