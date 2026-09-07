<?php

use Illuminate\Support\Facades\Route;
use Modules\Pdss\Http\Controllers\PdssController;

Route::middleware(['auth', 'tenant.guard'])->prefix('pdss')->name('pdss.')->group(function () {
    Route::get('/', [PdssController::class, 'index'])->name('index');
});
