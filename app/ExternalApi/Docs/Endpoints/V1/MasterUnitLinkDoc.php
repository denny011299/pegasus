<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PATCH /api/external/v1/master/units/connect (API-001 lanjutan).
 *
 * Path-nya tetap (/connect), tidak menerima path parameter — setiap satuan
 * yang mau dihubungkan disebutkan lewat id pada tiap butir body connections.
 */
class MasterUnitLinkDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-units-connect';
    }

    public function title(): string
    {
        return 'Hubungkan Satuan';
    }

    public function method(): string
    {
        return 'PATCH';
    }

    public function path(): string
    {
        return '/master/units/connect';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Menghubungkan satu atau banyak satuan Pegasus yang SUDAH ADA dengan id satuan pada sistem PMO (ref_unit_id) sekaligus, tanpa membuat satuan baru. Tiap butir diproses independen — sebagian boleh berhasil walau sebagian lain gagal.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'connections', 'type' => 'array', 'required' => true, 'description' => 'Daftar pasangan yang mau dihubungkan, minimal satu butir.'],
            ['name' => 'connections[].id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan PEGASUS (bukan ref_unit_id) yang akan dihubungkan — lihat field id pada GET /master/units, atau field id pada respons POST /master/units.'],
            ['name' => 'connections[].ref_unit_id', 'type' => 'integer', 'required' => true, 'description' => 'id satuan pada sistem PMO yang akan dipasang ke satuan tersebut.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'connections' => [
                ['id' => 1, 'ref_unit_id' => 1042],
                ['id' => 2, 'ref_unit_id' => 1043],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'ref_unit_id' => 1042,
                    'success' => true,
                    'data' => [
                        'id' => 1,
                        'ref_unit_id' => 1042,
                        'unit_name' => 'Kilogram',
                        'unit_short_name' => 'kg',
                    ],
                ],
                [
                    'id' => 999,
                    'ref_unit_id' => 1043,
                    'success' => false,
                    'error' => [
                        'code' => 'not_found',
                        'message' => 'Satuan dengan id 999 tidak ditemukan atau tidak aktif.',
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
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'connections kosong/bukan array, atau salah satu butir tidak berbentuk {id, ref_unit_id} yang sah — berlaku untuk SELURUH permintaan (bukan per butir).'],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini SELALU menjawab 200 selama bentuk permintaannya sah (connections berupa array, tiap butir punya id dan ref_unit_id berupa integer) — kegagalan per butir (satuan tidak ditemukan/nonaktif) muncul lewat success:false pada butir itu di data, bukan lewat status HTTP gagal untuk seluruh permintaan. meta.failed > 0 menandakan ada butir yang gagal.',
            'id pada tiap butir connections adalah id satuan PEGASUS, bukan ref_unit_id — kebalikan dari PUT dan DELETE /master/units, yang path parameternya adalah ref_unit_id. Jangan tertukar.',
            'Setiap butir diproses independen, bukan satu transaksi besar: butir yang gagal tidak membatalkan butir lain yang berhasil dalam permintaan yang sama.',
            'Menimpa ref_unit_id yang sudah ada pada satuan tujuan diperbolehkan.',
            'Kalau ref_unit_id yang dikirim sedang dipegang satuan LAIN (termasuk satuan lain dalam butir connections yang sama), rujukan itu DILEPAS dulu dari satuan itu (jadi null) sebelum dipasang ke satuan tujuan — dipindah, bukan ditolak sebagai duplikat.',
            'Cocok dipakai untuk satuan yang dibuat lewat halaman admin atau Pusat Sinkronisasi yang belum/salah terhubung ke ref_unit_id yang benar, tanpa perlu membuat baris duplikat lewat POST.',
            'unit_name/unit_short_name tidak diubah oleh endpoint ini — hanya ref_unit_id yang dipasang.',
            'ref_unit_id juga ditulis Pusat Sinkronisasi (menarik data satuan dari PMO) — endpoint ini adalah jalur tulis kedua ke kolom yang sama, disengaja karena keduanya melayani sistem yang sama (PMO); siapa yang menulis terakhir yang berlaku.',
        ];
    }
}
