<?php

namespace App\Console\Commands;

use App\Support\ProductionPendingStockRestorer;
use Illuminate\Console\Command;

/**
 * Hapus "lock" log potongan bahan pada produksi pending
 * TANPA mengembalikan stok (aman untuk opname yang sudah mengunci angka).
 *
 * Contoh:
 *   php artisan production:clear-pending-material-locks --dry-run
 *   php artisan production:clear-pending-material-locks
 *   php artisan production:clear-pending-material-locks --code=PR0258
 */
class ClearPendingProductionMaterialLocksCommand extends Command
{
    protected $signature = 'production:clear-pending-material-locks
                            {--code= : Batasi ke satu production_code (mis. PR0258)}
                            {--dry-run : Hanya tampilkan yang akan dibersihkan}';

    protected $description = 'Hapus log potongan bahan di produksi pending tanpa restore stok (opname-safe)';

    public function handle(ProductionPendingStockRestorer $restorer): int
    {
        $code = $this->option('code');
        $dryRun = (bool) $this->option('dry-run');

        $this->warn('Mode: HAPUS LOG saja — stok bahan/produk TIDAK dikembalikan.');
        $this->info($dryRun ? '[DRY-RUN] Scan lock log pending…' : 'Membersihkan lock log produksi pending…');

        $summary = $restorer->clearPendingMaterialLocks(
            onlyCode: is_string($code) ? $code : null,
            dryRun: $dryRun,
        );

        if ($summary['productions'] === 0) {
            $this->info('Tidak ada produksi pending yang punya log orphan.');
            return self::SUCCESS;
        }

        $this->table(
            ['production_code', 'logs_akan_dihapus'],
            array_map(
                fn (array $row) => [$row['production_code'], $row['logs']],
                $summary['details']
            )
        );

        $this->info(sprintf(
            '%s %d produksi, %d log.',
            $dryRun ? 'Akan dibersihkan:' : 'Selesai hapus:',
            $summary['productions'],
            $summary['logs_cleared']
        ));

        if (! $dryRun) {
            $this->comment('Stok fisik/sistem tidak diubah (opname tetap).');
            $this->comment('Jangan ACC produksi di atas tanpa review — stok bahan sudah terpotong sebelumnya.');
            $this->comment('Disarankan: tolak/batalkan produksi pending tersebut, atau buat ulang jika perlu.');
        }

        return self::SUCCESS;
    }
}
