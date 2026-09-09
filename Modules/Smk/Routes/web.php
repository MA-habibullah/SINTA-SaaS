<?php

use Illuminate\Support\Facades\Route;
use Modules\Smk\Http\Controllers\SmkController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('smk')->name('smk.')->group(function () {
    Route::get('/', [SmkController::class, 'index'])->name('index');
    Route::post('/mitra', [SmkController::class, 'storeMitra'])->name('mitra.store');
    Route::post('/pkl', [SmkController::class, 'storePkl'])->name('pkl.store');
});
