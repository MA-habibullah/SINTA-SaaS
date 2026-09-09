<?php

use Illuminate\Support\Facades\Route;
use Modules\Perpustakaan\Http\Controllers\PerpustakaanController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('perpustakaan')->name('perpustakaan.')->group(function () {
    Route::get('/', [PerpustakaanController::class, 'index'])->name('index');
    Route::post('/buku', [PerpustakaanController::class, 'storeBuku'])->name('buku.store');
    Route::post('/pinjam', [PerpustakaanController::class, 'pinjamBuku'])->name('pinjam.store');
    Route::put('/kembali/{id}', [PerpustakaanController::class, 'kembalikanBuku'])->name('kembali.update');
});
