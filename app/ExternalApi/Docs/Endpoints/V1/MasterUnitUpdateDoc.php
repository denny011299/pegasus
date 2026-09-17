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
        return 'Upsert dua lapis: mengubah data satuan yang sudah ada, dicari lewat ref_unit_id (id satuan pada sistem PMO). Kalau ref_unit_id belum pernah ada, dicoba lebih dulu dicocokkan lewat unit_name ke satuan Pegasus yang belum tersambung PMO — cocok satu, satuan itu disambungkan (bukan dibuat baru); baru kalau tidak ada yang cocok, satuan baru dibuat. Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Satuan yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
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
            ['code' => 'AMBIGUOUS_NAME_MATCH', 'http_status' => 422, 'message' => 'ref_unit_id belum pernah ada, dan ada lebih dari satu satuan Pegasus dengan unit_name sama yang belum tersambung PMO — tidak bisa ditebak mana yang dimaksud.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'unit_name atau unit_short_name kosong/tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Kedua field wajib diisi meski hanya satu yang berubah — tidak ada partial update.',
            'Upsert dua lapis untuk ref_unit_id yang belum pernah ada: (1) coba cocokkan unit_name ke satuan Pegasus yang belum tersambung PMO (ref_unit_id kosong) — cocok tepat satu, satuan itu disambungkan ke ref_unit_id ini dan profilnya diperbarui (respons 200, BUKAN 201, karena tidak ada baris baru). (2) Kalau tidak ada yang cocok, satuan baru dibuat (respons 201) dengan data yang sama seperti dikirim ke POST /master/units. Logika pencocokan nama ini PERSIS sama dengan fase adopsi yang dipakai Pusat Sinkronisasi (Sinkronisasi Produk > langkah Satuan) — mencegah satuan duplikat untuk nama yang sebenarnya sudah ada di Pegasus.',
            'ref_unit_id yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui, bukan dijawab NOT_FOUND.',
            'Satuan yang sudah tersambung ref_unit_id LAIN tidak pernah diambil alih lewat pencocokan nama — hanya satuan yang ref_unit_id-nya masih kosong yang jadi kandidat.',
        ];
    }
}
