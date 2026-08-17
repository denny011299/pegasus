<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/bahan (Data Bahan).
 */
class MasterSuppliesListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'bahan';
    }

    public function title(): string
    {
        return 'Daftar Bahan';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/bahan';
    }

    public function group(): string
    {
        return 'bahan';
    }

    public function description(): string
    {
        return 'Mengambil daftar bahan mentah/kemasan yang berstatus aktif. Paginasi bersifat opsional, dan daftar satuan bisa ikut disertakan lewat parameter show_units.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh bahan aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "supplies_name:asc,created_at:desc". Kunci yang sah: id, ref_supplies_id, supplies_name, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada supplies_name dan supplies_desc.'],
            ['name' => 'show_units', 'type' => 'boolean', 'required' => false, 'description' => 'Kirim true untuk menyertakan field "units": daftar satuan (id, unit_name, unit_short_name) hasil resolusi supplies_unit digabung supplies_default_unit, tanpa duplikat.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 5,
                    'ref_supplies_id' => 12,
                    'supplies_name' => 'Aki Zuur',
                    'supplies_desc' => 'Cairan pengisi aki',
                    'supplies_default_unit' => 1,
                    'supplies_unit' => [1, 7],
                    'units' => [
                        ['id' => 1, 'unit_name' => 'Liter', 'unit_short_name' => 'L'],
                        ['id' => 7, 'unit_name' => 'DRUM', 'unit_short_name' => 'DRUM'],
                    ],
                ],
            ],
            'meta' => [
                'total' => 40,
                'per_page' => 40,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua bahan aktif.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan (dibuat lebih dulu → id) tetap berlaku.',
            'Hanya bahan berstatus aktif yang muncul. Bahan yang dihapus lewat DELETE /bahan (atau dinonaktifkan lewat halaman admin) hilang dari daftar ini.',
            'id adalah id bahan pada sistem Pegasus (supplies.supplies_id), dipakai sebagai field id pada tiap butir body PATCH /bahan/connect DAN sebagai item.ref_id (type=1) pada POST /shipments/returns SETELAH dikonversi lewat ref_supplies_id — lihat catatan berikut. ref_supplies_id adalah id bahan yang sama pada sistem PMO, boleh null selama bahan tersebut belum pernah dihubungkan lewat POST/PATCH endpoint ini — dan dipakai sebagai path parameter pada PUT/DELETE /bahan MAUPUN sebagai item.ref_id (type=1) pada POST /shipments/returns.',
            'supplies_default_unit adalah satuan default bahan ini. supplies_unit adalah daftar id satuan yang boleh dipakai untuk bahan ini. Tidak ada jaminan supplies_default_unit selalu ikut tercantum di dalam supplies_unit — endpoint ini tidak memaksakan itu — jadi field "units" pada show_units=true selalu menggabungkan keduanya supaya satuan default tidak pernah hilang dari daftar.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
