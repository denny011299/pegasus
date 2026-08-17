<?php

namespace App\Console\Commands;

use App\Support\SnapshotRegistry;
use Database\Seeders\SnapshotSeeder;
use Illuminate\Console\Command;

/**
 * Restores a named snapshot (see `snapshot:list`) by pointing SnapshotSeeder
 * at that snapshot's directory. `php artisan db:seed` still works exactly as
 * before and always restores the "default" snapshot — this command is the
 * only way to pick a different one, and is what DeployController's
 * /deploy/seed route calls under the hood.
 *
 * Deliberately uses $this->call() (Command::call(), which runs the target
 * command against $this->output directly) rather than the Artisan facade's
 * Artisan::call(). The facade version stashes its output in a shared
 * Application::$lastOutput BufferedOutput and immediately fetch()es (which
 * drains it) when relayed here — fine in isolation, but DeployController
 * itself reaches this command THROUGH Artisan::call(), so the nested facade
 * call overwrites and drains that same shared buffer before the controller
 * ever reads Artisan::output(), producing an empty response body (blank
 * page) even though the restore itself succeeded. $this->call() sidesteps
 * this entirely by writing straight into the outer command's own output.
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

        return $this->call('db:seed', ['--class' => SnapshotSeeder::class, '--force' => true]);
    }
}
