<?php

use Illuminate\Support\Facades\Route;
use Modules\Absensi\Http\Controllers\PresensiController;

Route::prefix('v1/absensi')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [PresensiController::class, 'index']);
    Route::post('/tap', [PresensiController::class, 'tapPresensi']);
});
