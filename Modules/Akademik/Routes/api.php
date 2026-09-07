<?php

use Illuminate\Support\Facades\Route;
use Modules\Akademik\Http\Controllers\AkademikMasterController;
use Modules\Akademik\Http\Controllers\PenilaianController;
use Modules\Akademik\Http\Controllers\RaporController;

Route::prefix('v1/akademik')->middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
    Route::get('/master', [AkademikMasterController::class, 'index']);
    Route::post('/kelas', [AkademikMasterController::class, 'storeKelas']);
    Route::post('/mapel', [AkademikMasterController::class, 'storeMapel']);

    Route::get('/penilaian', [PenilaianController::class, 'index']);
    Route::post('/penilaian/batch', [PenilaianController::class, 'batchStore']);

    Route::post('/rapor/bulk-queue', [RaporController::class, 'bulkQueue']);
});
