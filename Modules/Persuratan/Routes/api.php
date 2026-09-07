<?php

use Illuminate\Support\Facades\Route;
use Modules\Persuratan\Http\Controllers\PersuratanController;

Route::prefix('v1/persuratan')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [PersuratanController::class, 'index']);
    Route::post('/surat-masuk', [PersuratanController::class, 'storeSuratMasuk']);
    Route::post('/disposisi', [PersuratanController::class, 'storeDisposisi']);
});
