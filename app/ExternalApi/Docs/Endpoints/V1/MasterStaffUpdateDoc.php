<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/master/staff/{staff_id} (GitHub #177).
 */
class MasterStaffUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-staff-update';
    }

    public function title(): string
    {
        return 'Ubah Staf (Non-Sales)';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/master/staff/{staff_id}';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengubah data staf yang sudah ada, dicari lewat staff_id (rujukan milik sistem Anda sendiri, bukan id Pegasus). Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Tidak pernah membuat staf baru, dan tidak mengubah role.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'staff_id', 'type' => 'string', 'required' => true, 'description' => 'id milik sistem Anda sendiri untuk staf yang akan diubah (BUKAN id Pegasus) — nilai yang sama dengan field staff_id pada GET /master/staff atau yang dikirim saat POST.'],
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
            ['code' => 'NOT_FOUND', 'http_status' => 404, 'message' => 'staff_id (rujukan Anda) tidak ditemukan, atau ditemukan tapi bukan staf yang dikelola endpoint ini (staf itu punya role, atau sudah dihapus).'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'nama_depan kosong, atau salah satu field lain tidak valid (mis. email dikirim tapi bukan alamat email yang sah).'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id pada path adalah rujukan milik sistem Anda sendiri (external_ref_id), BUKAN id Pegasus.',
            'Hanya nama_depan yang wajib diisi, meski hanya satu field yang berubah; email, nama_belakang, dan alamat boleh dikosongkan.',
            'Body selalu dianggap representasi penuh staf ini: email/nama_belakang/alamat yang tidak dikirim disimpan sebagai kosong, bukan mempertahankan nilai lama. Kirim ulang email lama kalau tidak ingin menghapusnya.',
            'Endpoint ini HANYA boleh menyentuh staf yang role_id-nya NULL (dan aktif) — staff_id yang menunjuk staf berperan apa pun dijawab NOT_FOUND.',
            'role tidak bisa diubah lewat endpoint ini.',
        ];
    }
}
