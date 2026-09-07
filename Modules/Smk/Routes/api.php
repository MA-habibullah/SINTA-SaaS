<?php

use Illuminate\Support\Facades\Route;
use Modules\Smk\Http\Controllers\SmkController;

Route::prefix('v1/smk')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [SmkController::class, 'index']);
    Route::post('/mitra', [SmkController::class, 'storeMitra']);
    Route::post('/pkl', [SmkController::class, 'storePkl']);
});
