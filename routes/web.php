<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes (Central & Redirects)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/up', function () {
    return response()->json(['status' => 'healthy', 'timestamp' => now()->toIso8601String()]);
});
