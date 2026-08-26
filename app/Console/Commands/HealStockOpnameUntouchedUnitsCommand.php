<?php

namespace App\Console\Commands;

use App\Support\StockOpnameUntouchedUnitHealer;
use Illuminate\Console\Command;

/**
 * One-time repair for GitHub #78 on a document created BEFORE the fix shipped — see
 * App\Support\StockOpnameUntouchedUnitHealer for the full two-tier algorithm and its rationale.
 * Defaults to a dry run (prints what it WOULD change, writes nothing); pass --apply to persist.
 *
 * Contoh:
 *   php artisan stockopname:heal-untouched-units 69                 (dry-run, dokumen Produk)
 *   php artisan stockopname:heal-untouched-units 69 --apply         (benar-benar menulis)
 *   php artisan stockopname:heal-untouched-units 5 --bahan --apply  (dokumen Bahan Mentah)
 */
class HealStockOpnameUntouchedUnitsCommand extends Command
{
    protected $signature = 'stockopname:heal-untouched-units
                            {id : sto_id (Produk) atau stob_id (--bahan)}
                            {--bahan : dokumen Stock Opname Bahan Mentah, bukan Produk}
                            {--apply : benar-benar menulis perubahan (default: dry-run, tidak menulis apa pun)}';

    protected $description = 'GitHub #78: perbaiki satuan yang dulu diam-diam di-fallback ke stok sistem, pada dokumen yang dibuat sebelum fix';

    public function handle(StockOpnameUntouchedUnitHealer $healer): int
    {
        $id = (int) $this->argument('id');
        $bahan = (bool) $this->option('bahan');
        $apply = (bool) $this->option('apply');
        $label = $bahan ? 'stob_id' : 'sto_id';

        $this->info($apply
            ? "Menjalankan PERBAIKAN SUNGGUHAN untuk {$label} {$id}…"
            : "[DRY-RUN] Menganalisis {$label} {$id} — tidak ada yang ditulis ke DB…");

        $report = $bahan ? $healer->healSupplies($id, $apply) : $healer->healProduct($id, $apply);

        if ($report === []) {
            $this->info('Tidak ada satuan yang perlu diproses (dokumen kosong, atau semua satuan sudah "-"/genuinely counted).');
            return self::SUCCESS;
        }

        if (isset($report[0]['status']) && $report[0]['status'] === 'ERROR') {
            $this->error($report[0]['detail']);
            return self::FAILURE;
        }

        $this->table(
            ['detail_id', 'item_id', 'unit', 'stored_real', 'system_at_creation', 'status'],
            array_map(fn ($r) => [
                $r['detail_id'],
                $r['item_id'],
                $r['unit'],
                $r['stored_real'],
                $r['reconstructed_system_at_creation'] ?? '(tidak ada riwayat)',
                $r['status'],
            ], $report)
        );

        $counts = collect($report)->countBy('status');
        foreach ($counts as $status => $count) {
            $this->line("{$status}: {$count}");
        }

        $unresolved = $counts['UNRESOLVED_NO_HISTORY'] ?? 0;
        if ($unresolved > 0) {
            $this->warn("{$unresolved} satuan tidak punya riwayat log_stocks sebelum dokumen dibuat -- TIDAK diubah, butuh review manual.");
        }

        $toChange = ($counts['TIER1_UNTOUCHED_ROW'] ?? 0) + ($counts['TIER2_CONVERTED'] ?? 0);
        if (! $apply && $toChange > 0) {
            $this->comment("Ini masih dry-run -- {$toChange} satuan di atas akan diubah jadi \"-\". Tambahkan --apply untuk benar-benar menulis.");
        } elseif ($apply && $toChange > 0) {
            $this->info("Selesai -- {$toChange} satuan ditulis ulang jadi \"-\".");
        }

        return self::SUCCESS;
    }
}
