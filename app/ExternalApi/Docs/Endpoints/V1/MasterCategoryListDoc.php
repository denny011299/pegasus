<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/categories (GitHub #171).
 *
 * Contoh respons diambil dari keluaran endpoint yang sebenarnya terhadap data
 * yang ada, bukan dikarang.
 */
class MasterCategoryListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-categories';
    }

    public function title(): string
    {
        return 'Daftar Kategori Produk';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/categories';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil daftar kategori produk yang berstatus aktif. Dipakai untuk '
            .'meresolusi id kategori yang dikirim sebagai category_id pada body '
            .'POST/PUT /produk — sama seperti /master/units dipakai untuk meresolusi unit_id.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh kategori aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "nama:asc". Kunci yang sah: id, nama, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada nama kategori.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 19,
                    'nama' => 'Air Zuur',
                ],
                [
                    'id' => 25,
                    'nama' => 'Coolant',
                ],
                [
                    'id' => 26,
                    'nama' => 'Air Aki',
                ],
            ],
            'meta' => [
                'total' => 3,
                'per_page' => 3,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua kategori aktif.',
            'Hanya kategori berstatus aktif yang muncul. Kategori yang dihapus di Pegasus akan hilang dari daftar ini, dan id-nya yang tersimpan di sisi pemanggil menjadi tidak valid lagi untuk POST/PUT /produk.',
            'Endpoint ini hanya baca — tidak ada create/update/delete kategori lewat External API. Kategori tetap dikelola lewat halaman admin Pegasus.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan tetap berlaku.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
