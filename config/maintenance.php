<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Maintenance mode (toggle)
    |--------------------------------------------------------------------------
    |
    | Aktif jika APP_MAINTENANCE_MODE=true di .env ATAU file flag ada
    | (php artisan app:maintenance on). Semua pengguna di-logout dan tidak
    | bisa login sampai dimatikan lagi.
    |
    */

    'enabled' => env('APP_MAINTENANCE_MODE', false),

    'message' => env(
        'APP_MAINTENANCE_MESSAGE',
        'Sistem sedang dalam pemeliharaan. Silakan coba lagi beberapa saat lagi.'
    ),

    'file' => storage_path('framework/app-maintenance.on'),

];
