<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/produk (Data Produk).
 */
class MasterProductCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'produk-create';
    }

    public function title(): string
    {
        return 'Buat Produk';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/produk';
    }

    public function group(): string
    {
        return 'produk';
    }

    public function description(): string
    {
        return 'Membuat produk baru di Pegasus, dihubungkan dengan id produk pada sistem PMO (ref_product_id). Selalu membuat baris baru — bukan upsert; ref_product_id yang sudah dipakai produk lain ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_product_id', 'type' => 'integer', 'required' => true, 'description' => 'id produk yang sama pada sistem PMO. Wajib belum pernah dipakai produk lain di Pegasus.'],
            ['name' => 'product_name', 'type' => 'string', 'required' => true, 'description' => 'Nama produk.'],
            ['name' => 'category_id', 'type' => 'integer', 'required' => true, 'description' => 'id kategori produk. Wajib menunjuk kategori yang berstatus aktif.'],
            ['name' => 'unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan default produk ini. Wajib menunjuk satuan yang berstatus aktif.'],
            ['name' => 'product_unit', 'type' => 'array of integer', 'required' => true, 'description' => 'Daftar id satuan yang boleh dipakai untuk produk ini, minimal satu. Setiap unsurnya wajib menunjuk satuan yang berstatus aktif.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_product_id' => 12,
            'product_name' => 'AIR AKI HIKARI',
            'category_id' => 4,
            'unit_id' => 1,
            'product_unit' => [1, 7],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 81,
                'ref_product_id' => 12,
                'product_name' => 'AIR AKI HIKARI',
                'category_id' => 4,
                'unit_id' => 1,
                'product_unit' => [1, 7],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'ref_product_id/product_name kosong, atau category_id/unit_id/salah satu unsur product_unit tidak menunjuk kategori/satuan yang aktif.'],
            ['code' => 'duplicate_ref_id', 'http_status' => 422, 'message' => 'ref_product_id sudah dipakai produk lain (baik yang masih aktif maupun yang sudah dihapus lewat DELETE produk) — pakai PUT untuk memperbarui produk yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Kelima field wajib diisi, tidak ada yang bersifat opsional.',
            'id pada respons adalah id produk yang dibuat Pegasus sendiri (auto-increment) — SIMPAN nilai ini kalau nanti perlu memanggil PATCH /produk/connect (yang butir connections-nya memakai id Pegasus, bukan ref_product_id).',
            'Bukan upsert: mengirim ref_product_id yang sudah dipakai (aktif maupun yang produknya sudah dihapus) selalu ditolak dengan duplicate_ref_id, tidak pernah menimpa data yang sudah ada. Pakai PUT /produk/{ref_product_id} untuk memperbarui produk yang rujukannya sudah ada.',
            'Untuk menghubungkan ref_product_id ke produk Pegasus yang SUDAH ADA (dibuat lewat halaman admin atau Pusat Sinkronisasi, misalnya), pakai PATCH /produk/connect — bukan POST ini, yang selalu membuat produk baru.',
            'category_id dan setiap unsur product_unit (termasuk unit_id) DIVALIDASI benar-benar menunjuk kategori/satuan yang aktif — beda dengan form pelanggan lewat halaman admin yang tidak memeriksa ini. Kirim id yang salah akan ditolak validation_failed, bukan tersimpan dengan rujukan yang menggantung.',
            'ref_product_id juga ditulis Pusat Sinkronisasi (menarik data produk dari PMO) — endpoint ini adalah jalur tulis kedua ke kolom yang sama, disengaja karena keduanya melayani sistem yang sama (PMO).',
        ];
    }
}
