<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/master/warehouses/{gudang_id} (API-002 lanjutan).
 */
class MasterWarehouseUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-warehouses-update';
    }

    public function title(): string
    {
        return 'Ubah Gudang';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/master/warehouses/{gudang_id}';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengubah data gudang yang sudah ada. Bersifat penggantian penuh — seluruh field wajib dikirim meski hanya satu yang berubah.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'gudang_id', 'type' => 'integer', 'required' => true, 'description' => 'id gudang yang akan diubah, lihat GET /master/warehouses.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'nama', 'type' => 'string', 'required' => true, 'description' => 'Nama gudang. Harus unik di antara gudang aktif lain (di luar gudang ini sendiri).'],
            ['name' => 'tipe_nama', 'type' => 'string', 'required' => true, 'description' => 'Nama tipe gudang. Wajib sama persis (tanpa peduli besar/kecil huruf) dengan nama tipe gudang yang sebenarnya untuk tipe_id yang dikirim — dipakai sebagai konfirmasi, bukan disimpan.'],
            ['name' => 'tipe_id', 'type' => 'integer', 'required' => true, 'description' => 'id tipe gudang, lihat GET /master/warehouse_types.'],
            ['name' => 'alamat', 'type' => 'string', 'required' => true, 'description' => 'Alamat gudang.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'nama' => 'Gudang Surabaya Pusat',
            'tipe_nama' => 'Gudang Utama',
            'tipe_id' => 1,
            'alamat' => 'Jl. Raya Darmo No. 25, Surabaya',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'gudang_id' => 101,
                'nama' => 'Gudang Surabaya Pusat',
                'tipe_nama' => 'Gudang Utama',
                'tipe_id' => 1,
                'alamat' => 'Jl. Raya Darmo No. 25, Surabaya',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'Gudang dengan gudang_id tersebut tidak ditemukan (termasuk yang sudah dihapus).'],
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'Salah satu field wajib kosong, atau tipe_nama tidak sesuai dengan tipe_id yang dikirim.'],
            ['code' => 'duplicate_name', 'http_status' => 422, 'message' => 'Nama gudang sudah dipakai gudang aktif lain.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Keempat field body wajib diisi meski hanya satu yang berubah — tidak ada partial update.',
            'Gudang berstatus Non-Aktif tetap bisa diubah (sama seperti halaman admin); yang tidak ditemukan hanya gudang yang sudah dihapus.',
        ];
    }
}
