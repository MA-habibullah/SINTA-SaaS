<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\AuthController;
use Modules\Core\Http\Controllers\TenantManagementController;
use Modules\Core\Http\Controllers\UserController;
use Modules\Core\Http\Controllers\SekolahIdentitasController;
use Modules\Core\Http\Controllers\KonfigurasiAksesController;

// 1. Guest Routes (Landing, Registration & Login)
Route::middleware('guest')->group(function () {
    Route::get('/', [AuthController::class, 'showLandingPage'])->name('landing');
    Route::get('/landing', [AuthController::class, 'showLandingPage'])->name('landing.alias');
    Route::get('/daftar-sekolah', [AuthController::class, 'showRegisterForm'])->name('daftar-sekolah');
    Route::post('/daftar-sekolah', [AuthController::class, 'registerSchool'])->name('daftar-sekolah.submit');
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/super-admin/login', [AuthController::class, 'showSuperAdminLoginForm'])->name('super-admin.login');
    Route::post('/super-admin/login', [AuthController::class, 'login'])->name('super-admin.login.submit');
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
        Route::post('/{id}/approve', [TenantManagementController::class, 'approve'])->name('approve');
        Route::post('/{id}/reject', [TenantManagementController::class, 'reject'])->name('reject');
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
        Route::match(['post', 'put'], '/', [SekolahIdentitasController::class, 'update'])->name('update');
    });

    // Core - Konfigurasi Hak Akses (RBAC Matrix)
    Route::prefix('core/konfigurasi-akses')->name('core.konfigurasi-akses.')->group(function () {
        Route::get('/', [KonfigurasiAksesController::class, 'index'])->name('index');
        Route::post('/', [KonfigurasiAksesController::class, 'store'])->name('store');
        Route::get('/fetch', [KonfigurasiAksesController::class, 'fetch'])->name('fetch');
    });
});
