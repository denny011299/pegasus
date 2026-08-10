<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/armada/{customer_code} (API-002 lanjutan).
 */
class MasterArmadaUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'armada-update';
    }

    public function title(): string
    {
        return 'Ubah Armada';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/armada/{customer_code}';
    }

    public function group(): string
    {
        return 'armada';
    }

    public function description(): string
    {
        return 'Mengubah data armada yang sudah ada, dicari lewat customer_code. Bersifat penggantian penuh — field yang tidak dikirim disimpan kosong, bukan mempertahankan nilai lama. Tidak pernah membuat armada baru.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'customer_code', 'type' => 'string', 'required' => true, 'description' => 'id universal armada yang akan diubah, lihat GET /armada.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'customer_pic', 'type' => 'string', 'required' => false, 'description' => 'Nama penanggung jawab/pemilik armada. Boleh dikosongkan.'],
            ['name' => 'customer_pic_phone', 'type' => 'string', 'required' => false, 'description' => 'Nomor telepon penanggung jawab. Boleh dikosongkan.'],
            ['name' => 'customer_notes', 'type' => 'string', 'required' => false, 'description' => 'Catatan armada, konvensi "nomor polisi (nama)". Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'customer_pic' => 'Agus',
            'customer_pic_phone' => '08123456789',
            'customer_notes' => 'W 9518 PG (Agus)',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 12,
                'customer_code' => 'ARM-JKT-001',
                'customer_pic' => 'Agus',
                'customer_pic_phone' => '08123456789',
                'customer_notes' => 'W 9518 PG (Agus)',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404, 'message' => 'customer_code tidak ditemukan, atau ditemukan tapi armadanya nonaktif/sudah dihapus.'],
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'Salah satu field tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Seluruh field body bersifat opsional (nullable) — tapi karena bersifat penggantian penuh, field yang tidak ikut dikirim akan DISIMPAN KOSONG, bukan mempertahankan nilai yang sudah ada. Kirim ulang nilai lama untuk field yang tidak ingin diubah.',
            'customer_code tidak bisa diubah lewat endpoint ini — kirim customer_code baru dengan DELETE + POST kalau memang perlu mengganti id universal armada ini.',
            'Endpoint ini HANYA menyentuh armada berstatus aktif — customer_code yang menunjuk armada nonaktif/sudah dihapus dijawab not_found, bukan diizinkan mengubah data armada itu.',
        ];
    }
}
