<?php

namespace App\Console\Commands;

use App\Support\SnapshotDumper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SeedDumpCommand extends Command
{
    protected $signature = 'seed:dump
                            {tables? : Comma separated tables to dump, defaults to every table in the manifest}
                            {--path= : Override the output directory}';

    protected $description = 'Snapshot the current database into versioned JSON seed files (the "default" snapshot)';

    public function handle(): int
    {
        $manifest = require database_path('seeders/snapshot_manifest.php');
        $dir = $this->option('path') ?: database_path('seeders/data');
        File::ensureDirectoryExists($dir);

        $only = $this->argument('tables')
            ? array_map('trim', explode(',', $this->argument('tables')))
            : null;

        $dumper = new SnapshotDumper();

        $tables = $dumper->resolveTables(
            $manifest,
            $only,
            fn ($missing) => $this->warn('Table not found, skipped: ' . $missing)
        );

        if (empty($tables)) {
            $this->error('No tables matched.');
            return self::FAILURE;
        }

        [$meta, $total] = $dumper->dump($tables, $manifest, $dir, function ($table, $count, $cap) {
            $this->line(sprintf(
                '  %-42s %6d rows%s',
                $table,
                $count,
                $cap ? ' (capped at ' . $cap . ')' : ''
            ));
        });

        // Only rewrite entries we actually dumped, so a partial run does not
        // wipe the rest of the index.
        $snapshotPath = $dir . '/_snapshot.json';
        $existing = File::exists($snapshotPath)
            ? (json_decode(File::get($snapshotPath), true) ?: [])
            : [];

        $tableMeta = array_merge($existing['tables'] ?? [], $meta);

        File::put($snapshotPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'database' => $dumper->databaseName(),
            'last_migration' => $dumper->lastMigration(),
            'label' => $existing['label'] ?? null,
            'description' => $existing['description'] ?? null,
            'order' => $dumper->orderTables(array_keys($tableMeta), $manifest),
            'tables' => $tableMeta,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        $this->newLine();
        $this->info(sprintf('Dumped %d tables / %d rows to %s', count($tables), $total, $dir));
        $this->line('Commit the JSON diff to version the snapshot.');

        return self::SUCCESS;
    }
}
