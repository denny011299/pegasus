<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/bahan (Data Bahan).
 */
class MasterSuppliesCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'bahan-create';
    }

    public function title(): string
    {
        return 'Buat Bahan';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/bahan';
    }

    public function group(): string
    {
        return 'bahan';
    }

    public function description(): string
    {
        return 'Membuat bahan mentah/kemasan baru di Pegasus, dihubungkan dengan id bahan pada sistem PMO (ref_supplies_id). Selalu membuat baris baru — bukan upsert; ref_supplies_id yang sudah dipakai bahan lain ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_supplies_id', 'type' => 'integer', 'required' => true, 'description' => 'id bahan yang sama pada sistem PMO. Wajib belum pernah dipakai bahan lain di Pegasus.'],
            ['name' => 'supplies_name', 'type' => 'string', 'required' => true, 'description' => 'Nama bahan.'],
            ['name' => 'supplies_desc', 'type' => 'string', 'required' => false, 'description' => 'Deskripsi bahan.'],
            ['name' => 'supplies_default_unit', 'type' => 'integer', 'required' => true, 'description' => 'id satuan default bahan ini. Wajib menunjuk satuan yang berstatus aktif.'],
            ['name' => 'supplies_unit', 'type' => 'array of integer', 'required' => true, 'description' => 'Daftar id satuan yang boleh dipakai untuk bahan ini, minimal satu. Setiap unsurnya wajib menunjuk satuan yang berstatus aktif.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_supplies_id' => 12,
            'supplies_name' => 'Aki Zuur',
            'supplies_desc' => 'Cairan pengisi aki',
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
                'supplies_name' => 'Aki Zuur',
                'supplies_desc' => 'Cairan pengisi aki',
                'supplies_default_unit' => 1,
                'supplies_unit' => [1, 7],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'ref_supplies_id/supplies_name kosong, atau supplies_default_unit/salah satu unsur supplies_unit tidak menunjuk satuan yang aktif.'],
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'ref_supplies_id sudah dipakai bahan lain (baik yang masih aktif maupun yang sudah dihapus lewat DELETE bahan) — pakai PUT untuk memperbarui bahan yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'supplies_name, supplies_default_unit, dan supplies_unit wajib diisi; supplies_desc opsional.',
            'id pada respons adalah id bahan yang dibuat Pegasus sendiri (auto-increment) — SIMPAN nilai ini kalau nanti perlu memanggil PATCH /bahan/connect (yang butir connections-nya memakai id Pegasus, bukan ref_supplies_id).',
            'Bukan upsert: mengirim ref_supplies_id yang sudah dipakai (aktif maupun yang bahannya sudah dihapus) selalu ditolak dengan DUPLICATE_REF_ID, tidak pernah menimpa data yang sudah ada. Pakai PUT /bahan/{ref_supplies_id} untuk memperbarui bahan yang rujukannya sudah ada.',
            'Untuk menghubungkan ref_supplies_id ke bahan Pegasus yang SUDAH ADA (dibuat lewat halaman admin, misalnya), pakai PATCH /bahan/connect — bukan POST ini, yang selalu membuat bahan baru.',
            'supplies_default_unit dan setiap unsur supplies_unit DIVALIDASI benar-benar menunjuk satuan yang aktif — beda dengan form bahan lewat halaman admin yang tidak memeriksa ini. Kirim id yang salah akan ditolak VALIDATION_FAILED, bukan tersimpan dengan rujukan yang menggantung.',
            'Bahan yang dibuat lewat endpoint ini langsung bisa dipakai sebagai item.ref_id (type=1) pada POST /shipments/returns — lihat dokumentasi grup Pengiriman.',
        ];
    }
}
