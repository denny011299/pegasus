<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 5 — Sinkronisasi Konversi Satuan (tabel `product_relations`).
 *
 * Satu baris menyatakan "sekian satuan A setara sekian satuan B" untuk satu
 * varian produk. Tanpa ini, perhitungan stok antar satuan (UnitStockSorter,
 * cekStockBerlebih, stok opname) tidak punya dasar.
 *
 * Identitas (product_variant_id, pr_unit_id_1, pr_unit_id_2). PMO hanya
 * mengirim satu `relation` per varian, sedangkan Pegasus menampung banyak —
 * baris lokal yang tidak dijelaskan PMO dibiarkan dan dilaporkan langkah 7.
 */
class SyncProductRelationStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->products();
            $result->withDetails($snapshot->details());

            $productByRef = $this->productMap();
            $unitByRef = $this->unitMap();
            $variantBySku = $this->variantMap();
            $existing = $this->existingRelations();
            $now = Carbon::now();

            foreach ($snapshot->rows as $product) {
                $label = $this->productLabel($product);
                $refProductId = $this->pickInt($product, ['ref_product_id', 'product_id', 'id']);
                $localProductId = $productByRef[$refProductId] ?? null;

                foreach ($this->pickList($product, ['variant', 'variants']) as $variant) {
                    $relation = $this->pickObject($variant, ['relation']);
                    if ($relation === null) {
                        // PMO menandai relation sebagai opsional.
                        continue;
                    }

                    $result->processed++;

                    $sku = $this->pickString($variant, ['variant_sku', 'sku']);
                    $variantKey = $localProductId.'|'.ReferenceMatcher::normalise($sku);

                    if ($localProductId === null || ! isset($variantBySku[$variantKey])) {
                        $result->failed++;
                        $result->addError(
                            $label.': varian "'.$sku.'" belum ada di Pegasus. '
                            .'Jalankan ulang langkah Sinkronisasi Varian Produk.'
                        );

                        continue;
                    }

                    $localVariantId = $variantBySku[$variantKey];

                    $refFrom = $this->pickInt($relation, ['from_unit_id']);
                    $refTo = $this->pickInt($relation, ['to_unit_id']);
                    $unresolved = array_values(array_filter(
                        [$refFrom, $refTo],
                        static fn (int $ref) => $ref === 0 || ! isset($unitByRef[$ref])
                    ));

                    if ($unresolved !== []) {
                        $result->failed++;
                        $result->addError(
                            $label.' varian "'.$sku.'": satuan PMO '.implode(', ', $unresolved)
                            .' belum ada di Pegasus. Jalankan ulang langkah Sinkronisasi Satuan.'
                        );

                        continue;
                    }

                    $toQty = $this->pickInt($relation, ['to_unit_qty']);
                    if ($toQty <= 0) {
                        $result->failed++;
                        $result->addError(
                            $label.' varian "'.$sku.'": to_unit_qty harus lebih besar dari 0.'
                        );

                        continue;
                    }

                    $fromUnitId = $unitByRef[$refFrom];
                    $toUnitId = $unitByRef[$refTo];

                    $attributes = [
                        'product_variant_id' => $localVariantId,
                        'pr_unit_id_1' => $fromUnitId,
                        'pr_unit_value_1' => max(1, $this->pickInt($relation, ['from_unit_qty'], 1)),
                        'pr_unit_id_2' => $toUnitId,
                        'pr_unit_value_2' => $toQty,
                        'status' => 1,
                        'updated_at' => $now,
                    ];

                    $key = $localVariantId.'|'.$fromUnitId.'|'.$toUnitId;

                    if (isset($existing[$key])) {
                        DB::table('product_relations')->where('pr_id', $existing[$key])->update($attributes);
                        $result->updated++;

                        continue;
                    }

                    $prId = (int) DB::table('product_relations')->insertGetId(
                        // pr_default tidak dipakai Pegasus (0 dari 319 baris).
                        $attributes + ['pr_default' => 0, 'created_at' => $now],
                        'pr_id'
                    );

                    $existing[$key] = $prId;
                    $result->inserted++;
                }
            }

            if ($result->processed === 0) {
                $result->succeed('Tidak ada konversi satuan pada data PMO.');

                return;
            }

            $result->finish('Sinkronisasi konversi satuan selesai.');
        });
    }

    /**
     * "product_id|sku" => product_variant_id.
     *
     * @return array<string, int>
     */
    private function variantMap(): array
    {
        $map = [];

        $rows = DB::table('product_variants')
            ->select('product_variant_id', 'product_id', 'product_variant_sku')
            ->get();

        foreach ($rows as $row) {
            $sku = ReferenceMatcher::normalise((string) $row->product_variant_sku);
            if ($sku === '') {
                continue;
            }

            $map[$row->product_id.'|'.$sku] ??= (int) $row->product_variant_id;
        }

        return $map;
    }

    /**
     * "variant|unit1|unit2" => pr_id.
     *
     * @return array<string, int>
     */
    private function existingRelations(): array
    {
        $map = [];

        $rows = DB::table('product_relations')
            ->select('pr_id', 'product_variant_id', 'pr_unit_id_1', 'pr_unit_id_2')
            ->get();

        foreach ($rows as $row) {
            $map[$row->product_variant_id.'|'.$row->pr_unit_id_1.'|'.$row->pr_unit_id_2] ??= (int) $row->pr_id;
        }

        return $map;
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
