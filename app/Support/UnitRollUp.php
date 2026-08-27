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
