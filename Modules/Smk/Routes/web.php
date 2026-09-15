<?php

use Illuminate\Support\Facades\Route;
use Modules\Smk\Http\Controllers\SmkController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('smk')->name('smk.')->group(function () {
    Route::get('/', [SmkController::class, 'index'])->name('index');
    Route::post('/mitra', [SmkController::class, 'storeMitra'])->name('mitra.store');
    Route::put('/mitra/{id}', [SmkController::class, 'updateMitra'])->name('mitra.update');
    Route::delete('/mitra/{id}', [SmkController::class, 'destroyMitra'])->name('mitra.destroy');

    Route::post('/pkl', [SmkController::class, 'storePkl'])->name('pkl.store');
    Route::put('/pkl/{id}/nilai', [SmkController::class, 'updatePklNilai'])->name('pkl.nilai');

    Route::post('/jurnal', [SmkController::class, 'storeJurnal'])->name('jurnal.store');
    Route::put('/jurnal/{id}/verifikasi', [SmkController::class, 'verifikasiJurnal'])->name('jurnal.verifikasi');

    Route::post('/ukk', [SmkController::class, 'storeUkk'])->name('ukk.store');
});
