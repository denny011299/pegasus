<?php

/*
|--------------------------------------------------------------------------
| Database snapshot manifest
|--------------------------------------------------------------------------
|
| Drives `php artisan seed:dump` (writes database/seeders/data/*.json) and
| SnapshotSeeder (loads them back). This is an OPT-OUT list on purpose: any
| table not skipped below is dumped in full, so a table added by a future
| migration lands in the seed set automatically on the next dump.
|
*/

return [

    /*
     | Never dumped. Runtime scaffolding, auth throwaways, and the migrations
     | ledger itself (letting the seeder rewrite that would desync `migrate`).
     */
    'skip' => [
        'migrations',
        'sessions',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'password_reset_tokens',
    ],

    /*
     | fnmatch() patterns, matched against the table name. Covers the manual
     | backup_*_<date> tables that get left behind by data fixes.
     */
    'skip_patterns' => [
        'backup_*',
    ],

    /*
     | Append-only / derived tables where the full history is noise. Only the
     | newest N rows are dumped (ordered by primary key). Everything else is
     | dumped whole.
     */
    'cap' => [
        'log_stocks' => 1000,
        'dashboard_change_logs' => 500,
        'external_api_request_logs' => 200,
    ],

    /*
     | Loaded (and truncated in reverse) before everything else. This DB has no
     | real FK constraints, so ordering is not enforced by MySQL today — keeping
     | masters first means the data still makes sense if constraints are ever
     | added, and keeps the dump output readable. Unlisted tables follow in
     | alphabetical order.
     */
    'priority' => [
        'roles',
        'staffs',
        'users',
        'settings',
        'pengaturans',
        'provinces',
        'cities',
        'districts',
        'areas',
        'banks',
        'units',
        'variants',
        'categories',
        'cash_categories',
        'customers',
        'suppliers',
        'products',
        'product_variants',
        'supplies',
        'supplies_variants',
        'boms',
        'bom_details',
    ],
];
