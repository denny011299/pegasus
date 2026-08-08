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
        return 'Membuat sales baru, atau memperbarui sales yang sudah ada kalau staff_id yang dikirim sudah dipakai (upsert). Sales yang baru dibuat otomatis berperan Sales, tanpa akun login (staff_username/staff_password tetap kosong).';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'integer', 'required' => true, 'description' => 'id staf. Kalau sudah ada dan berperan Sales aktif, sales itu diperbarui (bukan dibuat baru). Kalau belum ada, dibuatkan staf baru dengan id ini juga (bukan id yang di-generate sistem).'],
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
                'staff_id' => 2,
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
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'staff_id sudah dipakai staf lain yang bukan sales aktif (mis. staf berperan Admin, atau sales yang sudah dihapus lewat DELETE sales) — permintaan ditolak, bukan menimpa staf itu.'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id, nama_depan, dan email wajib diisi; nama_belakang dan alamat boleh dikosongkan.',
            'Body selalu dianggap representasi penuh sales ini: nama_belakang/alamat yang tidak dikirim disimpan sebagai kosong, bukan mempertahankan nilai lama — sama seperti endpoint gudang.',
            'Endpoint ini HANYA boleh menyentuh staf yang berperan Sales (dan aktif). staff_id yang menunjuk staf lain, atau sales yang sudah dihapus lewat DELETE sales, dijawab not_found — bukan diizinkan mengubah/menghidupkan kembali staf itu.',
            'staff_id sekaligus dipakai sebagai path parameter pada endpoint ubah (PUT) dan hapus (DELETE) sales.',
            'kode (staff_code), telepon (staff_phone), dan role tidak dikelola lewat endpoint ini — lihat GET /master/sales untuk field itu.',
        ];
    }
}
