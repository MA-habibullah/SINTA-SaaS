<?php

use Illuminate\Support\Facades\Route;
use Modules\Keuangan\Http\Controllers\PosTarifController;
use Modules\Keuangan\Http\Controllers\TagihanSiswaController;
use Modules\Keuangan\Http\Controllers\PembayaranKasirController;
use Modules\Keuangan\Http\Controllers\LaporanKeuanganController;

Route::middleware(['auth', 'tenant.guard'])->prefix('keuangan')->name('keuangan.')->group(function () {
    // 1. Pos & Tarif Pembayaran
    Route::get('/pos-tarif', [PosTarifController::class, 'index'])->name('pos-tarif.index');
    Route::post('/pos', [PosTarifController::class, 'storePos'])->name('pos.store');
    Route::post('/tarif', [PosTarifController::class, 'storeTarif'])->name('tarif.store');

    // 2. Tagihan Siswa & Batch Invoicing
    Route::get('/tagihan', [TagihanSiswaController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/generate-monthly', [TagihanSiswaController::class, 'triggerMonthlyInvoices'])->name('tagihan.generate-monthly');

    // 3. Kasir Pembayaran Real-time
    Route::get('/kasir', [PembayaranKasirController::class, 'index'])->name('kasir.index');
    Route::post('/kasir/bayar', [PembayaranKasirController::class, 'bayar'])->name('kasir.bayar');

    // 4. Laporan Keuangan & Arus Kas
    Route::get('/laporan', [LaporanKeuanganController::class, 'index'])->name('laporan.index');
});
