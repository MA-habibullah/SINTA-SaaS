<?php

use Illuminate\Support\Facades\Route;
use Modules\Sistem\Http\Controllers\ActivityLogController;
use Modules\Sistem\Http\Controllers\ActiveSessionController;
use Modules\Sistem\Http\Controllers\QueueController;
use Modules\Sistem\Http\Controllers\ErrorMonitorController;
use Modules\Sistem\Http\Controllers\ServerMonitorController;
use Modules\Sistem\Http\Controllers\DocumentScannerController;

Route::middleware(['web', 'auth', 'tenant.guard'])->group(function () {
    Route::get('/sistem/activity-logs', [ActivityLogController::class, 'index'])->name('sistem.activity-logs');
    Route::get('/sistem/activity-logs/data', [ActivityLogController::class, 'fetchData'])->name('sistem.activity-logs.data');
    Route::post('/sistem/activity-logs/delete', [ActivityLogController::class, 'deleteLogs'])->name('sistem.activity-logs.delete');
    Route::get('/sistem/active-sessions', [ActiveSessionController::class, 'index'])->name('sistem.active-sessions');
    
    // Antrean / Queue Monitoring
    Route::get('/sistem/antrean', [QueueController::class, 'index'])->name('sistem.antrean');
    Route::get('/sistem/antrean/data', [QueueController::class, 'fetchData'])->name('sistem.antrean.data');
    Route::post('/sistem/antrean/dispatch', [QueueController::class, 'dispatchJob'])->name('sistem.antrean.dispatch');
    Route::post('/sistem/antrean/retry', [QueueController::class, 'retryJob'])->name('sistem.antrean.retry');
    Route::post('/sistem/antrean/delete', [QueueController::class, 'deleteJob'])->name('sistem.antrean.delete');
    Route::post('/sistem/antrean/run-worker', [QueueController::class, 'runWorker'])->name('sistem.antrean.run-worker');

    // Error Monitor / System Debugger
    Route::get('/sistem/error-monitor', [ErrorMonitorController::class, 'index'])->name('sistem.error-monitor');
    Route::get('/sistem/error-monitor/data', [ErrorMonitorController::class, 'fetchData'])->name('sistem.error-monitor.data');
    Route::post('/sistem/error-monitor/clear', [ErrorMonitorController::class, 'clearAll'])->name('sistem.error-monitor.clear');
    Route::post('/sistem/error-monitor/delete', [ErrorMonitorController::class, 'deleteOne'])->name('sistem.error-monitor.delete');

    // Server & Resource Monitor
    Route::get('/sistem/server-monitor', [ServerMonitorController::class, 'index'])->name('sistem.server-monitor');
    Route::get('/sistem/server-monitor/data', [ServerMonitorController::class, 'fetchData'])->name('sistem.server-monitor.data');
    Route::post('/sistem/server-monitor/save-network', [ServerMonitorController::class, 'saveNetworkConfig'])->name('sistem.server-monitor.save-network');
    Route::post('/sistem/server-monitor/update-server', [ServerMonitorController::class, 'updateServer'])->name('sistem.server-monitor.update-server');

    // Document Scanner / AeroScan Pemindai Dokumen
    Route::get('/sistem/document-scanner', [DocumentScannerController::class, 'index'])->name('sistem.document-scanner');
    Route::post('/sistem/document-scanner/save-pdf', [DocumentScannerController::class, 'saveScannedPdf'])->name('sistem.document-scanner.save-pdf');
});

// Client Error Logger API (Unauthenticated/Global Client Error Tracker)
Route::post('/api/v1/error-monitor/log-client', [ErrorMonitorController::class, 'logClientError'])->name('api.error-monitor.log-client');


