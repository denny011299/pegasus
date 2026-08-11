<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/master/units/{ref_unit_id} (API-001 lanjutan).
 */
class MasterUnitUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-units-update';
    }

    public function title(): string
    {
        return 'Ubah Satuan';
    }

    public function method(): string
    {
        return 'PUT';
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
        return 'Mengubah data satuan yang sudah ada, dicari lewat ref_unit_id (id satuan pada sistem PMO). Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Tidak pernah membuat satuan baru.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan pada sistem PMO untuk satuan yang akan diubah, lihat GET /master/units.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'unit_name', 'type' => 'string', 'required' => true, 'description' => 'Nama satuan, mis. "Kilogram".'],
            ['name' => 'unit_short_name', 'type' => 'string', 'required' => true, 'description' => 'Singkatan satuan, mis. "kg".'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'unit_name' => 'Kilogram',
            'unit_short_name' => 'kg',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 12,
                'ref_unit_id' => 1042,
                'unit_name' => 'Kilogram',
                'unit_short_name' => 'kg',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'ref_unit_id tidak ditemukan, atau ditemukan tapi satuannya nonaktif/sudah dihapus.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'unit_name atau unit_short_name kosong/tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Kedua field wajib diisi meski hanya satu yang berubah — tidak ada partial update.',
            'Endpoint ini HANYA menyentuh satuan berstatus aktif — ref_unit_id yang menunjuk satuan nonaktif/sudah dihapus dijawab NOT_FOUND, bukan diizinkan mengubah data satuan itu.',
        ];
    }
}
