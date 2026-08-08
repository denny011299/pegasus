<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PATCH /api/external/v1/master/sales/connect (API-002 lanjutan).
 *
 * Path-nya tetap (/connect), tidak menerima path parameter — setiap staf
 * yang mau dihubungkan disebutkan lewat staff_id pada tiap butir body
 * connections. staff_id di SINI adalah id Pegasus — kebalikan dari PUT/
 * DELETE sales yang path parameternya rujukan eksternal. Lihat catatan
 * kelas MasterSalesController.
 */
class MasterSalesLinkDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales-connect';
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
        return '/master/sales/connect';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Menghubungkan satu atau banyak staf Pegasus yang SUDAH ADA (berperan Sales, dibuat lewat halaman admin misalnya) dengan id milik sistem Anda sendiri sekaligus, tanpa membuat staf baru. Tiap butir diproses independen — sebagian boleh berhasil walau sebagian lain gagal.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'connections', 'type' => 'array', 'required' => true, 'description' => 'Daftar pasangan yang mau dihubungkan, minimal satu butir.'],
            ['name' => 'connections[].staff_id', 'type' => 'integer', 'required' => true, 'description' => 'id STAF PEGASUS (bukan rujukan Anda) yang akan dihubungkan — lihat field id pada GET /master/sales, atau field id pada respons POST /master/sales.'],
            ['name' => 'connections[].map_staff_id', 'type' => 'string', 'required' => true, 'description' => 'id milik sistem Anda sendiri yang akan dipasang ke staf tersebut.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'connections' => [
                ['staff_id' => 20, 'map_staff_id' => 'SLS-002'],
                ['staff_id' => 21, 'map_staff_id' => 'SLS-003'],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'staff_id' => 20,
                    'map_staff_id' => 'SLS-002',
                    'success' => true,
                    'data' => [
                        'id' => 20,
                        'staff_id' => 'SLS-002',
                        'nama_depan' => 'Bisma',
                        'nama_belakang' => null,
                        'email' => 'bisma@contoh.com',
                        'alamat' => 'Jl. Rungkut Industri No. 12, Surabaya',
                    ],
                ],
                [
                    'staff_id' => 999,
                    'map_staff_id' => 'SLS-003',
                    'success' => false,
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Staf dengan id 999 tidak ditemukan atau bukan sales aktif.',
                    ],
                ],
            ],
            'meta' => [
                'total' => 2,
                'success' => 1,
                'failed' => 1,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'connections kosong/bukan array, atau salah satu butir tidak berbentuk {staff_id, map_staff_id} yang sah — berlaku untuk SELURUH permintaan (bukan per butir).'],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini SELALU menjawab 200 selama bentuk permintaannya sah (connections berupa array, tiap butir punya staff_id integer dan map_staff_id string) — kegagalan per butir (staf tidak ditemukan/bukan sales, map_staff_id kosong) muncul lewat success:false pada butir itu di data, bukan lewat status HTTP gagal untuk seluruh permintaan. meta.failed > 0 menandakan ada butir yang gagal.',
            'staff_id pada tiap butir connections adalah id STAF PEGASUS — kebalikan dari PUT dan DELETE /master/sales, yang path parameternya adalah rujukan Anda sendiri. Jangan tertukar.',
            'Setiap butir diproses independen, bukan satu transaksi besar: butir yang gagal tidak membatalkan butir lain yang berhasil dalam permintaan yang sama.',
            'Menimpa link yang sudah ada pada staf tujuan diperbolehkan.',
            'Kalau map_staff_id yang dikirim sedang dipegang staf LAIN (termasuk staf lain dalam butir connections yang sama), rujukan itu DILEPAS dulu dari staf itu (jadi null) sebelum dipasang ke staf tujuan — dipindah, bukan ditolak sebagai duplikat. Staf yang kehilangan rujukannya tidak dihapus atau diubah datanya selain kehilangan link tersebut.',
            'Endpoint ini HANYA boleh menghubungkan staf yang berperan Sales (dan aktif) — staff_id yang menunjuk staf lain gagal dengan not_found pada butir itu.',
            'Cocok dipakai untuk sales yang dibuat lewat halaman admin (belum punya rujukan eksternal, staff_id-nya null pada GET /master/sales) yang baru mau disinkronkan ke sistem Anda, tanpa perlu membuat baris duplikat lewat POST.',
            'nama_depan/nama_belakang/email/alamat tidak diubah oleh endpoint ini — hanya rujukan yang dipasang.',
        ];
    }
}
