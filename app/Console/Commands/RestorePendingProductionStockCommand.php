<?php

namespace App\Console\Commands;

use App\Support\ProductionPendingStockRestorer;
use Illuminate\Console\Command;

/**
 * Kembalikan stok orphan pada produksi yang masih pending.
 *
 * Contoh:
 *   php artisan production:restore-pending-stock --dry-run
 *   php artisan production:restore-pending-stock --with-history --staff-id=9
 *   php artisan production:restore-pending-stock --code=PR0258 --with-history
 *   php artisan production:restore-pending-stock
 *     (tanpa history — stok dikembalikan, log orphan dihapus)
 */
class RestorePendingProductionStockCommand extends Command
{
    protected $signature = 'production:restore-pending-stock
                            {--code= : Batasi ke satu production_code (mis. PR0258)}
                            {--with-history : Catat log kompensasi di riwayat stok produk/bahan}
                            {--staff-id= : staff_id untuk kolom log (opsional, dipakai jika --with-history)}
                            {--dry-run : Hanya tampilkan yang akan diproses, tanpa ubah DB}';

    protected $description = 'Kembalikan stok yang kepotong di produksi pending (ACC gagal orphan)';

    public function handle(ProductionPendingStockRestorer $restorer): int
    {
        $code = $this->option('code');
        $withHistory = (bool) $this->option('with-history');
        $dryRun = (bool) $this->option('dry-run');
        $staffId = $this->option('staff-id');
        $staffId = $staffId !== null && $staffId !== '' ? (int) $staffId : null;

        $this->info($dryRun ? '[DRY-RUN] Scan produksi pending + log_stocks…' : 'Restore stok produksi pending…');
        $this->line('History log: ' . ($withHistory ? 'ON (--with-history)' : 'OFF'));

        $summary = $restorer->restoreAllPending(
            onlyCode: is_string($code) ? $code : null,
            withHistory: $withHistory,
            staffId: $staffId,
            dryRun: $dryRun,
        );

        if ($summary['productions'] === 0) {
            $this->info('Tidak ada produksi pending yang punya log orphan.');
            return self::SUCCESS;
        }

        $this->table(
            ['production_code', 'logs'],
            array_map(
                fn (array $row) => [$row['production_code'], $row['logs']],
                $summary['details']
            )
        );

        $this->info(sprintf(
            '%s %d produksi, %d log.',
            $dryRun ? 'Akan diproses:' : 'Selesai:',
            $summary['productions'],
            $summary['logs_reverted']
        ));

        if (! $dryRun && $withHistory) {
            $this->comment('Riwayat stok: cek Stok Produk / Stok Bahan Mentah — catatan "Pengembalian stok (perbaikan ACC pending gagal)".');
        }

        return self::SUCCESS;
    }
}
