<?php

namespace App\Support;

use Illuminate\Support\Facades\File;

/**
 * Enumerates the database snapshots available to restore: the original
 * `database/seeders/data` (name "default") plus any named datasets under
 * `database/seeders/snapshots/<name>/`, each produced by `seed:dump` or
 * `snapshot:import-sql`.
 *
 * This is the only place a snapshot "name" is trusted to become a filesystem
 * path. Every caller that takes a name from outside (DeployController,
 * `snapshot:restore`) resolves it through list()/path() here, whose results
 * only ever come from basename()-ing real, already-existing directories — so
 * an unknown or attacker-supplied name (e.g. `../../.env`) can never resolve
 * to anything and never escapes these two directories.
 */
class SnapshotRegistry
{
    public const DEFAULT_NAME = 'default';

    public static function defaultDir(): string
    {
        return database_path('seeders/data');
    }

    public static function snapshotsDir(): string
    {
        return database_path('seeders/snapshots');
    }

    /**
     * @return array<int, array{
     *     name: string, dir: string, label: ?string, description: ?string,
     *     generated_at: ?string, database: ?string, source_sql_file: ?string,
     *     tables: int, rows: int
     * }>
     */
    public static function list(): array
    {
        $out = [];

        if ($meta = self::readIndex(self::defaultDir())) {
            $out[] = self::describe(self::DEFAULT_NAME, self::defaultDir(), $meta);
        }

        $dirs = File::exists(self::snapshotsDir()) ? File::directories(self::snapshotsDir()) : [];

        foreach ($dirs as $dir) {
            $name = basename($dir);
            if ($meta = self::readIndex($dir)) {
                $out[] = self::describe($name, $dir, $meta);
            }
        }

        return $out;
    }

    /**
     * Resolves a snapshot name to its directory, or null if it is not a
     * known, valid snapshot. Callers must never build a path from a raw
     * name themselves — always go through this.
     */
    public static function path(string $name): ?string
    {
        foreach (self::list() as $snapshot) {
            if ($snapshot['name'] === $name) {
                return $snapshot['dir'];
            }
        }

        return null;
    }

    private static function readIndex(string $dir): ?array
    {
        $path = $dir . '/_snapshot.json';

        if (! File::exists($path)) {
            return null;
        }

        return json_decode(File::get($path), true) ?: null;
    }

    private static function describe(string $name, string $dir, array $meta): array
    {
        $tables = $meta['tables'] ?? [];

        return [
            'name' => $name,
            'dir' => $dir,
            'label' => $meta['label'] ?? null,
            'description' => $meta['description'] ?? null,
            'generated_at' => $meta['generated_at'] ?? null,
            'database' => $meta['database'] ?? null,
            'source_sql_file' => $meta['source_sql_file'] ?? null,
            'tables' => count($tables),
            'rows' => (int) array_sum(array_column($tables, 'rows')),
        ];
    }
}
