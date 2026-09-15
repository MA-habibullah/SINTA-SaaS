<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\AkademikMasterController;
use Modules\Akademik\Http\Controllers\JadwalPelajaranController;
use Modules\Akademik\Http\Controllers\PenilaianController;
use Modules\Akademik\Http\Controllers\RaporController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('akademik')->name('akademik.')->group(function () {
    // 1. Master Data Akademik
    Route::get('/master', [AkademikMasterController::class, 'index'])->name('master.index');
    Route::get('/kelas', [AkademikMasterController::class, 'index'])->name('kelas.index');
    Route::post('/kelas', [AkademikMasterController::class, 'storeKelas'])->name('kelas.store');
    Route::get('/mapel', [AkademikMasterController::class, 'index'])->name('mapel.index');
    Route::post('/mapel', [AkademikMasterController::class, 'storeMapel'])->name('mapel.store');

    // 2. Manajemen Jadwal Pelajaran (Matrix, Anti-Bentrok, Excel Import/Export)
    Route::get('/jadwal', [JadwalPelajaranController::class, 'index'])->name('jadwal.index');
    Route::post('/jadwal', [JadwalPelajaranController::class, 'store'])->name('jadwal.store');
    Route::put('/jadwal/{id}', [JadwalPelajaranController::class, 'update'])->name('jadwal.update');
    Route::delete('/jadwal/{id}', [JadwalPelajaranController::class, 'destroy'])->name('jadwal.destroy');
    Route::post('/jadwal/check-conflict', [JadwalPelajaranController::class, 'checkConflict'])->name('jadwal.check-conflict');
    Route::get('/jadwal/export', [JadwalPelajaranController::class, 'exportJadwal'])->name('jadwal.export');
    Route::get('/jadwal/template', [JadwalPelajaranController::class, 'downloadTemplateJadwal'])->name('jadwal.template');
    Route::post('/jadwal/preview-import', [JadwalPelajaranController::class, 'previewImport'])->name('jadwal.preview-import');
    Route::post('/jadwal/import', [JadwalPelajaranController::class, 'importJadwal'])->name('jadwal.import');
    Route::post('/jadwal/copy', [JadwalPelajaranController::class, 'copyJadwal'])->name('jadwal.copy');

    // 2. Lembar Penilaian Siswa
    Route::get('/penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
    Route::post('/penilaian/batch', [PenilaianController::class, 'batchStore'])->name('penilaian.batch-store');

    // 3. Rapor & Pencetakan PDF
    Route::get('/rapor', [RaporController::class, 'index'])->name('rapor.index');
    Route::get('/rapor/preview-html/{siswaId}', [RaporController::class, 'previewHtml'])->name('rapor.preview-html');
    Route::get('/rapor/preview-identitas/{siswaId}', [RaporController::class, 'previewIdentitas'])->name('rapor.preview-identitas');
    Route::get('/rapor/preview-kelas', [RaporController::class, 'previewHtmlKelas'])->name('rapor.preview-kelas');
    Route::get('/rapor/preview-identitas-kelas', [RaporController::class, 'previewIdentitasKelas'])->name('rapor.preview-identitas-kelas');
    Route::get('/rapor/ledger', [RaporController::class, 'exportLedger'])->name('rapor.ledger');
    Route::post('/rapor/bulk-queue', [RaporController::class, 'bulkQueue'])->name('rapor.bulk-queue');
});
