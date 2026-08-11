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
        return 'Menghapus sales (soft delete), dicari lewat staff_id (rujukan milik sistem Anda sendiri, bukan id Pegasus).';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'string', 'required' => true, 'description' => 'id milik sistem Anda sendiri untuk sales yang akan dihapus (BUKAN id Pegasus).'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'staff_id' => '2',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'staff_id (rujukan Anda) tidak ditemukan, atau ditemukan tapi bukan sales aktif (staf berperan lain, atau sudah dihapus sebelumnya).'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id pada path adalah rujukan milik sistem Anda sendiri (external_ref_id), BUKAN id Pegasus.',
            'Ini soft delete: status staf diubah menjadi 0, bukan baris yang dihapus dari basis data — sama seperti penghapusan lewat halaman admin (Pengguna).',
            'Rujukan (staff_id) TIDAK dilepas oleh operasi ini — baris staf lama masih memegangnya, hanya berstatus nonaktif. Karena itu staff_id yang sudah dihapus lewat endpoint ini tidak bisa dipakai lagi lewat POST /master/sales (ditolak DUPLICATE_REF_ID), dan tidak muncul lagi lewat GET /master/sales.',
            'Endpoint ini HANYA boleh menghapus staf yang berperan Sales (dan masih aktif) — staff_id yang menunjuk staf lain dijawab NOT_FOUND, bukan diizinkan menghapus staf itu.',
            'Akun login (staff_username/staff_password), riwayat transaksi, dan saldo staf tidak ikut dihapus atau diubah.',
        ];
    }
}
