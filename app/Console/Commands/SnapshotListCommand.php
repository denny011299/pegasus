<?php

namespace App\Console\Commands;

use App\Support\SnapshotRegistry;
use Illuminate\Console\Command;

/**
 * Lists every snapshot `snapshot:restore` and the deploy console can pick
 * from: the "default" one at database/seeders/data, plus any named ones
 * under database/seeders/snapshots/<name> (made with `snapshot:import-sql`
 * or `seed:dump --path=`).
 */
class SnapshotListCommand extends Command
{
    protected $signature = 'snapshot:list';

    protected $description = 'List available database snapshots (default + named)';

    public function handle(): int
    {
        $snapshots = SnapshotRegistry::list();

        if (empty($snapshots)) {
            $this->warn('No snapshots found.');
            return self::SUCCESS;
        }

        $this->table(
            ['Name', 'Label', 'Generated at', 'Tables', 'Rows', 'Source'],
            collect($snapshots)->map(fn ($s) => [
                $s['name'],
                $s['label'] ?? '-',
                $s['generated_at'] ?? '-',
                $s['tables'],
                $s['rows'],
                $s['source_sql_file'] ?? '-',
            ])->all()
        );

        return self::SUCCESS;
    }
}
