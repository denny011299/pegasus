<?php

namespace App\Support;

use App\Models\LogStock;
use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameDetailBahan;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

/**
 * One-time repair for GitHub #78: any Stock Opname document SAVED BEFORE the fix shipped may still
 * carry the old behaviour baked into stod_real/stod_selisih (stobd_* for Bahan) — a blank input
 * silently defaulted to whatever the live system stock was AT THAT INSTANT, and got stored as a
 * plain number indistinguishable from a genuine physical count. The fix itself (StockController::
 * getQty()/buildQtyString()) only recognises the literal "-" token as "not counted" going forward —
 * it can't retroactively tell an old fabricated number apart from a real one, so a document created
 * before the fix still risks overwriting live stock with those fabricated numbers on approval.
 *
 * Two-tier correction, most confident evidence first — see cdocs/testing/KNOWN_ISSUES.md GitHub #78
 * for the full incident writeup:
 *
 *  TIER 1 (always safe, no ambiguity): a row with touched=0 means NO unit in it was ever actually
 *  typed by staff — every one of its units is converted to "-" unconditionally.
 *
 *  TIER 2 (evidence-based, per unit within a touched=1 row): reconstruct what the live system stock
 *  for that specific unit was AT THE MOMENT the document was created/last edited, using log_stocks
 *  — its latest log_category=1 ("after") entry at or before that timestamp IS that point-in-time
 *  snapshot, no need to replay/sum deltas. If the stored real qty EXACTLY equals that reconstructed
 *  value, that's the unmistakable signature of the old fallback (empirically confirmed against a
 *  real production-mirrored case — see the test suite). A genuine human count coincidentally
 *  matching system to the exact unit is possible but rare, and even then treating it as "-" (i.e.
 *  "not independently verified this time") is the safe direction to be wrong in: the unit is simply
 *  skipped at approval rather than silently re-affirmed. If the stored value differs, or no log
 *  history exists before that timestamp to check against, the unit is LEFT UNTOUCHED — conversion
 *  only ever happens on positive evidence, never assumed by default for a touched row.
 *
 * Never touches ps_stock/ss_stock, never touches the STO/STOB header, never touches stod_system/
 * stobd_system — purely rewrites stod_real/stod_selisih (only the specific unit tokens it converts,
 * every other token stays byte-for-byte as stored) on the detail rows of ONE given document.
 *
 * Every entry point returns ['report' => per-unit classification lines, 'updates' => per-row new
 * values for whichever rows actually changed]. Three ways to use the result, for hosts with no CLI/
 * artisan access on the production box: (1) $apply=true writes via Eloquent directly — needs
 * artisan or a route that can reach this class on that same box; (2) $apply=false + toSql() turns
 * 'updates' into copy-pasteable UPDATE statements — run this against a LOCAL copy of the production
 * DB (a SQL dump import) to generate SQL that's then pasted into whatever the host DOES offer
 * (phpMyAdmin/Adminer/a raw SQL console); (3) inspect 'updates'/'report' programmatically.
 */
class StockOpnameUntouchedUnitHealer
{
    /** @return array{report: array<int, array<string, mixed>>, updates: array<int, array<string, mixed>>} */
    public function healProduct(int $stoId, bool $apply = false): array
    {
        $sto = StockOpname::find($stoId);
        if (! $sto) {
            return ['report' => [['status' => 'ERROR', 'detail' => "sto_id {$stoId} tidak ditemukan"]], 'updates' => []];
        }

        $rows = StockOpnameDetail::where('sto_id', $stoId)->where('status', 1)->get();

        $run = fn () => $this->healRows($rows, $sto->created_at, 'stod_real', 'stod_selisih', 'stod_touched', 'product_variant_id', 1, 'stock_opname_details', 'stod_id', $apply);

        return $apply ? DB::transaction($run) : $run();
    }

    /** @return array{report: array<int, array<string, mixed>>, updates: array<int, array<string, mixed>>} */
    public function healSupplies(int $stobId, bool $apply = false): array
    {
        $stob = StockOpnameBahan::find($stobId);
        if (! $stob) {
            return ['report' => [['status' => 'ERROR', 'detail' => "stob_id {$stobId} tidak ditemukan"]], 'updates' => []];
        }

        $rows = StockOpnameDetailBahan::where('stob_id', $stobId)->where('status', 1)->get();

        $run = fn () => $this->healRows($rows, $stob->created_at, 'stobd_real', 'stobd_selisih', 'stobd_touched', 'supplies_id', 2, 'stock_opname_detail_bahans', 'stobd_id', $apply);

        return $apply ? DB::transaction($run) : $run();
    }

