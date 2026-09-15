<?php

use Illuminate\Support\Facades\Route;
use Modules\Kepegawaian\Http\Controllers\KepegawaianController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('kepegawaian')->name('kepegawaian.')->group(function () {
    Route::get('/', [KepegawaianController::class, 'index'])->name('index');
    Route::post('/gtk', [KepegawaianController::class, 'storeGtk'])->name('gtk.store');
    Route::put('/gtk/{id}', [KepegawaianController::class, 'updateGtk'])->name('gtk.update');
    Route::delete('/gtk/{id}', [KepegawaianController::class, 'destroyGtk'])->name('gtk.destroy');

    Route::post('/pangkat', [KepegawaianController::class, 'storePangkat'])->name('pangkat.store');
    Route::post('/sertifikasi', [KepegawaianController::class, 'storeSertifikasi'])->name('sertifikasi.store');
    Route::post('/lowongan', [KepegawaianController::class, 'storeLowongan'])->name('lowongan.store');
    Route::post('/pelamar', [KepegawaianController::class, 'storePelamar'])->name('pelamar.store');
    Route::put('/pelamar/{id}/tahapan', [KepegawaianController::class, 'updateTahapanPelamar'])->name('pelamar.tahapan');
});
