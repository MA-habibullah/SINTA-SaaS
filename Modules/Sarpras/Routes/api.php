<?php

use Illuminate\Support\Facades\Route;
use Modules\Sarpras\Http\Controllers\SarprasController;

Route::prefix('v1/sarpras')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [SarprasController::class, 'index']);
    Route::post('/', [SarprasController::class, 'store']);
});
