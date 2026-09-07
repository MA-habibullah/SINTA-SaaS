<?php

use Illuminate\Support\Facades\Route;
use Modules\Tracer\Http\Controllers\TracerController;

Route::middleware(['auth', 'tenant.guard'])->prefix('tracer')->name('tracer.')->group(function () {
    Route::get('/', [TracerController::class, 'index'])->name('index');
    Route::post('/', [TracerController::class, 'store'])->name('store');
});
