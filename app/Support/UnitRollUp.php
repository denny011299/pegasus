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
     * $existingByUnitId (perbaikan bug dilaporkan user, multi-gudang): stok LIVE yang sudah ada di
     * satuan yang TIDAK diisi, dipakai sebagai bawaan default saat carry naik melewati satuan itu.
     * Tanpa ini, "84 DOS (sudah benar) + isi Piece 1000 sendirian (1 DOS = 12 pcs)" tergulung jadi
     * 83 DOS + 4 Piece -- DOS yang sudah ada 84 lenyap diam-diam digantikan angka yang cuma berasal
     * dari perhitungan Piece, dan angka itu ikut tertulis ke ps_stock saat ACC karena tidak lagi
     * NULL. Dengan bawaan ini, satuan yang tidak diisi menyumbang stok live-nya sendiri ke carry
     * (84 + 83 = 167 DOS), match kekekalan jumlah fisik (84*12 + 1000 = 167*12 + 4). Kalau satuan
     * itu MEMANG diisi eksplisit oleh staf, bawaan itu diabaikan (dianggap koreksi baru, bukan
     * ditambah ke yang lama) -- lihat pemakaian `??` di bawah, entered selalu menang lebih dulu.
     * Default [] mereproduksi perilaku lama persis (satuan pemanggil yang tidak pernah memberi ini).
     *
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @param  array<int, int|null>  $qtyByUnitId  null = satuan ini TIDAK diisi
     * @param  array<int, int>  $allowedUnitIds
     * @param  array<int, int>  $existingByUnitId  stok live per satuan, dipakai HANYA untuk satuan
     *         yang tidak diisi di $qtyByUnitId (lihat penjelasan di atas)
     * @return array<int, array{unit_id: int, qty: int}>  hanya satuan yang benar-benar
     *         disentuh/dihasilkan gulungan ini -- satuan yang tidak diisi dan tidak ikut tergulung
     *         TIDAK muncul di sini sama sekali (biarkan tetap NULL, jangan ditulis ulang).
     */
    public static function collapse(array $chain, array $qtyByUnitId, array $allowedUnitIds, array $existingByUnitId = []): array
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
            // menggulung salah satu lalu mengabaikan yang lain). Kalau TIDAK diisi, lipat stok
            // LIVE yang sudah ada di satuan itu (bukan 0) -- lihat docblock $existingByUnitId di
            // atas untuk alasan lengkap (satuan yang tidak disentuh tidak boleh lenyap).
            $carry = (int) floor($carry / $rel['ratio']) + (int) ($enteredInChain[$rel['big']] ?? $existingByUnitId[$rel['big']] ?? 0);
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
     * Gulung PENUH: seperti collapse(), tapi mulai dari DASAR tangga (satuan terkecil) dan
     * dipakai bukan hanya satuan yang staf isi -- satuan yang tidak diisi ikut disumbang dari
     * stok yang sudah ada ($existingByUnitId) di SETIAP tingkat, bukan cuma tingkat yang
     * dilewati saat naik dari satuan yang diisi. Hasilnya: representasi kanonik PENUH atas
     * seluruh tangga, termasuk melipat kelebihan satuan kecil yang TIDAK PERNAH disentuh staf
     * ke satuan besar yang staf isi sendiri.
     *
     * >>> INI SENGAJA MELANGGAR aturan GH #78 yang dipegang collapse() ("jangan pernah mengarang
     * angka satuan yang tidak pernah diperiksa staf") -- makanya TIDAK PERNAH dipanggil otomatis
     * dari input mana pun. Satu-satunya pemanggil yang sah: OpnameLifecycle::rollUpUnitsFull(),
     * dan itu HANYA dipanggil setelah staf melihat daftar produk yang akan tergulung dan
     * mengklik "Lanjut" secara eksplisit pada popup konfirmasi (keputusan user 2026-09-04,
     * GitHub bug report "93 Dos, 104 Piece") -- baca docblock rollUpUnitsFull() untuk alur
     * lengkapnya. Jangan panggil ini dari collapseProduct()/rollUpUnits() (jalur otomatis,
     * aman) atau dari draft mana pun.
     *
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @param  array<int, int|null>  $qtyByUnitId  null/tidak ada = satuan ini TIDAK diisi
     * @param  array<int, int>  $allowedUnitIds
     * @param  array<int, int>  $existingByUnitId  stok live per satuan, dipakai untuk satuan yang
     *         tidak diisi di $qtyByUnitId, di SETIAP tingkat (bukan cuma yang dilewati)
     * @return array<int, array{unit_id: int, qty: int}>  seluruh satuan yang tersentuh gulungan
     *         ini (bisa kosong kalau chain kosong atau tidak ada satuan apa pun yang punya nilai)
     */
    public static function collapseFull(array $chain, array $qtyByUnitId, array $allowedUnitIds, array $existingByUnitId = []): array
    {
        // Bug ditemukan user 2026-09-05 (hari yang sama fitur ini dipasang): tanpa guard ini,
        // produk yang SAMA SEKALI tidak disentuh staf ($qtyByUnitId semua null) tetap digulung
        // dari stok LIVE murni ($seed() jatuh ke $existingByUnitId di semua tingkat) -- bukan
        // "melipat kelebihan yang staf isi sendiri" seperti niatnya collapseFull(), tapi
        // "mengarang ulang representasi stok produk yang tidak sedang di-opname sama sekali".
        // Ini nyata berbahaya di jalur TULIS (OpnameLifecycle::rollUpUnitsFull(), dipanggil
        // setelah staf klik "Lanjut"): dokumen non-draft (.btn-save) selalu mengirim SELURUH
        // katalog yang sedang tampil (bukan cuma yang diisi -- itu memang disengaja, lihat
        // keepSparse di CreateStockOpname.js), jadi baris NULL ("tidak dihitung") milik produk
        // yang tidak disentuh bisa diam-diam tertimpa angka karangan. Sama seperti collapse()'s
        // guard $entered===[] di atas, cuma versi ini untuk gulung PENUH.
        $entered = array_filter($qtyByUnitId, fn ($q) => $q !== null);
        if ($entered === [] || $chain === []) {
            return [];
        }

        $multipliers = self::multipliersFromBottom($chain);
        if ($multipliers === []) {
            return [];
        }
        asort($multipliers);
        $bottom = array_key_first($multipliers);

        $seed = fn ($unitId) => $qtyByUnitId[$unitId] ?? $existingByUnitId[$unitId] ?? null;

        $allowed = array_flip(array_map('intval', $allowedUnitIds));
        $current = $bottom;
        $carry = (int) ($seed($bottom) ?? 0);
        $credits = [];
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
            $carry = (int) floor($carry / $rel['ratio']) + (int) ($seed($rel['big']) ?? 0);
            $current = $rel['big'];
        }

        $credits[] = ['unit_id' => $current, 'qty' => $carry];

        return $credits;
    }

    /**
     * Convenience wrapper: gulung PENUH satu baris produk (lihat collapseFull()) -- hanya dipakai
     * di belakang konfirmasi eksplisit staf, lihat OpnameLifecycle::rollUpUnitsFull().
     *
     * @param  array<int, int|null>  $qtyByUnitId
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function collapseProductFull(int $productVariantId, array $qtyByUnitId, ?int $warehouseId = null): array
    {
        return self::collapseFull(
            self::productChain($productVariantId),
            $qtyByUnitId,
            self::allowedProductUnitIds($productVariantId, $warehouseId),
            self::existingProductStockByUnit($productVariantId, $warehouseId)
        );
    }

    /**
     * Convenience wrapper: gulung satu baris produk, dibatasi ke satuan yang sudah punya baris
     * stok aktif (kebijakan yang sama dengan planProduct()).
     *
     * $warehouseId: sama seperti allowedProductUnitIds() -- untuk Stock Opname ini WAJIB diisi
     * gudang dokumennya sendiri, bukan gudang aktif sesi yang kebetulan menyimpan. Kalau tidak,
     * dokumen bisa tergulung ke satuan yang tidak punya baris stok di gudangnya sendiri, dan ACC-nya
     * nanti (accStockOpnameV2(), yang memang dipin ke gudang dokumen) menolak dengan "Baris stok
     * tidak ditemukan".
     *
     * @param  array<int, int|null>  $qtyByUnitId
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function collapseProduct(int $productVariantId, array $qtyByUnitId, ?int $warehouseId = null): array
    {
        return self::collapse(
            self::productChain($productVariantId),
            $qtyByUnitId,
            self::allowedProductUnitIds($productVariantId, $warehouseId),
            self::existingProductStockByUnit($productVariantId, $warehouseId)
        );
    }

    /**
     * Convenience wrapper: gulung satu baris bahan, dibatasi ke satuan yang sudah punya baris
     * stok aktif (kebijakan yang sama dengan planSupplies()).
     * $warehouseId: lihat catatan di collapseProduct().
     *
     * @param  array<int, int|null>  $qtyByUnitId
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function collapseSupplies(int $suppliesId, array $qtyByUnitId, ?int $warehouseId = null): array
    {
        return self::collapse(
            self::suppliesChain($suppliesId),
            $qtyByUnitId,
            self::allowedSuppliesUnitIds($suppliesId, $warehouseId),
            self::existingSuppliesStockByUnit($suppliesId, $warehouseId)
        );
    }

    /**
     * Stok live saat ini per satuan -- dipakai collapseProduct() untuk melipat satuan yang TIDAK
     * diisi ke dalam carry gulungan (lihat docblock collapse()'s $existingByUnitId). $warehouseId
     * berperilaku persis seperti allowedProductUnitIds() -- dipin ke SATU gudang, bukan scope
     * gudang aktif sesi, karena di multi-gudang (product_stocks, unit) bukan lagi baris tunggal.
     *
     * @return array<int, int> unit_id => ps_stock
     */
    public static function existingProductStockByUnit(int $productVariantId, ?int $warehouseId = null): array
    {
        return (($warehouseId !== null)
                ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : ProductStock::query())
            ->where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->pluck('ps_stock', 'unit_id')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /**
     * Kembaran existingProductStockByUnit() untuk Bahan/Supplies -- lihat docblock itu.
     *
     * @return array<int, int> unit_id => ss_stock
     */
    public static function existingSuppliesStockByUnit(int $suppliesId, ?int $warehouseId = null): array
    {
        return (($warehouseId !== null)
                ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : SuppliesStock::query())
            ->where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->pluck('ss_stock', 'unit_id')
            ->map(fn ($q) => (int) $q)
            ->all();
    }

    /**
     * Pengali tiap satuan pada $chain relatif terhadap satuan PALING BAWAH tangga itu (satuan
     * yang tidak pernah muncul sebagai `big`). Dipakai untuk mengurutkan "mana yang paling
     * kecil di antara yang diisi" di collapse() -- bukan untuk konversi langsung. Public (bukan
     * private lagi, 2026-09-05): App\Support\StockOpname\OpnameLifecycle juga memakainya untuk
     * mengurutkan popup konfirmasi gulung dari satuan BESAR ke KECIL (DOS di kiri, pcs di kanan).
     *
     * @param  array<int, array{small: int, big: int, ratio: int}>  $chain
     * @return array<int, int> unit_id => pengali relatif terhadap satuan paling bawah
     */
    public static function multipliersFromBottom(array $chain): array
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

    /**
     * EVERY unit appearing in a product's ladder, regardless of whether it currently has a stock
     * row — the permissive counterpart to allowedProductUnitIds().
     *
     * Only for callers that provision a missing row deliberately and visibly: today that is
     * ProductionController::accProduction(), whose `confirm_create_stock` round-trip asks the user
     * before creating anything (see this class's "Design notes" above, and
     * tests/Regression/ProductionOutputLadderNullGuardCrashTest.php which pins that behavior).
     * Any caller WITHOUT such a confirmation step must keep using allowedProductUnitIds().
     *
     * @return array<int, int>
     */
    public static function ladderUnitIds(int $productVariantId): array
    {
        $units = [];
        foreach (self::productChain($productVariantId) as $link) {
            $units[$link['small']] = true;
            $units[$link['big']] = true;
        }

        return array_map('intval', array_keys($units));
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

    /**
     * Units that already have an active ProductStock row — the default crediting policy.
     *
     * $warehouseId pins the lookup to ONE warehouse instead of ProductStock's ambient
     * "active session warehouse" global scope (added 2026-08-28 with the fase2/main merge, see
     * cdocs/docs/fase2-merge-plan.md Batch 11). This has to match whichever warehouse the CALLER
     * will actually credit: a plan built against warehouse A's unit list but applied to warehouse
     * B can roll into a unit that has no row in B, and ProductUnitStock::creditOneProductUnit()
     * CREATES a missing row — silently provisioning exactly the stock row this allow-list exists
     * to prevent. Pass null (default) only when the caller itself also writes through the ambient
     * scope, so both sides agree.
     */
    public static function allowedProductUnitIds(int $productVariantId, ?int $warehouseId = null): array
    {
        return (($warehouseId !== null)
                ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : ProductStock::query())
            ->where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->pluck('unit_id')
            ->map(fn ($u) => (int) $u)
            ->all();
    }

    /**
     * Units that already have an active SuppliesStock row — the default crediting policy.
     * $warehouseId behaves exactly as in allowedProductUnitIds(); see that method's note.
     */
    public static function allowedSuppliesUnitIds(int $suppliesId, ?int $warehouseId = null): array
    {
        return (($warehouseId !== null)
                ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : SuppliesStock::query())
            ->where('supplies_id', $suppliesId)
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
    public static function planProduct(int $productVariantId, int $startUnitId, int $qty, ?int $warehouseId = null): array
    {
        return self::plan(
            self::productChain($productVariantId),
            $startUnitId,
            $qty,
            self::allowedProductUnitIds($productVariantId, $warehouseId)
        );
    }

    /**
     * Convenience wrapper: plan a supplies roll-up restricted to units that already have stock rows.
     *
     * @return array<int, array{unit_id: int, qty: int}>
     */
    public static function planSupplies(int $suppliesId, int $startUnitId, int $qty, ?int $warehouseId = null): array
    {
        return self::plan(
            self::suppliesChain($suppliesId),
            $startUnitId,
            $qty,
            self::allowedSuppliesUnitIds($suppliesId, $warehouseId)
        );
    }
}
