<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/master/staff (GitHub #177).
 */
class MasterStaffCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-staff-create';
    }

    public function title(): string
    {
        return 'Buat Staf (Non-Sales)';
    }

    public function method(): string
    {
        return 'POST';
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
        return 'Membuat staf baru di Pegasus dengan peran non-Sales yang didukung (saat ini "Owner", ditentukan otomatis oleh Pegasus — bukan dipilih pemanggil), dihubungkan dengan id milik sistem pemanggil sendiri (staff_id pada body). Staf yang baru dibuat tidak mendapat akun login (staff_username/staff_password tetap kosong), sehingga tidak pernah bisa login ke Pegasus. Selalu membuat baris baru — bukan upsert; staff_id yang sudah dipakai staf lain ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'string|integer', 'required' => true, 'description' => 'id milik sistem pemanggil sendiri untuk staf ini (BUKAN id Pegasus). Wajib belum pernah dipakai staf lain di Pegasus.'],
            ['name' => 'nama_depan', 'type' => 'string', 'required' => true, 'description' => 'Nama depan.'],
            ['name' => 'nama_belakang', 'type' => 'string', 'required' => false, 'description' => 'Nama belakang. Boleh dikosongkan.'],
            ['name' => 'email', 'type' => 'string', 'required' => false, 'description' => 'Alamat email. Boleh dikosongkan; kalau dikirim, harus berbentuk alamat email yang sah.'],
            ['name' => 'alamat', 'type' => 'string', 'required' => false, 'description' => 'Alamat. Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'staff_id' => 'OWN-001',
            'nama_depan' => 'Budi',
            'nama_belakang' => 'Santoso',
            'email' => 'budi@contoh.com',
            'alamat' => 'Jl. Sudirman Block A No. 12',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 105,
                'staff_id' => 'OWN-001',
                'nama_depan' => 'Budi',
                'nama_belakang' => 'Santoso',
                'email' => 'budi@contoh.com',
                'alamat' => 'Jl. Sudirman Block A No. 12',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'staff_id atau nama_depan kosong, atau salah satu field lain tidak valid (mis. email dikirim tapi bukan alamat email yang sah).'],
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'staff_id sudah dipakai staf lain (baik yang masih aktif maupun yang sudah dihapus lewat DELETE staf) — pakai PUT untuk memperbarui staf yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id dan nama_depan wajib diisi; email, nama_belakang, dan alamat boleh dikosongkan.',
            'role BUKAN field body: peran staf yang dibuat lewat endpoint ini ditentukan otomatis oleh Pegasus (saat ini selalu "Owner"), tidak bisa dipilih atau diubah pemanggil. Staf ini tidak pernah mendapat akun login, sehingga peran/hak aksesnya tidak benar-benar terpakai untuk login — role hanya dipakai Pegasus untuk mengelompokkan data.',
            'Daftar peran yang didukung endpoint ini (untuk dibaca/dihubungkan lewat GET dan PATCH /connect) ditentukan dari sisi Pegasus dan bisa bertambah dari waktu ke waktu.',
            'id pada respons adalah id staf yang dibuat Pegasus sendiri (auto-increment) — SIMPAN nilai ini kalau nanti perlu memanggil PATCH /master/staff/connect (yang memakai id Pegasus, bukan staff_id/rujukan Anda).',
            'staff_id pada body dan respons adalah id milik sistem Anda sendiri, disimpan di kolom terpisah (external_ref_id) — TIDAK menjadi id staf Pegasus.',
            'Bukan upsert: mengirim staff_id yang sudah dipakai selalu ditolak dengan DUPLICATE_REF_ID. Pakai PUT /master/staff/{staff_id} untuk memperbarui staf yang rujukannya sudah ada.',
            'Untuk menghubungkan rujukan ke staf Pegasus yang SUDAH ADA (dibuat lewat halaman admin, misalnya), pakai PATCH /master/staff/connect — bukan POST ini, yang selalu membuat staf baru.',
            'kode (staff_code) dan telepon (staff_phone) tidak dikelola lewat endpoint ini — lihat GET /master/staff untuk field itu.',
        ];
    }
}
