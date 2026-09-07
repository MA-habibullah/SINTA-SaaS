<?php

use Illuminate\Support\Facades\Route;
use Modules\Siswa\Http\Controllers\BukuIndukController;
use Modules\Siswa\Http\Controllers\PpdbController;
use Modules\Siswa\Http\Controllers\MutasiController;
use Modules\Siswa\Http\Controllers\PrestasiController;

Route::prefix('v1/siswa')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::apiResource('buku-induk', BukuIndukController::class);
    Route::apiResource('ppdb', PpdbController::class);
    Route::put('/ppdb/{id}/verifikasi', [PpdbController::class, 'verifikasi']);
    Route::post('/ppdb/{id}/konversi', [PpdbController::class, 'konversiKeBukuInduk']);
    Route::apiResource('mutasi', MutasiController::class)->only(['index', 'store']);
    Route::apiResource('prestasi', PrestasiController::class)->only(['index', 'store']);
});
