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

        if ($dryRun) {
            // Ditambahkan: dry-run tidak pernah benar-benar memanggil accProduction(), jadi TIDAK
            // TAHU apakah tiap item bakal berhasil di-approve atau malah gagal (mis. stok bahan
            // kurang) — dulu baris ini tetap menampilkan "(0 approved, 0 declined)" seolah-olah itu
            // hasil nyata, padahal tabel di atas sudah benar menampilkan "would auto-acc". Sekarang
            // cuma melaporkan jumlah yang DITEMUKAN overdue, bukan pura-pura tahu hasil approve-nya.
            $this->info(sprintf(
                'Akan diproses — pending: %d ditemukan overdue. Cancel request: %d ditemukan overdue. Lihat kolom "action" di atas untuk detail per baris; jalankan tanpa --dry-run untuk memproses beneran.',
                $summary['pending_checked'],
                $summary['cancel_checked']
            ));
        } else {
            $this->info(sprintf(
                'Selesai — pending: %d dicek (%d approved, %d declined). Cancel request: %d dicek (%d di-timeout).',
                $summary['pending_checked'],
                $summary['pending_approved'],
                $summary['pending_declined'],
                $summary['cancel_checked'],
                $summary['cancel_timed_out']
            ));
        }

        return self::SUCCESS;
    }
}
