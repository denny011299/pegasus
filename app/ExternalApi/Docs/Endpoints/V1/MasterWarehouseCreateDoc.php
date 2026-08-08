<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/master/warehouses (API-002 lanjutan).
 */
class MasterWarehouseCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-warehouses-create';
    }

    public function title(): string
    {
        return 'Buat Gudang';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/master/warehouses';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Membuat gudang baru. Gudang baru otomatis mendapat baris stok produk dan bahan mentah bernilai 0 di seluruh produk aktif, sama seperti pembuatan lewat halaman admin.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'nama', 'type' => 'string', 'required' => true, 'description' => 'Nama gudang. Harus unik di antara gudang yang masih aktif.'],
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
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'Salah satu field wajib kosong, atau tipe_nama tidak sesuai dengan tipe_id yang dikirim.'],
            ['code' => 'duplicate_name', 'http_status' => 422, 'message' => 'Nama gudang sudah dipakai gudang aktif lain.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Keempat field wajib diisi, tidak ada yang bersifat opsional.',
            'GET /master/warehouse_types tidak menyertakan id (lihat catatan pada dokumentasinya), jadi tipe_id yang valid untuk saat ini didapat dari field tipe_id pada GET /master/warehouses, atau dikoordinasikan langsung dengan admin Pegasus.',
            'gudang_id pada respons adalah pegangan yang dipakai untuk memanggil endpoint ubah (PUT) dan hapus (DELETE) gudang ini.',
        ];
    }
}
