<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Data master gudang untuk sistem eksternal (API-002 lanjutan).
 *
 * Beda dengan MasterDataController: gudang di sini boleh dibuat, diubah, dan
 * dihapus lewat External API, bukan cuma dibaca. Sengaja ditaruh di
 * controller terpisah supaya MasterDataController tetap murni baca-saja
 * seperti didokumentasikan di sana.
 *
 * Aturan penyimpanan sepenuhnya memakai ulang Warehouse::insertWarehouse() /
 * updateWarehouse() / deleteWarehouse() — sama persis dengan yang dipakai
 * halaman admin (WarehouseController), termasuk cek nama ganda dan
 * pembuatan baris stok 0 otomatis untuk gudang baru. Controller ini hanya
 * menerjemahkan bentuk permintaan/respons API dan tidak menduplikasi logika
 * bisnisnya.
 *
 * tipe_id/tipe_nama pada create dan update bersifat upsert terhadap
 * warehouse_types, lihat upsertWarehouseType(). Karena warehouse_types
 * dipakai bersama oleh seluruh gudang, rename lewat satu panggilan create/
 * update gudang ikut mengubah nama tipe itu untuk semua gudang lain yang
 * memakai tipe_id yang sama — ini efek yang disengaja, bukan kebocoran.
 */
class MasterWarehouseController extends Controller
{
    /**
     * GET /api/external/v1/master/warehouses
     *
     * Mengikuti kontrak API-002 (nama, tipe_nama, tipe_id, alamat) ditambah
     * gudang_id, memakai nama field yang sama dengan yang dikembalikan
     * endpoint create/update/delete gudang.
     */
    public function index(): JsonResponse
    {
        $warehouses = (new Warehouse())->getWarehouseForExternalApi();

        return ApiResponse::success(
            $warehouses->map(static fn ($warehouse) => [
                'gudang_id' => (int) $warehouse->id,
                'nama' => (string) $warehouse->warehouse_name,
                'tipe_nama' => (string) ($warehouse->type->warehouse_type_name ?? ''),
                'tipe_id' => (int) $warehouse->warehouse_type_id,
                'alamat' => $warehouse->warehouse_address,
            ])->all(),
            ['total' => $warehouses->count()],
        );
    }

    /**
     * POST /api/external/v1/master/warehouses
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        $this->upsertWarehouseType((int) $data['tipe_id'], $data['tipe_nama']);

        $result = (new Warehouse())->insertWarehouse([
            'warehouse_name' => $data['nama'],
            'warehouse_type_id' => $data['tipe_id'],
            'warehouse_address' => $data['alamat'],
        ]);

        if ($result === -2) {
            return $this->duplicateNameError($data['nama']);
        }

        return ApiResponse::success(
            $this->present(Warehouse::with('type')->findOrFail($result)),
            [],
            201,
        );
    }

    /**
     * PUT /api/external/v1/master/warehouses/{gudang_id}
     */
    public function update(Request $request, int $gudang_id): JsonResponse
    {
        if (! Warehouse::whereIn('status', [1, 2])->where('id', $gudang_id)->exists()) {
            return $this->notFoundError($gudang_id);
        }

        $data = $this->validatePayload($request);

        $this->upsertWarehouseType((int) $data['tipe_id'], $data['tipe_nama']);

        $result = (new Warehouse())->updateWarehouse([
            'id' => $gudang_id,
            'warehouse_name' => $data['nama'],
            'warehouse_type_id' => $data['tipe_id'],
            'warehouse_address' => $data['alamat'],
        ]);

        if ($result === -2) {
            return $this->duplicateNameError($data['nama']);
        }

        return ApiResponse::success(
            $this->present(Warehouse::with('type')->findOrFail($result)),
        );
    }

    /**
     * DELETE /api/external/v1/master/warehouses/{gudang_id}
     *
     * Soft delete (status = 0), sama seperti halaman admin. Kalau gudang
     * masih punya stok produk/bahan mentah, permintaan ditolak dengan
     * warehouse_has_stock kecuali force=1 disertakan — cerminan tepat dari
     * konfirmasi dua langkah yang dilihat pengguna admin di halamannya.
     */
    public function destroy(Request $request, int $gudang_id): JsonResponse
    {
        if (! Warehouse::whereIn('status', [1, 2])->where('id', $gudang_id)->exists()) {
            return $this->notFoundError($gudang_id);
        }

        $result = (new Warehouse())->deleteWarehouse([
            'id' => $gudang_id,
            'force' => $request->boolean('force'),
        ]);

        if (is_array($result)) {
            return ApiResponse::error(
                'warehouse_has_stock',
                $result['message'],
                409,
                ['count' => $result['count']],
            );
        }

        return ApiResponse::success(['gudang_id' => (int) $result]);
    }

