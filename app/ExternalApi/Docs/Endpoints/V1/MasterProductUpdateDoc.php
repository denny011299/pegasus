<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/produk/{ref_product_id} (Data Produk).
 */
class MasterProductUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'produk-update';
    }

    public function title(): string
    {
        return 'Ubah Produk';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/produk/{ref_product_id}';
    }

    public function group(): string
    {
        return 'produk';
    }

    public function description(): string
    {
        return 'Mengubah data produk yang sudah ada, dicari lewat ref_product_id (id produk pada sistem PMO). Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Tidak pernah membuat produk baru.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_product_id', 'type' => 'integer', 'required' => true, 'description' => 'id produk pada sistem PMO untuk produk yang akan diubah, lihat GET /produk.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'product_name', 'type' => 'string', 'required' => true, 'description' => 'Nama produk.'],
            ['name' => 'category_id', 'type' => 'integer', 'required' => true, 'description' => 'id kategori produk. Wajib menunjuk kategori yang berstatus aktif.'],
            ['name' => 'unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan default produk ini. Wajib menunjuk satuan yang berstatus aktif.'],
            ['name' => 'product_unit', 'type' => 'array of integer', 'required' => true, 'description' => 'Daftar id satuan yang boleh dipakai untuk produk ini, minimal satu. Setiap unsurnya wajib menunjuk satuan yang berstatus aktif.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
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
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'ref_product_id tidak ditemukan, atau ditemukan tapi produknya nonaktif/sudah dihapus.'],
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'product_name kosong, atau category_id/unit_id/salah satu unsur product_unit tidak menunjuk kategori/satuan yang aktif.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Keempat field body wajib diisi meski hanya satu yang berubah — tidak ada partial update.',
            'ref_product_id tidak bisa diubah lewat endpoint ini — kirim ref_product_id baru dengan DELETE + POST kalau memang perlu mengganti rujukan PMO produk ini.',
            'category_id dan setiap unsur product_unit (termasuk unit_id) DIVALIDASI benar-benar menunjuk kategori/satuan yang aktif, sama seperti POST.',
            'Endpoint ini HANYA menyentuh produk berstatus aktif — ref_product_id yang menunjuk produk nonaktif/sudah dihapus dijawab not_found, bukan diizinkan mengubah data produk itu.',
        ];
    }
}
