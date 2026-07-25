<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 2 — Sinkronisasi Kategori (tabel `categories`).
 *
 * PMO tidak menerbitkan id kategori; `/getProducts` hanya membawa
 * `category_name`. Jadi namanya sendiri yang menjadi kunci, dan pencocokan
 * dilakukan tanpa membedakan huruf besar/kecil.
 *
 * Langkah ini menjadi berbasis id begitu PMO menambahkan `category_id`
 * (lihat §8.2 pada dokumen desain).
 */
class SyncCategoryStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->products();
            $result->withDetails($snapshot->details());

            // Satu kategori bisa dipakai banyak produk — dikumpulkan lebih dulu
            // supaya tiap kategori hanya diproses sekali.
            $categories = [];
            foreach ($snapshot->rows as $product) {
                $name = $this->pickString($product, ['category_name', 'category']);

                if ($name === '') {
                    $result->addError($this->productLabel($product).': category_name kosong.');

                    continue;
                }

                $categories[ReferenceMatcher::normalise($name)] ??= $name;
            }

            $matcher = (new ReferenceMatcher('categories', 'category_id', null, 'category_name'))->load();
            $now = Carbon::now();

            foreach ($categories as $name) {
                $result->processed++;

                $match = $matcher->match(null, $name);

                if ($match->isAmbiguous()) {
                    $result->failed++;
                    $result->addError(
                        'Kategori "'.$name.'": ada '.count($match->candidates)
                        .' kategori di Pegasus dengan nama sama (category_id '.implode(', ', $match->candidates)
                        .'). Gabungkan duplikatnya lebih dulu, lalu jalankan ulang langkah ini.'
                    );

                    continue;
                }

                if ($match->found()) {
                    // Nama sudah sama persis (pencocokan lewat nama), jadi tidak
                    // ada atribut lain dari PMO yang perlu ditulis.
                    $result->updated++;
                    $matcher->remember(null, $name, $match->localId);

                    continue;
                }

                $localId = (int) DB::table('categories')->insertGetId([
                    'category_name' => mb_substr($name, 0, 250),
                    'status' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'category_id');

                $matcher->remember(null, $name, $localId);
                $result->inserted++;
                $result->addNotice('Kategori "'.$name.'" ditambahkan (category_id '.$localId.').');
            }

            if ($result->processed === 0) {
                $result->succeed('Tidak ada kategori pada data PMO.');

                return;
            }

            $result->finish('Sinkronisasi kategori selesai.');
        });
    }
}
