<?php

use Illuminate\Support\Facades\Route;
use Modules\Sistem\Http\Controllers\ActivityLogController;

Route::middleware(['auth', 'tenant.guard'])->group(function () {
    Route::get('/sistem/activity-logs', [ActivityLogController::class, 'index'])->name('sistem.activity-logs');
});
