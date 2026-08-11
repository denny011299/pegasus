<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi DELETE /api/external/v1/armada/{customer_code} (API-002 lanjutan).
 */
class MasterArmadaDeleteDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'armada-delete';
    }

    public function title(): string
    {
        return 'Hapus Armada';
    }

    public function method(): string
    {
        return 'DELETE';
    }

    public function path(): string
    {
        return '/armada/{customer_code}';
    }

    public function group(): string
    {
        return 'armada';
    }

    public function description(): string
    {
        return 'Menghapus armada (soft delete), dicari lewat customer_code.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'customer_code', 'type' => 'string', 'required' => true, 'description' => 'id universal armada yang akan dihapus.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'customer_code' => 'ARM-JKT-001',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'customer_code tidak ditemukan, atau ditemukan tapi armadanya sudah nonaktif/dihapus sebelumnya.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Ini soft delete: status pelanggan diubah menjadi 0, bukan baris yang dihapus dari basis data — sama seperti penghapusan lewat halaman admin.',
            'customer_code TIDAK dilepas oleh operasi ini — baris pelanggan lama masih memegangnya, hanya berstatus nonaktif. Karena itu customer_code yang sudah dihapus lewat endpoint ini tidak bisa dipakai lagi lewat POST /armada (ditolak DUPLICATE_REF_ID), dan tidak muncul lagi lewat GET /armada.',
            'Riwayat transaksi kas armada (cash_armadas) yang sudah tercatat atas nama pelanggan ini tidak ikut dihapus atau diubah.',
        ];
    }
}
