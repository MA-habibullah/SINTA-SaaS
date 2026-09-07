<?php

use Illuminate\Support\Facades\Route;
use Modules\Perpustakaan\Http\Controllers\PerpustakaanController;

Route::prefix('v1/perpustakaan')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/', [PerpustakaanController::class, 'index']);
    Route::post('/buku', [PerpustakaanController::class, 'storeBuku']);
    Route::post('/pinjam', [PerpustakaanController::class, 'pinjamBuku']);
    Route::put('/kembali/{id}', [PerpustakaanController::class, 'kembalikanBuku']);
});
