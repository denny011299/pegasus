<?php

namespace App\Support;

use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;

/**
 * Roll a quantity UP a product's / raw material's unit ladder — the "naik satuan" direction
 * (small -> big, e.g. 24 Piece -> 2 DOS -> 1 Sak).
 *
 * Why this exists (2026-08-25): an audit of every `ps_stock`/`ss_stock` write site found that the
 * roll-up direction existed in exactly ONE place — `ProductionController::accProduction()`'s
 * finished-goods crediting (added for GitHub #19), as its own private duplicate implementation.
 * Every other stock-IN path did a flat `$row->ps_stock += $qty` with no conversion at all: Purchase
 * Order goods receipt (`PurchaseOrderDeliveryDetail::insertPoDeliveryDetail()`), stock restores
 * (`ProductIssuesDetail::deleteProductIssuesDetail()`), and Sales Order's edit-time revert
 * (`CustomerController::updateSalesOrder()` TAHAP 1). The same physical goods therefore ended up
 * represented differently depending on how they entered the system — 24 Piece stayed 24 Piece when
 * purchased, but became 1 Sak when produced. This class extracted that logic to share it with the
 * other three paths; production itself only got wired onto it later, via `planProductOutput()`
 * (GitHub #87 — its private duplicate had drifted from this class and reintroduced the bug below).
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
 *   user confirmation) — see `planProductOutput()`. Every other caller passes only units that
 *   ALREADY have an active stock row, via `allowedProductUnitIds()`/`allowedSuppliesUnitIds()`
 *   below — so a roll-up never silently creates a stock row the user never asked for. Rolling stops
 *   at the first disallowed unit and the remainder simply stays at the level below it, which is
 *   always a valid state.
 * - `$existingByUnitId` (GitHub #87, 2026-08-29) is what's already sitting in each unit's stock row
 *   BEFORE this credit. Every `plan*()` convenience wrapper below fetches and passes it
 *   automatically. Without it, a credit that's below a level's ratio on its own but pushes an
 *   existing leftover over it never rolled up — the leftover just kept growing past its own unit's
 *   ratio forever, e.g. 20 Piece already in stock + 4 Piece freshly credited never became 1 DOS even
 *   at exactly 24 Piece = 1 DOS. Folding in the existing amount can make a credit at a given level
 *   NEGATIVE (existing stock physically moving up a level along with the new credit) — that's
 *   expected, not an error; every caller applies credits with a plain `+=`, which handles a negative
 *   delta correctly.
 * - Guarded at 20 hops, same as the ladder walkers in ProductionController, so a circular or
 *   corrupt relation chain can't loop forever.
 */
class UnitRollUp
{
    private const MAX_HOPS = 20;

