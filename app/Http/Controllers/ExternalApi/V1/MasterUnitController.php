<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Data master satuan untuk sistem eksternal (API-001 lanjutan).
 *
 * Beda dengan MasterDataController: satuan di sini boleh dibuat, diubah, dan
 * dihapus lewat External API, bukan cuma dibaca — sama alasannya dengan
 * kenapa MasterWarehouseController/MasterSalesController dipisah dari
 * MasterDataController.
 *
 * Polanya sama persis dengan MasterSalesController, disesuaikan untuk
 * satuan:
 *   - units.unit_id     : id asli Pegasus, auto-increment, tidak pernah
 *                         ditentukan pemanggil. Disebut "id" pada respons.
 *   - units.ref_unit_id : id satuan yang sama pada sistem PMO (nullable,
 *                         unik lewat units_ref_unit_id_unique — lihat
 *                         migrasi tabel units). Inilah yang dipakai sebagai
 *                         BODY create dan PATH ubah/hapus. TIDAK diganti
 *                         nama jadi "external_ref_id" seperti pada sales:
 *                         kolom ini memang sudah dipakai kontrak PMO
 *                         (SyncUnitStep, API-001) dengan nama ref_unit_id,
 *                         jadi nama itu dipertahankan di sini.
 *
 * CATATAN PENTING: kolom ref_unit_id ini juga ditulis Pusat Sinkronisasi
 * (SyncUnitStep, menarik data dari PMO). Endpoint di controller ini adalah
 * jalur tulis KEDUA ke kolom yang sama — disengaja (dikonfirmasi pemilik
 * produk): keduanya melayani sistem yang sama (PMO), siapa yang menulis
 * terakhir yang berlaku, tidak ada penguncian tambahan di antara keduanya.
 *
 * Tidak ada konsep "peran" untuk satuan (beda dengan sales yang dibatasi ke
 * staf berperan Sales) — satu-satunya syarat "dikelola" endpoint ini adalah
 * status aktif (status = 1), sama seperti getUnit()/getUnitForExternalApi().
 */
class MasterUnitController extends Controller
{
    /**
     * GET /api/external/v1/master/units
     */
    public function index(): JsonResponse
    {
        $units = (new Unit())->getUnitForExternalApi();

        return ApiResponse::success(
            $units->map(fn ($unit) => $this->present($unit))->all(),
            ['total' => $units->count()],
        );
    }

    /**
     * POST /api/external/v1/master/units
     *
     * Selalu membuat baris satuan baru (id Pegasus-nya auto-increment,
     * tidak pernah ditentukan pemanggil). Bukan upsert: ref_unit_id yang
     * sudah dipakai satuan lain ditolak sebagai duplicate_ref_id — pakai
     * PUT untuk memperbarui satuan yang rujukannya sudah ada, atau PATCH
     * /master/units/connect untuk menghubungkan rujukan ke satuan Pegasus
     * yang sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $refUnitId = (int) $data['ref_unit_id'];

        if (Unit::where('ref_unit_id', $refUnitId)->exists()) {
            return $this->duplicateRefError($refUnitId);
        }

        $unit = new Unit();
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->created_by = null;
        $this->applyPayload($unit, $data);

        try {
            $unit->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan ref_unit_id baru yang sama, nyaris
            // bersamaan: keduanya sama-sama tidak menemukan baris di atas,
            // lalu unique index menolak yang kalah cepat.
            if (Unit::where('ref_unit_id', $refUnitId)->exists()) {
                return $this->duplicateRefError($refUnitId);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($unit), [], 201);
    }

    /**
     * PUT /api/external/v1/master/units/{ref_unit_id}
     *
     * {ref_unit_id} tidak pernah membuat satuan baru; ref_unit_id yang
     * tidak ditemukan (atau ditemukan tapi statusnya nonaktif) selalu
     * dijawab not_found.
     */
    public function update(Request $request, int $ref_unit_id): JsonResponse
    {
        $unit = $this->findManagedByRef($ref_unit_id);

        if ($unit === null) {
            return $this->notFoundByRefError($ref_unit_id);
        }

        $data = $this->validateProfilePayload($request);
        $this->applyPayload($unit, $data);
        $unit->save();

        return ApiResponse::success($this->present($unit));
    }

    /**
     * DELETE /api/external/v1/master/units/{ref_unit_id}
     *
     * Soft delete (status = 0), memakai ulang Unit::deleteUnit() — sama
     * persis dengan yang dipakai halaman admin. ref_unit_id TIDAK dilepas
     * oleh operasi ini, jadi id yang sudah dihapus lewat endpoint ini tidak
     * bisa dipakai ulang lewat POST (baris lama masih memegangnya, hanya
     * berstatus nonaktif).
     */
    public function destroy(int $ref_unit_id): JsonResponse
    {
        $unit = $this->findManagedByRef($ref_unit_id);

        if ($unit === null) {
            return $this->notFoundByRefError($ref_unit_id);
        }

        (new Unit())->deleteUnit(['unit_id' => $unit->unit_id]);

        return ApiResponse::success(['ref_unit_id' => $ref_unit_id]);
    }

