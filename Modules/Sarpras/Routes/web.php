<?php

use Illuminate\Support\Facades\Route;
use Modules\Sarpras\Http\Controllers\SarprasController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('sarpras')->name('sarpras.')->group(function () {
    Route::get('/', [SarprasController::class, 'index'])->name('index');
    Route::post('/', [SarprasController::class, 'store'])->name('store');
});
