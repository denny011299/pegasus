<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/master/sales/{staff_id} (API-002 lanjutan).
 */
class MasterSalesUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales-update';
    }

    public function title(): string
    {
        return 'Ubah Sales';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/master/sales/{staff_id}';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengubah data sales yang sudah ada, dicari lewat staff_id (rujukan milik sistem Anda sendiri, bukan id Pegasus). Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Tidak pernah membuat sales baru.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'string', 'required' => true, 'description' => 'id milik sistem Anda sendiri untuk sales yang akan diubah (BUKAN id Pegasus) — nilai yang sama dengan field staff_id pada GET /master/sales atau yang dikirim saat POST/PATCH.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'nama_depan', 'type' => 'string', 'required' => true, 'description' => 'Nama depan.'],
            ['name' => 'nama_belakang', 'type' => 'string', 'required' => false, 'description' => 'Nama belakang. Boleh dikosongkan.'],
            ['name' => 'email', 'type' => 'string', 'required' => true, 'description' => 'Alamat email.'],
            ['name' => 'alamat', 'type' => 'string', 'required' => false, 'description' => 'Alamat. Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
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
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'staff_id (rujukan Anda) tidak ditemukan, atau ditemukan tapi bukan sales aktif (staf berperan lain, atau sales yang sudah dihapus).'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'nama_depan atau email kosong/tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id pada path adalah rujukan milik sistem Anda sendiri (external_ref_id), BUKAN id Pegasus — endpoint yang path parameternya id Pegasus adalah PATCH /master/sales/{id}.',
            'nama_depan dan email wajib diisi meski hanya satu yang berubah; nama_belakang dan alamat boleh dikosongkan.',
            'Body selalu dianggap representasi penuh sales ini: nama_belakang/alamat yang tidak dikirim disimpan sebagai kosong, bukan mempertahankan nilai lama.',
            'Endpoint ini HANYA boleh menyentuh staf yang berperan Sales (dan aktif) — staff_id yang menunjuk staf lain dijawab NOT_FOUND, bukan diizinkan mengubah data staf itu.',
            'kode (staff_code), telepon (staff_phone), dan role tidak dikelola lewat endpoint ini — nilainya tidak berubah walau tidak dikirim.',
        ];
    }
}
