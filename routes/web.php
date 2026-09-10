<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Http\Controllers\TenantManagementController;
use Modules\Core\Http\Controllers\TenantMenuController;
use Modules\Core\Http\Controllers\SekolahIdentitasController;
use Modules\Core\Http\Controllers\KonfigurasiAksesController;
use Modules\Siswa\Http\Controllers\BukuIndukController;
use Modules\Siswa\Http\Controllers\PpdbController;
use Modules\Siswa\Http\Controllers\MutasiController;
use Modules\Siswa\Http\Controllers\PrestasiController;
use Modules\Akademik\Http\Controllers\AkademikMasterController;
use Modules\Akademik\Http\Controllers\PenilaianController;
use Modules\Akademik\Http\Controllers\RaporController;
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
use Modules\Sistem\Http\Controllers\ActiveSessionController;
use Modules\Sistem\Http\Controllers\QueueController;

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
    // 1. Data Pokok & Siswa (Manajemen Pengguna Sentral)
    Route::get('/pengguna', [UserController::class, 'index'])->name('menu.pengguna');
    Route::post('/pengguna', [UserController::class, 'store'])->name('menu.pengguna.store');
    Route::post('/pengguna/quick-add', [UserController::class, 'quickAddSiswa'])->name('menu.pengguna.quick-add');
    Route::get('/pengguna/siswa-by-kelas', [UserController::class, 'getSiswaByKelas'])->name('menu.pengguna.siswa-by-kelas');
    Route::get('/pengguna/riwayat-siswa/{id}', [UserController::class, 'getRiwayatSiswa'])->name('menu.pengguna.riwayat-siswa');
    Route::post('/pengguna/promote', [UserController::class, 'promoteKelas'])->name('menu.pengguna.promote');
    Route::get('/pengguna/export-excel', [UserController::class, 'exportExcel'])->name('menu.pengguna.export-excel');
    Route::post('/pengguna/upload-bulk-photos', [UserController::class, 'uploadBulkPhotos'])->name('menu.pengguna.upload-bulk-photos');
    Route::get('/cetak-rapot', [RaporController::class, 'previewHtmlByQuery'])->name('cetak.rapot');
    Route::get('/cetak-rapot-kelas', [RaporController::class, 'previewHtmlKelas'])->name('cetak.rapot.kelas');
    Route::put('/pengguna/{id}', [UserController::class, 'update'])->name('menu.pengguna.update');
    Route::delete('/pengguna/{id}', [UserController::class, 'destroy'])->name('menu.pengguna.destroy');
    Route::post('/pengguna/restore/{id}', [UserController::class, 'restore'])->name('menu.pengguna.restore');
    
    // Rute Lengkap Form Siswa (Edit & Tambah)
    Route::get('/siswa/tambah', [BukuIndukController::class, 'create'])->name('siswa.tambah');
    Route::get('/siswa/create', [BukuIndukController::class, 'create'])->name('siswa.create');
    Route::get('/siswa/edit', [BukuIndukController::class, 'edit'])->name('siswa.edit.query');
    Route::get('/siswa/{id}/edit', [BukuIndukController::class, 'edit'])->name('siswa.edit');
    Route::post('/siswa', [BukuIndukController::class, 'store'])->name('siswa.store');
    Route::match(['post', 'put', 'patch'], '/siswa/{id}', [BukuIndukController::class, 'update'])->name('siswa.update');
    Route::post('/siswa/update', [BukuIndukController::class, 'update'])->name('siswa.update.post');
    Route::delete('/siswa/{id}', [BukuIndukController::class, 'destroy'])->name('siswa.destroy');

    // API Wilayah Indonesia untuk Dropdown Bertingkat
    Route::get('/wilayah/provinsi', [BukuIndukController::class, 'getProvinsi'])->name('wilayah.provinsi');
    Route::get('/wilayah/kota/{id_provinsi}', [BukuIndukController::class, 'getKota'])->name('wilayah.kota');
    Route::get('/wilayah/kecamatan/{id_kota}', [BukuIndukController::class, 'getKecamatan'])->name('wilayah.kecamatan');
    Route::get('/wilayah/kelurahan/{id_kecamatan}', [BukuIndukController::class, 'getKelurahan'])->name('wilayah.kelurahan');
    Route::get('/wilayah/semua-kota', [BukuIndukController::class, 'getAllKota'])->name('wilayah.semua-kota');

    Route::get('/master-data', [AkademikMasterController::class, 'index'])->name('menu.master-data');
    Route::post('/master-data/store', [AkademikMasterController::class, 'store'])->name('master.store');
    Route::put('/master-data/{id}', [AkademikMasterController::class, 'update'])->name('master.update');
    Route::post('/master-data/update/{id}', [AkademikMasterController::class, 'update'])->name('master.update.post');
    Route::delete('/master-data/{id}', [AkademikMasterController::class, 'destroy'])->name('master.destroy');
    Route::post('/master-data/destroy/{id}', [AkademikMasterController::class, 'destroy'])->name('master.destroy.post');
    Route::post('/master-data/restore/{id}', [AkademikMasterController::class, 'restore'])->name('master.restore');
    Route::post('/master-data/toggle-status/{id}', [AkademikMasterController::class, 'toggleStatus'])->name('master.toggle-status');
    Route::get('/master-data/options', [AkademikMasterController::class, 'options'])->name('master.options');
    // Buku Induk Sentral & API Actions
    Route::get('/buku-induk', [BukuIndukController::class, 'index'])->name('menu.buku-induk');
    Route::get('/buku-induk/cetak/{id?}', [BukuIndukController::class, 'cetakLembarBukuInduk'])->name('buku-induk.cetak');
    Route::get('/buku-induk/api/detail/{id}', [BukuIndukController::class, 'fetchDetailApi'])->name('buku-induk.detail');
    Route::post('/buku-induk/api/beasiswa', [BukuIndukController::class, 'storeBeasiswa'])->name('buku-induk.beasiswa.store');
    Route::delete('/buku-induk/api/beasiswa/{id}', [BukuIndukController::class, 'destroyBeasiswa'])->name('buku-induk.beasiswa.destroy');
    Route::get('/buku-induk/api/kurikulum', [BukuIndukController::class, 'loadKurikulum'])->name('buku-induk.kurikulum.load');
    Route::post('/buku-induk/api/kurikulum', [BukuIndukController::class, 'saveKurikulum'])->name('buku-induk.kurikulum.save');
    Route::post('/buku-induk/api/kurikulum/copy', [BukuIndukController::class, 'copyKurikulum'])->name('buku-induk.kurikulum.copy');
    Route::post('/buku-induk/api/toggle-lock', [BukuIndukController::class, 'toggleLock'])->name('buku-induk.toggle-lock');
    Route::get('/buku-induk/api/nilai-rapor', [BukuIndukController::class, 'loadNilaiRapor'])->name('buku-induk.nilai-rapor.load');
    Route::post('/buku-induk/api/nilai-rapor', [BukuIndukController::class, 'saveNilaiRapor'])->name('buku-induk.nilai-rapor.save');
    Route::delete('/buku-induk/api/nilai-rapor', [BukuIndukController::class, 'deleteNilaiRapor'])->name('buku-induk.nilai-rapor.delete');
    Route::get('/buku-induk/api/nilai-rapor/export', [BukuIndukController::class, 'exportNilaiExcel'])->name('buku-induk.nilai-rapor.export');
    Route::post('/buku-induk/api/nilai-rapor/import', [BukuIndukController::class, 'importNilaiExcel'])->name('buku-induk.nilai-rapor.import');
    Route::get('/buku-induk/api/matrix-cetak', [BukuIndukController::class, 'loadMatrixCetak'])->name('buku-induk.matrix-cetak.load');
    Route::get('/buku-induk/api/riwayat-kepsek', [BukuIndukController::class, 'getRiwayatKepsek'])->name('buku-induk.riwayat-kepsek.load');
    Route::post('/buku-induk/api/riwayat-kepsek', [BukuIndukController::class, 'storeRiwayatKepsek'])->name('buku-induk.riwayat-kepsek.save');
    Route::delete('/buku-induk/api/riwayat-kepsek/{id}', [BukuIndukController::class, 'destroyRiwayatKepsek'])->name('buku-induk.riwayat-kepsek.delete');
    Route::get('/buku-induk/api/alumni', [BukuIndukController::class, 'loadAlumni'])->name('buku-induk.alumni.load');
    Route::post('/buku-induk/api/alumni/upload', [BukuIndukController::class, 'uploadAlumniDoc'])->name('buku-induk.alumni.upload');
    Route::delete('/buku-induk/api/alumni/{id}', [BukuIndukController::class, 'destroyAlumniDoc'])->name('buku-induk.alumni.delete');
    Route::get('/buku-induk/export-excel', [BukuIndukController::class, 'exportExcel'])->name('buku-induk.export-excel');
    Route::get('/buku-induk/export-pdss', [BukuIndukController::class, 'exportPdssExcel'])->name('buku-induk.export-pdss');

    Route::get('/ppdb/verifikasi', [PpdbController::class, 'index'])->name('menu.ppdb.verifikasi');
    Route::get('/ppdb/calon-siswa', [PpdbController::class, 'index'])->name('menu.ppdb.calon-siswa');
    Route::get('/ppdb/riwayat', [PpdbController::class, 'index'])->name('menu.ppdb.riwayat');

    // 2. Sistem & Utilitas
    Route::get('/sekolah/identitas', [SekolahIdentitasController::class, 'show'])->name('menu.sekolah.identitas');
    Route::match(['post', 'put'], '/sekolah/identitas', [SekolahIdentitasController::class, 'update'])->name('menu.sekolah.identitas.update');
    Route::get('/konfigurasi/akses', [KonfigurasiAksesController::class, 'index'])->name('menu.konfigurasi.akses');
    Route::post('/konfigurasi/akses', [KonfigurasiAksesController::class, 'store'])->name('menu.konfigurasi.akses.store');
    Route::get('/konfigurasi/akses/fetch', [KonfigurasiAksesController::class, 'fetch'])->name('menu.konfigurasi.akses.fetch');
    Route::get('/utilitas/sesi-aktif', [ActiveSessionController::class, 'index'])->name('menu.utilitas.sesi-aktif');
    Route::get('/utilitas/sesi-aktif/data', [ActiveSessionController::class, 'fetchData'])->name('menu.utilitas.sesi-aktif.data');
    Route::get('/utilitas/sesi-aktif/audit', [ActiveSessionController::class, 'fetchAudit'])->name('menu.utilitas.sesi-aktif.audit');
    Route::post('/utilitas/sesi-aktif/retention', [ActiveSessionController::class, 'deleteRetention'])->name('menu.utilitas.sesi-aktif.retention');
    Route::post('/utilitas/sesi-aktif/audit/retention', [ActiveSessionController::class, 'deleteAuditRetention'])->name('menu.utilitas.sesi-aktif.audit.retention');
    Route::get('/utilitas/antrean', [QueueController::class, 'index'])->name('menu.utilitas.antrean');
    Route::get('/utilitas/antrean/data', [QueueController::class, 'fetchData'])->name('menu.utilitas.antrean.data');
    Route::post('/utilitas/antrean/dispatch', [QueueController::class, 'dispatchJob'])->name('menu.utilitas.antrean.dispatch');
    Route::post('/utilitas/antrean/retry', [QueueController::class, 'retryJob'])->name('menu.utilitas.antrean.retry');
    Route::post('/utilitas/antrean/delete', [QueueController::class, 'deleteJob'])->name('menu.utilitas.antrean.delete');
    Route::post('/utilitas/antrean/run-worker', [QueueController::class, 'runWorker'])->name('menu.utilitas.antrean.run-worker');
    Route::get('/super-admin/tenant-menus', [TenantMenuController::class, 'index'])->name('menu.super-admin.tenant-menus');
    Route::get('/super-admin/tenant-menus/fetch', [TenantMenuController::class, 'fetch'])->name('menu.super-admin.tenant-menus.fetch');
    Route::post('/super-admin/tenant-menus/save', [TenantMenuController::class, 'save'])->name('menu.super-admin.tenant-menus.save');
    Route::get('/super-admin/tenants', [TenantManagementController::class, 'index'])->name('menu.super-admin.tenants');
    Route::post('/super-admin/tenants/simpan', [TenantManagementController::class, 'store'])->name('menu.super-admin.tenants.simpan');
    Route::post('/super-admin/tenants/toggle-status', [TenantManagementController::class, 'toggleStatus'])->name('menu.super-admin.tenants.toggle-status');
    Route::post('/super-admin/tenants/hapus', [TenantManagementController::class, 'destroy'])->name('menu.super-admin.tenants.hapus');
    Route::delete('/super-admin/tenants/{id}', [TenantManagementController::class, 'destroy'])->name('menu.super-admin.tenants.destroy');
    Route::get('/utilitas/log-aktivitas', [ActivityLogController::class, 'index'])->name('menu.utilitas.log-aktivitas');
    Route::get('/utilitas/log-aktivitas/data', [ActivityLogController::class, 'fetchData'])->name('menu.utilitas.log-aktivitas.data');
    Route::post('/utilitas/log-aktivitas/delete', [ActivityLogController::class, 'deleteLogs'])->name('menu.utilitas.log-aktivitas.delete');
    
    // Error Monitor Routes
    Route::get('/super-admin/error-monitor', [\Modules\Sistem\Http\Controllers\ErrorMonitorController::class, 'index'])->name('menu.super-admin.error-monitor');
    Route::get('/utilitas/error-monitor', [\Modules\Sistem\Http\Controllers\ErrorMonitorController::class, 'index'])->name('menu.utilitas.error-monitor');
    Route::get('/utilitas/error-monitor/data', [\Modules\Sistem\Http\Controllers\ErrorMonitorController::class, 'fetchData'])->name('menu.utilitas.error-monitor.data');
    Route::post('/utilitas/error-monitor/clear', [\Modules\Sistem\Http\Controllers\ErrorMonitorController::class, 'clearAll'])->name('menu.utilitas.error-monitor.clear');
    Route::post('/utilitas/error-monitor/delete', [\Modules\Sistem\Http\Controllers\ErrorMonitorController::class, 'deleteOne'])->name('menu.utilitas.error-monitor.delete');

    // Server & Resource Monitor Routes
    Route::get('/super-admin/server-monitor', [\Modules\Sistem\Http\Controllers\ServerMonitorController::class, 'index'])->name('menu.super-admin.server-monitor');
    Route::get('/utilitas/server-monitor', [\Modules\Sistem\Http\Controllers\ServerMonitorController::class, 'index'])->name('menu.utilitas.server-monitor');
    Route::get('/utilitas/server-monitor/data', [\Modules\Sistem\Http\Controllers\ServerMonitorController::class, 'fetchData'])->name('menu.utilitas.server-monitor.data');
    Route::post('/utilitas/server-monitor/save-network', [\Modules\Sistem\Http\Controllers\ServerMonitorController::class, 'saveNetworkConfig'])->name('menu.utilitas.server-monitor.save-network');
    Route::post('/utilitas/server-monitor/update-server', [\Modules\Sistem\Http\Controllers\ServerMonitorController::class, 'updateServer'])->name('menu.utilitas.server-monitor.update-server');

    // Document Scanner / AeroScan Routes
    Route::get('/utility/document-scanner', [\Modules\Sistem\Http\Controllers\DocumentScannerController::class, 'index'])->name('menu.utility.scanner');
    Route::get('/utilitas/document-scanner', [\Modules\Sistem\Http\Controllers\DocumentScannerController::class, 'index'])->name('menu.utilitas.scanner');
    Route::get('/utilitas/pemindai-dokumen', [\Modules\Sistem\Http\Controllers\DocumentScannerController::class, 'index'])->name('menu.utilitas.pemindai');
    Route::post('/utilitas/document-scanner/save-pdf', [\Modules\Sistem\Http\Controllers\DocumentScannerController::class, 'saveScannedPdf'])->name('menu.utilitas.scanner.save-pdf');

    // 3. Bimbingan Konseling & Layanan Khusus
    Route::get('/bk/layanan', [BkController::class, 'layanan'])->name('menu.bk.layanan');
    Route::get('/bk/kedisiplinan', [BkController::class, 'kedisiplinan'])->name('menu.bk.kedisiplinan');
    Route::get('/bk/akademik', [PdssController::class, 'index'])->name('menu.bk.akademik');
    Route::get('/bk/alumni', [TracerController::class, 'index'])->name('menu.bk.alumni');
    Route::post('/bk/konseling', [BkController::class, 'storeKonseling'])->name('menu.bk.konseling.store');
    Route::put('/bk/konseling/{id}', [BkController::class, 'updateKonseling'])->name('menu.bk.konseling.update');
    Route::delete('/bk/konseling/{id}', [BkController::class, 'deleteKonseling'])->name('menu.bk.konseling.delete');
    Route::patch('/bk/konseling/{id}/status', [BkController::class, 'updateStatusKonseling'])->name('menu.bk.konseling.status');
    Route::post('/bk/pelanggaran', [BkController::class, 'storePelanggaran'])->name('menu.bk.pelanggaran.store');
    Route::put('/bk/pelanggaran/{id}', [BkController::class, 'updatePelanggaran'])->name('menu.bk.pelanggaran.update');
    Route::delete('/bk/pelanggaran/{id}', [BkController::class, 'deletePelanggaran'])->name('menu.bk.pelanggaran.delete');
    Route::post('/bk/master-pelanggaran', [BkController::class, 'storeMasterPelanggaran'])->name('menu.bk.master.store');
    Route::put('/bk/master-pelanggaran/{id}', [BkController::class, 'updateMasterPelanggaran'])->name('menu.bk.master.update');
    Route::delete('/bk/master-pelanggaran/{id}', [BkController::class, 'deleteMasterPelanggaran'])->name('menu.bk.master.delete');

    // 4. Informasi & Kesiswaan
    Route::get('/informasi/pengumuman', [CmsController::class, 'pengumuman'])->name('menu.informasi.pengumuman');
    Route::get('/informasi/agenda', [CmsController::class, 'agenda'])->name('menu.informasi.agenda');
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
