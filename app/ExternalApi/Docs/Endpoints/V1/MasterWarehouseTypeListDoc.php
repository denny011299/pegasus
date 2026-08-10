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
        return 'Mengambil daftar tipe gudang yang berstatus aktif. '
            .'Berdiri sendiri sebagai ekspor data master, bukan pelengkap daftar gudang. Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh tipe gudang aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "nama:asc". Kunci yang sah: nama, status, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada nama.'],
        ];
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
                'per_page' => 1,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua tipe gudang aktif.',
            'Endpoint ini berdiri sendiri sebagai ekspor data master tipe gudang, dan memang tidak dimaksudkan untuk dicocokkan dengan tipe_id pada daftar gudang. Karena itu isinya hanya nama dan status.',
            'status berupa bilangan bulat mengikuti penyimpanan di Pegasus: 1 berarti aktif. Karena hanya tipe aktif yang dikembalikan, nilainya selalu 1; field ini tetap disertakan karena merupakan bagian dari kontrak yang disepakati.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan tetap berlaku.',
            'Urutan daftar bersifat tetap.',
        ];
    }
}
