<?php

use Illuminate\Support\Facades\Route;
use Modules\Perpustakaan\Http\Controllers\PerpustakaanController;

Route::middleware(['web', 'auth', 'tenant.guard'])->prefix('perpustakaan')->name('perpustakaan.')->group(function () {
    // 1. Katalog & Inventori
    Route::get('/', [PerpustakaanController::class, 'katalog'])->name('index');
    Route::get('/katalog', [PerpustakaanController::class, 'katalog'])->name('katalog');
    Route::post('/katalog', [PerpustakaanController::class, 'storeBuku'])->name('katalog.store');
    Route::post('/buku', [PerpustakaanController::class, 'storeBuku'])->name('buku.store');
    Route::match(['post', 'put', 'patch'], '/katalog/{id}', [PerpustakaanController::class, 'updateBuku'])->name('katalog.update');
    Route::delete('/katalog/{id}', [PerpustakaanController::class, 'destroyBuku'])->name('katalog.destroy');
    Route::post('/katalog/{id}/toggle-status', [PerpustakaanController::class, 'toggleStatusBuku'])->name('katalog.toggle-status');
    
    // Eksemplar, Rak, Usulan, Serial
    Route::post('/eksemplar', [PerpustakaanController::class, 'storeEksemplar'])->name('eksemplar.store');
    Route::delete('/eksemplar/{id}', [PerpustakaanController::class, 'destroyEksemplar'])->name('eksemplar.destroy');
    Route::post('/master-rak', [PerpustakaanController::class, 'storeRak'])->name('rak.store');
    Route::delete('/master-rak/{id}', [PerpustakaanController::class, 'destroyRak'])->name('rak.destroy');
    Route::post('/usulan-buku', [PerpustakaanController::class, 'storeUsulan'])->name('usulan.store');
    Route::patch('/usulan-buku/{id}', [PerpustakaanController::class, 'updateStatusUsulan'])->name('usulan.update');
    Route::post('/serial-berkala', [PerpustakaanController::class, 'storeSerial'])->name('serial.store');
    Route::delete('/serial-berkala/{id}', [PerpustakaanController::class, 'destroySerial'])->name('serial.destroy');

    // 2. Sirkulasi & Layanan
    Route::get('/sirkulasi', [PerpustakaanController::class, 'sirkulasi'])->name('sirkulasi');
    Route::post('/sirkulasi/pinjam', [PerpustakaanController::class, 'pinjamBuku'])->name('sirkulasi.pinjam');
    Route::post('/pinjam', [PerpustakaanController::class, 'pinjamBuku'])->name('pinjam.store');
    Route::match(['post', 'put'], '/sirkulasi/kembali/{id}', [PerpustakaanController::class, 'kembalikanBuku'])->name('sirkulasi.kembali');
    Route::match(['post', 'put'], '/kembali/{id}', [PerpustakaanController::class, 'kembalikanBuku'])->name('kembali.update');
    Route::post('/sirkulasi/perpanjang/{id}', [PerpustakaanController::class, 'perpanjangBuku'])->name('sirkulasi.perpanjang');
    Route::post('/sirkulasi/bayar-denda/{id}', [PerpustakaanController::class, 'bayarDenda'])->name('sirkulasi.bayar-denda');
    Route::post('/sirkulasi/distribusi-paket', [PerpustakaanController::class, 'distribusiBukuPaket'])->name('sirkulasi.distribusi-paket');

    // 3. Anggota & Buku Tamu
    Route::get('/anggota', [PerpustakaanController::class, 'anggota'])->name('anggota');
    Route::post('/anggota', [PerpustakaanController::class, 'storeAnggota'])->name('anggota.store');
    Route::delete('/anggota/{id}', [PerpustakaanController::class, 'destroyAnggota'])->name('anggota.destroy');
    Route::get('/anggota/bebas-pustaka/{id}', [PerpustakaanController::class, 'cekBebasPustaka'])->name('anggota.bebas-pustaka');
    Route::post('/buku-tamu', [PerpustakaanController::class, 'storeBukuTamu'])->name('buku-tamu.store');
    Route::post('/pengaturan', [PerpustakaanController::class, 'updatePengaturan'])->name('pengaturan.update');

    // 4. OPAC & Riwayat Saya
    Route::get('/opac', [PerpustakaanController::class, 'opac'])->name('opac');
    Route::get('/riwayat-saya', [PerpustakaanController::class, 'riwayatSaya'])->name('riwayat-saya');
});
