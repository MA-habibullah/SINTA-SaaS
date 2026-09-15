<?php

use Illuminate\Support\Facades\Route;
use Modules\Persuratan\Http\Controllers\PersuratanController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('persuratan')->name('persuratan.')->group(function () {
    // Index Utama Persuratan
    Route::get('/', [PersuratanController::class, 'index'])->name('index');

    // Agenda Surat Masuk
    Route::get('/surat-masuk', [PersuratanController::class, 'suratMasukIndex'])->name('surat-masuk.index');
    Route::post('/surat-masuk', [PersuratanController::class, 'storeSuratMasuk'])->name('surat-masuk.store');
    Route::put('/surat-masuk/{id}', [PersuratanController::class, 'updateSuratMasuk'])->name('surat-masuk.update');
    Route::delete('/surat-masuk/{id}', [PersuratanController::class, 'destroySuratMasuk'])->name('surat-masuk.destroy');

    // Arsip Surat Keluar
    Route::get('/surat-keluar', [PersuratanController::class, 'suratKeluarIndex'])->name('surat-keluar.index');
    Route::post('/surat-keluar', [PersuratanController::class, 'storeSuratKeluar'])->name('surat-keluar.store');
    Route::put('/surat-keluar/{id}', [PersuratanController::class, 'updateSuratKeluar'])->name('surat-keluar.update');
    Route::delete('/surat-keluar/{id}', [PersuratanController::class, 'destroySuratKeluar'])->name('surat-keluar.destroy');

    // Lembar Disposisi
    Route::post('/disposisi', [PersuratanController::class, 'storeDisposisi'])->name('disposisi.store');
});
