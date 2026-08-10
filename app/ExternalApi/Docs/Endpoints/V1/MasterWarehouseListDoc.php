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
        return 'Mengambil daftar gudang yang berstatus aktif, '
            .'beserta nama dan id tipe gudangnya. Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh gudang aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "nama:asc,created_at:desc". Kunci yang sah: gudang_id, nama, tipe_id, alamat, created_at, updated_at (tipe_nama TIDAK bisa dipakai, lihat catatan). arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada nama ATAU alamat.'],
        ];
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
                'per_page' => 2,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua gudang aktif.',
            'gudang_id adalah id gudang pada sistem Pegasus dan merupakan pegangan yang stabil untuk merujuk satu gudang, dipakai sebagai path parameter pada endpoint ubah dan hapus gudang. Pakai gudang_id, bukan nama, karena nama gudang bisa berubah.',
            'Hanya gudang berstatus Aktif yang muncul. Gudang yang dinonaktifkan atau dihapus tidak ikut, jadi gudang yang hilang dari daftar berarti sudah tidak berlaku.',
            'alamat boleh bernilai null bila gudang memang belum diisi alamatnya.',
            'tipe_nama diambil lewat relasi ke tipe gudang, bukan disalin, sehingga selalu mengikuti nama tipe yang berlaku saat ini. Karena itu tipe_nama TIDAK bisa dipakai sebagai kunci ?sort= atau ikut dicari ?search= — kirim tipe_id kalau perlu memfilter/mengurutkan berdasarkan tipe.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal (termasuk tipe_nama), urutan bawaan (dibuat lebih dulu → gudang_id) tetap berlaku.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
