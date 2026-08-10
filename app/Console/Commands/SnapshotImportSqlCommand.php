<?php

namespace App\Console\Commands;

use App\Support\SnapshotDumper;
use App\Support\SnapshotRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Turns a full mysqldump .sql file into a new named snapshot under
 * database/seeders/snapshots/<name>/, in the same per-table JSON shape
 * `seed:dump` already produces — so `snapshot:restore <name>` (and the
 * deploy console's snapshot picker) can load it exactly like the "default"
 * one.
 *
 * The dump file is never read into PHP or into this conversation: it is
 * imported by shelling out to the `mysql` client into a disposable scratch
 * database (created for this run, dropped afterwards unless --keep-database
 * is passed), then read back out table-by-table the same way `seed:dump`
 * reads the app's own database. That is what makes a multi-GB dump handled
 * at all — nothing about its contents is ever loaded into memory at once or
 * inspected by hand.
 *
 * Local/dev tool only, same as `seed:dump` — NOT reachable from the deploy
 * console or any HTTP route. It needs a `mysql` client binary and CREATE
 * DATABASE privilege on the configured DB connection, neither of which the
 * shared-hosting target of DeployController has. Run this locally, review
 * the generated JSON diff, then commit database/seeders/snapshots/<name>/ —
 * only the already-committed result is what the deploy console picks from.
 */
class SnapshotImportSqlCommand extends Command
{
    protected $signature = 'snapshot:import-sql
                            {path? : Path to the .sql dump file}
                            {name? : Snapshot name to save as (letters/digits/-/_ only)}
                            {--label= : Short human label shown in the deploy console}
                            {--description= : Longer note, e.g. where this dump came from}
                            {--database= : Scratch database name (default: snapshot_import_<name>)}
                            {--mysql-bin= : Path to the mysql client (default: MYSQL_CLI_PATH env, then common install paths)}
                            {--keep-database : Do not drop the scratch database when done (for debugging)}';

    protected $description = 'Import a full .sql dump into a scratch database and save it as a named, restorable snapshot';

    /** Probed in order when --mysql-bin/MYSQL_CLI_PATH is not set. */
    private const COMMON_MYSQL_BINS = [
        'mysql',
        '/opt/homebrew/bin/mysql',
        '/opt/homebrew/opt/mysql-client/bin/mysql',
        '/usr/local/mysql/bin/mysql',
        '/usr/local/opt/mysql-client/bin/mysql',
    ];

    public function handle(): int
    {
        $path = trim((string) ($this->argument('path') ?: $this->ask('Path to the .sql dump file')));

        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            $this->error("SQL file not found or not readable: {$path}");
            return self::FAILURE;
        }

        $name = $this->resolveName();

        if ($name === null) {
            return self::FAILURE;
        }

        $dir = SnapshotRegistry::snapshotsDir() . '/' . $name;

        if (File::exists($dir) && ! $this->confirm("Snapshot \"{$name}\" already exists and will be overwritten. Continue?")) {
            return self::FAILURE;
        }

        $bin = $this->resolveMysqlBin();

        if ($bin === null) {
            $this->error('No working mysql client found. Pass --mysql-bin=/path/to/mysql or set MYSQL_CLI_PATH in .env.');
            return self::FAILURE;
        }

        $connectionName = config('database.default');
        $config = config('database.connections.' . $connectionName);
        $scratch = $this->scratchDatabaseName($name);

        $this->info(sprintf('Importing "%s" (%s) into scratch database `%s` ...', basename($path), $this->humanSize(filesize($path)), $scratch));

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$scratch}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        try {
            $this->importDump($bin, $config, $scratch, $path);
            $this->registerScratchConnection($config, $scratch);

            return $this->dumpScratchDatabase($dir, $name, $path);
        } catch (Throwable $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return self::FAILURE;
        } finally {
            DB::purge('snapshot_import');

            if ($this->option('keep-database')) {
                $this->warn("Scratch database `{$scratch}` left in place (--keep-database).");
            } else {
                DB::statement("DROP DATABASE IF EXISTS `{$scratch}`");
            }
        }
    }

    private function resolveName(): ?string
    {
        $raw = $this->argument('name') ?: $this->ask('Snapshot name (e.g. "client-acme-2026-08")');
        $name = Str::slug((string) $raw, '_');

        if ($name === '' || ! preg_match('/^[a-z0-9][a-z0-9_-]{0,62}$/', $name)) {
            $this->error('Snapshot name must be letters/digits/-/_ (after slugifying), got: ' . var_export($raw, true));
            return null;
        }

        if ($name === SnapshotRegistry::DEFAULT_NAME) {
            $this->error('"default" is reserved for database/seeders/data — pick another name.');
            return null;
        }

        return $name;
    }

    private function resolveMysqlBin(): ?string
    {
        $candidates = array_filter([
            $this->option('mysql-bin'),
            env('MYSQL_CLI_PATH'),
            ...self::COMMON_MYSQL_BINS,
            ...glob('/Users/Shared/DBngin/mysql/*/bin/mysql') ?: [], // DBngin: versioned path, no fixed location
        ]);

        foreach ($candidates as $bin) {
            if ($this->mysqlBinWorks($bin)) {
                return $bin;
            }
        }

        return null;
    }

    private function mysqlBinWorks(string $bin): bool
    {
        try {
            $process = new Process([$bin, '--version']);
            $process->run();

            return $process->isSuccessful();
        } catch (Throwable) {
            return false;
        }
    }

    private function scratchDatabaseName(string $name): string
    {
        if ($custom = $this->option('database')) {
            return $custom;
        }

        return substr(preg_replace('/[^a-zA-Z0-9_]/', '_', 'snapshot_import_' . $name), 0, 64);
    }

    private function importDump(string $bin, array $config, string $scratch, string $path): void
    {
        $command = [$bin, '-h', $config['host'], '-P', (string) $config['port'], '-u', $config['username']];

        if (! empty($config['unix_socket'])) {
            $command[] = '--socket=' . $config['unix_socket'];
        }

        $command[] = $scratch;

        $process = new Process($command);
        $process->setTimeout(null); // dumps can legitimately take a long time

        if (! empty($config['password'])) {
            $process->setEnv(['MYSQL_PWD' => $config['password']]); // avoids the password showing up in `ps`
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException("Could not open {$path} for reading.");
        }

        try {
            $process->setInput($handle); // streamed, never loaded into memory whole
            $process->run();
        } finally {
            fclose($handle);
        }

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysql import failed: ' . trim($process->getErrorOutput()));
        }
    }

    private function registerScratchConnection(array $config, string $scratch): void
    {
        config(['database.connections.snapshot_import' => array_merge($config, ['database' => $scratch])]);
        DB::purge('snapshot_import');
    }

    private function dumpScratchDatabase(string $dir, string $name, string $sourcePath): int
    {
        $manifest = require database_path('seeders/snapshot_manifest.php');
        $dumper = new SnapshotDumper('snapshot_import');

        $tables = $dumper->resolveTables($manifest, null, fn ($missing) => $this->warn('Table not found, skipped: ' . $missing));

        if (empty($tables)) {
            $this->error('No tables found in the imported dump — is it really a full database dump?');
            return self::FAILURE;
        }

        [$meta, $total] = $dumper->dump($tables, $manifest, $dir, function ($table, $count, $cap) {
            $this->line(sprintf('  %-42s %6d rows%s', $table, $count, $cap ? ' (capped at ' . $cap . ')' : ''));
        });

        File::put($dir . '/_snapshot.json', json_encode([
            'generated_at' => now()->toIso8601String(),
            'database' => $dumper->databaseName(),
            'last_migration' => $dumper->lastMigration(),
            'label' => $this->option('label'),
            'description' => $this->option('description'),
            'source_sql_file' => basename($sourcePath),
            'order' => $dumper->orderTables(array_keys($meta), $manifest),
            'tables' => $meta,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        $this->newLine();
        $this->info(sprintf('Saved snapshot "%s": %d tables / %d rows to %s', $name, count($tables), $total, $dir));
        $this->line('Review the JSON diff, then commit database/seeders/snapshots/' . $name . '/.');
        $this->line("Restore it later with: php artisan snapshot:restore {$name}");

        return self::SUCCESS;
    }

    private function humanSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . $units[$i];
    }
}
