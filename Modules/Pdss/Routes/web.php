<?php

use Illuminate\Support\Facades\Route;
use Modules\Pdss\Http\Controllers\PdssController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('pdss')->name('pdss.')->group(function () {
    Route::get('/', [PdssController::class, 'index'])->name('index');
    Route::get('/kesiapan', [PdssController::class, 'index'])->name('kesiapan');
    Route::get('/search-prodi', [PdssController::class, 'searchProdi'])->name('search.prodi');
    Route::get('/export-nilai', [PdssController::class, 'exportNilai'])->name('export.nilai');
    Route::get('/kampus/{kampusId}/prodi', [PdssController::class, 'getProdiByKampus'])->name('kampus.prodi');
    Route::get('/prodi/{prodiId}/riwayat', [PdssController::class, 'getProdiRiwayat'])->name('prodi.riwayat');
    
    // Master Kampus & Prodi CRUD Routes
    Route::post('/kampus', [PdssController::class, 'storeKampus'])->name('kampus.store');
    Route::put('/kampus/{id}', [PdssController::class, 'updateKampus'])->name('kampus.update');
    Route::delete('/kampus/{id}', [PdssController::class, 'destroyKampus'])->name('kampus.destroy');

    Route::post('/prodi', [PdssController::class, 'storeProdi'])->name('prodi.store');
    Route::put('/prodi/{id}', [PdssController::class, 'updateProdi'])->name('prodi.update');
    Route::delete('/prodi/{id}', [PdssController::class, 'destroyProdi'])->name('prodi.destroy');

    // Ekspor & Impor Master Kampus & Prodi
    Route::get('/export-master-kampus', [PdssController::class, 'exportMasterKampus'])->name('export.kampus');
    Route::post('/import-master-kampus', [PdssController::class, 'importMasterKampus'])->name('import.kampus');
    Route::get('/download-template-kampus', [PdssController::class, 'downloadTemplateKampus'])->name('template.kampus');
    
    Route::post('/simpan-pilihan', [PdssController::class, 'simpanPilihan'])->name('pilihan.simpan');
    Route::post('/simpan-mapel', [PdssController::class, 'simpanConfigMapel'])->name('mapel.simpan');
    Route::post('/auto-detect-mapel', [PdssController::class, 'autoDetectMapelFromRapor'])->name('mapel.auto_detect');
    Route::post('/override-eligible', [PdssController::class, 'overrideEligible'])->name('override');
    Route::post('/reset-eligible', [PdssController::class, 'resetAllEligible'])->name('reset');
    Route::post('/pengunduran-diri', [PdssController::class, 'simpanPengunduranDiri'])->name('pengunduran.simpan');
    Route::post('/batal-pengunduran-diri', [PdssController::class, 'batalkanPengunduranDiri'])->name('pengunduran.batal');
    Route::get('/siswa/{siswaId}/nilai-detail', [PdssController::class, 'getDetailNilaiRaporSiswa'])->name('siswa.nilai');
    Route::post('/salin-simulasi', [PdssController::class, 'salinSimulasi'])->name('simulasi.salin');
    Route::post('/kunci-permanen-simulasi', [PdssController::class, 'kunciPermanenSimulasi'])->name('simulasi.permanen');
    Route::post('/lock-step', [PdssController::class, 'lockStep'])->name('lock');
});

