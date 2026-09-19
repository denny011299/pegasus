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
        return 'Ubah/Buat Staf (Non-Sales, Upsert)';
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
        return 'Upsert: mengubah data staf yang sudah ada, dicari lewat staff_id (rujukan milik sistem Anda sendiri, bukan id Pegasus), atau membuat staf baru dengan staff_id itu kalau belum pernah ada (role_id selalu NULL, sama seperti POST). Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Tidak pernah mengubah role. Staf yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
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
            ['name' => 'email', 'type' => 'string', 'required' => false, 'description' => 'Alamat email atau username. Boleh dikosongkan; tidak divalidasi harus berformat email (PMO bisa mengirim username di field ini).'],
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
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'staff_id (rujukan Anda) sudah dipakai staf BERPERAN (mis. Sales) — di luar jangkauan endpoint ini, tidak diambil alih.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'nama_depan kosong, atau salah satu field lain tidak valid (mis. melebihi panjang maksimum).'],
        ];
    }

    public function notes(): array
    {
        return [
            'staff_id pada path adalah rujukan milik sistem Anda sendiri (external_ref_id), BUKAN id Pegasus.',
            'Hanya nama_depan yang wajib diisi, meski hanya satu field yang berubah; email, nama_belakang, dan alamat boleh dikosongkan.',
            'Body selalu dianggap representasi penuh staf ini: email/nama_belakang/alamat yang tidak dikirim disimpan sebagai kosong, bukan mempertahankan nilai lama. Kirim ulang email lama kalau tidak ingin menghapusnya.',
            'Upsert: staff_id yang belum pernah ada membuat staf baru (respons 201). staff_id yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui. staff_id yang sudah dipakai staf berperan (di luar jangkauan endpoint ini) tetap ditolak sebagai DUPLICATE_REF_ID, tidak diambil alih.',
            'role tidak bisa diubah lewat endpoint ini.',
        ];
    }
}
