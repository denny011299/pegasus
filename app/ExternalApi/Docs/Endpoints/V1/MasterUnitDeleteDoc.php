<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi DELETE /api/external/v1/master/units/{ref_unit_id} (API-001 lanjutan).
 */
class MasterUnitDeleteDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-units-delete';
    }

    public function title(): string
    {
        return 'Hapus Satuan';
    }

    public function method(): string
    {
        return 'DELETE';
    }

    public function path(): string
    {
        return '/master/units/{ref_unit_id}';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Menghapus satuan (soft delete), dicari lewat ref_unit_id (id satuan pada sistem PMO).';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan pada sistem PMO untuk satuan yang akan dihapus.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_unit_id' => 1042,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'ref_unit_id tidak ditemukan, atau ditemukan tapi satuannya sudah nonaktif/dihapus sebelumnya.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Ini soft delete: status satuan diubah menjadi 0, bukan baris yang dihapus dari basis data — sama seperti penghapusan lewat halaman admin.',
            'ref_unit_id TIDAK dilepas oleh operasi ini — baris satuan lama masih memegangnya, hanya berstatus nonaktif. Karena itu ref_unit_id yang sudah dihapus lewat endpoint ini tidak bisa dipakai lagi lewat POST /master/units (ditolak duplicate_ref_id), dan tidak muncul lagi lewat GET /master/units.',
            'Satuan yang masih dipakai produk/varian/stok tetap bisa dihapus lewat endpoint ini — tidak ada pemeriksaan pemakaian seperti pada penghapusan gudang.',
        ];
    }
}
