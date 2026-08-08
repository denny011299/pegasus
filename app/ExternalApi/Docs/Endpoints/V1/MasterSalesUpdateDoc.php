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
        return 'Mengubah data sales yang sudah ada. Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Berbeda dengan POST, PUT tidak pernah membuat sales baru.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'integer', 'required' => true, 'description' => 'id sales yang akan diubah, lihat GET /master/sales.'],
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
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'staff_id tidak ditemukan, atau ditemukan tapi bukan sales aktif (staf berperan lain, atau sales yang sudah dihapus).'],
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'nama_depan atau email kosong/tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'nama_depan dan email wajib diisi meski hanya satu yang berubah; nama_belakang dan alamat boleh dikosongkan.',
            'Body selalu dianggap representasi penuh sales ini: nama_belakang/alamat yang tidak dikirim disimpan sebagai kosong, bukan mempertahankan nilai lama.',
            'Endpoint ini HANYA boleh menyentuh staf yang berperan Sales (dan aktif) — staff_id yang menunjuk staf lain dijawab not_found, bukan diizinkan mengubah data staf itu.',
            'kode (staff_code), telepon (staff_phone), dan role tidak dikelola lewat endpoint ini — nilainya tidak berubah walau tidak dikirim.',
        ];
    }
}
