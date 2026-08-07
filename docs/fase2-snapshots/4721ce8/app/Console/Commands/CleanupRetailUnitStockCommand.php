<?php

namespace App\Console\Commands;

use App\Support\RetailStockCleanup;
use Illuminate\Console\Command;

/**
 * Cleanup satu-kali: gudang eceran hanya boleh menyimpan stok di retail_unit varian.
 * Konversi sisa stok satuan lain (DOS/Jerigen) ke retail_unit bila memungkinkan,
 * atau nol-kan + catat di log_stocks kalau tidak bisa dikonversi.
 *
 * Contoh:
 *   php artisan stock:cleanup-retail-units --dry-run
 *   php artisan stock:cleanup-retail-units
 */
class CleanupRetailUnitStockCommand extends Command
{
    protected $signature = 'stock:cleanup-retail-units
                            {--dry-run : Hanya tampilkan yang akan diproses, tanpa ubah DB}';

    protected $description = 'Bersihkan stok non-retail_unit di gudang eceran (konversi atau nol-kan)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun
            ? '[DRY-RUN] Scan product_stocks gudang eceran…'
            : 'Cleanup stok eceran non-retail_unit…');

        $summary = RetailStockCleanup::run($dryRun);

        if ($summary['details'] === []) {
            $this->info('Tidak ada stok non-retail_unit di gudang eceran yang perlu dibersihkan.');

            return self::SUCCESS;
        }

        $this->table(
            ['action', 'label'],
            array_map(
                fn (array $row) => [$row['action'], $row['label']],
                $summary['details']
            )
        );

        $this->info(sprintf(
            '%s dikonversi ke retail_unit: %d, di-nol-kan (beda chain, tidak bisa dikonversi): %d, dilewati (retail_unit belum diatur — atur dulu lalu jalankan ulang): %d.',
            $dryRun ? 'Akan diproses —' : 'Selesai —',
            $summary['converted'],
            $summary['zeroed_no_chain'],
            $summary['skipped_no_retail_unit']
        ));

        if (! $dryRun) {
            $this->comment('Riwayat: cek Histori Stok Produk — catatan "Cleanup stok eceran → satuan retail".');
        }

        return self::SUCCESS;
    }
}
