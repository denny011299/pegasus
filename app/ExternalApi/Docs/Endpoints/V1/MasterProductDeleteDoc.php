<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi DELETE /api/external/v1/produk/{ref_product_id} (Data Produk).
 */
class MasterProductDeleteDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'produk-delete';
    }

    public function title(): string
    {
        return 'Hapus Produk';
    }

    public function method(): string
    {
        return 'DELETE';
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
        return 'Menghapus produk (soft delete), dicari lewat ref_product_id (id produk pada sistem PMO).';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_product_id', 'type' => 'integer', 'required' => true, 'description' => 'id produk pada sistem PMO untuk produk yang akan dihapus.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_product_id' => 12,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'ref_product_id tidak ditemukan, atau ditemukan tapi produknya sudah nonaktif/dihapus sebelumnya.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Ini soft delete: produk dinonaktifkan, bukan dihapus permanen — sama seperti penghapusan lewat halaman admin. Seluruh varian dan stok produk ini ikut dinonaktifkan.',
            'ref_product_id TIDAK dilepas oleh operasi ini — baris produk lama masih memegangnya, hanya berstatus nonaktif. Karena itu ref_product_id yang sudah dihapus lewat endpoint ini tidak bisa dipakai lagi lewat POST /produk (ditolak DUPLICATE_REF_ID), dan tidak muncul lagi lewat GET /produk.',
        ];
    }
}
