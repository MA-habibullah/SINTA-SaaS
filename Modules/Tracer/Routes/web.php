<?php

use Illuminate\Support\Facades\Route;
use Modules\Tracer\Http\Controllers\TracerController;

// Rute Tunggal Resmi: /bk/alumni
Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('bk/alumni')->name('tracer.')->group(function () {
    Route::get('/', [TracerController::class, 'index'])->name('index');
    Route::get('/export-excel', [TracerController::class, 'exportExcel'])->name('export.excel');
    Route::get('/api/siswa-alumni', [TracerController::class, 'searchSiswaAlumni'])->name('api.siswa');
    Route::get('/api/prodi-by-kampus/{kampusId}', [TracerController::class, 'getProdiByKampus'])->name('api.prodi');

    // Riwayat Kuliah
    Route::post('/kuliah', [TracerController::class, 'storeKuliah'])->name('kuliah.store');
    Route::put('/kuliah/{id}', [TracerController::class, 'updateKuliah'])->name('kuliah.update');
    Route::delete('/kuliah/{id}', [TracerController::class, 'destroyKuliah'])->name('kuliah.destroy');

    // Riwayat Pekerjaan
    Route::post('/pekerjaan', [TracerController::class, 'storePekerjaan'])->name('pekerjaan.store');
    Route::put('/pekerjaan/{id}', [TracerController::class, 'updatePekerjaan'])->name('pekerjaan.update');
    Route::delete('/pekerjaan/{id}', [TracerController::class, 'destroyPekerjaan'])->name('pekerjaan.destroy');
});

// Auto-Redirect dari legacy /tracer ke URL tunggal /bk/alumni
Route::middleware(['web', 'auth', 'tenant.guard'])->group(function () {
    Route::redirect('/tracer', '/bk/alumni', 301);
});
