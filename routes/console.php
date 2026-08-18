<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-timeout produksi yang mangkrak (lihat ProductionOverdueAutoResolver) — dijalankan sekali
// sehari saat traffic rendah. Server masih perlu satu crontab entry yang menjalankan
// `php artisan schedule:run` tiap menit; Laravel yang menentukan kapan job ini sendiri due.
Schedule::command('production:resolve-overdue')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->onOneServer();
