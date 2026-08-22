<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/armada (API-002 lanjutan).
 */
class MasterArmadaListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'armada';
    }

    public function title(): string
    {
        return 'Daftar Armada';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/armada';
    }

    public function group(): string
    {
        return 'armada';
    }

    public function description(): string
    {
        return 'Mengambil daftar armada yang berstatus aktif. "Armada" adalah baris pada tabel pelanggan Pegasus yang mewakili kendaraan — bukan tabel tersendiri. Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh armada aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "code:asc,created_at:desc". Kunci yang sah: id, code, pic, pic_phone, nomor_polisi, category, merk_model, tahun_kendaraan, lokasi, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada code, pic, pic_phone, ATAU nomor_polisi.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 12,
                    'code' => 'ARM-JKT-001',
                    'pic' => 'Agus',
                    'pic_phone' => '08123456789',
                    'nomor_polisi' => 'W 9518 PG',
                    'category' => 'Truk Engkel',
                    'merk_model' => 'Mitsubishi Colt Diesel',
                    'tahun_kendaraan' => '2019',
                    'lokasi' => 'Pool Surabaya',
                ],
                [
                    'id' => 13,
                    'code' => 'CUS0013',
                    'pic' => null,
                    'pic_phone' => null,
                    'nomor_polisi' => null,
                    'category' => null,
                    'merk_model' => null,
                    'tahun_kendaraan' => null,
                    'lokasi' => null,
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
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua armada aktif.',
            'Hanya armada berstatus aktif yang muncul. Armada yang dihapus lewat DELETE /armada (atau dinonaktifkan lewat halaman admin) hilang dari daftar ini.',
            'id adalah id pelanggan pada sistem Pegasus (customers.customer_id) — hanya untuk referensi, tidak dipakai sebagai path parameter di endpoint mana pun pada modul ini.',
            'code adalah id UNIVERSAL untuk armada ini (tersimpan di customers.customer_code): kalau dibuat lewat POST endpoint ini, nilainya persis yang dikirim pemanggil; kalau dibuat lewat halaman admin, nilainya di-generate otomatis Pegasus (format "CUSxxxx"). Dipakai sebagai path parameter pada PUT dan DELETE /armada.',
            'Seluruh field selain id dan code boleh bernilai null bila datanya memang belum diisi. nomor_polisi tersimpan pada kolom customers.customer_notes, sehingga armada lama yang dibuat sebelum perubahan ini bisa berisi catatan bebas dengan konvensi "nomor polisi (nama)", mis. "W 9518 PG (Agus)".',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan (dibuat lebih dulu → id) tetap berlaku.',
            'Urutan daftar bersifat tetap (diurutkan berdasarkan waktu pembuatan, lalu id), sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
