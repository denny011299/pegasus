<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\Supplies;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Data master bahan mentah/kemasan untuk sistem eksternal (Data Bahan) — dibangun bersamaan
 * dengan POST /shipments/returns (GitHub #58): item type=1 (bahan) pada endpoint itu
 * me-resolve item.ref_id lewat kolom yang controller ini kelola, sama seperti item type=2
 * (produk) me-resolve lewat product_variant_sku.
 *
 * Polanya sama persis dengan MasterProductController (bukan MasterArmadaController): bahan punya
 * endpoint connect, karena supplies.ref_supplies_id — sama seperti products.ref_product_id —
 * nullable dan sering kosong untuk bahan yang dibuat lewat halaman admin, bukan selalu terisi
 * seperti customers.customer_code pada armada.
 *
 *   - supplies.supplies_id     : id asli Pegasus, auto-increment, tidak pernah ditentukan
 *                                pemanggil. Disebut "id" pada respons.
 *   - supplies.ref_supplies_id : id bahan yang sama pada sistem PMO (nullable, unik lewat
 *                                supplies_ref_supplies_id_unique — lihat migrasi
 *                                2026_08_17_090000_*). Inilah yang dipakai sebagai BODY create
 *                                dan PATH ubah/hapus/connect.
 *
 * Tidak ada konsep "peran" untuk bahan (beda dengan sales) — satu-satunya syarat "dikelola"
 * endpoint ini adalah status aktif, sama seperti getSupplies()/getSuppliesForExternalApi().
 *
 * Field body (supplies_name, supplies_desc, supplies_default_unit, supplies_unit) mengikuti
 * persis field yang sudah dikelola Supplies::insertSupplies()/updateSupplies() milik halaman
 * admin, TIDAK termasuk supplies_alert/lead_time_days/safety_stock (parameter reorder-point yang
 * tidak relevan buat pemanggil eksternal, tetap 0/default lewat Supplies::insertSupplies() kalau
 * baris ini nanti diedit dari halaman admin). Beda dengan Supplies::insertSupplies(), endpoint
 * ini MEMVALIDASI supplies_default_unit/supplies_unit benar-benar menunjuk satuan aktif sebelum
 * menyimpan — pemanggil eksternal tidak boleh membuat bahan dengan rujukan yang menggantung.
 */
class MasterSuppliesController extends Controller
{
    use HandlesListQueryParams;

    /**
     * GET /api/external/v1/bahan
     *
     * Paginasi, urutan (?sort=), dan pencarian (?search=) semuanya opsional — lihat
     * HandlesListQueryParams. Kunci ?sort= yang sah: id, ref_supplies_id, supplies_name,
     * created_at, updated_at. ?search= mencari di supplies_name dan supplies_desc.
     *
     * ?show_units=true menambahkan field "units": daftar Unit (id, unit_name, unit_short_name)
     * hasil resolusi supplies_unit DIGABUNG supplies_default_unit, tanpa duplikat. Diambil sekali
     * per permintaan (tabel units kecil), bukan sekali per bahan.
     */
    public function index(Request $request): JsonResponse
    {
        $showUnits = $request->boolean('show_units');
        $unitsMap = $showUnits ? Unit::where('status', 1)->get()->keyBy('unit_id') : null;

        return $this->respondList(
            (new Supplies())->getSuppliesForExternalApi(),
            $request,
            fn ($supplies) => $this->present($supplies, $unitsMap),
            sortable: [
                'id' => 'supplies_id',
                'ref_supplies_id' => 'ref_supplies_id',
                'supplies_name' => 'supplies_name',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            searchable: ['supplies_name', 'supplies_desc'],
            tieBreaker: 'supplies_id',
        );
    }

    /**
     * POST /api/external/v1/bahan
     *
     * Selalu membuat baris bahan baru (id Pegasus-nya auto-increment, tidak pernah ditentukan
     * pemanggil). Bukan upsert: ref_supplies_id yang sudah dipakai bahan lain ditolak sebagai
     * duplicate_ref_id — pakai PUT untuk memperbarui bahan yang rujukannya sudah ada, atau PATCH
     * /bahan/connect untuk menghubungkan rujukan ke bahan Pegasus yang sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $refSuppliesId = (int) $data['ref_supplies_id'];

        if (Supplies::where('ref_supplies_id', $refSuppliesId)->exists()) {
            return $this->duplicateRefError($refSuppliesId);
        }

        $supplies = new Supplies();
        $supplies->ref_supplies_id = $refSuppliesId;
        $supplies->status = 1;
        $supplies->created_by = null;
        $this->applyPayload($supplies, $data);

        try {
            $supplies->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan ref_supplies_id baru yang sama, nyaris bersamaan: keduanya
            // sama-sama tidak menemukan baris di atas, lalu unique index menolak yang kalah cepat.
            if (Supplies::where('ref_supplies_id', $refSuppliesId)->exists()) {
                return $this->duplicateRefError($refSuppliesId);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($supplies), [], 201);
    }

    /**
     * PUT /api/external/v1/bahan/{ref_supplies_id}
     *
     * Tidak pernah membuat bahan baru; ref_supplies_id yang tidak ditemukan (atau ditemukan tapi
     * statusnya nonaktif) selalu dijawab not_found.
     */
    public function update(Request $request, int $ref_supplies_id): JsonResponse
    {
        $supplies = $this->findManagedByRef($ref_supplies_id);

        if ($supplies === null) {
            return $this->notFoundByRefError($ref_supplies_id);
        }

        $data = $this->validateProfilePayload($request);
        $this->applyPayload($supplies, $data);
        $supplies->save();

        return ApiResponse::success($this->present($supplies));
    }

    /**
     * DELETE /api/external/v1/bahan/{ref_supplies_id}
     *
     * Soft delete (status = 0), memakai ulang Supplies::deleteSupplies() — sama persis dengan
     * yang dipakai halaman admin, termasuk menonaktifkan varian dan stok bahan ini.
     * ref_supplies_id TIDAK dilepas oleh operasi ini, jadi id yang sudah dihapus lewat endpoint
     * ini tidak bisa dipakai ulang lewat POST (baris lama masih memegangnya, hanya berstatus
     * nonaktif).
     */
    public function destroy(int $ref_supplies_id): JsonResponse
    {
        $supplies = $this->findManagedByRef($ref_supplies_id);

        if ($supplies === null) {
            return $this->notFoundByRefError($ref_supplies_id);
        }

        (new Supplies())->deleteSupplies(['supplies_id' => $supplies->supplies_id]);

        return ApiResponse::success(['ref_supplies_id' => $ref_supplies_id]);
    }

    /**
     * PATCH /api/external/v1/bahan/connect
     *
     * Bentuk jamak, sama seperti PATCH /produk/connect dan /master/units/connect: satu
     * permintaan boleh menghubungkan banyak bahan sekaligus lewat body.connections (array).
     * Setiap butir berisi id (id internal Pegasus) dan ref_supplies_id (rujukan yang mau
     * dipasang), dipakai untuk menghubungkan bahan yang sudah ada dengan id PMO-nya tanpa perlu
     * membuat baris baru.
     *
     * Setiap butir diproses independen: butir yang datanya salah tidak menggagalkan butir lain
     * dalam permintaan yang sama. Respons berupa daftar hasil per butir, masing-masing menandai
     * berhasil/gagal sendiri-sendiri lewat success.
     *
     * Menimpa link yang sudah ada pada bahan tujuan diperbolehkan. Kalau ref_supplies_id yang
     * dikirim sedang dipegang bahan LAIN, rujukan itu dilepas dulu dari bahan itu (jadi null)
     * sebelum dipasang ke bahan tujuan — "dipindah", bukan ditolak sebagai duplikat.
     */
    public function connect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'connections' => ['required', 'array', 'min:1'],
            'connections.*.id' => ['required', 'integer', 'min:1'],
            'connections.*.ref_supplies_id' => ['required', 'integer', 'min:1'],
        ]);

        $results = array_map(
            fn (array $item) => $this->connectOne((int) $item['id'], (int) $item['ref_supplies_id']),
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
    private function connectOne(int $suppliesId, int $refSuppliesId): array
    {
        $supplies = Supplies::find($suppliesId);

        if ($supplies === null || ! $this->isManagedSupplies($supplies)) {
            return $this->connectFailure(
                $suppliesId,
                $refSuppliesId,
                ErrorCatalog::NOT_FOUND,
                'Bahan dengan id '.$suppliesId.' tidak ditemukan atau tidak aktif.',
            );
        }

        DB::transaction(function () use ($supplies, $refSuppliesId) {
            Supplies::where('ref_supplies_id', $refSuppliesId)
                ->where('supplies_id', '!=', $supplies->supplies_id)
                ->update(['ref_supplies_id' => null]);

            $supplies->ref_supplies_id = $refSuppliesId;
            $supplies->save();
        });

        return [
            'id' => $suppliesId,
            'ref_supplies_id' => $refSuppliesId,
            'success' => true,
            'data' => $this->present($supplies->fresh()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function connectFailure(int $suppliesId, int $refSuppliesId, string $code, string $message): array
    {
        return [
            'id' => $suppliesId,
            'ref_supplies_id' => $refSuppliesId,
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
            'ref_supplies_id' => ['required', 'integer', 'min:1'],
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
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'supplies_name' => ['required', 'string', 'max:255'],
            'supplies_desc' => ['nullable', 'string'],
            'supplies_default_unit' => ['required', 'integer', Rule::exists('units', 'unit_id')->where('status', 1)],
            'supplies_unit' => ['required', 'array', 'min:1'],
            'supplies_unit.*' => ['integer', Rule::exists('units', 'unit_id')->where('status', 1)],
        ];
    }

    /**
     * supplies_unit disimpan sebagai JSON array of string ("[\"7\",\"9\"]"), mengikuti konvensi
     * yang sudah dipakai halaman admin (public/Custom_js/Backoffice/Product/Supplies.js:
     * JSON.stringify($('#supplies_unit').val())) — bukan keputusan baru di sini.
     */
    private function applyPayload(Supplies $supplies, array $data): void
    {
        $supplies->supplies_name = trim($data['supplies_name']);
        $supplies->supplies_desc = $data['supplies_desc'] ?? null;
        $supplies->supplies_default_unit = (int) $data['supplies_default_unit'];
        $supplies->supplies_unit = json_encode(array_map('strval', $data['supplies_unit']));
        $supplies->supplies_alert = $supplies->supplies_alert ?? 0;
    }

    /**
     * Bahan dikelola endpoint ini kalau statusnya aktif — tidak ada penyaringan lain seperti
     * peran pada sales, karena tabel supplies tidak punya kolom jenis yang membedakan bahan yang
     * boleh dikelola External API dari yang tidak.
     */
    private function isManagedSupplies(Supplies $supplies): bool
    {
        return (int) $supplies->status === 1;
    }

    private function findManagedByRef(int $refSuppliesId): ?Supplies
    {
        $supplies = Supplies::where('ref_supplies_id', $refSuppliesId)->first();

        return ($supplies !== null && $this->isManagedSupplies($supplies)) ? $supplies : null;
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function present(Supplies $supplies, ?Collection $unitsMap = null): array
    {
        $unitIds = $this->decodeSuppliesUnit($supplies->supplies_unit);

        $data = [
            'id' => (int) $supplies->supplies_id,
            'ref_supplies_id' => $supplies->ref_supplies_id !== null ? (int) $supplies->ref_supplies_id : null,
            'supplies_name' => (string) $supplies->supplies_name,
            'supplies_desc' => $supplies->supplies_desc,
            'supplies_default_unit' => (int) $supplies->supplies_default_unit,
            'supplies_unit' => $unitIds,
        ];

        if ($unitsMap !== null) {
            $resolvedIds = array_values(array_unique([...$unitIds, (int) $supplies->supplies_default_unit]));

            $data['units'] = collect($resolvedIds)
                ->map(fn ($id) => $unitsMap->get($id))
                ->filter()
                ->map(static fn ($unit) => [
                    'id' => (int) $unit->unit_id,
                    'unit_name' => (string) $unit->unit_name,
                    'unit_short_name' => (string) $unit->unit_short_name,
                ])
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<int, int>
     */
    private function decodeSuppliesUnit($raw): array
    {
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $decoded)));
    }

    private function notFoundByRefError(int $refSuppliesId): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::NOT_FOUND,
            'Bahan dengan ref_supplies_id '.$refSuppliesId.' tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(int $refSuppliesId): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::DUPLICATE_REF_ID,
            'ref_supplies_id '.$refSuppliesId.' sudah dipakai bahan lain.',
            422,
        );
    }
}
