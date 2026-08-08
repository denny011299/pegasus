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
     * tipe_nama tidak tersimpan sebagai kolom sendiri — namanya selalu
     * diturunkan dari relasi ke warehouse_types lewat tipe_id. Karena itu
     * tipe_nama di sini diperlakukan sebagai konfirmasi dari pemanggil, bukan
     * sumber data: nilainya wajib sama persis (tanpa peduli besar/kecil
     * huruf) dengan nama tipe gudang yang sebenarnya untuk tipe_id yang
     * dikirim, kalau tidak permintaan ditolak. Ini mencegah salah kirim
     * tipe_id yang lolos tanpa disadari pemanggil.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:250'],
            'tipe_nama' => ['required', 'string', 'max:250'],
            'tipe_id' => ['required', 'integer'],
            'alamat' => ['required', 'string'],
        ]);

        $type = WarehouseType::active()->find($data['tipe_id']);

        if (! $type) {
            $this->fail('tipe_id', 'Tipe gudang dengan tipe_id '.$data['tipe_id'].' tidak ditemukan atau tidak aktif.');
        }

        if (mb_strtolower(trim($type->warehouse_type_name)) !== mb_strtolower(trim($data['tipe_nama']))) {
            $this->fail(
                'tipe_nama',
                'tipe_nama "'.$data['tipe_nama'].'" tidak sesuai dengan nama tipe gudang untuk tipe_id '
                    .$data['tipe_id'].' ("'.$type->warehouse_type_name.'").',
            );
        }

        return $data;
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
