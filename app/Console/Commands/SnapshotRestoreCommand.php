<?php

namespace App\Console\Commands;

use App\Support\SnapshotRegistry;
use Database\Seeders\SnapshotSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Restores a named snapshot (see `snapshot:list`) by pointing SnapshotSeeder
 * at that snapshot's directory. `php artisan db:seed` still works exactly as
 * before and always restores the "default" snapshot — this command is the
 * only way to pick a different one, and is what DeployController's
 * /deploy/seed route calls under the hood.
 */
class SnapshotRestoreCommand extends Command
{
    protected $signature = 'snapshot:restore {name=default : Snapshot name, see: php artisan snapshot:list}';

    protected $description = 'Restore a named database snapshot (truncates + reloads its tables)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $dir = SnapshotRegistry::path($name);

        if ($dir === null) {
            $this->error("Unknown snapshot \"{$name}\".");
            $this->call('snapshot:list');
            return self::FAILURE;
        }

        app()->instance('snapshot.dir', $dir);

        Artisan::call('db:seed', ['--class' => SnapshotSeeder::class, '--force' => true]);
        $this->output->write(Artisan::output());

        return self::SUCCESS;
    }
}
