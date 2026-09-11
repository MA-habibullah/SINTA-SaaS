<?php

use Illuminate\Support\Facades\Route;
use Modules\Kesiswaan\Http\Controllers\EkskulController;

Route::middleware(['api', 'auth:sanctum', 'tenant.guard'])->prefix('kesiswaan')->name('api.kesiswaan.')->group(function () {
    Route::get('/ekskul', [EkskulController::class, 'index'])->name('ekskul.index');
});
