<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/units (API-001).
 *
 * Contoh respons di bawah diambil dari keluaran endpoint yang sebenarnya
 * terhadap data yang ada, bukan dikarang — sesuai permintaan spesifikasi
 * API-001 pada bagian Documentation.
 */
class MasterUnitListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-units';
    }

    public function title(): string
    {
        return 'Daftar Satuan';
    }

    public function method(): string
    {
        return 'GET';
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
        return 'Mengambil seluruh data master satuan yang berstatus aktif. '
            .'Dipakai sistem eksternal untuk menyelaraskan satuan sebelum '
            .'menukar data produk atau stok.';
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'unit_id' => 1,
                    'ref_unit_id' => null,
                    'unit_name' => 'Kilogram',
                    'unit_short_name' => 'kg',
                ],
                [
                    'unit_id' => 2,
                    'ref_unit_id' => null,
                    'unit_name' => 'Jerigen',
                    'unit_short_name' => 'jerigen',
                ],
                [
                    'unit_id' => 7,
                    'ref_unit_id' => null,
                    'unit_name' => 'DOS',
                    'unit_short_name' => 'DOS',
                ],
            ],
            'meta' => [
                'total' => 11,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini tidak menerima parameter apa pun. Seluruh satuan aktif selalu dikembalikan sekaligus.',
            'Hanya satuan berstatus aktif yang muncul. Satuan yang dihapus di Pegasus akan hilang dari daftar ini — perlakukan satuan yang tidak lagi muncul sebagai satuan yang sudah tidak berlaku.',
            'unit_id adalah id pada sistem Pegasus. ref_unit_id adalah id satuan yang sama pada sistem PMO, dan bernilai null selama satuan tersebut belum pernah dicocokkan lewat Pusat Sinkronisasi.',
            'unit_short_name adalah singkatan yang tampil di layar stok, misalnya "kg". Nilainya tidak pernah kosong.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
