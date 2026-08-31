<?php

namespace App\Console\Commands;

use App\Support\AppMaintenance;
use Illuminate\Console\Command;

class AppMaintenanceCommand extends Command
{
    protected $signature = 'app:maintenance {action : on|off|status}';

    protected $description = 'Toggle maintenance mode (logout semua user, blokir login & akses)';

    public function handle(): int
    {
        $action = strtolower(trim((string) $this->argument('action')));

        if ($action === 'status') {
            $this->line('Maintenance: '.(AppMaintenance::enabled() ? 'ON' : 'OFF'));
            $this->line('ENV APP_MAINTENANCE_MODE: '.(filter_var(config('maintenance.enabled'), FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'));
            $this->line('File flag: '.(AppMaintenance::fileFlagEnabled() ? 'ada' : 'tidak ada'));

            return self::SUCCESS;
        }

        if ($action === 'on') {
            AppMaintenance::enableFileFlag();
            $this->info('Maintenance ON — semua pengguna akan di-logout dan tidak bisa akses.');

            return self::SUCCESS;
        }

        if ($action === 'off') {
            AppMaintenance::disableFileFlag();
            $this->info('Maintenance OFF (file flag dihapus). Matikan juga APP_MAINTENANCE_MODE di .env bila masih true.');

            return self::SUCCESS;
        }

        $this->error('Action tidak dikenal. Pakai: on, off, atau status.');

        return self::FAILURE;
    }
}
