<?php

use Illuminate\Support\Facades\Route;
use Modules\Pdss\Http\Controllers\PdssController;

Route::prefix('v1/pdss')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [PdssController::class, 'index']);
});
