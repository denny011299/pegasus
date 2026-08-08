<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/master/sales (API-002 lanjutan).
 */
class MasterSalesCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales-create';
    }

    public function title(): string
    {
        return 'Buat Sales';
    }

    public function method(): string
    {
        return 'POST';
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
        return 'Membuat sales baru di Pegasus, dihubungkan dengan id milik sistem pemanggil sendiri (staff_id pada body). Sales yang baru dibuat otomatis berperan Sales, tanpa akun login (staff_username/staff_password tetap kosong). Selalu membuat baris baru — bukan upsert; staff_id yang sudah dipakai sales lain ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'string|integer', 'required' => true, 'description' => 'id milik sistem pemanggil sendiri untuk sales ini (BUKAN id Pegasus). Wajib belum pernah dipakai sales lain di Pegasus.'],
            ['name' => 'nama_depan', 'type' => 'string', 'required' => true, 'description' => 'Nama depan.'],
            ['name' => 'nama_belakang', 'type' => 'string', 'required' => false, 'description' => 'Nama belakang. Boleh dikosongkan.'],
            ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'Alamat email.'],
            ['name' => 'alamat', 'type' => 'string', 'required' => false, 'description' => 'Alamat. Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'staff_id' => 2,
            'nama_depan' => 'Willian',
            'nama_belakang' => 'Hartanto',
            'email' => 'wilha.h@gmail.com',
            'alamat' => 'Jl. Sudirman Block A No. 12',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 105,
                'staff_id' => '2',
                'nama_depan' => 'Willian',
                'nama_belakang' => 'Hartanto',
                'email' => 'wilha.h@gmail.com',
                'alamat' => 'Jl. Sudirman Block A No. 12',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'staff_id, nama_depan, atau email kosong/tidak valid.'],
            ['code' => 'duplicate_ref_id', 'http_status' => 422, 'message' => 'staff_id sudah dipakai sales lain (baik yang masih aktif maupun yang sudah dihapus lewat DELETE sales) — pakai PUT untuk memperbarui sales yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id, nama_depan, dan email wajib diisi; nama_belakang dan alamat boleh dikosongkan.',
            'id pada respons adalah id staf yang dibuat Pegasus sendiri (auto-increment) — SIMPAN nilai ini kalau nanti perlu memanggil PATCH /master/sales/{staff_id} (yang path parameternya memakai id Pegasus, bukan staff_id/rujukan Anda).',
            'staff_id pada body dan respons adalah id milik sistem Anda sendiri, disimpan di kolom terpisah (external_ref_id) — TIDAK menjadi id staf Pegasus. Endpoint ini tidak pernah membiarkan Anda menentukan id Pegasus.',
            'Bukan upsert: mengirim staff_id yang sudah dipakai (aktif maupun yang sales-nya sudah dihapus) selalu ditolak dengan duplicate_ref_id, tidak pernah menimpa data yang sudah ada. Pakai PUT /master/sales/{staff_id} untuk memperbarui sales yang rujukannya sudah ada.',
            'Untuk menghubungkan rujukan ke staf Pegasus yang SUDAH ADA (dibuat lewat halaman admin, misalnya), pakai PATCH /master/sales/{id} — bukan POST ini, yang selalu membuat staf baru.',
            'kode (staff_code), telepon (staff_phone), dan role tidak dikelola lewat endpoint ini — lihat GET /master/sales untuk field itu.',
        ];
    }
}
