<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Core "read these tables, write them as JSON" mechanics, shared by
 * `seed:dump` (dumps the app's own default connection) and
 * `snapshot:import-sql` (dumps a scratch connection an .sql file was just
 * imported into). Both produce the same per-table JSON shape that
 * SnapshotSeeder / SnapshotCodec already know how to read back.
 *
 * Every read goes through DB::connection($this->connection) / Schema::
 * connection($this->connection) — null means "the app's default connection",
 * exactly like the facades already behave, so seed:dump's behavior is
 * unchanged by this class existing.
 */
class SnapshotDumper
{
    public function __construct(private readonly ?string $connection = null)
    {
    }

    public function databaseName(): string
    {
        return DB::connection($this->connection)->getDatabaseName();
    }

    public function lastMigration(): ?string
    {
        return DB::connection($this->connection)->table('migrations')
            ->orderByDesc('id')->value('migration');
    }

    /**
     * Every base table in the connection's database, minus the manifest
     * exclusions (or filtered down to $only), in write order.
     *
     * @return array<int, string>
     */
    public function resolveTables(array $manifest, ?array $only, ?callable $onMissing = null): array
    {
        $names = collect(DB::connection($this->connection)->select(
            'SELECT TABLE_NAME AS name FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = "BASE TABLE"',
            [$this->databaseName()]
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

        if ($only !== null && $onMissing) {
            foreach (array_diff($only, $names) as $missing) {
                $onMissing($missing);
            }
        }

        return $this->orderTables($names, $manifest);
    }

    /**
     * Manifest priority first, then alphabetical, so dumps are deterministic
     * and the git diff stays readable across runs.
     *
     * @return array<int, string>
     */
    public function orderTables(array $names, array $manifest): array
    {
        $priority = $manifest['priority'] ?? [];
        $names = array_values(array_unique($names));
        sort($names);

        $ordered = array_values(array_intersect($priority, $names));

        return array_merge($ordered, array_values(array_diff($names, $ordered)));
    }

    /**
     * Dumps already-resolved tables to $dir/<table>.json. Does not write the
     * _snapshot.json index — callers do that, since what belongs in it
     * (label, description, partial-run merge...) differs between seed:dump
     * and snapshot:import-sql.
     *
     * @param array<int, string> $tables
     * @return array{0: array<string, array{rows: int, columns: array<int,string>, capped: ?int}>, 1: int}
     */
    public function dump(array $tables, array $manifest, string $dir, ?callable $onTable = null): array
    {
        File::ensureDirectoryExists($dir);

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

            if ($onTable) {
                $onTable($table, count($rows), $cap);
            }
        }

        return [$meta, $total];
    }

    /**
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, string>}
     */
    private function readTable(string $table, ?int $cap): array
    {
        $columns = Schema::connection($this->connection)->getColumnListing($table);
        $sortColumn = $this->sortColumn($table) ?? $columns[0];

        $query = DB::connection($this->connection)->table($table);

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
        $pk = DB::connection($this->connection)->select(
            'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = "PRI"
             ORDER BY ORDINAL_POSITION',
            [$this->databaseName(), $table]
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
