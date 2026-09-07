<?php

use Illuminate\Support\Facades\Route;
use Modules\Absensi\Http\Controllers\PresensiController;

Route::middleware(['auth', 'tenant.guard'])->prefix('absensi')->name('absensi.')->group(function () {
    Route::get('/', [PresensiController::class, 'index'])->name('index');
    Route::post('/tap', [PresensiController::class, 'tapPresensi'])->name('tap');
});
