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
 * Defaults to a dry run: pass $apply=true to actually persist anything.
 */
class StockOpnameUntouchedUnitHealer
{
    /** @return array<int, array<string, mixed>> */
    public function healProduct(int $stoId, bool $apply = false): array
    {
        $sto = StockOpname::find($stoId);
        if (! $sto) {
            return [['status' => 'ERROR', 'detail' => "sto_id {$stoId} tidak ditemukan"]];
        }

        $rows = StockOpnameDetail::where('sto_id', $stoId)->where('status', 1)->get();

        $run = fn () => $this->healRows($rows, $sto->created_at, 'stod_real', 'stod_selisih', 'stod_touched', 'product_variant_id', 1, $apply);

        return $apply ? DB::transaction($run) : $run();
    }

    /** @return array<int, array<string, mixed>> */
    public function healSupplies(int $stobId, bool $apply = false): array
    {
        $stob = StockOpnameBahan::find($stobId);
        if (! $stob) {
            return [['status' => 'ERROR', 'detail' => "stob_id {$stobId} tidak ditemukan"]];
        }

        $rows = StockOpnameDetailBahan::where('stob_id', $stobId)->where('status', 1)->get();

        $run = fn () => $this->healRows($rows, $stob->created_at, 'stobd_real', 'stobd_selisih', 'stobd_touched', 'supplies_id', 2, $apply);

        return $apply ? DB::transaction($run) : $run();
    }

    /**
     * @param  \Illuminate\Support\Collection  $rows
     * @return array<int, array<string, mixed>>
     */
    private function healRows($rows, $createdAt, string $realKey, string $selisihKey, string $touchedKey, string $itemIdKey, int $logType, bool $apply): array
    {
        $report = [];

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

            if ($changed && $apply) {
                $row->{$realKey} = $this->buildString($realTokens);
                $row->{$selisihKey} = $this->buildString($selisihTokens);
                $row->save();
            }
        }

        return $report;
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