    /* ------------------------------------------------------------------ */
    /* Validasi                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Seluruh field wajib diisi, baik pada create maupun update.
     *
     * tipe_id/tipe_nama tidak lagi diverifikasi kecocokannya di sini — lihat
     * upsertWarehouseType(), dipanggil terpisah setelah validasi ini supaya
     * pesan error kegagalan format field (tipe_id bukan angka, dsb.) tetap
     * lolos lewat validation_failed standar sebelum upsert dicoba.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:250'],
            'tipe_nama' => ['required', 'string', 'max:250'],
            'tipe_id' => ['required', 'integer', 'min:1'],
            'alamat' => ['required', 'string'],
        ]);
    }

    /**
     * Upsert tipe gudang: tipe_id menentukan baris, tipe_nama menentukan isi.
     *
     * - tipe_id sudah ada di warehouse_types (aktif atau tidak) -> nama tipe
     *   itu DIGANTI menjadi tipe_nama kalau berbeda. Ini rename, bukan tipe
     *   baru, dan berlaku untuk seluruh gudang lain yang memakai tipe_id yang
     *   sama — bukan cuma gudang yang sedang dibuat/diubah di panggilan ini.
     * - tipe_id belum ada -> dibuat baris warehouse_types baru dengan id
     *   PERSIS tipe_id yang dikirim (bukan id auto-increment berikutnya),
     *   supaya gudang yang dibuat/diubah sesudah ini benar-benar memakai
     *   tipe_id yang diminta pemanggil, bukan id lain yang kebetulan
     *   dihasilkan sistem.
     *
     * Nama tipe (baik hasil rename maupun tipe baru) tetap wajib unik di
     * antara tipe gudang aktif lain, sama seperti pembuatan tipe lewat
     * halaman admin.
     */
    private function upsertWarehouseType(int $tipeId, string $tipeNama): WarehouseType
    {
        $tipeNama = trim($tipeNama);
        $type = WarehouseType::find($tipeId);

        if ($type) {
            if (trim((string) $type->warehouse_type_name) === $tipeNama) {
                return $type;
            }

            if ($type->isDuplicateName($tipeNama, $type->id)) {
                $this->fail('tipe_nama', 'Nama tipe gudang "'.$tipeNama.'" sudah dipakai tipe gudang lain.');
            }

            $type->warehouse_type_name = $tipeNama;
            $type->save();

            return $type;
        }

        if ((new WarehouseType())->isDuplicateName($tipeNama)) {
            $this->fail('tipe_nama', 'Nama tipe gudang "'.$tipeNama.'" sudah dipakai tipe gudang lain.');
        }

        $type = new WarehouseType();
        $type->id = $tipeId;
        $type->warehouse_type_name = $tipeNama;
        $type->is_main_warehouse = 0;
        $type->status = 1;
        $type->created_by = null;

        try {
            $type->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan tipe_id baru yang sama, nyaris bersamaan:
            // keduanya sama-sama tidak menemukan baris di atas, lalu primary
            // key menolak yang kalah cepat. Perlakukan sebagai upsert biasa
            // terhadap baris yang barusan dibuat request lain, bukan galat.
            $existing = WarehouseType::find($tipeId);

            if ($existing === null) {
                throw $e;
            }

            $type = $existing;

            if (trim((string) $type->warehouse_type_name) !== $tipeNama) {
                $type->warehouse_type_name = $tipeNama;
                $type->save();
            }
        }

        return $type;
    }

    private function fail(string $field, string $message): void
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function present(Warehouse $warehouse): array
    {
        return [
            'gudang_id' => (int) $warehouse->id,
            'nama' => (string) $warehouse->warehouse_name,
            'tipe_nama' => (string) ($warehouse->type->warehouse_type_name ?? ''),
            'tipe_id' => (int) $warehouse->warehouse_type_id,
            'alamat' => $warehouse->warehouse_address,
        ];
    }

    private function duplicateNameError(string $name): JsonResponse
    {
        return ApiResponse::error(
            'duplicate_name',
            'Nama gudang "'.$name.'" sudah dipakai gudang aktif lain.',
            422,
        );
    }

    private function notFoundError(int $gudangId): JsonResponse
    {
        return ApiResponse::error(
            ApiResponse::ERROR_NOT_FOUND,
            'Gudang dengan gudang_id '.$gudangId.' tidak ditemukan.',
            404,
        );
    }
}
