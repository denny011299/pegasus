<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Support\MatchResult;
use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 3 — Sinkronisasi Produk (tabel `products`).
 *
 * Kunci mapan `products.ref_product_id`; pada sinkronisasi pertama diadopsi
 * lewat nama produk.
 *
 * Berbeda dengan draf flowchart yang hanya menyisipkan produk baru, langkah ini
 * juga **memperbarui** produk yang sudah ada — kalau tidak, perubahan nama,
 * kategori, satuan, atau status di PMO tidak akan pernah sampai ke Pegasus.
 */
class SyncProductStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->products();
            $result->withDetails($snapshot->details());

            $unitByRef = $this->unitMap();
            $categoryByName = $this->categoryMap();
            $matcher = (new ReferenceMatcher('products', 'product_id', 'ref_product_id', 'product_name'))->load();
            $now = Carbon::now();

            foreach ($snapshot->rows as $product) {
                $result->processed++;
                $label = $this->productLabel($product);

                $refProductId = $this->pickInt($product, ['ref_product_id', 'product_id', 'id']);
                $name = $this->pickString($product, ['product_name', 'name']);

                if ($refProductId === 0 || $name === '') {
                    $result->failed++;
                    $result->addError($label.': ref_product_id atau product_name kosong.');

                    continue;
                }

                // Kategori
                $categoryName = $this->pickString($product, ['category_name', 'category']);
                $categoryKey = ReferenceMatcher::normalise($categoryName);
                if ($categoryName === '' || ! isset($categoryByName[$categoryKey])) {
                    $result->failed++;
                    $result->addError(
                        $label.': kategori "'.$categoryName.'" belum ada di Pegasus. '
                        .'Jalankan ulang langkah Sinkronisasi Kategori.'
                    );

                    continue;
                }
                if ($categoryByName[$categoryKey] === null) {
                    $result->failed++;
                    $result->addError(
                        $label.': kategori "'.$categoryName.'" ganda di Pegasus, tidak bisa dipilih otomatis.'
                    );

                    continue;
                }

                // Satuan yang dipakai produk
                $localUnitIds = [];
                $unresolved = [];
                foreach ($this->pickList($product, ['units', 'unit']) as $unit) {
                    $refUnitId = $this->pickInt($unit, ['unit_id', 'id']);
                    if ($refUnitId === 0) {
                        continue;
                    }

                    if (! isset($unitByRef[$refUnitId])) {
                        $unresolved[] = $refUnitId;

                        continue;
                    }

                    $localUnitIds[$unitByRef[$refUnitId]] = true;
                }

                // Satuan dasar
                $refDefaultUnit = $this->pickInt($product, ['default_unit_id', 'unit_id']);
                if ($refDefaultUnit !== 0 && ! isset($unitByRef[$refDefaultUnit])) {
                    $unresolved[] = $refDefaultUnit;
                }

                if ($unresolved !== []) {
                    $result->failed++;
                    $result->addError(
                        $label.': satuan PMO '.implode(', ', array_unique($unresolved))
                        .' belum ada di Pegasus. Jalankan ulang langkah Sinkronisasi Satuan.'
                    );

                    continue;
                }

                $defaultUnitId = $refDefaultUnit !== 0 ? $unitByRef[$refDefaultUnit] : 0;
                if ($defaultUnitId !== 0) {
                    $localUnitIds[$defaultUnitId] = true;
                }

                if ($localUnitIds === []) {
                    $result->failed++;
                    $result->addError($label.': tidak ada satuan sama sekali, padahal Pegasus mewajibkannya.');

                    continue;
                }

                $match = $matcher->match($refProductId, $name);

                if ($match->isAmbiguous()) {
                    $result->failed++;
                    $result->addError(
                        $label.': ada '.count($match->candidates).' produk di Pegasus dengan nama sama (product_id '
                        .implode(', ', $match->candidates).'). Gabungkan duplikatnya lebih dulu, '
                        .'lalu jalankan ulang langkah ini.'
                    );

                    continue;
                }

                $attributes = [
                    'ref_product_id' => $refProductId,
                    'product_name' => mb_substr($name, 0, 250),
                    'category_id' => $categoryByName[$categoryKey],
                    // Disimpan sebagai array string, mengikuti bentuk yang sudah
                    // dipakai Pegasus: ["7","9"].
                    'product_unit' => json_encode(array_map('strval', array_keys($localUnitIds))),
                    'unit_id' => $defaultUnitId,
                    'status' => $this->pickStatus($product),
                    'updated_at' => $now,
                ];

                if ($match->found()) {
                    DB::table('products')->where('product_id', $match->localId)->update($attributes);
                    $result->updated++;

                    if ($match->kind === MatchResult::ADOPTED) {
                        $result->addNotice(
                            $label.' diadopsi ke produk Pegasus yang sudah ada (product_id '.$match->localId.').'
                        );
                    }

                    $matcher->remember($refProductId, $name, $match->localId);

                    continue;
                }

                $localId = (int) DB::table('products')->insertGetId(
                    $attributes + ['product_alert' => 0, 'created_at' => $now],
                    'product_id'
                );

                $matcher->remember($refProductId, $name, $localId);
                $result->inserted++;
            }

            if ($result->processed === 0) {
                $result->succeed('PMO tidak mengirimkan satu pun produk.');

                return;
            }

            $result->finish('Sinkronisasi produk selesai.');
        });
    }

    /**
     * ref_unit_id PMO => unit_id Pegasus.
     *
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

    /**
     * Nama ternormalisasi => category_id, atau null bila namanya ganda.
     *
     * @return array<string, int|null>
     */
    private function categoryMap(): array
    {
        $map = [];

        foreach (DB::table('categories')->select('category_id', 'category_name')->get() as $row) {
            $key = ReferenceMatcher::normalise((string) $row->category_name);
            if ($key === '') {
                continue;
            }

            $map[$key] = array_key_exists($key, $map) ? null : (int) $row->category_id;
        }

        return $map;
    }
}
