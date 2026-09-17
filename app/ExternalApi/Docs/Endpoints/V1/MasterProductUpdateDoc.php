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
        return 'Ubah/Buat Produk (Upsert)';
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
        return 'Upsert: mengubah data produk yang sudah ada, dicari lewat ref_product_id (id produk pada sistem PMO), atau membuat produk baru dengan ref_product_id itu kalau belum pernah ada. Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Produk yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
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
            ['name' => 'category_id', 'type' => 'integer', 'required' => true, 'description' => 'id kategori produk. Wajib menunjuk kategori yang berstatus aktif — daftarnya bisa diambil dari GET /master/categories.'],
            ['name' => 'unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan default produk ini. Wajib menunjuk satuan yang berstatus aktif — daftarnya bisa diambil dari GET /master/units.'],
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
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'product_name kosong, atau category_id/unit_id/salah satu unsur product_unit tidak menunjuk kategori/satuan yang aktif.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Keempat field body wajib diisi meski hanya satu yang berubah — tidak ada partial update.',
            'ref_product_id tidak bisa diubah lewat endpoint ini — kirim ref_product_id baru dengan DELETE + POST kalau memang perlu mengganti rujukan PMO produk ini.',
            'category_id dan setiap unsur product_unit (termasuk unit_id) DIVALIDASI benar-benar menunjuk kategori/satuan yang aktif, sama seperti POST.',
            'Upsert: ref_product_id yang belum pernah ada membuat produk baru (respons 201) dengan data yang sama seperti dikirim ke POST /produk. ref_product_id yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui, bukan dijawab NOT_FOUND.',
        ];
    }
}
