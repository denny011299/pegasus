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
            ['name' => 'category_id', 'type' => 'integer', 'required' => false, 'description' => 'id kategori produk di Pegasus, kalau sudah diketahui. Wajib diisi kalau category_name tidak dikirim — boleh dikirim BERSAMA category_name.'],
            ['name' => 'category_name', 'type' => 'string', 'required' => false, 'description' => 'Nama kategori — dipakai kalau category_id tidak dikirim, atau dikirim tapi tidak menunjuk kategori aktif. Tidak ada yang cocok, kategori baru dibuat otomatis. Wajib diisi kalau category_id tidak dikirim.'],
            ['name' => 'unit_id', 'type' => 'integer ATAU objek', 'required' => true, 'description' => 'Satuan default produk ini. Angka polos = id satuan Pegasus yang SUDAH ADA & aktif. Objek {ref_unit_id?, unit_name?, unit_short_name?} = satuan yang mungkin belum pernah disinkronkan — ref_unit_id dan unit_name BOLEH dikirim bersamaan (salah satunya wajib ada).'],
            ['name' => 'product_unit', 'type' => 'array of (integer ATAU objek)', 'required' => true, 'description' => 'Daftar satuan yang boleh dipakai untuk produk ini, minimal satu. Tiap unsurnya menerima bentuk yang sama dengan unit_id, boleh dicampur dalam satu array.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'product_name' => 'AIR AKI HIKARI',
            'category_id' => 4,
            'category_name' => 'Aki & Baterai',
            'unit_id' => ['ref_unit_id' => 1042, 'unit_name' => 'Dus', 'unit_short_name' => 'dus'],
            'product_unit' => [1, ['ref_unit_id' => 1042, 'unit_name' => 'Dus', 'unit_short_name' => 'dus']],
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
                'unit_id' => 15,
                'product_unit' => [1, 15],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'product_name kosong, category_id maupun category_name sama-sama tidak dikirim, category_id (kalau dikirim) tidak menunjuk kategori aktif, unit_id/unsur product_unit berbentuk angka tapi tidak menunjuk satuan aktif, atau berbentuk objek tapi ref_unit_id/unit_name-nya tidak valid.'],
            ['code' => 'AMBIGUOUS_NAME_MATCH', 'http_status' => 422, 'message' => 'unit_id/unsur product_unit atau category_name berbentuk nama, dan ada lebih dari satu satuan/kategori Pegasus dengan nama sama yang belum tersambung — tidak bisa ditebak mana yang dimaksud.'],
        ];
    }

    public function notes(): array
    {
        return [
            'product_name, unit_id, dan product_unit wajib diisi meski hanya satu yang berubah — tidak ada partial update. Dari category_id/category_name, salah satu wajib diisi — boleh dua-duanya sekaligus.',
            'ref_product_id tidak bisa diubah lewat endpoint ini — kirim ref_product_id baru dengan DELETE + POST kalau memang perlu mengganti rujukan PMO produk ini.',
            'SINKRONISASI OTOMATIS satuan & kategori, TERMASUK urutan resolusi saat id DAN nama dikirim bersamaan: sama persis dengan POST /produk — lihat catatan di dokumentasi endpoint itu. Berlaku juga untuk produk yang sudah ada, bukan cuma saat upsert membuat produk baru.',
            'Upsert: ref_product_id yang belum pernah ada membuat produk baru (respons 201) dengan data yang sama seperti dikirim ke POST /produk. ref_product_id yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui, bukan dijawab NOT_FOUND.',
        ];
    }
}
