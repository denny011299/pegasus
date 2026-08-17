<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/cash_categories (API-001).
 *
 * Contoh respons diambil dari keluaran endpoint yang sebenarnya terhadap data
 * yang ada, bukan dikarang.
 */
class MasterCashCategoryListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-cash-categories';
    }

    public function title(): string
    {
        return 'Daftar Kategori Kas';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/cash_categories';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil daftar kategori kas yang berstatus aktif, '
            .'beserta jenis arus kasnya (uang masuk atau uang keluar). Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh kategori kas aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "cc_name:asc". Kunci yang sah: cc_id, cc_name, cc_type, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada cc_name.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'cc_id' => 1,
                    'cc_name' => 'Uang Setoran Kustomer',
                    'cc_type' => 'Masuk',
                ],
                [
                    'cc_id' => 2,
                    'cc_name' => 'Uang Makan',
                    'cc_type' => 'Keluar',
                ],
                [
                    'cc_id' => 3,
                    'cc_name' => 'BBM',
                    'cc_type' => 'Keluar',
                ],
            ],
            'meta' => [
                'total' => 41,
                'per_page' => 41,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua kategori kas aktif.',
            'Hanya kategori berstatus aktif yang muncul. Kategori yang dihapus di Pegasus akan hilang dari daftar ini.',
            'cc_type menyatakan arah arus kas dan pada data aktif hanya bernilai "Masuk" atau "Keluar". Nilainya berupa teks, bukan angka, dan huruf besar-kecilnya mengikuti apa adanya seperti tersimpan di Pegasus.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan tetap berlaku.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
