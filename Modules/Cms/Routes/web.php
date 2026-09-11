<?php

use Illuminate\Support\Facades\Route;
use Modules\Cms\Http\Controllers\CmsController;
use Modules\Cms\Http\Controllers\CmsPromosiController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('cms')->name('cms.')->group(function () {
    // Index Redirect
    Route::get('/', [CmsController::class, 'index'])->name('index');

    // Pengumuman Routes
    Route::get('/pengumuman', [CmsController::class, 'pengumuman'])->name('pengumuman');
    Route::post('/pengumuman', [CmsController::class, 'storePengumuman'])->name('pengumuman.store');
    Route::match(['post', 'put', 'patch'], '/pengumuman/{id}', [CmsController::class, 'updatePengumuman'])->name('pengumuman.update');
    Route::delete('/pengumuman/{id}', [CmsController::class, 'destroyPengumuman'])->name('pengumuman.destroy');

    // Kategori Pengumuman Routes
    Route::post('/kategori-pengumuman', [CmsController::class, 'storeKategoriPengumuman'])->name('kategori.store');
    Route::delete('/kategori-pengumuman/{id}', [CmsController::class, 'destroyKategoriPengumuman'])->name('kategori.destroy');

    // Agenda Routes
    Route::get('/agenda', [CmsController::class, 'agenda'])->name('agenda');
    Route::post('/agenda', [CmsController::class, 'storeAgenda'])->name('agenda.store');
    Route::match(['post', 'put', 'patch'], '/agenda/{id}', [CmsController::class, 'updateAgenda'])->name('agenda.update');
    Route::delete('/agenda/{id}', [CmsController::class, 'destroyAgenda'])->name('agenda.destroy');

    // Kategori Agenda Routes
    Route::post('/kategori-agenda', [CmsController::class, 'storeKategoriAgenda'])->name('kategori_agenda.store');
    Route::delete('/kategori-agenda/{id}', [CmsController::class, 'destroyKategoriAgenda'])->name('kategori_agenda.destroy');

    // Super Admin Promosi & Landing Page Routes
    Route::prefix('promosi')->name('promosi.')->group(function () {
        Route::get('/', [CmsPromosiController::class, 'index'])->name('index');
        Route::post('/', [CmsPromosiController::class, 'store'])->name('store');
        Route::put('/{id}', [CmsPromosiController::class, 'update'])->name('update');
        Route::post('/{id}/toggle', [CmsPromosiController::class, 'toggle'])->name('toggle');
        Route::delete('/{id}', [CmsPromosiController::class, 'destroy'])->name('destroy');
    });
});
