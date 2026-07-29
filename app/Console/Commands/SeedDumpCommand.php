<?php

namespace App\Console\Commands;

use App\Support\SnapshotCodec;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SeedDumpCommand extends Command
{
    protected $signature = 'seed:dump
                            {tables? : Comma separated tables to dump, defaults to every table in the manifest}
                            {--path= : Override the output directory}';

    protected $description = 'Snapshot the current database into versioned JSON seed files';

    public function handle(): int
    {
        $manifest = require database_path('seeders/snapshot_manifest.php');
        $dir = $this->option('path') ?: database_path('seeders/data');
        File::ensureDirectoryExists($dir);

        $only = $this->argument('tables')
            ? array_map('trim', explode(',', $this->argument('tables')))
            : null;

        $tables = $this->resolveTables($manifest, $only);

        if (empty($tables)) {
            $this->error('No tables matched.');
            return self::FAILURE;
        }

        $meta = [];
        $total = 0;

        foreach ($tables as $table) {
            $cap = $manifest['cap'][$table] ?? null;
            [$rows, $columns] = $this->readTable($table, $cap);

            File::put(
                $dir . '/' . $table . '.json',
                json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n"
            );

            $meta[$table] = [
                'rows' => count($rows),
                'columns' => $columns,
                'capped' => $cap,
            ];
            $total += count($rows);

            $this->line(sprintf(
                '  %-42s %6d rows%s',
                $table,
                count($rows),
                $cap ? ' (capped at ' . $cap . ')' : ''
            ));
        }

        // Only rewrite entries we actually dumped, so a partial run does not
        // wipe the rest of the index.
        $snapshotPath = $dir . '/_snapshot.json';
        $existing = File::exists($snapshotPath)
            ? (json_decode(File::get($snapshotPath), true) ?: [])
            : [];

        $tableMeta = array_merge($existing['tables'] ?? [], $meta);

        File::put($snapshotPath, json_encode([
            'generated_at' => now()->toIso8601String(),
            'database' => DB::connection()->getDatabaseName(),
            'last_migration' => DB::table('migrations')->orderByDesc('id')->value('migration'),
            'order' => $this->orderTables(array_keys($tableMeta), $manifest),
            'tables' => $tableMeta,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

        $this->newLine();
        $this->info(sprintf('Dumped %d tables / %d rows to %s', count($tables), $total, $dir));
        $this->line('Commit the JSON diff to version the snapshot.');

        return self::SUCCESS;
    }

    /**
     * Every base table in the current database, minus the manifest exclusions.
     */
    private function resolveTables(array $manifest, ?array $only): array
    {
        $names = collect(DB::select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE"',
            [DB::connection()->getDatabaseName()]
        ))->pluck('name')->all();

        $skip = $manifest['skip'] ?? [];
        $patterns = $manifest['skip_patterns'] ?? [];

        $names = array_filter($names, function ($name) use ($skip, $patterns, $only) {
            if ($only !== null) {
                return in_array($name, $only, true);
            }
            if (in_array($name, $skip, true)) {
                return false;
            }
            foreach ($patterns as $pattern) {
                if (fnmatch($pattern, $name)) {
                    return false;
                }
            }
            return true;
        });

        if ($only !== null) {
            foreach (array_diff($only, $names) as $missing) {
                $this->warn('Table not found, skipped: ' . $missing);
            }
        }

        return $this->orderTables($names, $manifest);
    }

    /**
     * Manifest priority first, then alphabetical, so dumps are deterministic
     * and the git diff stays readable across runs.
     */
    private function orderTables(array $names, array $manifest): array
    {
        $priority = $manifest['priority'] ?? [];
        $names = array_values(array_unique($names));
        sort($names);

        $ordered = array_values(array_intersect($priority, $names));

        return array_merge($ordered, array_values(array_diff($names, $ordered)));
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function readTable(string $table, ?int $cap): array
    {
        $columns = Schema::getColumnListing($table);
        $sortColumn = $this->sortColumn($table) ?? $columns[0];

        $query = DB::table($table);

        if ($cap !== null) {
            // Newest N rows, then flipped back so the file stays ascending.
            $rows = $query->orderByDesc($sortColumn)->limit($cap)->get()->reverse()->values();
        } else {
            $rows = $query->orderBy($sortColumn)->get();
        }

        $rows = $rows->map(fn ($row) => $this->encodeRow((array) $row))->all();

        return [$rows, $columns];
    }

    private function sortColumn(string $table): ?string
    {
        $pk = DB::select(
            'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = "PRI"
             ORDER BY ORDINAL_POSITION',
            [DB::connection()->getDatabaseName(), $table]
        );

        return $pk[0]->name ?? null;
    }

    /**
     * json_encode() dies on invalid UTF-8, which legacy rows do contain. Those
     * values are stashed base64 behind a marker and restored by SnapshotSeeder.
     */
    private function encodeRow(array $row): array
    {
        foreach ($row as $key => $value) {
            if (is_string($value) && ! mb_check_encoding($value, 'UTF-8')) {
                $row[$key] = SnapshotCodec::BINARY_PREFIX . base64_encode($value);
            }
        }

        return $row;
    }
}
