<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\AkademikMasterController;
use Modules\Akademik\Http\Controllers\PenilaianController;
use Modules\Akademik\Http\Controllers\RaporController;

Route::middleware(['auth', 'tenant.guard'])->prefix('akademik')->name('akademik.')->group(function () {
    // 1. Master Data Akademik
    Route::get('/master', [AkademikMasterController::class, 'index'])->name('master.index');
    Route::post('/kelas', [AkademikMasterController::class, 'storeKelas'])->name('kelas.store');
    Route::post('/mapel', [AkademikMasterController::class, 'storeMapel'])->name('mapel.store');

    // 2. Lembar Penilaian Siswa
    Route::get('/penilaian', [PenilaianController::class, 'index'])->name('penilaian.index');
    Route::post('/penilaian/batch', [PenilaianController::class, 'batchStore'])->name('penilaian.batch-store');

    // 3. Rapor & Pencetakan PDF
    Route::get('/rapor', [RaporController::class, 'index'])->name('rapor.index');
    Route::get('/rapor/preview-html/{siswaId}', [RaporController::class, 'previewHtml'])->name('rapor.preview-html');
    Route::post('/rapor/bulk-queue', [RaporController::class, 'bulkQueue'])->name('rapor.bulk-queue');
});
