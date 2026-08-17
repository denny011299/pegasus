<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/produk (Data Produk).
 */
class MasterProductListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'produk';
    }

    public function title(): string
    {
        return 'Daftar Produk';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/produk';
    }

    public function group(): string
    {
        return 'produk';
    }

    public function description(): string
    {
        return 'Mengambil daftar produk yang berstatus aktif. Paginasi bersifat opsional, dan tiga relasi (satuan, kategori, varian) bisa ikut disertakan lewat parameter show_*.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh produk aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "product_name:asc,created_at:desc". Kunci yang sah: id, ref_product_id, product_name, category_id, unit_id, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada product_name.'],
            ['name' => 'show_units', 'type' => 'boolean', 'required' => false, 'description' => 'Kirim true untuk menyertakan field "units": daftar satuan (id, unit_name, unit_short_name) hasil resolusi product_unit digabung unit_id (default), tanpa duplikat.'],
            ['name' => 'show_category', 'type' => 'boolean', 'required' => false, 'description' => 'Kirim true untuk menyertakan field "category": {id, category_name} hasil resolusi category_id, atau null kalau kategorinya sudah tidak ada.'],
            ['name' => 'show_variants', 'type' => 'boolean', 'required' => false, 'description' => 'Kirim true untuk menyertakan field "variants": daftar varian aktif produk ini (tanpa konversi satuannya sendiri).'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 5,
                    'ref_product_id' => 12,
                    'product_name' => 'AIR AKI HIKARI',
                    'category_id' => 4,
                    'unit_id' => 1,
                    'product_unit' => [1, 7],
                    'units' => [
                        ['id' => 1, 'unit_name' => 'Kilogram', 'unit_short_name' => 'kg'],
                        ['id' => 7, 'unit_name' => 'DOS', 'unit_short_name' => 'DOS'],
                    ],
                    'category' => ['id' => 4, 'category_name' => 'AKI'],
                    'variants' => [
                        [
                            'id' => 30,
                            'product_variant_name' => '1 Liter',
                            'product_variant_sku' => 'HKCH1L',
                            'product_variant_barcode' => '8991234567890',
                            'product_variant_price' => 25000,
                            'product_variant_alert' => 10,
                            'unit_id' => 1,
                        ],
                    ],
                ],
            ],
            'meta' => [
                'total' => 80,
                'per_page' => 80,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua produk aktif.',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan (dibuat lebih dulu → id) tetap berlaku.',
            'Hanya produk berstatus aktif yang muncul. Produk yang dihapus lewat DELETE /produk (atau dinonaktifkan lewat halaman admin) hilang dari daftar ini.',
            'id adalah id produk pada sistem Pegasus (products.product_id), dipakai sebagai field id pada tiap butir body PATCH /produk/connect. ref_product_id adalah id produk yang sama pada sistem PMO, boleh null selama produk tersebut belum pernah dihubungkan lewat POST/PATCH endpoint ini atau Pusat Sinkronisasi — dan dipakai sebagai path parameter pada PUT/DELETE /produk.',
            'ref_product_id sekaligus ditulis Pusat Sinkronisasi (menarik data produk dari PMO). POST/PUT/PATCH pada grup endpoint ini adalah jalur tulis kedua ke kolom yang sama — disengaja, keduanya melayani sistem yang sama (PMO); siapa yang menulis terakhir yang berlaku.',
            'unit_id adalah satuan default produk ini. product_unit adalah daftar id satuan yang boleh dipakai untuk produk ini (dari mana pemanggil bisa memilih saat mencatat stok/transaksi). Tidak ada jaminan unit_id selalu ikut tercantum di dalam product_unit — endpoint ini tidak memaksakan itu — jadi field "units" pada show_units=true selalu menggabungkan keduanya supaya satuan default tidak pernah hilang dari daftar.',
            'show_units, show_category, dan show_variants masing-masing berdiri sendiri — kirim salah satu, semuanya, atau tidak sama sekali. Ketiganya diambil dengan query yang efisien (bukan satu-per-satu per produk): units dari satu kali ambil seluruh tabel satuan aktif, category lewat eager-load, variants lewat eager-load dengan penyaringan status aktif.',
            'variants pada show_variants=true tidak menyertakan konversi satuannya sendiri (product_relations) — hanya satu tingkat relasi dari produk.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
