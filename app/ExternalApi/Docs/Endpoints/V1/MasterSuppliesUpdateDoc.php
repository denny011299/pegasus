<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/bahan/{ref_supplies_id} (Data Bahan).
 */
class MasterSuppliesUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'bahan-update';
    }

    public function title(): string
    {
        return 'Ubah/Buat Bahan (Upsert)';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/bahan/{ref_supplies_id}';
    }

    public function group(): string
    {
        return 'bahan';
    }

    public function description(): string
    {
        return 'Upsert: mengubah data bahan yang sudah ada, dicari lewat ref_supplies_id (id bahan pada sistem PMO), atau membuat bahan baru dengan ref_supplies_id itu kalau belum pernah ada. Bersifat penggantian penuh — seluruh field body wajib dikirim meski hanya satu yang berubah. Bahan yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_supplies_id', 'type' => 'integer', 'required' => true, 'description' => 'id bahan pada sistem PMO, sama dengan yang dikirim saat POST /bahan atau PATCH /bahan/connect.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'supplies_name', 'type' => 'string', 'required' => true, 'description' => 'Nama bahan.'],
            ['name' => 'supplies_desc', 'type' => 'string', 'required' => false, 'description' => 'Deskripsi bahan.'],
            ['name' => 'supplies_default_unit', 'type' => 'integer', 'required' => true, 'description' => 'id satuan default bahan ini. Wajib menunjuk satuan yang berstatus aktif.'],
            ['name' => 'supplies_unit', 'type' => 'array of integer', 'required' => true, 'description' => 'Daftar id satuan yang boleh dipakai untuk bahan ini, minimal satu. Setiap unsurnya wajib menunjuk satuan yang berstatus aktif.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'supplies_name' => 'Aki Zuur (Kemasan Baru)',
            'supplies_desc' => 'Cairan pengisi aki, kemasan drum',
            'supplies_default_unit' => 1,
            'supplies_unit' => [1, 7],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 41,
                'ref_supplies_id' => 12,
                'supplies_name' => 'Aki Zuur (Kemasan Baru)',
                'supplies_desc' => 'Cairan pengisi aki, kemasan drum',
                'supplies_default_unit' => 1,
                'supplies_unit' => [1, 7],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'supplies_name kosong, atau supplies_default_unit/salah satu unsur supplies_unit tidak menunjuk satuan yang aktif.'],
        ];
    }

    public function notes(): array
    {
        return [
            'ref_supplies_id pada path TIDAK BISA diubah lewat endpoint ini — kirim seluruh field body meski hanya satu yang berubah (bukan partial update).',
            'Upsert: ref_supplies_id yang belum pernah ada membuat bahan baru (respons 201) dengan data yang sama seperti dikirim ke POST /bahan. Bahan yang sudah ada tapi nonaktif/sudah dihapus (lewat DELETE /bahan) DIAKTIFKAN KEMBALI sekaligus diperbarui, bukan dijawab NOT_FOUND.',
        ];
    }
}
