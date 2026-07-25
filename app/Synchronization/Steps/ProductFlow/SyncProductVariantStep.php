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
 * Identitas dua fase, sesuai keputusan D5:
 *   fase mapan  — (product_id, variant_sku)
 *   fase adopsi — nama varian di dalam produk yang sama, lalu SKU dari PMO
 *                 ditulis ke baris itu (kotak "Ganti SKU Variant product" pada
 *                 flowchart)
 *
 * SKU yang berubah dianggap varian baru; varian lama dilaporkan langkah 7
 * sebagai kejanggalan, tidak pernah dihapus otomatis.
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
            $now = Carbon::now();

            foreach ($snapshot->rows as $product) {
                $label = $this->productLabel($product);
                $refProductId = $this->pickInt($product, ['ref_product_id', 'product_id', 'id']);
                $variants = $this->pickList($product, ['variant', 'variants']);

                if ($variants === []) {
                    continue;
                }

                if (! isset($productByRef[$refProductId])) {
                    $result->processed += count($variants);
                    $result->failed += count($variants);
                    $result->addError(
                        $label.': produk belum ada di Pegasus, '.count($variants)
                        .' varian dilewati. Jalankan ulang langkah Sinkronisasi Produk.'
                    );

                    continue;
                }

                $localProductId = $productByRef[$refProductId];
                $refDefaultUnit = $this->pickInt($product, ['default_unit_id', 'unit_id']);
                $localUnitId = $unitByRef[$refDefaultUnit] ?? 0;

                $pool = $localVariants[$localProductId] ?? [];
                $claimed = [];
                $assignment = [];
                $blocked = [];

                // Fase 1 — cocokkan lewat SKU (kunci mapan).
                foreach ($variants as $i => $variant) {
                    $sku = ReferenceMatcher::normalise($this->pickString($variant, ['variant_sku', 'sku']));
                    if ($sku === '') {
                        continue;
                    }

                    $hits = $this->candidates($pool, 'sku', $sku, $claimed);

                    if (count($hits) === 1) {
                        $assignment[$i] = $hits[0];
                        $claimed[$hits[0]] = true;
                    } elseif (count($hits) > 1) {
                        $blocked[$i] = 'SKU "'.$this->pickString($variant, ['variant_sku', 'sku'])
                            .'" cocok dengan '.count($hits).' varian Pegasus pada produk yang sama '
                            .'(product_variant_id '.implode(', ', $hits).'). Rapikan duplikatnya lebih dulu.';
                    }
                }

                // Fase 2 — adopsi lewat nama untuk varian yang belum terpetakan.
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

                    $attributes = [
                        'product_id' => $localProductId,
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
                    $claimed[$localId] = true;
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
