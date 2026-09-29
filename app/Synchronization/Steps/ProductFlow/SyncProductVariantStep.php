<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Models\ProductVariant;
use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 4 — Sinkronisasi Varian Produk (tabel `product_variants`).
 *
 * Kunci utama = **SKU, tidak case-sensitive** (`ReferenceMatcher::normalise` = lower+trim).
 *
 *   fase 1 — SKU global lintas produk; utamakan varian di produk tanpa `ref_product_id`
 *            (IPM lokal). Varian **tetap di produk IPM** (jangan pindah ke shell PMO).
 *            Ref PMO dipasang ke produk IPM bila masih NULL; shell PMO kosong dimatikan.
 *   fase 2 — nama varian di dalam produk target yang sama (fallback), SKU PMO ditulis.
 *
 * Model benar: 1 produk IPM banyak varian. Sync PMO 1-SKU/produk tidak boleh
 * memecah induk IPM jadi banyak produk sejenis.
 *
 * `product_variant_price` dan `product_variant_stock` tidak pernah ditulis —
 * PMO tidak mengirimkannya dan keduanya milik Pegasus.
 */
class SyncProductVariantStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->products();
            $result->withDetails($snapshot->details());

            $productByRef = $this->productMap();
            $unitByRef = $this->unitMap();
            $localVariants = $this->localVariantsByProduct();
            $globalBySku = $this->globalVariantsBySku();
            $productsHaveRef = $this->productsWithRef();
            $now = Carbon::now();

            foreach ($snapshot->rows as $product) {
                $label = $this->productLabel($product);
                $refProductId = $this->pickInt($product, ['ref_product_id', 'product_id', 'id']);
                $variants = $this->pickList($product, ['variant', 'variants']);

                if ($variants === []) {
                    continue;
                }

                if (! isset($productByRef[$refProductId])) {
                    // Ref belum di products (induk IPM sudah pegang ref lain) → cari lewat SKU.
                    $viaSku = $this->resolveProductIdViaPayloadSku($variants, $globalBySku, $productsHaveRef);
                    if ($viaSku === null) {
                        $result->processed += count($variants);
                        $result->failed += count($variants);
                        $result->addError(
                            $label.': produk belum ada di Pegasus, '.count($variants)
                            .' varian dilewati. Jalankan ulang langkah Sinkronisasi Produk.'
                        );

                        continue;
                    }
                    $localProductId = $viaSku;
                    $result->addNotice(
                        $label.': produk disambung lewat SKU ke product_id '.$localProductId
                        .' (ref '.$refProductId.' tidak unik di induk).'
                    );
                } else {
                    $localProductId = $productByRef[$refProductId];
                }
                $refDefaultUnit = $this->pickInt($product, ['default_unit_id', 'unit_id']);
                $localUnitId = $unitByRef[$refDefaultUnit] ?? 0;

                $pool = $localVariants[$localProductId] ?? [];
                $claimed = [];
                $assignment = [];
                $blocked = [];

                // Fase 1 — SKU global case-insensitive (utamakan IPM lokal tanpa ref).
                // Harus sebelum match in-product supaya twin sync-PMO (stok 0) tidak menang
                // atas baris lokal berstok dengan SKU sama beda huruf.
                foreach ($variants as $i => $variant) {
                    $skuRaw = $this->pickString($variant, ['variant_sku', 'sku']);
                    $sku = ReferenceMatcher::normalise($skuRaw);
                    if ($sku === '') {
                        continue;
                    }

                    $hits = $this->globalSkuCandidates($globalBySku, $sku, $claimed, $productsHaveRef);

                    if (count($hits) === 1) {
                        $assignment[$i] = $hits[0];
                        $claimed[$hits[0]] = true;
                        // Notice hanya jika varian datang dari produk lain / SKU beda ejaan.
                        $fromOther = true;
                        foreach ($pool as $row) {
                            if ((int) $row->id === (int) $hits[0]) {
                                $fromOther = false;
                                break;
                            }
                        }
                        if ($fromOther) {
                            $result->addNotice(
                                $label.': varian diadopsi lewat SKU (abaikan huruf besar/kecil) ke '
                                .'product_variant_id '.$hits[0].' (tetap di produk IPM), SKU menjadi "'
                                .$skuRaw.'".'
                            );
                        }
                    } elseif (count($hits) > 1) {
                        $blocked[$i] = 'SKU "'.$skuRaw.'" (case-insensitive) cocok dengan '
                            .count($hits).' varian Pegasus '
                            .'(product_variant_id '.implode(', ', $hits).'). Rapikan duplikatnya lebih dulu.';
                    }
                }

                // Fase 2 — adopsi lewat nama di produk yang sama (belum ketemu SKU).
                foreach ($variants as $i => $variant) {
                    if (isset($assignment[$i]) || isset($blocked[$i])) {
                        continue;
                    }

                    $name = ReferenceMatcher::normalise($this->pickString($variant, ['variant_name', 'name']));
                    if ($name === '') {
                        continue;
                    }

                    $hits = $this->candidates($pool, 'name', $name, $claimed);

                    if (count($hits) === 1) {
                        $assignment[$i] = $hits[0];
                        $claimed[$hits[0]] = true;
                        $result->addNotice(
                            $label.': varian "'.$this->pickString($variant, ['variant_name', 'name'])
                            .'" diadopsi ke product_variant_id '.$hits[0].', SKU diganti menjadi "'
                            .$this->pickString($variant, ['variant_sku', 'sku']).'".'
                        );
                    } elseif (count($hits) > 1) {
                        $blocked[$i] = 'nama varian "'.$this->pickString($variant, ['variant_name', 'name'])
                            .'" cocok dengan '.count($hits).' varian Pegasus pada produk yang sama '
                            .'(product_variant_id '.implode(', ', $hits).'). Rapikan duplikatnya lebih dulu.';
                    }
                }

                // Tulis.
                foreach ($variants as $i => $variant) {
                    $result->processed++;

                    $sku = $this->pickString($variant, ['variant_sku', 'sku']);
                    $name = $this->pickString($variant, ['variant_name', 'name']);

                    if (isset($blocked[$i])) {
                        $result->failed++;
                        $result->addError($label.': '.$blocked[$i]);

                        continue;
                    }

                    if ($sku === '') {
                        $result->failed++;
                        $result->addError(
                            $label.': varian tanpa variant_sku'.($name !== '' ? ' (nama "'.$name.'")' : '')
                            .' tidak bisa disinkronkan, SKU adalah kuncinya.'
                        );

                        continue;
                    }

                    $alert = $this->resolveAlert($variant, $refDefaultUnit);
                    if ($alert === false) {
                        $result->addNotice(
                            $label.': peringatan stok varian "'.$sku.'" tidak dikonversi — '
                            .'tidak ada konversi satuan yang menghubungkan alert_stock_unit_id dengan satuan varian.'
                        );
                    }

                    // Default: tulis ke produk hasil SyncProductStep (punya ref).
                    // Kalau adopsi varian IPM lokal (produk tanpa ref) → JANGAN pindah induk.
                    $targetProductId = $localProductId;
                    $adoptedLocal = false;
                    if (isset($assignment[$i])) {
                        $existing = DB::table('product_variants')
                            ->where('product_variant_id', $assignment[$i])
                            ->first(['product_id']);
                        if ($existing !== null) {
                            $existingPid = (int) $existing->product_id;
                            if ($existingPid !== $localProductId && ! isset($productsHaveRef[$existingPid])) {
                                $targetProductId = $existingPid;
                                $adoptedLocal = true;
                            }
                        }
                    }

                    $attributes = [
                        'product_id' => $targetProductId,
                        'product_variant_name' => mb_substr($name, 0, 100),
                        'product_variant_sku' => mb_substr($sku, 0, 100),
                        'unit_id' => $localUnitId,
                        'status' => $this->pickStatus($product),
                        'updated_at' => $now,
                    ];

                    if (is_int($alert)) {
                        $attributes['product_variant_alert'] = $alert;
                    }

                    $barcode = $this->pickString($variant, ['variant_barcode', 'barcode']);
                    if ($barcode !== '') {
                        $attributes['product_variant_barcode'] = $barcode;
                    }

                    if (isset($assignment[$i])) {
                        DB::table('product_variants')
                            ->where('product_variant_id', $assignment[$i])
                            ->update($attributes);
                        $result->updated++;

                        if ($adoptedLocal) {
                            $this->attachRefToIpmProduct($targetProductId, $localProductId, $refProductId, $result, $label);
                            $productsHaveRef[$targetProductId] = true;
                            unset($productsHaveRef[$localProductId]);
                        }

                        // Pool produk yang menerima varian harus kenal SKU ini.
                        $poolTarget = $localVariants[$targetProductId] ?? [];
                        $poolTarget[] = (object) [
                            'id' => $assignment[$i],
                            'sku' => ReferenceMatcher::normalise($sku),
                            'name' => ReferenceMatcher::normalise($name),
                        ];
                        $localVariants[$targetProductId] = $poolTarget;
                        if ($targetProductId === $localProductId) {
                            $pool = $poolTarget;
                        }
                        $this->rememberGlobalSku($globalBySku, $sku, $assignment[$i], $targetProductId);

                        continue;
                    }

                    $localId = (int) DB::table('product_variants')->insertGetId(
                        $attributes + [
                            'product_variant_barcode' => $barcode !== ''
                                ? $barcode
                                : (new ProductVariant())->generateBarcode(),
                            'product_variant_alert' => is_int($alert) ? $alert : 0,
                            'created_at' => $now,
                        ],
                        'product_variant_id'
                    );

                    $pool[] = (object) ['id' => $localId, 'sku' => ReferenceMatcher::normalise($sku), 'name' => ReferenceMatcher::normalise($name)];
                    $localVariants[$localProductId] = $pool;
                    $claimed[$localId] = true;
                    $this->rememberGlobalSku($globalBySku, ReferenceMatcher::normalise($sku), $localId, $localProductId);
                    $result->inserted++;
                }
            }

            if ($result->processed === 0) {
                $result->succeed('Tidak ada varian pada data PMO.');

                return;
            }

            $result->finish('Sinkronisasi varian produk selesai.');
        });
    }

    /**
     * Pindahkan ref dari shell produk PMO ke induk IPM (jika IPM masih NULL),
     * lalu matikan shell bila tidak punya varian aktif.
     */
    private function attachRefToIpmProduct(
        int $ipmProductId,
        int $pmoShellProductId,
        int $refProductId,
        SyncStepResult $result,
        string $label
    ): void {
        $ipm = DB::table('products')->where('product_id', $ipmProductId)->first(['ref_product_id']);
        if ($ipm === null) {
            return;
        }

        if ($ipm->ref_product_id === null || $ipm->ref_product_id === '') {
            // UNIQUE: lepas dulu dari shell PMO
            DB::table('products')
                ->where('product_id', $pmoShellProductId)
                ->update(['ref_product_id' => null, 'updated_at' => now()]);

            DB::table('products')
                ->where('product_id', $ipmProductId)
                ->update([
                    'ref_product_id' => $refProductId,
                    'status' => 1,
                    'updated_at' => now(),
                ]);

            $result->addNotice(
                $label.': ref_product_id '.$refProductId.' dipasang ke produk IPM '.$ipmProductId.'.'
            );
        }

        $activeLeft = (int) DB::table('product_variants')
            ->where('product_id', $pmoShellProductId)
            ->where('status', 1)
            ->count();

        if ($activeLeft === 0 && $pmoShellProductId !== $ipmProductId) {
            DB::table('products')
                ->where('product_id', $pmoShellProductId)
                ->update(['status' => 0, 'updated_at' => now()]);
        }
    }

    /**
     * Peringatan stok PMO dinyatakan sebagai qty + satuan; Pegasus hanya
     * menyimpan satu angka dalam satuan varian itu sendiri. Konversi memakai
     * rasio pada `relation` (keputusan D4).
     *
     * @param  array<string, mixed>  $variant
     * @return int|false|null  int = nilai siap simpan, false = tidak bisa dikonversi, null = PMO tidak mengirim
     */
    private function resolveAlert(array $variant, int $variantUnitRef): int|false|null
    {
        $qty = $this->pick($variant, ['alert_stock_qty']);
        if ($qty === null || ! is_numeric($qty)) {
            return null;
        }

        $qty = (float) $qty;
        $alertUnitRef = $this->pickInt($variant, ['alert_stock_unit_id'], $variantUnitRef);

        if ($alertUnitRef === 0 || $alertUnitRef === $variantUnitRef) {
            return (int) ceil($qty);
        }

        $relation = $this->pickObject($variant, ['relation']);
        if ($relation === null) {
            return false;
        }

        $from = $this->pickInt($relation, ['from_unit_id']);
        $fromQty = $this->pickInt($relation, ['from_unit_qty'], 1);
        $to = $this->pickInt($relation, ['to_unit_id']);
        $toQty = $this->pickInt($relation, ['to_unit_qty']);

        if ($fromQty <= 0 || $toQty <= 0) {
            return false;
        }

        if ($alertUnitRef === $to && $variantUnitRef === $from) {
            return (int) ceil($qty * $fromQty / $toQty);
        }

        if ($alertUnitRef === $from && $variantUnitRef === $to) {
            return (int) ceil($qty * $toQty / $fromQty);
        }

        return false;
    }

    /**
     * Bila ref PMO belum tertulis di products (induk sudah punya ref lain),
     * temukan product_id dari SKU payload yang sudah ada di DB.
     *
     * @param  array<int, array<string, mixed>>  $variants
     * @param  array<string, array<int, object>>  $globalBySku
     * @param  array<int, bool>  $productsHaveRef
     */
    private function resolveProductIdViaPayloadSku(
        array $variants,
        array $globalBySku,
        array $productsHaveRef
    ): ?int {
        $productHits = [];

        foreach ($variants as $variant) {
            $sku = ReferenceMatcher::normalise($this->pickString($variant, ['variant_sku', 'sku']));
            if ($sku === '') {
                continue;
            }
            foreach ($globalBySku[$sku] ?? [] as $row) {
                if ((int) ($row->status ?? 1) !== 1) {
                    continue;
                }
                $pid = (int) $row->product_id;
                // Utamakan induk tanpa ref; kalau semua sudah ber-ref, tetap terima.
                $productHits[$pid] = ! isset($productsHaveRef[$pid]) ? 2 : 1;
            }
        }

        if ($productHits === []) {
            return null;
        }

        arsort($productHits);
        $topScore = reset($productHits);
        $top = array_keys(array_filter($productHits, static fn ($s) => $s === $topScore));

        return count($top) === 1 ? (int) $top[0] : null;
    }

    /**
     * @param  array<int, object>  $pool
     * @param  array<int, bool>  $claimed
     * @return array<int, int>
     */
    private function candidates(array $pool, string $field, string $value, array $claimed): array
    {
        $hits = [];

        foreach ($pool as $row) {
            if ($row->{$field} === $value && ! isset($claimed[$row->id])) {
                $hits[] = $row->id;
            }
        }

        return $hits;
    }

    /**
     * Cari varian di seluruh DB by SKU ternormalisasi (lowercase).
     * Kalau lebih dari satu: utamakan yang produknya belum punya ref_product_id
     * (IPM lokal). Kalau masih >1 → ambiguous.
     *
     * @param  array<string, array<int, object>>  $globalBySku
     * @param  array<int, bool>  $claimed
     * @param  array<int, bool>  $productsHaveRef  product_id => true bila sudah ber-ref
     * @return array<int, int>
     */
    private function globalSkuCandidates(
        array $globalBySku,
        string $skuNorm,
        array $claimed,
        array $productsHaveRef
    ): array {
        $rows = $globalBySku[$skuNorm] ?? [];
        $hits = [];

        foreach ($rows as $row) {
            if (isset($claimed[$row->id])) {
                continue;
            }
            $hits[] = $row;
        }

        if (count($hits) <= 1) {
            return array_map(static fn ($r) => (int) $r->id, $hits);
        }

        // Utamakan lokal (produk tanpa ref PMO) supaya twin sync-PMO kosong tidak menang.
        $localOnly = array_values(array_filter(
            $hits,
            static fn ($r) => ! isset($productsHaveRef[(int) $r->product_id])
        ));

        if (count($localOnly) === 1) {
            return [(int) $localOnly[0]->id];
        }

        if (count($localOnly) > 1) {
            return array_map(static fn ($r) => (int) $r->id, $localOnly);
        }

        return array_map(static fn ($r) => (int) $r->id, $hits);
    }

    /**
     * @return array<int, array<int, object>>
     */
    private function localVariantsByProduct(): array
    {
        $out = [];

        $rows = DB::table('product_variants')
            ->select('product_variant_id', 'product_id', 'product_variant_sku', 'product_variant_name')
            ->get();

        foreach ($rows as $row) {
            $out[(int) $row->product_id][] = (object) [
                'id' => (int) $row->product_variant_id,
                'sku' => ReferenceMatcher::normalise((string) $row->product_variant_sku),
                'name' => ReferenceMatcher::normalise((string) $row->product_variant_name),
            ];
        }

        return $out;
    }

    /**
     * SKU ternormalisasi => daftar varian (lintas produk).
     *
     * @return array<string, array<int, object>>
     */
    private function globalVariantsBySku(): array
    {
        $out = [];

        $rows = DB::table('product_variants')
            ->select('product_variant_id', 'product_id', 'product_variant_sku', 'status')
            ->get();

        foreach ($rows as $row) {
            $sku = ReferenceMatcher::normalise((string) $row->product_variant_sku);
            if ($sku === '') {
                continue;
            }

            $out[$sku][] = (object) [
                'id' => (int) $row->product_variant_id,
                'product_id' => (int) $row->product_id,
                'status' => (int) $row->status,
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, array<int, object>>  $globalBySku
     */
    private function rememberGlobalSku(array &$globalBySku, string $skuNorm, int $variantId, int $productId): void
    {
        if ($skuNorm === '') {
            return;
        }

        // Lepas entri lama untuk id yang sama (pindah produk).
        foreach ($globalBySku as $key => $rows) {
            $globalBySku[$key] = array_values(array_filter(
                $rows,
                static fn ($r) => (int) $r->id !== $variantId
            ));
            if ($globalBySku[$key] === []) {
                unset($globalBySku[$key]);
            }
        }

        $globalBySku[$skuNorm][] = (object) [
            'id' => $variantId,
            'product_id' => $productId,
            'status' => 1,
        ];
    }

    /**
     * @return array<int, bool>
     */
    private function productsWithRef(): array
    {
        $out = [];
        foreach (DB::table('products')->whereNotNull('ref_product_id')->pluck('product_id') as $id) {
            $out[(int) $id] = true;
        }

        return $out;
    }

    /**
     * @return array<int, int>
     */
    private function productMap(): array
    {
        return DB::table('products')
            ->whereNotNull('ref_product_id')
            ->pluck('product_id', 'ref_product_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function unitMap(): array
    {
        return DB::table('units')
            ->whereNotNull('ref_unit_id')
            ->pluck('unit_id', 'ref_unit_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }
}
