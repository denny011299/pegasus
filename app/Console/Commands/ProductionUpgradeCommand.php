<?php

namespace App\Console\Commands;

use Database\Seeders\ProductionMultiWarehouseSeeder;
use Database\Seeders\RoleWarehouseAccessSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProductionUpgradeCommand extends Command
{
    protected $signature = 'pegasus:production-upgrade
                            {--sql : Jalankan file SQL in-place (bukan seeder PHP)}
                            {--file= : Path SQL custom (default: pegasuso_production_upgrade_in_place.sql)}
                            {--skip-role : Lewati RoleWarehouseAccessSeeder setelah upgrade}';

    protected $description = 'Upgrade DB production ke fase2 multi-gudang (seeder atau SQL)';

    public function handle(): int
    {
        if ($this->option('sql')) {
            return $this->runSqlUpgrade();
        }

        $this->info('Mode seeder (default)...');
        $this->call('db:seed', ['--class' => ProductionMultiWarehouseSeeder::class]);

        return self::SUCCESS;
    }

    private function runSqlUpgrade(): int
    {
        $path = $this->option('file')
            ?: database_path('sql/pegasuso_production_upgrade_in_place.sql');

        if (!is_file($path)) {
            $this->error("File SQL tidak ditemukan: {$path}");
            $this->line('Generate dulu: php docs/scripts/build_production_upgrade_in_place_sql.php');

            return self::FAILURE;
        }

        $this->info("Mode SQL: {$path}");
        $this->warn('Pastikan sudah backup DB sebelum melanjutkan.');

        if (!$this->confirm('Lanjutkan upgrade SQL?', true)) {
            $this->comment('Dibatalkan.');

            return self::SUCCESS;
        }

        DB::unprepared(File::get($path));
        $this->info('SQL upgrade selesai.');

        if (!$this->option('skip-role')) {
            $this->call('db:seed', ['--class' => RoleWarehouseAccessSeeder::class]);
        }

        $this->newLine();
        $this->info('Upgrade SQL selesai. Verifikasi manual atau jalankan:');
        $this->line('  php docs/scripts/verify_production_import.php [dump.sql] [database]');

        return self::SUCCESS;
    }
}
