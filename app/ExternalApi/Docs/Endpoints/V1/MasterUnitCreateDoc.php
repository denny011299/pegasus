<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/master/units (API-001 lanjutan).
 */
class MasterUnitCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-units-create';
    }

    public function title(): string
    {
        return 'Buat Satuan';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/master/units';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Membuat satuan baru di Pegasus, dihubungkan dengan id satuan pada sistem PMO (ref_unit_id). Selalu membuat baris baru — bukan upsert; ref_unit_id yang sudah dipakai satuan lain ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan yang sama pada sistem PMO. Wajib belum pernah dipakai satuan lain di Pegasus.'],
            ['name' => 'unit_name', 'type' => 'string', 'required' => true, 'description' => 'Nama satuan, mis. "Kilogram".'],
            ['name' => 'unit_short_name', 'type' => 'string', 'required' => true, 'description' => 'Singkatan satuan, mis. "kg".'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_unit_id' => 1042,
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
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'ref_unit_id, unit_name, atau unit_short_name kosong/tidak valid.'],
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'ref_unit_id sudah dipakai satuan lain (baik yang masih aktif maupun yang sudah dihapus lewat DELETE units) — pakai PUT untuk memperbarui satuan yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Ketiga field wajib diisi, tidak ada yang bersifat opsional.',
            'id pada respons adalah id satuan yang dibuat Pegasus sendiri (auto-increment) — SIMPAN nilai ini kalau nanti perlu memanggil PATCH /master/units/connect (yang butir connections-nya memakai id Pegasus, bukan ref_unit_id).',
            'Bukan upsert: mengirim ref_unit_id yang sudah dipakai (aktif maupun yang satuannya sudah dihapus) selalu ditolak dengan DUPLICATE_REF_ID, tidak pernah menimpa data yang sudah ada. Pakai PUT /master/units/{ref_unit_id} untuk memperbarui satuan yang rujukannya sudah ada.',
            'Untuk menghubungkan ref_unit_id ke satuan Pegasus yang SUDAH ADA (dibuat lewat halaman admin atau Pusat Sinkronisasi, misalnya), pakai PATCH /master/units/connect — bukan POST ini, yang selalu membuat satuan baru.',
            'ref_unit_id juga ditulis Pusat Sinkronisasi (menarik data satuan dari PMO) — endpoint ini adalah jalur tulis kedua ke kolom yang sama, disengaja karena keduanya melayani sistem yang sama (PMO).',
        ];
    }
}
