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
        return 'Mengambil daftar satuan yang berstatus aktif. '
            .'Dipakai sistem eksternal untuk menyelaraskan satuan sebelum '
            .'menukar data produk atau stok. Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh satuan aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "unit_name:asc,created_at:desc". Kunci yang sah: id, ref_unit_id, unit_name, unit_short_name, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada unit_name ATAU unit_short_name.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'ref_unit_id' => null,
                    'unit_name' => 'Kilogram',
                    'unit_short_name' => 'kg',
                ],
                [
                    'id' => 2,
                    'ref_unit_id' => null,
                    'unit_name' => 'Jerigen',
                    'unit_short_name' => 'jerigen',
                ],
                [
                    'id' => 7,
                    'ref_unit_id' => 1042,
                    'unit_name' => 'DOS',
                    'unit_short_name' => 'DOS',
                ],
            ],
            'meta' => [
                'total' => 11,
                'per_page' => 11,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua satuan aktif.',
            'Hanya satuan berstatus aktif yang muncul. Satuan yang dihapus di Pegasus akan hilang dari daftar ini — perlakukan satuan yang tidak lagi muncul sebagai satuan yang sudah tidak berlaku.',
            'id adalah id pada sistem Pegasus, dipakai sebagai field id pada tiap butir body PATCH /master/units/connect. ref_unit_id adalah id satuan yang sama pada sistem PMO, boleh null selama satuan tersebut belum pernah dihubungkan lewat POST/PATCH endpoint ini atau Pusat Sinkronisasi — dan dipakai sebagai path parameter pada PUT/DELETE /master/units.',
            'ref_unit_id sekaligus ditulis Pusat Sinkronisasi (menarik data satuan dari PMO). POST/PUT/PATCH pada grup endpoint ini adalah jalur tulis kedua ke kolom yang sama — disengaja, keduanya melayani sistem yang sama (PMO); siapa yang menulis terakhir yang berlaku.',
            'unit_short_name adalah singkatan yang tampil di layar stok, misalnya "kg". Nilainya tidak pernah kosong.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan (dibuat lebih dulu → id) tetap berlaku.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
