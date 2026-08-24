<?php

namespace App\Support;

use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;

/**
 * ⚠️ CARA ROLLBACK "ROLL-UP GLOBAL" — BACA DULU SEBELUM MENGUBAH APA PUN DI SINI ⚠️
 * =================================================================================
 * Keputusan PM (2026-08-25): stok SELALU dinaikkan satuannya secara global, TANPA memedulikan
 * apakah barangnya benar-benar dikemas/dibox secara fisik. Ini keputusan sadar, tapi diakui
 * sebagai area berisiko — makanya cara membalikkannya ditulis eksplisit di sini.
 *
 * RISIKONYA (kalau nanti bermasalah, ini gejalanya): roll-up mengubah pembagian satuan TANPA ada
 * tindakan fisik. Stok 12 DOS + 10 PCS yang menerima 20 PCS jadi 13 DOS + 18 PCS — padahal tidak
 * ada seorang pun yang benar-benar memasukkan 12 pcs ke dalam dus. Kalau di gudang barangnya
 * memang dibiarkan lepasan, sistem akan mengklaim DOS yang fisiknya tidak ada, dan SETIAP stock
 * opname akan terus melaporkan selisih DOS/PCS walaupun total kuantitasnya sebenarnya cocok.
 * Catatan lain: yang dinaikkan hanya kuantitas YANG MASUK, bukan total gabungan — jadi satuan
 * kecil tetap bisa melebihi rasio (contoh di atas: 18 PCS padahal 1 DOS = 12 PCS).
 *
 * KALAU DIMINTA ROLLBACK, kembalikan ke `$row->ss_stock += $qty` / `$row->ps_stock += $qty` biasa di:
 *   1. App\Models\PurchaseOrderDeliveryDetail::insertPoDeliveryDetail()  — penerimaan barang PO
 *   2. App\Models\ProductIssuesDetail::deleteProductIssuesDetail()       — pengembalian stok retur
 *   3. App\Http\Controllers\CustomerController::updateSalesOrder()       — blok $masihDipakai di TAHAP 1
 *   4. App\Http\Controllers\SupplierController::tolakPO() — WAJIB ikut dibalikkan bersama #1.
 *      suppliesLadderHasEnough() + bongkarSuppliesUntilEnough() ADA HANYA untuk menopang roll-up
 *      di #1. Tapi HATI-HATI arah baliknya: kalau #1 dibalikkan tapi #4 tidak, sistem tetap jalan
 *      (cuma jadi lebih permisif). Kalau #4 dibalikkan tapi #1 tidak, SEMUA pembatalan PO untuk
 *      bahan ber-ladder akan gagal "Stok bahan tidak mencukupi".
 *   Lalu hapus test: tests/Workflow/PurchaseOrderReceiptRollUpFlowTest.php dan
 *   tests/Workflow/SalesOrderUpdateRollUpFlowTest.php.
 *
 * JANGAN ikut dibalikkan (semuanya independen dari keputusan roll-up ini):
 *   - Perbaikan bongkar di CustomerController::updateSalesOrder()'s $siapkanStok (cari unit atas
 *     lewat relasi, bukan posisi array). Itu perbaikan bug korupsi stok yang berdiri sendiri —
 *     lihat tests/Regression/SalesOrderUpdateBongkarFailsOnStockRowInsertionOrderTest.php.
 *   - Roll-up hasil produksi di ProductionController::creditProductOutputUpChain() (GitHub #19,
 *     disetujui PM terpisah dan sudah jalan sejak sebelum keputusan ini).
 *   - Gerbang opname basi di StockController::itemsMovedSinceOpname().
 *   - Class ini sendiri boleh dibiarkan; tidak berefek apa-apa kalau tidak dipanggil.
 *
 * Verifikasi setelah rollback: `php vendor/bin/phpunit --testsuite=Workflow` — yang boleh gagal
 * hanya kedua test roll-up di atas (kalau belum dihapus). Kalau ada test PO/tolakPO lain yang ikut
 * merah, berarti #1 dan #4 tidak dibalikkan berpasangan.
 * =================================================================================
 *
 * Roll a quantity UP a product's / raw material's unit ladder — the "naik satuan" direction
 * (small -> big, e.g. 24 Piece -> 2 DOS -> 1 Sak).
 *
 * Why this exists (2026-08-25): an audit of every `ps_stock`/`ss_stock` write site found that the
 * roll-up direction existed in exactly ONE place — `ProductionController::accProduction()`'s
 * finished-goods crediting (added for GitHub #19). Every other stock-IN path did a flat
 * `$row->ps_stock += $qty` with no conversion at all: Purchase Order goods receipt
 * (`PurchaseOrderDeliveryDetail::insertPoDeliveryDetail()`), stock restores
 * (`ProductIssuesDetail::deleteProductIssuesDetail()`), and Sales Order's edit-time revert
 * (`CustomerController::updateSalesOrder()` TAHAP 1). The same physical goods therefore ended up
 * represented differently depending on how they entered the system — 24 Piece stayed 24 Piece when
 * purchased, but became 1 Sak when produced.
 *
 * The opposite direction ("bongkar", big -> small, used when a consuming unit is short) is NOT
 * here: it is need-driven — it only makes sense against a specific target quantity to satisfy —
 * so it stays at each consumption site. See `ProductIssuesDetail::stockCheck()` and
 * `StockController::deleteProductIssue()` for the canonical relation-lookup implementations of it.
 *
 * Design notes:
 * - `plan*()` is PURE: it performs no DB writes and returns the credit plan as an ordered list of
 *   `{unit_id, qty}`, lowest unit first. The caller applies it and writes its own `log_stocks`
 *   rows, because the log note/code differs per flow.
 * - `$allowedUnitIds` is the caller's policy hook for "may I credit this unit?". Production passes
 *   every unit in the ladder because it provisions missing `ProductStock` rows on demand (behind a
 *   user confirmation). Every other caller passes only units that ALREADY have an active stock
 *   row, via `allowedProductUnitIds()`/`allowedSuppliesUnitIds()` below — so a roll-up never
 *   silently creates a stock row the user never asked for. Rolling stops at the first disallowed
 *   unit and the remainder simply stays at the level below it, which is always a valid state.
 * - Guarded at 20 hops, same as the ladder walkers in ProductionController, so a circular or
 *   corrupt relation chain can't loop forever.
 */
