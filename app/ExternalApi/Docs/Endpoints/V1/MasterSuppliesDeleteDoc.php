<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi DELETE /api/external/v1/bahan/{ref_supplies_id} (Data Bahan).
 */
class MasterSuppliesDeleteDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'bahan-delete';
    }

    public function title(): string
    {
        return 'Hapus Bahan';
    }

    public function method(): string
    {
        return 'DELETE';
    }

    public function path(): string
    {
        return '/bahan/{ref_supplies_id}';
    }

    public function group(): string
    {
        return 'bahan';
    }

    public function description(): string
    {
        return 'Menonaktifkan (soft delete) bahan yang ref_supplies_id-nya sudah terhubung — memakai ulang proses yang sama dengan tombol Hapus di halaman admin, termasuk menonaktifkan varian dan stok bahan ini.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_supplies_id', 'type' => 'integer', 'required' => true, 'description' => 'id bahan pada sistem PMO, sama dengan yang dikirim saat POST /bahan atau PATCH /bahan/connect.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => ['ref_supplies_id' => 12],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'ref_supplies_id tidak ditemukan, atau bahannya sudah dihapus sebelumnya.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Soft delete (status = 0), sama persis dengan tombol Hapus milik halaman admin — bukan penghapusan baris dari basis data.',
            'ref_supplies_id TIDAK dilepas oleh operasi ini, jadi id yang sudah dihapus lewat endpoint ini tidak bisa dipakai ulang lewat POST /bahan (baris lama masih memegangnya, hanya berstatus nonaktif). Hubungkan ulang lewat PATCH /bahan/connect kalau perlu memakai id itu lagi untuk baris lain.',
            'Bahan yang sudah dihapus lewat endpoint ini tidak lagi bisa dipakai sebagai item.ref_id (type=1) pada POST /shipments/returns — permintaan itu akan ditolak seolah bahannya tidak pernah ada.',
        ];
    }
}
