<?php

use Illuminate\Support\Facades\Route;
use Modules\Kepegawaian\Http\Controllers\KepegawaianController;

Route::prefix('v1/kepegawaian')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [KepegawaianController::class, 'index']);
    Route::post('/', [KepegawaianController::class, 'store']);
});
