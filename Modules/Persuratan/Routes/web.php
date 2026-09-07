<?php

use Illuminate\Support\Facades\Route;
use Modules\Persuratan\Http\Controllers\PersuratanController;

Route::middleware(['auth', 'tenant.guard'])->prefix('persuratan')->name('persuratan.')->group(function () {
    Route::get('/', [PersuratanController::class, 'index'])->name('index');
    Route::post('/surat-masuk', [PersuratanController::class, 'storeSuratMasuk'])->name('surat-masuk.store');
    Route::post('/disposisi', [PersuratanController::class, 'storeDisposisi'])->name('disposisi.store');
});
