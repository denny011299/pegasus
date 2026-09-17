<?php

namespace App\ExternalApi\Support;

use App\ExternalApi\Support\Exceptions\AmbiguousNameMatchException;
use App\Models\Unit;
use App\Synchronization\Support\ReferenceMatcher;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Resolusi satuan lewat rujukan PMO (ref_unit_id) + nama, dipakai dua tempat:
 *
 *  - MasterUnitController::update() — PUT /master/units/{ref_unit_id} upsert.
 *  - MasterProductController — saat body produk mengirim unit_id/product_unit
 *    sebagai objek {ref_unit_id, unit_name, unit_short_name?} (bukan id
 *    Pegasus polos), yaitu satuan yang belum pernah disinkronkan lewat jalur
 *    manapun. Lihat catatan kelas MasterProductController.
 *
 * Logikanya SENGAJA disalin dari App\Synchronization\Steps\ProductFlow\SyncUnitStep,
 * BUKAN dari kontrak penggantian-penuh MasterUnitController — tiga lapis sama
 * persis dengan Pusat Sinkronisasi, ref_unit_id DAN unit_name sama-sama
 * opsional (salah satunya wajib ada, lihat resolve()):
 *
 *  1. ref_unit_id dikirim & sudah ada di Pegasus -> baris itu diperbarui
 *     langsung, tidak perlu unit_name sama sekali.
 *  2. ref_unit_id tidak dikirim, atau dikirim tapi belum ada -> kalau
 *     unit_name dikirim, dicoba diadopsi lewat nama (ReferenceMatcher, hanya
 *     satuan yang ref_unit_id-nya masih kosong) — cocok tepat satu, satuan
 *     itu disambungkan (ref_unit_id dipasang kalau dikirim); cocok lebih
 *     dari satu, dilempar AmbiguousNameMatchException (pemanggil yang
 *     menerjemahkan ke respons API); tidak ada yang cocok, satuan baru
 *     dibuat.
 *  3. ref_unit_id tidak ada/tidak dikirim DAN unit_name tidak dikirim ->
 *     tidak ada apa pun untuk dicocokkan atau dibuat, dilempar
 *     InvalidArgumentException (pemanggil yang menerjemahkan ke
 *     VALIDATION_FAILED — lihat MasterProductController::resolveUnit()).
 *
 * unit_short_name kosong TIDAK PERNAH menghapus singkatan yang sudah ada, dan
 * unit_name kosong TIDAK PERNAH menghapus nama yang sudah ada (baris yang
 * ditemukan lewat ref_unit_id semata, tanpa unit_name, dipertahankan nama
 * lamanya) — sama seperti SyncUnitStep, karena PMO tidak selalu
 * mengirimkan keduanya. Status selalu dipaksa aktif pada baris yang
 * disentuh, sama seperti SyncUnitStep (PMO tidak mengirim status per satuan
 * pada alur ini).
 */
class UnitAutoSync
{
    public function resolve(?int $refUnitId, ?string $unitName, string $unitShortName = ''): UnitAutoSyncResult
    {
        $now = Carbon::now();
        $name = $this->nullIfBlank($unitName);

        if ($refUnitId !== null) {
            $unit = Unit::where('ref_unit_id', $refUnitId)->first();

            if ($unit !== null) {
                $this->applyAndSave($unit, $refUnitId, $name, $unitShortName, $now);

                return UnitAutoSyncResult::linked($unit);
            }
        }

        if ($name === null) {
            throw new \InvalidArgumentException(
                'UnitAutoSync::resolve() butuh ref_unit_id yang sudah ada di Pegasus, atau unit_name '
                    .'untuk membuat/mengadopsi satuan baru.'
            );
        }

        $matcher = (new ReferenceMatcher('units', 'unit_id', 'ref_unit_id', 'unit_name'))->load();
        $match = $matcher->match($refUnitId, $name);

        if ($match->isAmbiguous()) {
            throw new AmbiguousNameMatchException('Satuan', $name, $match->candidates);
        }

        if ($match->found()) {
            $unit = Unit::findOrFail($match->localId);
            $this->applyAndSave($unit, $refUnitId, $name, $unitShortName, $now);

            return UnitAutoSyncResult::adopted($unit);
        }

        return $this->create($refUnitId, $name, $unitShortName, $now);
    }

    private function nullIfBlank(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function applyAndSave(Unit $unit, ?int $refUnitId, ?string $unitName, string $unitShortName, Carbon $now): void
    {
        if ($refUnitId !== null) {
            $unit->ref_unit_id = $refUnitId;
        }

        if ($unitName !== null) {
            $unit->unit_name = mb_substr($unitName, 0, 250);
        }

        $unit->status = 1;

        $trimmedShortName = trim($unitShortName);
        if ($trimmedShortName !== '') {
            $unit->unit_short_name = mb_substr($trimmedShortName, 0, 250);
        }

        $unit->updated_at = $now;
        $unit->save();
    }

    private function create(?int $refUnitId, string $unitName, string $unitShortName, Carbon $now): UnitAutoSyncResult
    {
        $shortName = trim($unitShortName);

        $unit = new Unit();
        $unit->ref_unit_id = $refUnitId;
        $unit->unit_name = mb_substr($unitName, 0, 250);
        $unit->unit_short_name = mb_substr($shortName !== '' ? $shortName : $unitName, 0, 250);
        $unit->status = 1;
        $unit->created_by = null;
        $unit->created_at = $now;
        $unit->updated_at = $now;

        if ($refUnitId === null) {
            $unit->save();

            return UnitAutoSyncResult::created($unit);
        }

        try {
            $unit->save();
        } catch (QueryException $e) {
            // Dua permintaan nyaris bersamaan dengan ref_unit_id baru yang sama:
            // keduanya sama-sama tidak menemukan baris di atas, lalu unique index
            // menolak yang kalah cepat. Perlakukan sebagai upsert terhadap baris
            // yang barusan dibuat request lain.
            $existing = Unit::where('ref_unit_id', $refUnitId)->first();

            if ($existing === null) {
                throw $e;
            }

            $this->applyAndSave($existing, $refUnitId, $unitName, $unitShortName, $now);

            return UnitAutoSyncResult::linked($existing);
        }

        return UnitAutoSyncResult::created($unit);
    }
}
