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
        return 'Mengambil daftar armada yang berstatus aktif. "Armada" adalah baris pada tabel pelanggan Pegasus yang mewakili kendaraan (nomor polisi dicatat di customer_notes) — bukan tabel tersendiri. Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh armada aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "customer_code:asc,created_at:desc". Kunci yang sah: id, customer_code, customer_pic, customer_pic_phone, customer_notes, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada customer_code, customer_pic, customer_pic_phone, ATAU customer_notes.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 12,
                    'customer_code' => 'ARM-JKT-001',
                    'customer_pic' => 'Agus',
                    'customer_pic_phone' => '08123456789',
                    'customer_notes' => 'W 9518 PG (Agus)',
                ],
                [
                    'id' => 13,
                    'customer_code' => 'CUS0013',
                    'customer_pic' => null,
                    'customer_pic_phone' => null,
                    'customer_notes' => null,
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
            'customer_code adalah id UNIVERSAL untuk armada ini: kalau dibuat lewat POST endpoint ini, nilainya persis yang dikirim pemanggil; kalau dibuat lewat halaman admin, nilainya di-generate otomatis Pegasus (format "CUSxxxx"). Dipakai sebagai path parameter pada PUT dan DELETE /armada.',
            'customer_pic, customer_pic_phone, dan customer_notes boleh bernilai null bila datanya memang belum diisi. customer_notes adalah tempat konvensi "nomor polisi (nama)" tersimpan, mis. "W 9518 PG (Agus)".',
            '?sort= menggantikan urutan bawaan sepenuhnya begitu ada satu saja kunci yang sah; kalau seluruh kunci yang dikirim tidak dikenal, urutan bawaan (dibuat lebih dulu → id) tetap berlaku.',
            'Urutan daftar bersifat tetap (diurutkan berdasarkan waktu pembuatan, lalu id), sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
