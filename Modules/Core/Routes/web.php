<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\TenantManagementController;
use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Http\Controllers\SekolahIdentitasController;

// 1. Guest Routes (Login & Public Auth)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// 2. Authenticated Central & Tenant Routes
Route::middleware(['auth', 'tenant.guard'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::post('/switch-tenant', [AuthController::class, 'switchTenant'])->name('core.switch-tenant');

    // Dashboard
    Route::get('/dashboard', function () {
        return \Inertia\Inertia::render('Dashboard');
    })->name('dashboard');

    // Core - Tenant Management (Super Admin Platform)
    Route::prefix('core/tenants')->name('core.tenants.')->middleware('role:super_admin')->group(function () {
        Route::get('/', [TenantManagementController::class, 'index'])->name('index');
        Route::post('/', [TenantManagementController::class, 'store'])->name('store');
        Route::put('/{id}', [TenantManagementController::class, 'update'])->name('update');
        Route::delete('/{id}', [TenantManagementController::class, 'destroy'])->name('destroy');
    });

    // Core - User Management
    Route::prefix('core/users')->name('core.users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{id}', [UserController::class, 'update'])->name('update');
        Route::delete('/{id}', [UserController::class, 'destroy'])->name('destroy');
    });

    // Core - Sekolah Identitas (Profil Sekolah)
    Route::prefix('core/sekolah-identitas')->name('core.sekolah-identitas.')->group(function () {
        Route::get('/', [SekolahIdentitasController::class, 'show'])->name('show');
        Route::put('/', [SekolahIdentitasController::class, 'update'])->name('update');
    });
});
