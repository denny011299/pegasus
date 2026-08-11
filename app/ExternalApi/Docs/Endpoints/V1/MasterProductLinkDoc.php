<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PATCH /api/external/v1/produk/connect (Data Produk).
 *
 * Path-nya tetap (/connect), tidak menerima path parameter — setiap produk
 * yang mau dihubungkan disebutkan lewat id pada tiap butir body connections.
 */
class MasterProductLinkDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'produk-connect';
    }

    public function title(): string
    {
        return 'Hubungkan Produk';
    }

    public function method(): string
    {
        return 'PATCH';
    }

    public function path(): string
    {
        return '/produk/connect';
    }

    public function group(): string
    {
        return 'produk';
    }

    public function description(): string
    {
        return 'Menghubungkan satu atau banyak produk Pegasus yang SUDAH ADA dengan id produk pada sistem PMO (ref_product_id) sekaligus, tanpa membuat produk baru. Tiap butir diproses independen — sebagian boleh berhasil walau sebagian lain gagal.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'connections', 'type' => 'array', 'required' => true, 'description' => 'Daftar pasangan yang mau dihubungkan, minimal satu butir.'],
            ['name' => 'connections[].id', 'type' => 'integer', 'required' => true, 'description' => 'id produk PEGASUS (bukan ref_product_id) yang akan dihubungkan — lihat field id pada GET /produk, atau field id pada respons POST /produk.'],
            ['name' => 'connections[].ref_product_id', 'type' => 'integer', 'required' => true, 'description' => 'id produk pada sistem PMO yang akan dipasang ke produk tersebut.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'connections' => [
                ['id' => 1, 'ref_product_id' => 12],
                ['id' => 2, 'ref_product_id' => 13],
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
                    'ref_product_id' => 12,
                    'success' => true,
                    'data' => [
                        'id' => 1,
                        'ref_product_id' => 12,
                        'product_name' => 'AIR AKI PEGASUS',
                        'category_id' => 26,
                        'unit_id' => 7,
                        'product_unit' => [7, 9],
                    ],
                ],
                [
                    'id' => 999,
                    'ref_product_id' => 13,
                    'success' => false,
                    'error' => [
                        'code' => 'NOT_FOUND',
                        'message' => 'Produk dengan id 999 tidak ditemukan atau tidak aktif.',
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
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'connections kosong/bukan array, atau salah satu butir tidak berbentuk {id, ref_product_id} yang sah — berlaku untuk SELURUH permintaan (bukan per butir).'],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini SELALU menjawab 200 selama bentuk permintaannya sah (connections berupa array, tiap butir punya id dan ref_product_id berupa integer) — kegagalan per butir (produk tidak ditemukan/nonaktif) muncul lewat success:false pada butir itu di data, bukan lewat status HTTP gagal untuk seluruh permintaan. meta.failed > 0 menandakan ada butir yang gagal.',
            'id pada tiap butir connections adalah id produk PEGASUS, bukan ref_product_id — kebalikan dari PUT dan DELETE /produk, yang path parameternya adalah ref_product_id. Jangan tertukar.',
            'Setiap butir diproses independen, bukan satu transaksi besar: butir yang gagal tidak membatalkan butir lain yang berhasil dalam permintaan yang sama.',
            'Menimpa link yang sudah ada pada produk tujuan diperbolehkan.',
            'Kalau ref_product_id yang dikirim sedang dipegang produk LAIN (termasuk produk lain dalam butir connections yang sama), rujukan itu DILEPAS dulu dari produk itu (jadi null) sebelum dipasang ke produk tujuan — dipindah, bukan ditolak sebagai duplikat.',
            'Cocok dipakai untuk produk yang dibuat lewat halaman admin atau Pusat Sinkronisasi yang belum/salah terhubung ke ref_product_id yang benar, tanpa perlu membuat baris duplikat lewat POST.',
            'product_name/category_id/unit_id/product_unit tidak diubah oleh endpoint ini — hanya ref_product_id yang dipasang.',
            'ref_product_id juga ditulis Pusat Sinkronisasi (menarik data produk dari PMO) — endpoint ini adalah jalur tulis kedua ke kolom yang sama, disengaja karena keduanya melayani sistem yang sama (PMO); siapa yang menulis terakhir yang berlaku.',
        ];
    }
}