    /**
     * PATCH /api/external/v1/master/units/connect
     *
     * Bentuk jamak, sama seperti PATCH /master/sales/connect: satu
     * permintaan boleh menghubungkan banyak satuan sekaligus lewat
     * body.connections (array). Setiap butir berisi id (id internal
     * Pegasus) dan ref_unit_id (rujukan yang mau dipasang), dipakai untuk
     * menghubungkan satuan yang sudah ada dengan id PMO-nya tanpa perlu
     * membuat baris baru.
     *
     * Setiap butir diproses independen: butir yang datanya salah tidak
     * menggagalkan butir lain dalam permintaan yang sama. Respons berupa
     * daftar hasil per butir, masing-masing menandai berhasil/gagal
     * sendiri-sendiri lewat success.
     *
     * Menimpa link yang sudah ada pada satuan tujuan diperbolehkan. Kalau
     * ref_unit_id yang dikirim sedang dipegang satuan LAIN, rujukan itu
     * dilepas dulu dari satuan itu (jadi null) sebelum dipasang ke satuan
     * tujuan — "dipindah", bukan ditolak sebagai duplikat.
     */
    public function connect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'connections' => ['required', 'array', 'min:1'],
            'connections.*.id' => ['required', 'integer', 'min:1'],
            'connections.*.ref_unit_id' => ['required', 'integer', 'min:1'],
        ]);

        $results = array_map(
            fn (array $item) => $this->connectOne((int) $item['id'], (int) $item['ref_unit_id']),
            $data['connections'],
        );

        $successCount = count(array_filter($results, static fn ($r) => $r['success']));

        return ApiResponse::success($results, [
            'total' => count($results),
            'success' => $successCount,
            'failed' => count($results) - $successCount,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function connectOne(int $unitId, int $refUnitId): array
    {
        $unit = Unit::find($unitId);

        if ($unit === null || ! $this->isManagedUnit($unit)) {
            return $this->connectFailure(
                $unitId,
                $refUnitId,
                ApiResponse::ERROR_NOT_FOUND,
                'Satuan dengan id '.$unitId.' tidak ditemukan atau tidak aktif.',
            );
        }

        DB::transaction(function () use ($unit, $refUnitId) {
            Unit::where('ref_unit_id', $refUnitId)
                ->where('unit_id', '!=', $unit->unit_id)
                ->update(['ref_unit_id' => null]);

            $unit->ref_unit_id = $refUnitId;
            $unit->save();
        });

        return [
            'id' => $unitId,
            'ref_unit_id' => $refUnitId,
            'success' => true,
            'data' => $this->present($unit->fresh()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function connectFailure(int $unitId, int $refUnitId, string $code, string $message): array
    {
        return [
            'id' => $unitId,
            'ref_unit_id' => $refUnitId,
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Validasi & penyimpanan                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validateCreatePayload(Request $request): array
    {
        return $request->validate([
            'ref_unit_id' => ['required', 'integer', 'min:1'],
        ] + $this->profileRules());
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProfilePayload(Request $request): array
    {
        return $request->validate($this->profileRules());
    }

    /**
     * unit_name dan unit_short_name berdua wajib (tidak nullable di basis
     * data), tidak seperti nama_belakang/alamat pada sales — jadi tidak ada
     * field opsional di sini. Body tetap dianggap representasi penuh:
     * memperbarui satuan berarti mengirim ulang kedua field, bukan hanya
     * yang berubah.
     *
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'unit_name' => ['required', 'string', 'max:250'],
            'unit_short_name' => ['required', 'string', 'max:250'],
        ];
    }

    private function applyPayload(Unit $unit, array $data): void
    {
        $unit->unit_name = trim($data['unit_name']);
        $unit->unit_short_name = trim($data['unit_short_name']);
    }

    /**
     * Satuan dikelola endpoint ini kalau statusnya aktif — tidak ada
     * penyaringan lain seperti peran pada sales, karena tabel units tidak
     * punya sub-jenis baris yang perlu dilindungi dari endpoint ini.
     */
    private function isManagedUnit(Unit $unit): bool
    {
        return (int) $unit->status === 1;
    }

    private function findManagedByRef(int $refUnitId): ?Unit
    {
        $unit = Unit::where('ref_unit_id', $refUnitId)->first();

        return ($unit !== null && $this->isManagedUnit($unit)) ? $unit : null;
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function present(Unit $unit): array
    {
        return [
            'id' => (int) $unit->unit_id,
            'ref_unit_id' => $unit->ref_unit_id !== null ? (int) $unit->ref_unit_id : null,
            'unit_name' => (string) $unit->unit_name,
            'unit_short_name' => (string) $unit->unit_short_name,
        ];
    }

    private function notFoundByRefError(int $refUnitId): JsonResponse
    {
        return ApiResponse::error(
            ApiResponse::ERROR_NOT_FOUND,
            'Satuan dengan ref_unit_id '.$refUnitId.' tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(int $refUnitId): JsonResponse
    {
        return ApiResponse::error(
            'duplicate_ref_id',
            'ref_unit_id '.$refUnitId.' sudah dipakai satuan lain.',
            422,
        );
    }
}
