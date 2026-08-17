<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/stock/check.
 */
class StockCheckDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'stok-cek';
    }

    public function title(): string
    {
        return 'Cek Kekurangan Stok';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/stock/check';
    }

    public function group(): string
    {
        return 'stok';
    }

    public function description(): string
    {
        return 'Mengecek ketersediaan stok banyak SKU sekaligus terhadap satu gudang, '
            .'dan menghitung kekurangan (shortage) per item bila jumlah yang diminta '
            .'melebihi stok yang tersedia. Hanya membaca — tidak memotong atau '
            .'mengunci stok apa pun.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda milik sistem pemanggil untuk permintaan ini. Dikembalikan apa adanya pada respons, tidak disimpan di Pegasus.'],
            ['name' => 'gudang_id', 'type' => 'integer', 'required' => false,
                'description' => 'Merujuk warehouses.id (sama seperti {gudang_id} pada PUT/DELETE /master/warehouses). Tidak dikirim -> memakai gudang utama.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Daftar item yang dicek, minimal satu.'],
            ['name' => 'items[].sku', 'type' => 'string', 'required' => true,
                'description' => 'product_variants.product_variant_sku. Harus merujuk varian produk aktif.'],
            ['name' => 'items[].qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah yang diminta, dalam satuan items[].unit_id.'],
            ['name' => 'items[].unit_id', 'type' => 'integer', 'required' => true,
                'description' => 'Rujukan units.ref_unit_id (id satuan pada sistem PMO), BUKAN id internal Pegasus. Harus merujuk satuan aktif.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_shipment_id' => 'SHP-2026-000123',
            'gudang_id' => 1,
            'items' => [
                ['sku' => 'MRP300P', 'qty' => 50, 'unit_id' => 5],
                ['sku' => 'SOHP', 'qty' => 10, 'unit_id' => 3],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_shipment_id' => 'SHP-2026-000123',
                'has_shortage' => true,
                'items' => [
                    ['sku' => 'MRP300P', 'unit_id' => 5, 'requested' => 50, 'available' => 50, 'shortage' => 0],
                    ['sku' => 'SOHP', 'unit_id' => 3, 'requested' => 10, 'available' => 4, 'shortage' => 6],
                ],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items harus berisi minimal satu baris.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.sku tidak ditemukan sebagai varian produk aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.unit_id tidak merujuk satuan aktif manapun.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'gudang_id tidak ditemukan atau tidak aktif.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Seluruh sku dan unit_id divalidasi lebih dulu: SKU yang tidak dikenal atau unit_id yang tidak merujuk satuan aktif manapun membuat SELURUH permintaan ditolak VALIDATION_FAILED, bukan diam-diam dianggap shortage penuh untuk item itu saja.',
            'Kombinasi SKU + unit_id yang keduanya valid tapi bukan satuan produk itu (tidak sechain lewat product_relations) tetap lolos validasi — hasilnya wajar available: 0, karena memang tidak ada cara mengonversi stok ke satuan itu.',
            'available dihitung setara satu satuan (items[].unit_id): stok pada satuan itu sendiri, ditambah stok pada satuan lebih besar dalam chain product_relations yang sama (dibongkar otomatis). Stok pada satuan lebih KECIL tidak digabungkan naik (tidak ada packing).',
            'shortage tidak pernah negatif — selisih requested - available, dibulatkan ke 0 kalau stok mencukupi atau berlebih.',
            'gudang_id merujuk warehouses.id langsung (bukan kolom rujukan eksternal seperti ref_unit_id/ref_product_id) — gudang tidak disinkronkan sistem PMO. Tidak dikirim berarti memakai gudang utama (warehouse_types.is_main_warehouse = 1), sama seperti default fitur stok lain di Pegasus.',
            'Endpoint ini murni pengecekan (read-only). Tidak ada reservasi/kunci stok — status stok bisa berubah di antara pengecekan ini dan operasi berikutnya yang benar-benar memotong stok.',
        ];
    }
}
