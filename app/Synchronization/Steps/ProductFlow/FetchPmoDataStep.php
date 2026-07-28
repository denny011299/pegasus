<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;

/**
 * Langkah 0 — Ambil & Periksa Data PMO.
 *
 * Selalu menarik ulang /getProducts dan menyimpannya sebagai potret yang
 * dipakai bersama langkah 2–5 dan 7. Tidak menulis satu pun data master:
 * tujuannya memastikan PMO terjangkau dan datanya layak sebelum ada yang
 * ditulis, sekaligus memperlihatkan kejanggalan lebih dulu.
 */
class FetchPmoDataStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->snapshots->refresh(self::FLOW_KEY, 'products');

            if ($snapshot->rows === []) {
                $result->withDetails($snapshot->details());
                $result->succeed('PMO tidak mengirimkan satu pun produk.');

                return;
            }

            $units = [];
            $categories = [];
            $variantCount = 0;
            $relationCount = 0;

            foreach ($snapshot->rows as $index => $product) {
                $result->processed++;
                $label = $this->productLabel($product);

                $declaredUnits = [];
                foreach ($this->pickList($product, ['units', 'unit']) as $unit) {
                    $unitId = $this->pickInt($unit, ['unit_id', 'id']);
                    if ($unitId !== 0) {
                        $declaredUnits[$unitId] = true;
                        $units[$unitId] = $this->pickString($unit, ['unit_name', 'name']);
                    }
                }

                if ($this->pickInt($product, ['ref_product_id', 'product_id', 'id']) === 0) {
                    $result->failed++;
                    $result->addError('Baris ke-'.($index + 1).': tidak ada ref_product_id.');
                }

                if ($this->pickString($product, ['product_name', 'name']) === '') {
                    $result->failed++;
                    $result->addError($label.': nama produk kosong.');
                }

                $category = $this->pickString($product, ['category_name', 'category']);
                if ($category === '') {
                    $result->failed++;
                    $result->addError($label.': category_name kosong, padahal kategori wajib di Pegasus.');
                } else {
                    $categories[ReferenceMatcher::normalise($category)] = true;
                }

                $defaultUnit = $this->pickInt($product, ['default_unit_id', 'unit_id']);
                if ($defaultUnit !== 0 && ! isset($declaredUnits[$defaultUnit])) {
                    $result->addError($label.': default_unit_id '.$defaultUnit.' tidak ada di dalam units[].');
                }

                $seenSku = [];
                foreach ($this->pickList($product, ['variant', 'variants']) as $variant) {
                    $variantCount++;

                    $sku = $this->pickString($variant, ['variant_sku', 'sku']);
                    $name = $this->pickString($variant, ['variant_name', 'name']);

                    if ($sku === '') {
                        $result->addError($label.': ada varian tanpa variant_sku'
                            .($name !== '' ? ' (nama "'.$name.'")' : '').'.');
                    } else {
                        $key = ReferenceMatcher::normalise($sku);
                        if (isset($seenSku[$key])) {
                            $result->addError($label.': SKU "'.$sku.'" muncul lebih dari sekali dalam satu produk.');
                        }
                        $seenSku[$key] = true;
                    }

                    if ($name === '') {
                        $result->addError($label.': ada varian tanpa variant_name (SKU "'.$sku.'").');
                    }

                    $alertUnit = $this->pickInt($variant, ['alert_stock_unit_id']);
                    if ($alertUnit !== 0 && ! isset($declaredUnits[$alertUnit])) {
                        $result->addError($label.': alert_stock_unit_id '.$alertUnit.' tidak ada di dalam units[].');
                    }

                    $relation = $this->pickObject($variant, ['relation']);
                    if ($relation === null) {
                        continue;
                    }

                    $relationCount++;

                    foreach (['from_unit_id', 'to_unit_id'] as $field) {
                        $unitId = $this->pickInt($relation, [$field]);
                        if ($unitId !== 0 && ! isset($declaredUnits[$unitId])) {
                            $result->addError($label.': relation.'.$field.' '.$unitId.' tidak ada di dalam units[].');
                        }
                    }

                    if ($this->pickInt($relation, ['to_unit_qty']) <= 0) {
                        $result->addError($label.': relation.to_unit_qty harus lebih besar dari 0.');
                    }
                }
            }

            $result->withDetails($snapshot->details() + [
                'Jumlah Produk' => count($snapshot->rows),
                'Jumlah Satuan Berbeda' => count($units),
                'Jumlah Kategori Berbeda' => count($categories),
                'Jumlah Varian' => $variantCount,
                'Jumlah Konversi Satuan' => $relationCount,
            ]);

            if ($result->failed > 0) {
                $result->fail(
                    $result->failed.' baris PMO tidak memenuhi syarat minimum. '
                    .'Perbaiki di sisi PMO sebelum menjalankan langkah berikutnya.'
                );

                return;
            }

            $result->succeed(
                'Data PMO berhasil diambil dan diperiksa. '
                .count($snapshot->rows).' produk siap disinkronkan.'
            );
        });
    }
}
