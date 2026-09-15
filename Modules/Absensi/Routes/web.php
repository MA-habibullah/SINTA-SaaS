<?php

use Illuminate\Support\Facades\Route;
use Modules\Absensi\Http\Controllers\PresensiController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('absensi')->name('absensi.')->group(function () {
    Route::get('/', [PresensiController::class, 'index'])->name('index');
    Route::get('/kelas/{kelasId}/siswa', [PresensiController::class, 'getSiswaByKelas'])->name('kelas.siswa');
    
    // Presensi Siswa Mandiri & Scanner
    Route::post('/scan-siswa', [PresensiController::class, 'scanQrSiswa'])->name('scan.siswa');
    Route::post('/presensi-siswa-gps', [PresensiController::class, 'presensiSiswaGps'])->name('presensi.siswa.gps');
    
    // Presensi Massal Wali Kelas & Verifikasi
    Route::post('/presensi-wali-kelas', [PresensiController::class, 'storePresensiWaliKelas'])->name('presensi.wali_kelas.store');
    Route::put('/presensi-siswa/{id}/verifikasi', [PresensiController::class, 'verifikasiPresensiSiswa'])->name('presensi.siswa.verifikasi');
    
    // Presensi GTK
    Route::post('/presensi-gtk-gps', [PresensiController::class, 'presensiGtkGps'])->name('presensi.gtk.gps');
    
    // Jurnal Mengajar Guru (KBM)
    Route::post('/jurnal', [PresensiController::class, 'storeJurnal'])->name('jurnal.store');
    Route::put('/jurnal/{id}', [PresensiController::class, 'updateJurnal'])->name('jurnal.update');
    Route::delete('/jurnal/{id}', [PresensiController::class, 'destroyJurnal'])->name('jurnal.destroy');
    Route::put('/jurnal/{id}/supervisi', [PresensiController::class, 'supervisiJurnal'])->name('jurnal.supervisi');
    
    // Izin / Cuti & Setting
    Route::post('/izin', [PresensiController::class, 'storeIzin'])->name('izin.store');
    Route::put('/izin/{id}/status', [PresensiController::class, 'updateStatusIzin'])->name('izin.status');
    Route::post('/setting', [PresensiController::class, 'updateSetting'])->name('setting.update');
});
