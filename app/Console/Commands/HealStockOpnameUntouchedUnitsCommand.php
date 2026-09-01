<?php

namespace App\Console\Commands;

use App\Support\StockOpnameUntouchedUnitHealer;
use Illuminate\Console\Command;

/**
 * One-time repair for GitHub #78 on a document created BEFORE the fix shipped — see
 * App\Support\StockOpnameUntouchedUnitHealer for the full two-tier algorithm and its rationale.
 * Defaults to a dry run (prints what it WOULD change, writes nothing).
 *
 * Contoh:
 *   php artisan stockopname:heal-untouched-units 69                 (dry-run, tabel saja)
 *   php artisan stockopname:heal-untouched-units 69 --apply         (benar-benar menulis di DB ini)
 *   php artisan stockopname:heal-untouched-units 5 --bahan --apply  (dokumen Bahan Mentah)
 *
 * Server tanpa akses `php artisan` (shared hosting dsb.): jalankan --sql di sini terhadap SALINAN
 * LOKAL data produksi (import dump SQL), lalu tempel hasil UPDATE-nya ke akses SQL mentah apa pun
 * yang tersedia di server (phpMyAdmin/Adminer/dsb.) — TIDAK butuh artisan di server itu sendiri:
 *   php artisan stockopname:heal-untouched-units 69 --sql > sp69-fix.sql
 */
class HealStockOpnameUntouchedUnitsCommand extends Command
{
    protected $signature = 'stockopname:heal-untouched-units
                            {id : sto_id (Produk) atau stob_id (--bahan)}
                            {--bahan : dokumen Stock Opname Bahan Mentah, bukan Produk}
                            {--apply : benar-benar menulis perubahan DI DATABASE INI (default: dry-run)}
                            {--sql : cetak UPDATE SQL siap-tempel untuk dijalankan di database LAIN (mis. server tanpa artisan), tidak menulis apa pun di sini}';

    protected $description = 'GitHub #78: perbaiki satuan yang dulu diam-diam di-fallback ke stok sistem, pada dokumen yang dibuat sebelum fix';

    public function handle(StockOpnameUntouchedUnitHealer $healer): int
    {
        $id = (int) $this->argument('id');
        $bahan = (bool) $this->option('bahan');
        $apply = (bool) $this->option('apply');
        $sql = (bool) $this->option('sql');
        $label = $bahan ? 'stob_id' : 'sto_id';

        if ($sql && $apply) {
            $this->error('--sql dan --apply tidak bisa dipakai bersamaan -- --sql murni mencetak teks, tidak menulis apa pun.');
            return self::FAILURE;
        }

        if (! $sql) {
            $this->info($apply
                ? "Menjalankan PERBAIKAN SUNGGUHAN untuk {$label} {$id} di database ini…"
                : "[DRY-RUN] Menganalisis {$label} {$id} — tidak ada yang ditulis ke DB…");
        }

        $result = $bahan ? $healer->healSupplies($id, $apply) : $healer->healProduct($id, $apply);
        $report = $result['report'];
        $updates = $result['updates'];

        if (isset($report[0]['status']) && $report[0]['status'] === 'ERROR') {
            $this->error($report[0]['detail']);
            return self::FAILURE;
        }

        if ($sql) {
            if ($updates === []) {
                $this->comment('-- Tidak ada baris yang perlu diubah.');
                return self::SUCCESS;
            }
            $this->line('-- GitHub #78 heal: '.$label.' '.$id.' -- review sebelum menjalankan.');
            $this->line('START TRANSACTION;');
            foreach ($healer->toSql($updates) as $statement) {
                $this->line($statement);
            }
            $this->line('COMMIT;');
            return self::SUCCESS;
        }

        if ($report === []) {
            $this->info('Tidak ada satuan yang perlu diproses (dokumen kosong, atau semua satuan sudah "-"/genuinely counted).');
            return self::SUCCESS;
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
            $this->comment("Ini masih dry-run -- {$toChange} satuan di atas akan diubah jadi \"-\" (".count($updates)." baris). Tambahkan --apply untuk menulis di DB ini, atau --sql untuk mencetak UPDATE yang bisa ditempel ke server lain.");
        } elseif ($apply && $toChange > 0) {
            $this->info("Selesai -- {$toChange} satuan (".count($updates)." baris) ditulis ulang jadi \"-\".");
        }

        return self::SUCCESS;
    }
}
