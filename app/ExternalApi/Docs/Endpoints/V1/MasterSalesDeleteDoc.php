<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi DELETE /api/external/v1/master/sales/{staff_id} (API-002 lanjutan).
 */
class MasterSalesDeleteDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales-delete';
    }

    public function title(): string
    {
        return 'Hapus Sales';
    }

    public function method(): string
    {
        return 'DELETE';
    }

    public function path(): string
    {
        return '/master/sales/{staff_id}';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Menghapus sales (soft delete).';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'integer', 'required' => true, 'description' => 'id sales yang akan dihapus, lihat GET /master/sales.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'staff_id' => 2,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'staff_id tidak ditemukan, atau ditemukan tapi bukan sales aktif (staf berperan lain, atau sudah dihapus sebelumnya).'],
        ];
    }

    public function notes(): array
    {
        return [
            'Ini soft delete: status staf diubah menjadi 0, bukan baris yang dihapus dari basis data — sama seperti penghapusan lewat halaman admin (Pengguna).',
            'staff_id yang sudah dihapus lewat endpoint ini tidak bisa dibuat ulang lewat POST /master/sales dengan id yang sama — baris dengan id itu tetap ada (soft deleted) dan endpoint ini hanya mengelola staf yang statusnya aktif.',
            'Endpoint ini HANYA boleh menghapus staf yang berperan Sales (dan masih aktif) — staff_id yang menunjuk staf lain dijawab not_found, bukan diizinkan menghapus staf itu.',
            'Akun login (staff_username/staff_password), riwayat transaksi, dan saldo staf tidak ikut dihapus atau diubah.',
        ];
    }
}
