<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PATCH /api/external/v1/master/staff/connect (GitHub #177).
 *
 * Path-nya tetap (/connect), tidak menerima path parameter — setiap staf
 * yang mau dihubungkan disebutkan lewat staff_id pada tiap butir body
 * connections. staff_id di SINI adalah id Pegasus — kebalikan dari PUT/
 * DELETE staff yang path parameternya rujukan eksternal.
 */
class MasterStaffLinkDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-staff-connect';
    }

    public function title(): string
    {
        return 'Hubungkan Staf (Non-Sales)';
    }

    public function method(): string
    {
        return 'PATCH';
    }

    public function path(): string
    {
        return '/master/staff/connect';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Menghubungkan satu atau banyak staf Pegasus yang SUDAH ADA (berperan yang didukung endpoint ini, dibuat lewat halaman admin misalnya) dengan id milik sistem Anda sendiri sekaligus, tanpa membuat staf baru. Tiap butir diproses independen — sebagian boleh berhasil walau sebagian lain gagal.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'connections', 'type' => 'array', 'required' => true, 'description' => 'Daftar pasangan yang mau dihubungkan, minimal satu butir.'],
            ['name' => 'connections[].staff_id', 'type' => 'integer', 'required' => true, 'description' => 'id STAF PEGASUS (bukan rujukan Anda) yang akan dihubungkan — lihat field id pada GET /master/staff, atau field id pada respons POST /master/staff.'],
            ['name' => 'connections[].map_staff_id', 'type' => 'string', 'required' => true, 'description' => 'id milik sistem Anda sendiri yang akan dipasang ke staf tersebut.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'connections' => [
                ['staff_id' => 1, 'map_staff_id' => 'OWN-001'],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'staff_id' => 1,
                    'map_staff_id' => 'OWN-001',
                    'success' => true,
                    'data' => [
                        'id' => 1,
                        'staff_id' => 'OWN-001',
                        'nama_depan' => 'Budi',
                        'nama_belakang' => 'Santoso',
                        'email' => 'budi@contoh.com',
                        'alamat' => 'Jl. Rungkut Industri No. 12, Surabaya',
                    ],
                ],
            ],
            'meta' => [
                'total' => 1,
                'success' => 1,
                'failed' => 0,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'connections kosong/bukan array, atau salah satu butir tidak berbentuk {staff_id, map_staff_id} yang sah — berlaku untuk SELURUH permintaan (bukan per butir).'],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini SELALU menjawab 200 selama bentuk permintaannya sah — kegagalan per butir (staf tidak ditemukan/bukan peran yang didukung, map_staff_id kosong) muncul lewat success:false pada butir itu di data, bukan lewat status HTTP gagal untuk seluruh permintaan.',
            'staff_id pada tiap butir connections adalah id STAF PEGASUS — kebalikan dari PUT dan DELETE /master/staff, yang path parameternya adalah rujukan Anda sendiri.',
            'Setiap butir diproses independen, bukan satu transaksi besar: butir yang gagal tidak membatalkan butir lain yang berhasil dalam permintaan yang sama.',
            'Menimpa link yang sudah ada pada staf tujuan diperbolehkan.',
            'Kalau map_staff_id yang dikirim sedang dipegang staf LAIN, rujukan itu DILEPAS dulu dari staf itu (jadi null) sebelum dipasang ke staf tujuan — dipindah, bukan ditolak sebagai duplikat.',
            'Endpoint ini HANYA boleh menghubungkan staf dengan peran yang didukung (dan aktif) — staff_id yang menunjuk staf lain gagal dengan NOT_FOUND pada butir itu.',
            'nama_depan/nama_belakang/email/alamat tidak diubah oleh endpoint ini — hanya rujukan yang dipasang.',
        ];
    }
}