    /**
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @param  array<int, int>  $allowedUnitIds
     * @param  array<int, int>  $existingByUnitId  Whatever is ALREADY on the shelf at each unit
     *         level, keyed by unit_id (0/absent = nothing there yet). Fixed 2026-08-29 (GitHub
     *         #87): without this, a qty that's below the ratio on its own but pushes an existing
     *         leftover over it (e.g. 20 Piece already in stock + 4 Piece just credited, 1 DOS = 24
     *         Piece) never rolled up — the leftover just kept growing past its own unit's ratio
     *         forever. Passing [] reproduces the old existing-blind behaviour exactly (every
     *         caller that doesn't pass it gets the pre-fix math, byte for byte).
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function plan(array $chain, int $startUnitId, int $qty, array $allowedUnitIds, array $existingByUnitId = []): array
    {
        $credits = [];
        $currentUnitId = $startUnitId;
        $remaining = $qty;
        $allowed = array_flip(array_map('intval', $allowedUnitIds));
        $existing = array_map('intval', $existingByUnitId);
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

            $existingHere = $existing[$currentUnitId] ?? 0;
            $total = $existingHere + $remaining;

            // Chain ended, what's already here PLUS what's being credited now still doesn't fill a
            // whole bigger unit, ratio is nonsense, or the caller won't let us credit the next unit
            // up — stop and leave the rest where it is (existing stock untouched).
            if ($rel === null
                || $rel['ratio'] <= 0
                || $total < $rel['ratio']
                || ! isset($allowed[$rel['big']])
            ) {
                break;
            }

            // The combined total resettles at $total % ratio here; the delta from whatever was
            // already sitting at this level can be negative — that's correct, it means some of the
            // pre-existing stock physically moved up a level along with the new credit, not that it
            // vanished. Conservation still holds: summed back down to $startUnitId's unit, every
            // credit in the returned plan always adds up to exactly $qty (existing stock elsewhere
            // is only ever repositioned, never counted twice) — see UnitRollUpTest's conservation
            // tests for the proof.
            $credits[] = ['unit_id' => $currentUnitId, 'qty' => (int) ($total % $rel['ratio']) - $existingHere];

            $currentUnitId = $rel['big'];
            $remaining = (int) floor($total / $rel['ratio']);
        }

        $credits[] = ['unit_id' => $currentUnitId, 'qty' => (int) $remaining];

        return $credits;
    }

    /**
     * Gulung SEMUA satuan yang benar-benar diisi (bukan null) di satu baris Stock Opname jadi
     * representasi kanonik di sepanjang tangganya -- rekursif menembus berapa pun tingkat
     * (pcs -> DOS -> SAK -> ...), dibangun di atas plan() tanpa mengubahnya sama sekali. Dipakai
     * oleh App\Support\StockOpname\OpnameLifecycle/BahanOpnameLifecycle.
     *
     * Contoh (PM, 2026-08-27): 1 DOS = 12 pcs. Staf mengisi 30 pcs, DOS dibiarkan kosong ->
     * tersimpan jadi 2 DOS + 6 pcs.
     *
     * ATURAN KESELAMATAN, setara GitHub #78: TIDAK PERNAH mengisi satuan yang lebih KECIL
     * daripada satuan TERKECIL yang benar-benar diisi. Menggulung KE ATAS dari yang sudah diisi
     * itu wajar (angka yang SAMA, cuma direpresentasikan ulang) -- tapi menyimpulkan satuan lebih
     * kecil dari satuan besar yang diisi (mis. DOS diisi 0 sendirian, pcs ikut disimpulkan 0)
     * berarti mengarang data yang tidak pernah benar-benar diperiksa staf, persis pelanggaran yang
     * GitHub #78 tutup. Makanya titik mulai gulungnya BUKAN dasar tangga (seperti plan()), tapi
     * satuan diisi yang paling kecil -- satuan-satuan di bawahnya yang tidak diisi tetap NULL,
     * satuan-satuan di atasnya ikut menyerap kelebihannya (plus nilai yang mereka isi sendiri,
     * kalau ada) sepanjang jalan naik.
     *
     * Satuan yang diisi tapi sama sekali tidak terhubung ke $chain (relasi tidak dikenal untuk
     * satuan itu) TIDAK ikut dihitung/ditulis di sini -- tetap seperti aslinya.
     *
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @param  array<int, int|null>  $qtyByUnitId  null = satuan ini TIDAK diisi
     * @param  array<int, int>  $allowedUnitIds
     * @return array<int, array{unit_id: int, qty: int}>  hanya satuan yang benar-benar
     *         disentuh/dihasilkan gulungan ini -- satuan yang tidak diisi dan tidak ikut tergulung
     *         TIDAK muncul di sini sama sekali (biarkan tetap NULL, jangan ditulis ulang).
     */
    public static function collapse(array $chain, array $qtyByUnitId, array $allowedUnitIds): array
    {
        $entered = array_filter($qtyByUnitId, fn ($q) => $q !== null);
        if ($entered === [] || $chain === []) {
            return [];
        }

        $multipliers = self::multipliersFromBottom($chain);
        $enteredInChain = array_intersect_key($entered, $multipliers);
        if ($enteredInChain === []) {
            return [];
        }

        // Satuan terkecil yang BENAR-BENAR diisi -- titik mulai gulung (bukan dasar tangga penuh).
        asort($multipliers);
        $startUnit = null;
        foreach (array_keys($multipliers) as $unitId) {
            if (array_key_exists($unitId, $enteredInChain)) {
                $startUnit = $unitId;
                break;
            }
        }

        $allowed = array_flip(array_map('intval', $allowedUnitIds));
        $current = $startUnit;
        $carry = (int) $enteredInChain[$startUnit];
        $credits = [];
        $visited = [$startUnit => true];
        $hops = 0;

        while ($hops < self::MAX_HOPS) {
            $hops++;

            $rel = null;
            foreach ($chain as $link) {
                if ($link['small'] === $current) {
                    $rel = $link;
                    break;
                }
            }

            if ($rel === null || $rel['ratio'] <= 0 || $carry < $rel['ratio'] || ! isset($allowed[$rel['big']])) {
                break;
            }

            $credits[] = ['unit_id' => $current, 'qty' => $carry % $rel['ratio']];
            // Lipat nilai yang staf isi SENDIRI di tingkat ini (kalau ada) ke dalam bawaan naik --
            // inilah yang membuat "DOS diisi 1 DAN pcs diisi 15" digabung benar (bukan cuma
            // menggulung salah satu lalu mengabaikan yang lain).
            $carry = (int) floor($carry / $rel['ratio']) + (int) ($enteredInChain[$rel['big']] ?? 0);
            $current = $rel['big'];
            $visited[$current] = true;
        }

        $credits[] = ['unit_id' => $current, 'qty' => $carry];

        // Satuan yang diisi tapi TIDAK PERNAH disinggahi jalan naik ini (carry-nya berhenti duluan
        // sebelum sempat mencapainya -- lihat test idempotency) tetap harus muncul di hasil dengan
        // nilai aslinya, tidak berubah. Tanpa ini, menjalankan collapse() dua kali pada hasilnya
        // sendiri bisa diam-diam "kehilangan" satuan yang sudah benar dari daftar yang dikembalikan
        // -- efek akhirnya tetap benar (baris itu memang sudah punya nilai yang tepat, jadi tidak
        // perlu ditulis ulang), tapi kontraknya jadi tidak bisa diandalkan pemanggil.
        foreach ($enteredInChain as $unitId => $qty) {
            if (! isset($visited[$unitId])) {
                $credits[] = ['unit_id' => $unitId, 'qty' => (int) $qty];
            }
        }

        return $credits;
    }