    /**
     * Turn a healProduct()/healSupplies() result's 'updates' into copy-pasteable SQL, for a host
     * where artisan/the app itself can't reach the target database directly. Run the analysis
     * against a LOCAL copy of the production data (an imported SQL dump), then paste this output
     * into whatever raw-SQL access the host DOES offer.
     *
     * @param  array<int, array<string, mixed>>  $updates
     * @return array<int, string>
     */
    public function toSql(array $updates): array
    {
        $pdo = DB::connection()->getPdo();
        $statements = [];

        foreach ($updates as $u) {
            $statements[] = sprintf(
                'UPDATE %s SET %s = %s, %s = %s WHERE %s = %d;',
                $u['table'],
                $u['real_key'],
                $pdo->quote($u['real_value']),
                $u['selisih_key'],
                $pdo->quote($u['selisih_value']),
                $u['key_column'],
                $u['key_value']
            );
        }

        return $statements;
    }

    /**
     * @param  \Illuminate\Support\Collection  $rows
     * @return array{report: array<int, array<string, mixed>>, updates: array<int, array<string, mixed>>}
     */
    private function healRows($rows, $createdAt, string $realKey, string $selisihKey, string $touchedKey, string $itemIdKey, int $logType, string $table, string $keyColumn, bool $apply): array
    {
        $report = [];
        $updates = [];

        foreach ($rows as $row) {
            $realTokens = $this->parseTokens($row->{$realKey});
            $selisihTokens = $this->parseTokens($row->{$selisihKey});
            $itemId = (int) $row->{$itemIdKey};
            $changed = false;

            foreach ($realTokens as $unitShortName => $qty) {
                if ($qty === '-') {
                    continue; // sudah "tidak dihitung", tidak perlu disentuh
                }

                if (! $row->{$touchedKey}) {
                    $realTokens[$unitShortName] = '-';
                    $selisihTokens[$unitShortName] = '-';
                    $changed = true;
                    $report[] = $this->line($row, $itemId, $unitShortName, $qty, null, 'TIER1_UNTOUCHED_ROW');
                    continue;
                }

                $unit = Unit::where('unit_short_name', $unitShortName)->first();
                if (! $unit) {
                    $report[] = $this->line($row, $itemId, $unitShortName, $qty, null, 'SKIP_UNKNOWN_UNIT');
                    continue;
                }

                $systemAtCreation = $this->reconstructSystemAt($logType, $itemId, (int) $unit->unit_id, $createdAt);
                if ($systemAtCreation === null) {
                    $report[] = $this->line($row, $itemId, $unitShortName, $qty, null, 'UNRESOLVED_NO_HISTORY');
                    continue;
                }

                if ((int) $qty === $systemAtCreation) {
                    $realTokens[$unitShortName] = '-';
                    $selisihTokens[$unitShortName] = '-';
                    $changed = true;
                    $report[] = $this->line($row, $itemId, $unitShortName, $qty, $systemAtCreation, 'TIER2_CONVERTED');
                } else {
                    $report[] = $this->line($row, $itemId, $unitShortName, $qty, $systemAtCreation, 'KEPT_GENUINE');
                }
            }

            if ($changed) {
                $newReal = $this->buildString($realTokens);
                $newSelisih = $this->buildString($selisihTokens);

                $updates[] = [
                    'table' => $table,
                    'key_column' => $keyColumn,
                    'key_value' => $row->getKey(),
                    'real_key' => $realKey,
                    'real_value' => $newReal,
                    'selisih_key' => $selisihKey,
                    'selisih_value' => $newSelisih,
                ];

                if ($apply) {
                    $row->{$realKey} = $newReal;
                    $row->{$selisihKey} = $newSelisih;
                    $row->save();
                }
            }
        }

        return ['report' => $report, 'updates' => $updates];
    }

    /** Titik-waktu stok sistem: entry log_category=1 ("setelah") terakhir pada atau sebelum $at. */
    private function reconstructSystemAt(int $logType, int $itemId, int $unitId, $at): ?int
    {
        $log = LogStock::where('log_item_id', $itemId)
            ->where('log_type', $logType)
            ->where('unit_id', $unitId)
            ->where('log_category', 1)
            ->where('log_date', '<=', $at)
            ->orderByDesc('log_date')
            ->orderByDesc('log_id')
            ->first();

        return $log ? (int) $log->log_jumlah : null;
    }

    /** @return array<string, string> unit_short_name => token mentah (bisa "-" atau angka string) */
    private function parseTokens(?string $string): array
    {
        $tokens = [];
        foreach (explode(',', (string) $string) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            [$qty, $u] = array_pad(explode(' ', $part, 2), 2, '');
            $tokens[$u] = $qty;
        }
        return $tokens;
    }

    private function buildString(array $tokens): string
    {
        $parts = [];
        foreach ($tokens as $unit => $qty) {
            $parts[] = $qty . ' ' . $unit;
        }
        return implode(', ', $parts);
    }

    private function line($row, int $itemId, string $unit, string $storedQty, ?int $reconstructed, string $status): array
    {
        return [
            'detail_id' => $row->getKey(),
            'item_id' => $itemId,
            'unit' => $unit,
            'stored_real' => $storedQty,
            'reconstructed_system_at_creation' => $reconstructed,
            'status' => $status,
        ];
    }
}
