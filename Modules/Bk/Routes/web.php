<?php

use Illuminate\Support\Facades\Route;
use Modules\Bk\Http\Controllers\BkController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('bk')->name('bk.')->group(function () {
    Route::get('/', [BkController::class, 'layanan'])->name('index');
    Route::get('/layanan', [BkController::class, 'layanan'])->name('layanan');
    Route::get('/kedisiplinan', [BkController::class, 'kedisiplinan'])->name('kedisiplinan');
    Route::get('/search-siswa', [BkController::class, 'searchSiswa'])->name('search.siswa');

    Route::post('/pelanggaran', [BkController::class, 'storePelanggaran'])->name('pelanggaran.store');
    Route::put('/pelanggaran/{id}', [BkController::class, 'updatePelanggaran'])->name('pelanggaran.update');
    Route::delete('/pelanggaran/{id}', [BkController::class, 'deletePelanggaran'])->name('pelanggaran.delete');

    Route::post('/master-pelanggaran', [BkController::class, 'storeMasterPelanggaran'])->name('master.store');
    Route::put('/master-pelanggaran/{id}', [BkController::class, 'updateMasterPelanggaran'])->name('master.update');
    Route::delete('/master-pelanggaran/{id}', [BkController::class, 'deleteMasterPelanggaran'])->name('master.delete');

    Route::post('/konseling', [BkController::class, 'storeKonseling'])->name('konseling.store');
    Route::put('/konseling/{id}', [BkController::class, 'updateKonseling'])->name('konseling.update');
    Route::delete('/konseling/{id}', [BkController::class, 'deleteKonseling'])->name('konseling.delete');
    Route::patch('/konseling/{id}/status', [BkController::class, 'updateStatusKonseling'])->name('konseling.status');
});
