<?php

use Illuminate\Support\Facades\Route;
use Modules\Tracer\Http\Controllers\TracerController;

Route::prefix('v1/tracer')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [TracerController::class, 'index']);
    Route::post('/', [TracerController::class, 'store']);
});