class UnitRollUp
{
    private const MAX_HOPS = 20;

    /**
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @param  array<int, int>  $allowedUnitIds
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function plan(array $chain, int $startUnitId, int $qty, array $allowedUnitIds): array
    {
        $credits = [];
        $currentUnitId = $startUnitId;
        $remaining = $qty;
        $allowed = array_flip(array_map('intval', $allowedUnitIds));
        $hops = 0;

        while ($hops < self::MAX_HOPS) {
            $hops++;

            $rel = null;
            foreach ($chain as $link) {
                if ($link['small'] === $currentUnitId) {
                    $rel = $link;
                    break;
                }
            }

            // Chain ended, quantity no longer fills a whole bigger unit, ratio is nonsense, or the
            // caller won't let us credit the next unit up — stop and leave the rest where it is.
            if ($rel === null
                || $rel['ratio'] <= 0
                || $remaining < $rel['ratio']
                || ! isset($allowed[$rel['big']])
            ) {
                break;
            }

            $credits[] = ['unit_id' => $currentUnitId, 'qty' => (int) ($remaining % $rel['ratio'])];

            $currentUnitId = $rel['big'];
            $remaining = (int) floor($remaining / $rel['ratio']);
        }

        $credits[] = ['unit_id' => $currentUnitId, 'qty' => (int) $remaining];

        return $credits;
    }

    /** @return array<int, array{small: int, big: int, ratio: int}> */
    public static function productChain(int $productVariantId): array
    {
        return ProductRelation::where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->get()
            ->map(fn ($r) => [
                'small' => (int) $r->pr_unit_id_2,
                'big' => (int) $r->pr_unit_id_1,
                'ratio' => (int) $r->pr_unit_value_2,
            ])
            ->all();
    }

    /** @return array<int, array{small: int, big: int, ratio: int}> */
    public static function suppliesChain(int $suppliesId): array
    {
        return SuppliesRelation::where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->get()
            ->map(fn ($r) => [
                'small' => (int) $r->su_id_2,
                'big' => (int) $r->su_id_1,
                'ratio' => (int) $r->sr_value_2,
            ])
            ->all();
    }

    /** Units that already have an active ProductStock row — the default crediting policy. */
    public static function allowedProductUnitIds(int $productVariantId): array
    {
        return ProductStock::where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->pluck('unit_id')
            ->map(fn ($u) => (int) $u)
            ->all();
    }

    /** Units that already have an active SuppliesStock row — the default crediting policy. */
    public static function allowedSuppliesUnitIds(int $suppliesId): array
    {
        return SuppliesStock::where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->pluck('unit_id')
            ->map(fn ($u) => (int) $u)
            ->all();
    }

    /**
     * Convenience wrapper: plan a product roll-up restricted to units that already have stock rows.
     *
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function planProduct(int $productVariantId, int $startUnitId, int $qty): array
    {
        return self::plan(
            self::productChain($productVariantId),
            $startUnitId,
            $qty,
            self::allowedProductUnitIds($productVariantId)
        );
    }

    /**
     * Convenience wrapper: plan a supplies roll-up restricted to units that already have stock rows.
     *
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function planSupplies(int $suppliesId, int $startUnitId, int $qty): array
    {
        return self::plan(
            self::suppliesChain($suppliesId),
            $startUnitId,
            $qty,
            self::allowedSuppliesUnitIds($suppliesId)
        );
    }
}
