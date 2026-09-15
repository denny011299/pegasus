<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/staff (GitHub #177).
 */
class MasterStaffListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-staff';
    }

    public function title(): string
    {
        return 'Daftar Staf (Non-Sales)';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/staff';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil daftar staf aktif di luar Sales — peran mana saja yang didukung ditentukan dari sisi Pegasus, saat ini "Owner". Paginasi bersifat opsional.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Nomor halaman. Kalau parameter ini tidak dikirim sama sekali, seluruh staf yang didukung dikembalikan sekaligus tanpa paginasi.'],
            ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Jumlah baris per halaman, hanya berlaku kalau page dikirim. Default 20, maksimum 100.'],
            ['name' => 'sort', 'type' => 'string', 'required' => false, 'description' => 'Urutan kustom, format "kunci:arah" dipisah koma, mis. "nama:asc,created_at:desc". Kunci yang sah: id, staff_id, nama, kode, email, telepon, alamat, role, created_at, updated_at. arah: asc atau desc. Kunci/arah lain dilewati diam-diam, bukan galat.'],
            ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Kata kunci, dicocokkan %LIKE% pada nama, kode, email, telepon, alamat, ATAU staff_id.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'id' => 1,
                    'staff_id' => null,
                    'nama' => 'Budi Santoso',
                    'nama_depan' => 'Budi',
                    'nama_belakang' => 'Santoso',
                    'kode' => null,
                    'email' => 'budi@contoh.com',
                    'telepon' => '08123456789',
                    'alamat' => 'Jl. Rungkut Industri No. 12, Surabaya',
                    'role' => 'Owner',
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
            'Endpoint terpisah dari GET /master/sales: yang dikembalikan di sini adalah staf yang perannya PERSIS salah satu peran yang didukung Pegasus untuk sinkronisasi non-Sales — saat ini hanya "Owner". Daftar peran ini bisa bertambah di sisi Pegasus tanpa mengubah bentuk endpoint.',
            'Hanya staf berstatus aktif yang muncul.',
            'id adalah id staf pada sistem Pegasus — nilai yang dipakai pada field staff_id tiap butir body PATCH /master/staff/connect.',
            'staff_id di respons ini BUKAN id staf Pegasus — ini rujukan milik sistem pemanggil sendiri (external_ref_id), boleh null kalau staf itu belum pernah dihubungkan ke sistem eksternal mana pun. Inilah nilai yang dipakai sebagai path parameter {staff_id} pada PUT dan DELETE /master/staff.',
            'nama_depan dan nama_belakang adalah hasil pemisahan otomatis dari nama (dipisah pada spasi pertama) — untuk staf yang namanya lebih dari dua kata dan tidak pernah dibuat/diubah lewat endpoint ini, pemisahan ini bisa saja tidak sama dengan pembagian depan/belakang yang sebenarnya. Karena bukan kolom tersendiri, keduanya TIDAK bisa dipakai sebagai kunci ?sort= atau ?search=.',
            'role berisi nama peran staf apa adanya seperti tersimpan di Pegasus, misalnya "Owner".',
            'kode, email, telepon, alamat, staff_id, dan nama_belakang boleh bernilai null bila datanya memang belum diisi.',
            'Kata sandi, nama pengguna, saldo staf, dan hak akses tidak pernah dikembalikan.',
        ];
    }
}
