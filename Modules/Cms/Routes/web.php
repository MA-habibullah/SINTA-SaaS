<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('cms')->name('cms.')->group(function () {
    // Index Redirect
    Route::get('/', [CmsController::class, 'index'])->name('index');

    // Pengumuman Routes
    Route::get('/pengumuman', [CmsController::class, 'pengumuman'])->name('pengumuman');
    Route::post('/pengumuman', [CmsController::class, 'storePengumuman'])->name('pengumuman.store');
    Route::put('/pengumuman/{id}', [CmsController::class, 'updatePengumuman'])->name('pengumuman.update');
    Route::delete('/pengumuman/{id}', [CmsController::class, 'destroyPengumuman'])->name('pengumuman.destroy');

    // Kategori Pengumuman Routes
    Route::post('/kategori-pengumuman', [CmsController::class, 'storeKategoriPengumuman'])->name('kategori.store');
    Route::delete('/kategori-pengumuman/{id}', [CmsController::class, 'destroyKategoriPengumuman'])->name('kategori.destroy');

    // Agenda Routes
    Route::get('/agenda', [CmsController::class, 'agenda'])->name('agenda');
    Route::post('/agenda', [CmsController::class, 'storeAgenda'])->name('agenda.store');
    Route::put('/agenda/{id}', [CmsController::class, 'updateAgenda'])->name('agenda.update');
    Route::delete('/agenda/{id}', [CmsController::class, 'destroyAgenda'])->name('agenda.destroy');
});
