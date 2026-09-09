<?php

use Illuminate\Support\Facades\Route;
use Modules\Siswa\Http\Controllers\BukuIndukController;
use Modules\Siswa\Http\Controllers\PpdbController;
use Modules\Siswa\Http\Controllers\MutasiController;
use Modules\Siswa\Http\Controllers\PrestasiController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('siswa')->name('siswa.')->group(function () {
    // 1. Buku Induk Siswa
    Route::prefix('buku-induk')->name('buku-induk.')->group(function () {
        Route::get('/', [BukuIndukController::class, 'index'])->name('index');
        Route::get('/cetak/{id?}', [BukuIndukController::class, 'cetakLembarBukuInduk'])->name('cetak');
        Route::get('/create', [BukuIndukController::class, 'create'])->name('create');
        Route::post('/', [BukuIndukController::class, 'store'])->name('store');
        Route::get('/{id}', [BukuIndukController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [BukuIndukController::class, 'edit'])->name('edit');
        Route::match(['post', 'put', 'patch'], '/{id}', [BukuIndukController::class, 'update'])->name('update');
        Route::delete('/{id}', [BukuIndukController::class, 'destroy'])->name('destroy');
    });

    // 2. PPDB & Pendaftaran Calon Siswa
    Route::prefix('ppdb')->name('ppdb.')->group(function () {
        Route::get('/', [PpdbController::class, 'index'])->name('index');
        Route::put('/{id}/verifikasi', [PpdbController::class, 'verifikasi'])->name('verifikasi');
        Route::post('/{id}/konversi', [PpdbController::class, 'konversiKeBukuInduk'])->name('konversi');
    });

    // 3. Mutasi Siswa
    Route::prefix('mutasi')->name('mutasi.')->group(function () {
        Route::get('/', [MutasiController::class, 'index'])->name('index');
        Route::post('/', [MutasiController::class, 'store'])->name('store');
    });

    // 4. Prestasi Siswa
    Route::prefix('prestasi')->name('prestasi.')->group(function () {
        Route::get('/', [PrestasiController::class, 'index'])->name('index');
        Route::post('/', [PrestasiController::class, 'store'])->name('store');
    });
});