    /**
     * Convenience wrapper: gulung satu baris produk, dibatasi ke satuan yang sudah punya baris
     * stok aktif (kebijakan yang sama dengan planProduct()).
     *
     * @param  array<int, int|null>  $qtyByUnitId
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function collapseProduct(int $productVariantId, array $qtyByUnitId): array
    {
        return self::collapse(
            self::productChain($productVariantId),
            $qtyByUnitId,
            self::allowedProductUnitIds($productVariantId)
        );
    }

    /**
     * Convenience wrapper: gulung satu baris bahan, dibatasi ke satuan yang sudah punya baris
     * stok aktif (kebijakan yang sama dengan planSupplies()).
     *
     * @param  array<int, int|null>  $qtyByUnitId
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function collapseSupplies(int $suppliesId, array $qtyByUnitId): array
    {
        return self::collapse(
            self::suppliesChain($suppliesId),
            $qtyByUnitId,
            self::allowedSuppliesUnitIds($suppliesId)
        );
    }

    /**
     * Pengali tiap satuan pada $chain relatif terhadap satuan PALING BAWAH tangga itu (satuan
     * yang tidak pernah muncul sebagai `big`). Dipakai HANYA untuk mengurutkan "mana yang paling
     * kecil di antara yang diisi" di collapse() -- bukan untuk konversi langsung.
     *
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @return array<int, int> unit_id => pengali relatif terhadap satuan paling bawah
     */
    private static function multipliersFromBottom(array $chain): array
    {
        $smalls = array_unique(array_map(fn ($l) => $l['small'], $chain));
        $bigs = array_unique(array_map(fn ($l) => $l['big'], $chain));
        $bottomCandidates = array_values(array_diff($smalls, $bigs));
        $bottom = $bottomCandidates[0] ?? $smalls[0];

        $multipliers = [$bottom => 1];
        $current = $bottom;
        $hops = 0;

        while ($hops < self::MAX_HOPS) {
            $hops++;

            $rel = null;
            foreach ($chain as $link) {
                if ($link['small'] === $current) {
                    $rel = $link;
                    break;
                }
            }

            if ($rel === null || $rel['ratio'] <= 0 || isset($multipliers[$rel['big']])) {
                break; // rel null/rusak, atau sudah pernah dikunjungi (rantai melingkar) -- berhenti
            }

            $multipliers[$rel['big']] = $multipliers[$current] * $rel['ratio'];
            $current = $rel['big'];
        }

        return $multipliers;
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
     * What's already on the shelf at each unit level RIGHT NOW, keyed by unit_id — the plan()'s
     * $existingByUnitId input (GitHub #87). A unit with no active row simply doesn't appear (plan()
     * treats an absent key as 0, which is correct: nothing there to fold in).
     *
     * This ONE query also serves as the allow-list for the `plan*()` wrappers below (its keys are
     * exactly `allowedProductUnitIds()`'s result), so a roll-up costs one query here, not two
     * identical ones.
     *
     * ⚠️ **Must be given a warehouse scope when the warehouse module merges.** `main`'s
     * `product_stocks` has no `warehouse_id` at all, so (variant, unit) is always a single row and
     * the `SUM` below is exactly that row's value. On `fase2` the column exists — the test snapshot
     * already has 345 (variant, unit) combos with more than one active row — and summing ACROSS
     * warehouses would be wrong, because a roll-up is per-warehouse: it would decide to roll up
     * using stock sitting in a warehouse it is not crediting. `fase2`'s
     * `allowedProductUnitIds($productVariantId, $warehouseId)` already takes that scope; this must
     * grow the same parameter at merge time rather than keep aggregating blindly. The `SUM` is a
     * deliberate, deterministic placeholder (`pluck()` would silently pick an arbitrary row
     * instead), NOT an endorsement of cross-warehouse aggregation.
     *
     * @return array<int, int> unit_id => ps_stock
     */
    public static function existingProductStockByUnit(int $productVariantId): array
    {
        return ProductStock::where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->groupBy('unit_id')
            ->selectRaw('unit_id, SUM(ps_stock) AS qty')
            ->pluck('qty', 'unit_id')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /**
     * Supplies-side counterpart of existingProductStockByUnit() — including its warehouse caveat.
     *
     * @return array<int, int> unit_id => ss_stock
     */
    public static function existingSuppliesStockByUnit(int $suppliesId): array
    {
        return SuppliesStock::where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->groupBy('unit_id')
            ->selectRaw('unit_id, SUM(ss_stock) AS qty')
            ->pluck('qty', 'unit_id')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /**
     * Convenience wrapper: plan a product roll-up restricted to units that already have stock rows,
     * folding in whatever is already there (GitHub #87) so a credit that's below the ratio on its
     * own but crosses it combined with an existing leftover still rolls up correctly.
     *
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function planProduct(int $productVariantId, int $startUnitId, int $qty): array
    {
        // One lookup, two uses: its KEYS are exactly allowedProductUnitIds() (units with an active
        // stock row) and its VALUES are the existing quantities plan() folds in — issuing both
        // queries separately would just run the same WHERE twice.
        $existing = self::existingProductStockByUnit($productVariantId);

        return self::plan(
            self::productChain($productVariantId),
            $startUnitId,
            $qty,
            array_keys($existing),
            $existing
        );
    }

    /**
     * Convenience wrapper: plan a supplies roll-up restricted to units that already have stock rows,
     * folding in whatever is already there (GitHub #87) — see planProduct()'s docblock.
     *
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function planSupplies(int $suppliesId, int $startUnitId, int $qty): array
    {
        // Single lookup serving both the allow-list and the existing quantities — see planProduct().
        $existing = self::existingSuppliesStockByUnit($suppliesId);

        return self::plan(
            self::suppliesChain($suppliesId),
            $startUnitId,
            $qty,
            array_keys($existing),
            $existing
        );
    }

    /**
     * Production's own output-crediting policy: unlike planProduct(), this may roll into EVERY unit
     * in the product's ladder, not just ones that already have an active ProductStock row —
     * accProduction() auto-provisions missing rows on demand (behind a user confirmation, see
     * ProductionController::ensureProductStockRow()), so no allowedProductUnitIds() gate applies.
     * Replaces the private duplicate that used to live in ProductionController as
     * creditProductOutputUpChain() (GitHub #19, then #87 for the existing-stock fix).
     *
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function planProductOutput(int $productVariantId, int $startUnitId, int $qty): array
    {
        $chain = self::productChain($productVariantId);

        $everyUnitId = [];
        foreach ($chain as $link) {
            $everyUnitId[$link['small']] = true;
            $everyUnitId[$link['big']] = true;
        }

        return self::plan(
            $chain,
            $startUnitId,
            $qty,
            array_keys($everyUnitId),
            self::existingProductStockByUnit($productVariantId)
        );
    }
}
