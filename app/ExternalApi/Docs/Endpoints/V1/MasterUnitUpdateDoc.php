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
        return 'Ubah/Buat Satuan (Upsert)';
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
        return 'Upsert: mengubah data satuan yang sudah ada, dicari lewat ref_unit_id (id satuan pada sistem PMO), atau membuat satuan baru dengan ref_unit_id itu kalau belum pernah ada. Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Satuan yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
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
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'unit_name atau unit_short_name kosong/tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Kedua field wajib diisi meski hanya satu yang berubah — tidak ada partial update.',
            'Upsert: ref_unit_id yang belum pernah ada membuat satuan baru (respons 201) dengan data yang sama seperti dikirim ke POST /master/units. ref_unit_id yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui, bukan dijawab NOT_FOUND.',
        ];
    }
}
