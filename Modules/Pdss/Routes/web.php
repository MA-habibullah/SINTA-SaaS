<?php

use Illuminate\Support\Facades\Route;
use Modules\Pdss\Http\Controllers\PdssController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('pdss')->name('pdss.')->group(function () {
    Route::get('/', [PdssController::class, 'index'])->name('index');
    Route::get('/kesiapan', [PdssController::class, 'index'])->name('kesiapan');
    Route::get('/search-prodi', [PdssController::class, 'searchProdi'])->name('search.prodi');
    
    Route::post('/simpan-pilihan', [PdssController::class, 'simpanPilihan'])->name('pilihan.simpan');
    Route::post('/simpan-mapel', [PdssController::class, 'simpanConfigMapel'])->name('mapel.simpan');
    Route::post('/auto-detect-mapel', [PdssController::class, 'autoDetectMapelFromRapor'])->name('mapel.auto_detect');
    Route::post('/override-eligible', [PdssController::class, 'overrideEligible'])->name('override');
    Route::post('/pengunduran-diri', [PdssController::class, 'simpanPengunduranDiri'])->name('pengunduran.simpan');
    Route::post('/salin-simulasi', [PdssController::class, 'salinSimulasi'])->name('simulasi.salin');
    Route::post('/kunci-permanen-simulasi', [PdssController::class, 'kunciPermanenSimulasi'])->name('simulasi.permanen');
    Route::post('/lock-step', [PdssController::class, 'lockStep'])->name('lock');
});
