<?php

namespace Database\Seeders;

use App\Support\SnapshotCodec;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Loads the JSON snapshot written by `php artisan seed:dump`.
 *
 * Designed to survive schema drift in both directions: every row is filtered
 * down to the columns the CURRENT branch actually has, and tables missing on
 * this branch are reported and skipped rather than aborting the run. That is
 * what lets one committed snapshot restore a dev environment from any branch.
 *
 * Which snapshot: `php artisan db:seed` (no binding set) always restores the
 * "default" snapshot at database/seeders/data, unchanged from before. To
 * restore a different named snapshot (database/seeders/snapshots/<name>),
 * go through `php artisan snapshot:restore <name>` instead of calling this
 * seeder directly — that command binds 'snapshot.dir' before invoking it.
 */
class SnapshotSeeder extends Seeder
{
    private const CHUNK = 500;

    public function run(): void
    {
        $dir = app()->bound('snapshot.dir') ? app('snapshot.dir') : database_path('seeders/data');
        $indexPath = $dir . '/_snapshot.json';

        if (! File::exists($indexPath)) {
            $this->command->error('No snapshot found. Run: php artisan seed:dump');
            return;
        }

        $index = json_decode(File::get($indexPath), true);
        $tables = $index['order'] ?? [];

        $this->command->line('Snapshot taken ' . ($index['generated_at'] ?? 'unknown'));
        $this->command->newLine();

        Schema::disableForeignKeyConstraints();

        // Reverse order on the way out so children clear before their parents.
        foreach (array_reverse($tables) as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->truncate();
            }
        }

        $missing = [];
        $dropped = [];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $missing[] = $table;
                continue;
            }

            $result = $this->loadTable($table, $dir);

            if ($result === null) {
                continue;
            }

            [$count, $droppedColumns] = $result;

            if ($droppedColumns) {
                $dropped[$table] = $droppedColumns;
            }

            $this->command->line(sprintf('  %-42s %6d rows', $table, $count));
        }

        Schema::enableForeignKeyConstraints();

        $this->report($missing, $dropped);
    }

    /**
     * @return array{0: int, 1: array<int, string>}|null
     */
    private function loadTable(string $table, string $dir): ?array
    {
        $path = $dir . '/' . $table . '.json';

        if (! File::exists($path)) {
            return null;
        }

        $rows = json_decode(File::get($path), true) ?: [];

        if (empty($rows)) {
            return [0, []];
        }

        $live = array_flip(Schema::getColumnListing($table));
        $dropped = array_values(array_diff(array_keys($rows[0]), array_keys($live)));

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $payload = [];

            foreach ($chunk as $row) {
                // Columns the snapshot has but this branch does not are dropped;
                // columns this branch added since the snapshot fall back to their
                // schema default.
                $row = array_intersect_key($row, $live);
                $payload[] = array_map([SnapshotCodec::class, 'decode'], $row);
            }

            DB::table($table)->insert($payload);
        }

        $this->resetAutoIncrement($table);

        return [count($rows), $dropped];
    }

    /**
     * Primary keys are written literally so every (logical) foreign key still
     * resolves. The counter is then pushed past the highest inserted id rather
     * than trusting the engine to infer it.
     */
    private function resetAutoIncrement(string $table): void
    {
        $column = DB::selectOne(
            'SELECT COLUMN_NAME AS name FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND EXTRA LIKE "%auto_increment%"',
            [DB::connection()->getDatabaseName(), $table]
        );

        if (! $column) {
            return;
        }

        $next = (int) DB::table($table)->max($column->name) + 1;

        DB::statement('ALTER TABLE `' . $table . '` AUTO_INCREMENT = ' . $next);
    }

    private function report(array $missing, array $dropped): void
    {
        if (! $missing && ! $dropped) {
            return;
        }

        $this->command->newLine();
        $this->command->warn('Schema drift between this branch and the snapshot:');

        foreach ($missing as $table) {
            $this->command->line('  - table missing here, not seeded: ' . $table);
        }

        foreach ($dropped as $table => $columns) {
            $this->command->line('  - ' . $table . ': columns not on this branch, dropped: ' . implode(', ', $columns));
        }
    }
}
