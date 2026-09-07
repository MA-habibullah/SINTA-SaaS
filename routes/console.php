<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console & Periodic Task Schedules
|--------------------------------------------------------------------------
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Otomatisasi generate invoice SPP bulanan setiap tanggal 1 pukul 01:00 dini hari
Schedule::command('sinta:generate-monthly-invoices')->monthlyOn(1, '01:00');

// 2. Audit dan sinkronisasi pemakaian kuota storage per sekolah setiap malam pukul 02:00
Schedule::command('sinta:recalculate-storage')->dailyAt('02:00');

// 3. Eksekusi antrean worker jika menggunakan sync/cron
Schedule::command('queue:work --stop-when-empty --tries=3')->everyFiveMinutes();
