<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Restores the committed snapshot in database/seeders/data. Regenerate it
     * with `php artisan seed:dump` and commit the JSON diff.
     */
    public function run(): void
    {
        $this->call(SnapshotSeeder::class);
    }
}
