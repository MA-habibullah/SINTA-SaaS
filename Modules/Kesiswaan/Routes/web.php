<?php

use Illuminate\Support\Facades\Route;
use Modules\Kesiswaan\Http\Controllers\EkskulController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('kesiswaan')->name('kesiswaan.')->group(function () {
    // 1. Dashboard / Hub Ekstrakurikuler
    Route::get('/ekskul', [EkskulController::class, 'index'])->name('ekskul.index');

    // 2. Master Ekskul Mutations
    Route::post('/ekskul', [EkskulController::class, 'storeEkskul'])->name('ekskul.store');
    Route::put('/ekskul/{id}', [EkskulController::class, 'updateEkskul'])->name('ekskul.update');
    Route::delete('/ekskul/{id}', [EkskulController::class, 'destroyEkskul'])->name('ekskul.destroy');

    // 3. Anggota Ekskul Mutations & Batch Tools
    Route::post('/ekskul/anggota', [EkskulController::class, 'storeAnggota'])->name('ekskul.anggota.store');
    Route::post('/ekskul/anggota/batch', [EkskulController::class, 'storeAnggotaBatch'])->name('ekskul.anggota.batch');
    Route::post('/ekskul/anggota/copy', [EkskulController::class, 'copyAnggota'])->name('ekskul.anggota.copy');
    Route::get('/ekskul/siswa-by-kelas', [EkskulController::class, 'getSiswaByKelas'])->name('ekskul.siswa-by-kelas');
    Route::put('/ekskul/anggota/{id}', [EkskulController::class, 'updateAnggota'])->name('ekskul.anggota.update');
    Route::delete('/ekskul/anggota/{id}', [EkskulController::class, 'destroyAnggota'])->name('ekskul.anggota.destroy');

    // 4. Pembina Ekskul Mutations
    Route::post('/ekskul/pembina', [EkskulController::class, 'storePembina'])->name('ekskul.pembina.store');
    Route::put('/ekskul/pembina/{id}', [EkskulController::class, 'updatePembina'])->name('ekskul.pembina.update');
    Route::delete('/ekskul/pembina/{id}', [EkskulController::class, 'destroyPembina'])->name('ekskul.pembina.destroy');

    // 5. Jurnal Kegiatan Ekskul Mutations
    Route::post('/ekskul/jurnal', [EkskulController::class, 'storeJurnal'])->name('ekskul.jurnal.store');
    Route::match(['post', 'put', 'patch'], '/ekskul/jurnal/{id}', [EkskulController::class, 'updateJurnal'])->name('ekskul.jurnal.update');
    Route::delete('/ekskul/jurnal/{id}', [EkskulController::class, 'destroyJurnal'])->name('ekskul.jurnal.destroy');

    // 6. Penilaian Ekskul Mutations
    Route::post('/ekskul/nilai', [EkskulController::class, 'storeNilai'])->name('ekskul.nilai.store');
    Route::delete('/ekskul/nilai/{id}', [EkskulController::class, 'destroyNilai'])->name('ekskul.nilai.destroy');

    // 7. Prestasi Siswa Mutations
    Route::post('/ekskul/prestasi', [EkskulController::class, 'storePrestasi'])->name('ekskul.prestasi.store');
    Route::match(['post', 'put', 'patch'], '/ekskul/prestasi/{id}', [EkskulController::class, 'updatePrestasi'])->name('ekskul.prestasi.update');
    Route::delete('/ekskul/prestasi/{id}', [EkskulController::class, 'destroyPrestasi'])->name('ekskul.prestasi.destroy');
});
