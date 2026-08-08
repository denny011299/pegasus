<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/warehouses (API-002).
 *
 * Contoh respons diambil dari keluaran endpoint yang sebenarnya.
 */
class MasterWarehouseListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-warehouses';
    }

    public function title(): string
    {
        return 'Daftar Gudang';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/warehouses';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil seluruh data master gudang yang berstatus aktif, '
            .'beserta nama dan id tipe gudangnya.';
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'gudang_id' => 1,
                    'nama' => 'Gudang Surabaya',
                    'tipe_nama' => 'Gol A',
                    'tipe_id' => 1,
                    'alamat' => 'Jl. Surabaya',
                ],
                [
                    'gudang_id' => 2,
                    'nama' => 'Jakarta Warehouse',
                    'tipe_nama' => 'Gol A',
                    'tipe_id' => 1,
                    'alamat' => 'Jl. Jayakarta',
                ],
            ],
            'meta' => [
                'total' => 2,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini tidak menerima parameter apa pun. Seluruh gudang aktif selalu dikembalikan sekaligus.',
            'gudang_id adalah id gudang pada sistem Pegasus dan merupakan pegangan yang stabil untuk merujuk satu gudang, dipakai sebagai path parameter pada endpoint ubah dan hapus gudang. Pakai gudang_id, bukan nama, karena nama gudang bisa berubah.',
            'Hanya gudang berstatus Aktif yang muncul. Gudang yang dinonaktifkan atau dihapus tidak ikut, jadi gudang yang hilang dari daftar berarti sudah tidak berlaku.',
            'alamat boleh bernilai null bila gudang memang belum diisi alamatnya.',
            'tipe_nama diambil lewat relasi ke tipe gudang, bukan disalin, sehingga selalu mengikuti nama tipe yang berlaku saat ini.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
