<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Http\Controllers\TenantManagementController;
use Modules\Core\Http\Controllers\SekolahIdentitasController;
use Modules\Siswa\Http\Controllers\BukuIndukController;
use Modules\Siswa\Http\Controllers\PpdbController;
use Modules\Siswa\Http\Controllers\MutasiController;
use Modules\Siswa\Http\Controllers\PrestasiController;
use Modules\Akademik\Http\Controllers\AkademikMasterController;
use Modules\Akademik\Http\Controllers\PenilaianController;
use Modules\Keuangan\Http\Controllers\TagihanSiswaController;
use Modules\Keuangan\Http\Controllers\PembayaranKasirController;
use Modules\Keuangan\Http\Controllers\LaporanKeuanganController;
use Modules\Keuangan\Http\Controllers\PosTarifController;
use Modules\Bk\Http\Controllers\BkController;
use Modules\Pdss\Http\Controllers\PdssController;
use Modules\Perpustakaan\Http\Controllers\PerpustakaanController;
use Modules\Persuratan\Http\Controllers\PersuratanController;
use Modules\Sarpras\Http\Controllers\SarprasController;
use Modules\Smk\Http\Controllers\SmkController;
use Modules\Tracer\Http\Controllers\TracerController;
use Modules\Absensi\Http\Controllers\PresensiController;
use Modules\Kepegawaian\Http\Controllers\KepegawaianController;
use Modules\Cms\Http\Controllers\CmsController;
use Modules\Sistem\Http\Controllers\ActivityLogController;

/*
|--------------------------------------------------------------------------
| Web Routes (Central & Canonical Database Menu Routes)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/up', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()->toIso8601String()]);
});

// Authenticated Canonical Menu Route Aliases
Route::middleware(['auth', 'tenant.guard'])->group(function () {
    // 1. Data Pokok & Siswa
    Route::get('/pengguna', [UserController::class, 'index'])->name('menu.pengguna');
    Route::get('/master-data', [AkademikMasterController::class, 'index'])->name('menu.master-data');
    Route::get('/buku-induk', [BukuIndukController::class, 'index'])->name('menu.buku-induk');
    Route::get('/ppdb/verifikasi', [PpdbController::class, 'index'])->name('menu.ppdb.verifikasi');
    Route::get('/ppdb/calon-siswa', [PpdbController::class, 'index'])->name('menu.ppdb.calon-siswa');
    Route::get('/ppdb/riwayat', [PpdbController::class, 'index'])->name('menu.ppdb.riwayat');

    // 2. Sistem & Utilitas
    Route::get('/sekolah/identitas', [SekolahIdentitasController::class, 'show'])->name('menu.sekolah.identitas');
    Route::get('/konfigurasi/akses', [ActivityLogController::class, 'index'])->name('menu.konfigurasi.akses');
    Route::get('/utilitas/sesi-aktif', [ActivityLogController::class, 'index'])->name('menu.utilitas.sesi-aktif');
    Route::get('/utilitas/antrean', [ActivityLogController::class, 'index'])->name('menu.utilitas.antrean');
    Route::get('/super-admin/tenant-menus', [TenantManagementController::class, 'index'])->name('menu.super-admin.tenant-menus');
    Route::get('/super-admin/tenants', [TenantManagementController::class, 'index'])->name('menu.super-admin.tenants');
    Route::get('/utilitas/log-aktivitas', [ActivityLogController::class, 'index'])->name('menu.utilitas.log-aktivitas');
    Route::get('/super-admin/error-monitor', [ActivityLogController::class, 'index'])->name('menu.super-admin.error-monitor');
    Route::get('/super-admin/server-monitor', [ActivityLogController::class, 'index'])->name('menu.super-admin.server-monitor');
    Route::get('/utility/document-scanner', [SarprasController::class, 'index'])->name('menu.utility.scanner');

    // 3. Bimbingan Konseling & Layanan Khusus
    Route::get('/bk/layanan', [BkController::class, 'index'])->name('menu.bk.layanan');
    Route::get('/bk/kedisiplinan', [BkController::class, 'index'])->name('menu.bk.kedisiplinan');
    Route::get('/bk/akademik', [PdssController::class, 'index'])->name('menu.bk.akademik');
    Route::get('/bk/alumni', [TracerController::class, 'index'])->name('menu.bk.alumni');

    // 4. Informasi & Kesiswaan
    Route::get('/informasi/pengumuman', [CmsController::class, 'index'])->name('menu.informasi.pengumuman');
    Route::get('/informasi/agenda', [CmsController::class, 'index'])->name('menu.informasi.agenda');
    Route::get('/kesiswaan/ekskul', [PrestasiController::class, 'index'])->name('menu.kesiswaan.ekskul');

    // 5. Perpustakaan
    Route::get('/perpustakaan/katalog', [PerpustakaanController::class, 'index'])->name('menu.perpus.katalog');
    Route::get('/perpustakaan/sirkulasi', [PerpustakaanController::class, 'index'])->name('menu.perpus.sirkulasi');
    Route::get('/perpustakaan/anggota', [PerpustakaanController::class, 'index'])->name('menu.perpus.anggota');
    Route::get('/perpustakaan/opac', [PerpustakaanController::class, 'index'])->name('menu.perpus.opac');
    Route::get('/perpustakaan/riwayat-saya', [PerpustakaanController::class, 'index'])->name('menu.perpus.riwayat');

    // 6. Keuangan & Pembayaran
    Route::get('/keuangan/dashboard', [TagihanSiswaController::class, 'index'])->name('menu.keuangan.dashboard');
    Route::get('/keuangan/master', [PosTarifController::class, 'index'])->name('menu.keuangan.master');
    Route::get('/keuangan/tagihan-saya', [TagihanSiswaController::class, 'index'])->name('menu.keuangan.tagihan-saya');
    Route::get('/keuangan/audit-log', [LaporanKeuanganController::class, 'index'])->name('menu.keuangan.audit-log');

    // 7. Kurikulum & Akademik
    Route::get('/akademik/jadwal', [AkademikMasterController::class, 'index'])->name('menu.akademik.jadwal');

    // 8. Pembinaan & Bantuan
    Route::get('/pembinaan', [BkController::class, 'index'])->name('menu.pembinaan');
    Route::get('/bantuan', function () {
        return Inertia::render('Dashboard');
    })->name('menu.bantuan');
});
