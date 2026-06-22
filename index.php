<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/**
 * Front Controller & Router
 * Entry point untuk semua request aplikasi Dapodik & SPMB
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
App\Helpers\ErrorTracker::register();

// 3. Routing Sederhana
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Bersihkan path dari sub-folder XAMPP (case-insensitive, e.g. /sinta-saas/login -> /login)
$project_folder = '/SINTA-SaaS';
if (strncasecmp($path, $project_folder, strlen($project_folder)) === 0) {
    $path = substr($path, strlen($project_folder));
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

        case '/pengguna/download-excel':
            $controller = new App\Controllers\PenggunaController();
            $controller->downloadExcel();
            break;

        case '/dashboard':
            // Panggil DashboardController
            $controller = new App\Controllers\DashboardController();
            $controller->index();
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

        // ================================================================
        // TRACER STUDY ROUTES
        // ================================================================
        case '/tracer-study':
            // Halaman Tracer Study / Portofolio Alumni
            $controller = new App\Controllers\TracerController();
            $controller->index();
            break;

        case '/api/v1/tracer/kuliah':
            // API: Simpan Riwayat Kuliah (POST, hanya siswa Lulus & admin)
            $controller = new App\Controllers\TracerController();
            $controller->storeKuliah();
            break;

        case '/api/v1/tracer/pekerjaan':
            // API: Simpan Riwayat Pekerjaan (POST, hanya siswa Lulus & admin)
            $controller = new App\Controllers\TracerController();
            $controller->storePekerjaan();
            break;

        // ================================================================
        // BIMBINGAN KONSELING (BK) ROUTES
        // Role Guard: super_admin, operator_sekolah, guru_bk (enforced in BKController)
        // ================================================================
        case '/bk':
            // Halaman Utama Bimbingan Konseling (5-tab hub)
            $controller = new App\Controllers\BKController();
            $controller->index();
            break;

        case '/api/v1/bk/dashboard':
            // API: KPI Dashboard Monitoring BK (Tab 1)
            $controller = new App\Controllers\BKController();
            $controller->apiDashboard();
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

        case '/api/v1/bk/tracer':
            // API: Ringkasan Tracer Study alumni (Tab 3)
            $controller = new App\Controllers\BKController();
            $controller->apiTracerSummary();
            break;

        case '/api/v1/bk/pdss':
            // API: Daftar siswa eligible SNBP / PDSS (Tab 4)
            $controller = new App\Controllers\BKController();
            $controller->apiPdss();
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

        case '/utilitas/sesi-aktif':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->index();
            break;

        case '/api/v1/sessions/data':
            $controller = new App\Controllers\ActiveSessionController();
            $controller->fetchDataApi();
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
