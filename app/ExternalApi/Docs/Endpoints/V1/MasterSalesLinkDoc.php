<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PATCH /api/external/v1/master/sales/{staff_id} (API-002 lanjutan).
 *
 * {staff_id} pada endpoint ini adalah id Pegasus — kebalikan dari PUT/DELETE
 * sales yang path parameternya rujukan eksternal. Lihat catatan kelas
 * MasterSalesController.
 */
class MasterSalesLinkDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales-link';
    }

    public function title(): string
    {
        return 'Hubungkan Sales';
    }

    public function method(): string
    {
        return 'PATCH';
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
        return 'Menghubungkan staf Pegasus yang SUDAH ADA (berperan Sales, dibuat lewat halaman admin misalnya) dengan id milik sistem Anda sendiri, tanpa membuat staf baru. Beda dengan endpoint sales lain: {staff_id} di sini adalah id Pegasus, bukan rujukan Anda.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'integer', 'required' => true, 'description' => 'id STAF PEGASUS (bukan rujukan Anda) yang akan dihubungkan — lihat field id pada GET /master/sales, atau field id pada respons POST /master/sales.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'map_staff_id', 'type' => 'string', 'required' => true, 'description' => 'id milik sistem Anda sendiri yang akan dipasang ke staf ini.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'map_staff_id' => 'SLS-002',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 20,
                'staff_id' => 'SLS-002',
                'nama_depan' => 'Bisma',
                'nama_belakang' => null,
                'email' => 'bisma@contoh.com',
                'alamat' => 'Jl. Rungkut Industri No. 12, Surabaya',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'staff_id (id Pegasus) tidak ditemukan, atau ditemukan tapi bukan sales aktif (staf berperan lain, atau sudah dihapus).'],
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'map_staff_id kosong.'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id pada PATH endpoint ini adalah id STAF PEGASUS — kebalikan dari PUT dan DELETE /master/sales, yang path parameternya adalah rujukan Anda sendiri. Jangan tertukar.',
            'Menimpa link yang sudah ada pada staf tujuan diperbolehkan.',
            'Kalau map_staff_id yang dikirim sedang dipegang staf LAIN, rujukan itu DILEPAS dulu dari staf itu (jadi null) sebelum dipasang ke staf tujuan — dipindah, bukan ditolak sebagai duplikat. Staf yang kehilangan rujukannya tidak dihapus atau diubah datanya selain kehilangan link tersebut.',
            'Endpoint ini HANYA boleh menghubungkan staf yang berperan Sales (dan aktif) — staff_id yang menunjuk staf lain dijawab not_found.',
            'Cocok dipakai untuk sales yang dibuat lewat halaman admin (belum punya rujukan eksternal, staff_id-nya null pada GET /master/sales) yang baru mau disinkronkan ke sistem Anda, tanpa perlu membuat baris duplikat lewat POST.',
            'nama_depan/nama_belakang/email/alamat tidak diubah oleh endpoint ini — hanya rujukan yang dipasang.',
        ];
    }
}
