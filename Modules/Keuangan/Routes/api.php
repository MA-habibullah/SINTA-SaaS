<?php

use Illuminate\Support\Facades\Route;
use Modules\Keuangan\Http\Controllers\PosTarifController;
use Modules\Keuangan\Http\Controllers\TagihanSiswaController;
use Modules\Keuangan\Http\Controllers\PembayaranKasirController;
use Modules\Keuangan\Http\Controllers\LaporanKeuanganController;

Route::prefix('v1/keuangan')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/pos-tarif', [PosTarifController::class, 'index']);
    Route::post('/pos', [PosTarifController::class, 'storePos']);
    Route::post('/tarif', [PosTarifController::class, 'storeTarif']);

    Route::get('/tagihan', [TagihanSiswaController::class, 'index']);
    Route::post('/tagihan/generate-monthly', [TagihanSiswaController::class, 'triggerMonthlyInvoices']);

    Route::get('/kasir', [PembayaranKasirController::class, 'index']);
    Route::post('/kasir/bayar', [PembayaranKasirController::class, 'bayar']);

    Route::get('/laporan', [LaporanKeuanganController::class, 'index']);
});
