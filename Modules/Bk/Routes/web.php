<?php

use Illuminate\Support\Facades\Route;
use Modules\Bk\Http\Controllers\BkController;

Route::middleware(['auth', 'tenant.guard'])->prefix('bk')->name('bk.')->group(function () {
    Route::get('/', [BkController::class, 'index'])->name('index');
    Route::post('/pelanggaran', [BkController::class, 'storePelanggaran'])->name('pelanggaran.store');
    Route::post('/konseling', [BkController::class, 'storeKonseling'])->name('konseling.store');
});
