<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;

Route::prefix('v1/cms')->group(function () {
    // Public Endpoints
    Route::get('/', [CmsController::class, 'index']);

    // Admin Endpoints
    Route::middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
        Route::post('/pengumuman', [CmsController::class, 'storePengumuman']);
        Route::post('/berita', [CmsController::class, 'storeBerita']);
    });
});
