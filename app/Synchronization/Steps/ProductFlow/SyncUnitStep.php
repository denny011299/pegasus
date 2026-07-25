<?php

namespace App\Synchronization\Steps\ProductFlow;

use App\Synchronization\Support\MatchResult;
use App\Synchronization\Support\ReferenceMatcher;
use App\Synchronization\SyncStepResult;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Langkah 1 — Sinkronisasi Satuan (tabel `units`), sumber `GET /getUnits`.
 *
 * Akar seluruh alur: products.unit_id, products.product_unit,
 * product_variants.unit_id, product_relations.pr_unit_id_1/2, dan
 * product_stocks.unit_id semuanya menunjuk ke sini.
 *
 * Kunci mapan `units.ref_unit_id`; pada sinkronisasi pertama diadopsi lewat
 * nama satuan.
 */
class SyncUnitStep extends ProductFlowStep
{
    public function handle(): SyncStepResult
    {
        return $this->run(function (SyncStepResult $result) {
            $snapshot = $this->units();
            $result->withDetails($snapshot->details());

            $matcher = (new ReferenceMatcher('units', 'unit_id', 'ref_unit_id', 'unit_name'))->load();
            $now = Carbon::now();

            foreach ($snapshot->rows as $index => $row) {
                $result->processed++;

                $refUnitId = $this->pickInt($row, ['unit_id', 'id']);
                $name = $this->pickString($row, ['unit_name', 'name', 'nama']);
                $label = 'Satuan '.($refUnitId !== 0 ? '#'.$refUnitId : 'baris ke-'.($index + 1));

                if ($refUnitId === 0) {
                    $result->failed++;
                    $result->addError($label.': tidak ada unit_id dari PMO.');

                    continue;
                }

                if ($name === '') {
                    $result->failed++;
                    $result->addError($label.': unit_name kosong.');

                    continue;
                }

                $shortName = $this->pickString($row, ['unit_short_name', 'short_name', 'singkatan']);
                $status = $this->pickStatus($row);
                $match = $matcher->match($refUnitId, $name);

                if ($match->isAmbiguous()) {
                    $result->failed++;
                    $result->addError(
                        $label.' "'.$name.'": ada '.count($match->candidates)
                        .' satuan di Pegasus dengan nama sama (unit_id '.implode(', ', $match->candidates)
                        .'). Gabungkan duplikatnya lebih dulu, lalu jalankan ulang langkah ini.'
                    );

                    continue;
                }

                if ($match->found()) {
                    $update = [
                        'ref_unit_id' => $refUnitId,
                        'unit_name' => mb_substr($name, 0, 250),
                        'status' => $status,
                        'updated_at' => $now,
                    ];

                    // unit_short_name tidak pernah dikosongkan; kalau PMO belum
                    // mengirimnya, singkatan yang sudah ada di Pegasus dipertahankan.
                    if ($shortName !== '') {
                        $update['unit_short_name'] = mb_substr($shortName, 0, 250);
                    }

                    DB::table('units')->where('unit_id', $match->localId)->update($update);

                    $result->updated++;
                    if ($match->kind === MatchResult::ADOPTED) {
                        $result->addNotice(
                            $label.' "'.$name.'" diadopsi ke satuan Pegasus yang sudah ada (unit_id '
                            .$match->localId.').'
                        );
                    }

                    $matcher->remember($refUnitId, $name, $match->localId);

                    continue;
                }

                $localId = (int) DB::table('units')->insertGetId([
                    'ref_unit_id' => $refUnitId,
                    'unit_name' => mb_substr($name, 0, 250),
                    'unit_short_name' => mb_substr($shortName !== '' ? $shortName : $name, 0, 250),
                    'status' => $status,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], 'unit_id');

                $matcher->remember($refUnitId, $name, $localId);
                $result->inserted++;
            }

            if ($result->processed === 0) {
                $result->succeed('PMO tidak mengirimkan data satuan.');

                return;
            }

            $result->finish('Sinkronisasi satuan selesai.');
        });
    }
}
