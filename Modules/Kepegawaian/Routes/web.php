<?php

use Illuminate\Support\Facades\Route;
use Modules\Kepegawaian\Http\Controllers\KepegawaianController;

Route::middleware(['auth', 'tenant.guard'])->prefix('kepegawaian')->name('kepegawaian.')->group(function () {
    Route::get('/', [KepegawaianController::class, 'index'])->name('index');
    Route::post('/', [KepegawaianController::class, 'store'])->name('store');
});
