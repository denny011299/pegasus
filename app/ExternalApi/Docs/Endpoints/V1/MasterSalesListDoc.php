<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/sales (API-002).
 */
class MasterSalesListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales';
    }

    public function title(): string
    {
        return 'Daftar Sales';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/sales';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil daftar staf sales yang berstatus aktif. Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh sales aktif dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 20,
                    'staff_id' => null,
                    'nama' => 'Bisma',
                    'nama_depan' => 'Bisma',
                    'nama_belakang' => null,
                    'kode' => null,
                    'email' => 'bisma@contoh.com',
                    'telepon' => '08123456789',
                    'alamat' => 'Jl. Rungkut Industri No. 12, Surabaya',
                    'role' => 'Sales',
                ],
                [
                    'id' => 26,
                    'staff_id' => 'SLS-002',
                    'nama' => 'Sales Counter',
                    'nama_depan' => 'Sales',
                    'nama_belakang' => 'Counter',
                    'kode' => null,
                    'email' => null,
                    'telepon' => null,
                    'alamat' => null,
                    'role' => 'Sales',
                ],
            ],
            'meta' => [
                'total' => 7,
                'per_page' => 7,
                'current_page' => 1,
                'next_page_exists' => false,
                'total_page' => 1,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Bentuk meta selalu sama, dipaginasi maupun tidak: total, per_page, current_page, next_page_exists, total_page. Tanpa ?page=, current_page selalu 1, total_page selalu 1, dan next_page_exists selalu false — satu halaman berisi semua sales aktif.',
            'Sales bukan tabel tersendiri: yang dikembalikan adalah staf yang nama perannya mengandung kata "sales". Penyaringan memakai nama peran, bukan id peran, sehingga peran baru yang mengandung kata itu otomatis ikut terbawa.',
            'Hanya staf berstatus aktif yang muncul.',
            'id adalah id staf pada sistem Pegasus. Inilah nilai yang diminta endpoint Pembayaran Kas pada field staff_id ketika payment_type bernilai 2, dan juga field staff_id pada tiap butir body PATCH /master/sales/connect (menghubungkan staf yang sudah ada dengan rujukan eksternal).',
            'staff_id di respons ini BUKAN id staf Pegasus — ini rujukan milik sistem pemanggil sendiri (external_ref_id), boleh null kalau staf itu belum pernah dihubungkan ke sistem eksternal mana pun lewat POST atau PATCH /master/sales/connect. Inilah nilai yang dipakai sebagai path parameter {staff_id} pada PUT dan DELETE /master/sales.',
            'nama_depan dan nama_belakang adalah hasil pemisahan otomatis dari nama (dipisah pada spasi pertama), disertakan supaya sejalan dengan bentuk body create/update sales. Untuk staf yang namanya terdiri lebih dari dua kata dan tidak pernah dibuat/diubah lewat endpoint create/update sales, pemisahan ini bisa saja tidak sama dengan pembagian depan/belakang yang sebenarnya — staffs.staff_name memang cuma satu kolom gabungan.',
            'role berisi nama peran staf yang bersangkutan apa adanya seperti tersimpan di Pegasus, misalnya "Sales".',
            'kode, email, telepon, alamat, staff_id, dan nama_belakang boleh bernilai null bila datanya memang belum diisi di Pegasus.',
            'Kata sandi, nama pengguna, saldo staf, dan hak akses tidak pernah dikembalikan.',
            'Urutan daftar bersifat tetap.',
        ];
    }
}
