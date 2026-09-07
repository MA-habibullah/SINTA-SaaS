<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\TenantManagementController;
use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Http\Controllers\SekolahIdentitasController;

Route::prefix('v1/core')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'tenant.guard'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/switch-tenant', [AuthController::class, 'switchTenant']);

        Route::apiResource('tenants', TenantManagementController::class)->middleware('role:super_admin');
        Route::apiResource('users', UserController::class);

        Route::get('/sekolah-identitas', [SekolahIdentitasController::class, 'show']);
        Route::put('/sekolah-identitas', [SekolahIdentitasController::class, 'update']);
    });
});
