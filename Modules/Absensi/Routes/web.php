<?php

use Illuminate\Support\Facades\Route;
use Modules\Absensi\Http\Controllers\PresensiController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('absensi')->name('absensi.')->group(function () {
    Route::get('/', [PresensiController::class, 'index'])->name('index');
    Route::post('/scan-siswa', [PresensiController::class, 'scanQrSiswa'])->name('scan.siswa');
    Route::post('/presensi-siswa-gps', [PresensiController::class, 'presensiSiswaGps'])->name('presensi.siswa.gps');
    Route::post('/presensi-gtk-gps', [PresensiController::class, 'presensiGtkGps'])->name('presensi.gtk.gps');
    Route::post('/izin', [PresensiController::class, 'storeIzin'])->name('izin.store');
    Route::put('/izin/{id}/status', [PresensiController::class, 'updateStatusIzin'])->name('izin.status');
    Route::post('/setting', [PresensiController::class, 'updateSetting'])->name('setting.update');
});
