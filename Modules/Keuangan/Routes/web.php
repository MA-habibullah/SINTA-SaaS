<?php

use Illuminate\Support\Facades\Route;
use Modules\Keuangan\Http\Controllers\DashboardKeuanganController;
use Modules\Keuangan\Http\Controllers\MasterKeuanganController;
use Modules\Keuangan\Http\Controllers\PembayaranKasirController;
use Modules\Keuangan\Http\Controllers\TagihanSiswaController;
use Modules\Keuangan\Http\Controllers\KasBankController;
use Modules\Keuangan\Http\Controllers\LaporanKeuanganController;
use Modules\Keuangan\Http\Controllers\TagihanSayaController;
use Modules\Keuangan\Http\Controllers\KeuanganAuditLogController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('keuangan')->name('keuangan.')->group(function () {
    // 1. Dashboard Keuangan
    Route::get('/', [DashboardKeuanganController::class, 'index'])->name('index');
    Route::get('/dashboard', [DashboardKeuanganController::class, 'index'])->name('dashboard');

    // 2. Master Data & Konfigurasi Biaya
    Route::get('/master', [MasterKeuanganController::class, 'index'])->name('master');
    Route::get('/pos-tarif', [MasterKeuanganController::class, 'index'])->name('pos-tarif.index');
    Route::post('/pos', [MasterKeuanganController::class, 'storePos'])->name('pos.store');
    Route::put('/pos/{id}', [MasterKeuanganController::class, 'updatePos'])->name('pos.update');
    Route::delete('/pos/{id}', [MasterKeuanganController::class, 'deletePos'])->name('pos.destroy');
    Route::post('/tarif', [MasterKeuanganController::class, 'storeTarif'])->name('tarif.store');
    Route::delete('/tarif/{id}', [MasterKeuanganController::class, 'deleteTarif'])->name('tarif.destroy');
    Route::post('/keringanan', [MasterKeuanganController::class, 'storeKeringanan'])->name('keringanan.store');
    Route::delete('/keringanan/{id}', [MasterKeuanganController::class, 'deleteKeringanan'])->name('keringanan.destroy');
    Route::post('/pengaturan', [MasterKeuanganController::class, 'updatePengaturan'])->name('pengaturan.update');
    Route::get('/search-siswa', [MasterKeuanganController::class, 'searchSiswa'])->name('search-siswa');

    // 3. Loket Kasir Pembayaran Real-time (Zero-SSR + Async)
    Route::get('/kasir', [PembayaranKasirController::class, 'index'])->name('kasir.index');
    Route::get('/kasir/search-siswa', [PembayaranKasirController::class, 'searchSiswa'])->name('kasir.search-siswa');
    Route::get('/kasir/siswa-tagihan/{siswaId}', [PembayaranKasirController::class, 'getSiswaTagihan'])->name('kasir.siswa-tagihan');
    Route::post('/kasir/bayar', [PembayaranKasirController::class, 'bayar'])->name('kasir.bayar');

    // 4. Manajemen Tagihan & Batch Invoicing (Zero-SSR + Async)
    Route::get('/tagihan', [TagihanSiswaController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan/generate', [TagihanSiswaController::class, 'generateTagihan'])->name('tagihan.generate');
    Route::delete('/tagihan/{id}', [TagihanSiswaController::class, 'deleteTagihan'])->name('tagihan.destroy');

    // 5. Buku Kas & Rekening Bank (BARU — Zero-SSR)
    Route::get('/kas-bank', [KasBankController::class, 'index'])->name('kas-bank.index');
    Route::post('/kas-bank', [KasBankController::class, 'store'])->name('kas-bank.store');
    Route::put('/kas-bank/{id}', [KasBankController::class, 'update'])->name('kas-bank.update');
    Route::delete('/kas-bank/{id}', [KasBankController::class, 'destroy'])->name('kas-bank.destroy');
    Route::get('/kas-bank/{kasId}/jurnal', [KasBankController::class, 'getJurnalKas'])->name('kas-bank.jurnal');

    // 6. Laporan Keuangan & Rekapitulasi
    Route::get('/laporan', [LaporanKeuanganController::class, 'index'])->name('laporan.index');

    // 7. Tagihan Saya (Portal Siswa & Orang Tua)
    Route::get('/tagihan-saya', [TagihanSayaController::class, 'index'])->name('tagihan-saya.index');

    // 8. Audit Log Keuangan & Pembatalan Transaksi (Void)
    Route::get('/audit-log', [KeuanganAuditLogController::class, 'index'])->name('audit-log.index');
    Route::post('/transaksi/{id}/void', [KeuanganAuditLogController::class, 'voidPayment'])->name('transaksi.void');
});
