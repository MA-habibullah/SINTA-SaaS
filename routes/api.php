<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Keuangan\Http\Controllers\PaymentWebhookController;

/*
|--------------------------------------------------------------------------
| API Central Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Payment Gateway Webhooks (Publik & Terenkripsi Signature)
Route::post('/webhook/midtrans', [PaymentWebhookController::class, 'handleMidtransCallback'])->name('webhook.midtrans');
