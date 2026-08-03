<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 7 — Laporan Selisih Pegasus vs PMO.
 *
 * Keputusan D3: sinkronisasi tidak pernah menonaktifkan baris Pegasus yang
 * berhenti dikirim PMO. Langkah ini yang memunculkannya supaya operator sendiri
 * yang memutuskan lewat layar master. **Tidak menulis apa pun.**
 *
 * Di sinilah varian yang "hilang" akibat penggantian SKU di PMO muncul —
 * lihat keputusan D5.
 */
class ReportDifferenceStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->products();
            $result->withDetails($snapshot->details());

            // Apa yang PMO kirim.
            $pmoProductRefs = [];
            $pmoSkuByRef = [];
            foreach ($snapshot->rows as $product) {
                $ref = $this->pickInt($product, ['ref_product_id', 'product_id', 'id']);
                if ($ref === 0) {
                    continue;
                }

                $pmoProductRefs[$ref] = true;

                foreach ($this->pickList($product, ['variant', 'variants']) as $variant) {
                    $sku = ReferenceMatcher::normalise($this->pickString($variant, ['variant_sku', 'sku']));
                    if ($sku !== '') {
                        $pmoSkuByRef[$ref][$sku] = true;
                    }
                }
            }

            $linkedProducts = DB::table('products')
                ->whereNotNull('ref_product_id')
                ->get(['product_id', 'ref_product_id', 'product_name', 'status']);

            $missingProducts = 0;
            $missingVariants = 0;
            $linkedProductIds = [];

            foreach ($linkedProducts as $product) {
                $ref = (int) $product->ref_product_id;
                $linkedProductIds[(int) $product->product_id] = $ref;
                $result->processed++;

                if (! isset($pmoProductRefs[$ref])) {
                    $missingProducts++;
                    $result->addNotice(
                        'Produk "'.$product->product_name.'" (product_id '.$product->product_id
                        .', ref_product_id '.$ref.') sudah tidak dikirim PMO'
                        .((int) $product->status === 1 ? ' tetapi masih aktif di Pegasus.' : ' dan sudah nonaktif.')
                    );
                }
            }

            if ($linkedProductIds !== []) {
                $variants = DB::table('product_variants')
                    ->whereIn('product_id', array_keys($linkedProductIds))
                    ->get(['product_variant_id', 'product_id', 'product_variant_sku', 'product_variant_name', 'status']);

                foreach ($variants as $variant) {
                    $ref = $linkedProductIds[(int) $variant->product_id] ?? null;
                    if ($ref === null || ! isset($pmoProductRefs[$ref])) {
                        // Produknya sendiri sudah hilang; sudah dilaporkan di atas.
                        continue;
                    }

                    $sku = ReferenceMatcher::normalise((string) $variant->product_variant_sku);

                    if ($sku === '' || ! isset($pmoSkuByRef[$ref][$sku])) {
                        $missingVariants++;
                        $result->addNotice(
                            'Varian "'.($variant->product_variant_sku ?: '(SKU kosong)').'" / "'
                            .$variant->product_variant_name.'" (product_variant_id '.$variant->product_variant_id
                            .') tidak ada pada data PMO'
                            .((int) $variant->status === 1 ? ' tetapi masih aktif di Pegasus.' : ' dan sudah nonaktif.')
                        );
                    }
                }
            }

            $unlinkedProducts = DB::table('products')->whereNull('ref_product_id')->count();

            $result->withDetails([
                'Produk Terhubung ke PMO' => $linkedProducts->count(),
                'Produk Belum Terhubung' => $unlinkedProducts,
                'Produk Hilang dari PMO' => $missingProducts,
                'Varian Hilang dari PMO' => $missingVariants,
            ]);

            $result->succeed(
                $missingProducts + $missingVariants === 0
                    ? 'Tidak ada selisih. Seluruh data Pegasus yang terhubung masih dikirim PMO.'
                    : 'Ditemukan '.$missingProducts.' produk dan '.$missingVariants
                        .' varian yang ada di Pegasus tetapi tidak dikirim PMO. '
                        .'Tidak ada data yang diubah — silakan tindak lanjuti lewat layar master.'
            );
        });
    }
}
