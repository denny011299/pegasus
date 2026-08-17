<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PATCH /api/external/v1/bahan/connect (Data Bahan).
 *
 * Path-nya tetap (/connect), tidak menerima path parameter — setiap bahan
 * yang mau dihubungkan disebutkan lewat id pada tiap butir body connections.
 */
class MasterSuppliesLinkDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'bahan-connect';
    }

    public function title(): string
    {
        return 'Hubungkan Bahan';
    }

    public function method(): string
    {
        return 'PATCH';
    }

    public function path(): string
    {
        return '/bahan/connect';
    }

    public function group(): string
    {
        return 'bahan';
    }

    public function description(): string
    {
        return 'Menghubungkan satu atau banyak bahan Pegasus yang SUDAH ADA dengan id bahan pada sistem PMO (ref_supplies_id) sekaligus, tanpa membuat bahan baru. Tiap butir diproses independen — sebagian boleh berhasil walau sebagian lain gagal.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'connections', 'type' => 'array', 'required' => true, 'description' => 'Daftar pasangan yang mau dihubungkan, minimal satu butir.'],
            ['name' => 'connections[].id', 'type' => 'integer', 'required' => true, 'description' => 'id bahan PEGASUS (bukan ref_supplies_id) yang akan dihubungkan — lihat field id pada GET /bahan, atau field id pada respons POST /bahan.'],
            ['name' => 'connections[].ref_supplies_id', 'type' => 'integer', 'required' => true, 'description' => 'id bahan pada sistem PMO yang akan dipasang ke bahan tersebut.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'connections' => [
                ['id' => 1, 'ref_supplies_id' => 12],
                ['id' => 2, 'ref_supplies_id' => 13],
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
                    'ref_supplies_id' => 12,
                    'success' => true,
                    'data' => [
                        'id' => 1,
                        'ref_supplies_id' => 12,
                        'supplies_name' => 'Aki Zuur',
                        'supplies_desc' => 'Cairan pengisi aki',
                        'supplies_default_unit' => 1,
                        'supplies_unit' => [1, 7],
                    ],
                ],
                [
                    'id' => 999,
                    'ref_supplies_id' => 13,
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Bahan dengan id 999 tidak ditemukan atau tidak aktif.',
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
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'connections kosong/bukan array, atau salah satu butir tidak berbentuk {id, ref_supplies_id} yang sah — berlaku untuk SELURUH permintaan (bukan per butir).'],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini SELALU menjawab 200 selama bentuk permintaannya sah (connections berupa array, tiap butir punya id dan ref_supplies_id berupa integer) — kegagalan per butir (bahan tidak ditemukan/nonaktif) muncul lewat success:false pada butir itu di data, bukan lewat status HTTP gagal untuk seluruh permintaan. meta.failed > 0 menandakan ada butir yang gagal.',
            'id pada tiap butir connections adalah id bahan PEGASUS, bukan ref_supplies_id — kebalikan dari PUT dan DELETE /bahan, yang path parameternya adalah ref_supplies_id. Jangan tertukar.',
            'Setiap butir diproses independen, bukan satu transaksi besar: butir yang gagal tidak membatalkan butir lain yang berhasil dalam permintaan yang sama.',
            'Menimpa link yang sudah ada pada bahan tujuan diperbolehkan.',
            'Kalau ref_supplies_id yang dikirim sedang dipegang bahan LAIN (termasuk bahan lain dalam butir connections yang sama), rujukan itu DILEPAS dulu dari bahan itu (jadi null) sebelum dipasang ke bahan tujuan — dipindah, bukan ditolak sebagai duplikat.',
            'Cocok dipakai untuk bahan yang dibuat lewat halaman admin dan belum/salah terhubung ke ref_supplies_id yang benar, tanpa perlu membuat baris duplikat lewat POST.',
            'supplies_name/supplies_desc/supplies_default_unit/supplies_unit tidak diubah oleh endpoint ini — hanya ref_supplies_id yang dipasang.',
        ];
    }
}
