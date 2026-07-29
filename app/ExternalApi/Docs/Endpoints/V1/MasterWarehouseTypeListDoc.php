<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/warehouse_types (API-002).
 */
class MasterWarehouseTypeListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-warehouse-types';
    }

    public function title(): string
    {
        return 'Daftar Tipe Gudang';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/warehouse_types';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil seluruh data master tipe gudang yang berstatus aktif. '
            .'Berdiri sendiri sebagai ekspor data master, bukan pelengkap daftar gudang.';
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'nama' => 'Gol A',
                    'status' => 1,
                ],
            ],
            'meta' => [
                'total' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini tidak menerima parameter apa pun.',
            'Endpoint ini berdiri sendiri sebagai ekspor data master tipe gudang, dan memang tidak dimaksudkan untuk dicocokkan dengan tipe_id pada daftar gudang. Karena itu isinya hanya nama dan status.',
            'status berupa bilangan bulat mengikuti penyimpanan di Pegasus: 1 berarti aktif. Karena hanya tipe aktif yang dikembalikan, nilainya selalu 1; field ini tetap disertakan karena merupakan bagian dari kontrak yang disepakati.',
            'Urutan daftar bersifat tetap.',
        ];
    }
}
