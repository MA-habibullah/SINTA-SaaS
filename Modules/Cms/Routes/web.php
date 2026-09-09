<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('cms')->name('cms.')->group(function () {
    Route::get('/', [CmsController::class, 'index'])->name('index');
    Route::post('/pengumuman', [CmsController::class, 'storePengumuman'])->name('pengumuman.store');
    Route::post('/berita', [CmsController::class, 'storeBerita'])->name('berita.store');
});
