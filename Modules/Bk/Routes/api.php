<?php

use Illuminate\Support\Facades\Route;
use Modules\Bk\Http\Controllers\BkController;

Route::prefix('v1/bk')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [BkController::class, 'index']);
    Route::post('/pelanggaran', [BkController::class, 'storePelanggaran']);
    Route::post('/konseling', [BkController::class, 'storeKonseling']);
});
