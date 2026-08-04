<?php

namespace App\Console\Commands;

use App\Support\ProductionOverdueAutoResolver;
use Illuminate\Console\Command;

/**
 * Jalankan auto-timeout produksi yang mangkrak (lihat ProductionOverdueAutoResolver) secara
 * eksplisit lewat cron/scheduler server, alih-alih hanya sebagai efek samping GET /getProduction.
 *
 * Contoh:
 *   php artisan production:resolve-overdue --dry-run
 *   php artisan production:resolve-overdue --days=7
 *   php artisan production:resolve-overdue
 *
 * Jadwalkan di routes/console.php, mis.:
 *   Schedule::command('production:resolve-overdue')->dailyAt('01:00');
 */
class ResolveOverdueProductionsCommand extends Command
{
    protected $signature = 'production:resolve-overdue
                            {--days= : Ambang hari overdue (default 4, sama seperti getProduction())}
                            {--dry-run : Hanya tampilkan yang akan diproses, tanpa ubah DB}';

    protected $description = 'Auto-ACC/tolak produksi pending, dan auto-tolak permintaan batal, yang sudah lewat ambang hari overdue';

    public function handle(ProductionOverdueAutoResolver $resolver): int
    {
        $days = $this->option('days');
        $days = $days !== null && $days !== '' ? (int) $days : null;
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? '[DRY-RUN] Scan produksi overdue…' : 'Memproses produksi overdue…');

        $summary = $resolver->resolveOverdue($days, $dryRun);

        if (count($summary['details']) === 0) {
            $this->info('Tidak ada produksi/permintaan batal yang overdue.');
            return self::SUCCESS;
        }

        $this->table(
            ['production_id', 'production_code', 'action'],
            array_map(
                fn (array $row) => [$row['production_id'], $row['production_code'], $row['action']],
                $summary['details']
            )
        );

        $this->info(sprintf(
            '%s pending: %d dicek (%d approved, %d declined). Cancel request: %d dicek (%d di-timeout).',
            $dryRun ? 'Akan diproses —' : 'Selesai —',
            $summary['pending_checked'],
            $summary['pending_approved'],
            $summary['pending_declined'],
            $summary['cancel_checked'],
            $summary['cancel_timed_out']
        ));

        return self::SUCCESS;
    }
}
