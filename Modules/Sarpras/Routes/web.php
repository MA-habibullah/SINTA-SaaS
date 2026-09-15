<?php

use Illuminate\Support\Facades\Route;
use Modules\Sarpras\Http\Controllers\SarprasController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('sarpras')->name('sarpras.')->group(function () {
    // === HALAMAN UTAMA 5-TAB (Zero-SSR + Async per tab) ===
    Route::get('/', [SarprasController::class, 'index'])->name('index');

    // === ASYNC DATA ENDPOINTS (dipanggil via Axios ?tab=...) ===
    // GET /sarpras?async=1&tab=barang-modal → Tab Barang Modal
    // GET /sarpras?async=1&tab=kir         → Tab KIR Ruangan
    // GET /sarpras?async=1&tab=bhp         → Tab BHP
    // GET /sarpras?async=1&tab=peminjaman  → Tab Peminjaman
    // GET /sarpras?async=1&tab=pemeliharaan→ Tab Pemeliharaan

    // === CRUD BARANG MODAL ===
    Route::post('/barang-modal', [SarprasController::class, 'storeBarangModal'])->name('barang-modal.store');
    Route::put('/barang-modal/{id}', [SarprasController::class, 'updateBarangModal'])->name('barang-modal.update');
    Route::delete('/barang-modal/{id}', [SarprasController::class, 'destroyBarangModal'])->name('barang-modal.destroy');

    // === CRUD BARANG HABIS PAKAI ===
    Route::post('/bhp', [SarprasController::class, 'storeBhp'])->name('bhp.store');
    Route::patch('/bhp/{id}/stok', [SarprasController::class, 'updateBhpStok'])->name('bhp.stok');

    // === CRUD PEMINJAMAN SARPRAS ===
    Route::post('/peminjaman', [SarprasController::class, 'storePeminjaman'])->name('peminjaman.store');
    Route::patch('/peminjaman/{id}/approve', [SarprasController::class, 'approvePeminjaman'])->name('peminjaman.approve');
    Route::patch('/peminjaman/{id}/kembali', [SarprasController::class, 'kembalikanSarpras'])->name('peminjaman.kembali');

    // === CRUD PEMELIHARAAN ===
    Route::post('/pemeliharaan', [SarprasController::class, 'storePemeliharaan'])->name('pemeliharaan.store');
    Route::patch('/pemeliharaan/{id}/status', [SarprasController::class, 'updateStatusPemeliharaan'])->name('pemeliharaan.status');
});
