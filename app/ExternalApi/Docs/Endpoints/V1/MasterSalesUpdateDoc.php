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
        return 'Ubah/Buat Sales (Upsert)';
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
        return 'Upsert: mengubah data sales yang sudah ada, dicari lewat staff_id (rujukan milik sistem Anda sendiri, bukan id Pegasus), atau membuat sales baru dengan staff_id itu kalau belum pernah ada (peran otomatis Sales, sama seperti POST). Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Sales yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
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
            ['name' => 'email', 'type' => 'string', 'required' => false, 'description' => 'Alamat email. Boleh dikosongkan; kalau dikirim, harus berbentuk alamat email yang sah.'],
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
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'staff_id (rujukan Anda) sudah dipakai staf yang BUKAN sales — di luar jangkauan endpoint ini, tidak diambil alih.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'nama_depan kosong, atau salah satu field lain tidak valid (mis. email dikirim tapi bukan alamat email yang sah).'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id pada path adalah rujukan milik sistem Anda sendiri (external_ref_id), BUKAN id Pegasus — endpoint yang path parameternya id Pegasus adalah PATCH /master/sales/{id}.',
            'Hanya nama_depan yang wajib diisi, meski hanya satu field yang berubah; email, nama_belakang, dan alamat boleh dikosongkan.',
            'Body selalu dianggap representasi penuh sales ini: email/nama_belakang/alamat yang tidak dikirim disimpan sebagai kosong, bukan mempertahankan nilai lama. Kirim ulang email lama kalau tidak ingin menghapusnya.',
            'Upsert: staff_id yang belum pernah ada membuat sales baru (respons 201). staff_id yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui. staff_id yang sudah dipakai staf berperan lain (di luar jangkauan endpoint ini) tetap ditolak sebagai DUPLICATE_REF_ID, tidak diambil alih.',
            'kode (staff_code), telepon (staff_phone), dan role tidak dikelola lewat endpoint ini — nilainya tidak berubah walau tidak dikirim.',
        ];
    }
}
