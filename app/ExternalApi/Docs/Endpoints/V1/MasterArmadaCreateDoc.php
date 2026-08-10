<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/master/armada (API-002 lanjutan).
 */
class MasterArmadaCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-armada-create';
    }

    public function title(): string
    {
        return 'Buat Armada';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/master/armada';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Membuat armada baru di Pegasus, dengan id universal (customer_code) ditentukan sendiri oleh pemanggil. Selalu membuat baris baru — bukan upsert; customer_code yang sudah dipakai ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'customer_code', 'type' => 'string', 'required' => true, 'description' => 'id universal untuk armada ini, ditentukan sendiri oleh pemanggil (maks. 10 karakter). Wajib belum pernah dipakai pelanggan/armada lain di Pegasus.'],
            ['name' => 'customer_pic', 'type' => 'string', 'required' => false, 'description' => 'Nama penanggung jawab/pemilik armada. Boleh dikosongkan.'],
            ['name' => 'customer_pic_phone', 'type' => 'string', 'required' => false, 'description' => 'Nomor telepon penanggung jawab. Boleh dikosongkan.'],
            ['name' => 'customer_notes', 'type' => 'string', 'required' => false, 'description' => 'Catatan armada — konvensi yang dipakai Pegasus adalah "nomor polisi (nama)", mis. "W 9518 PG (Agus)". Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'customer_code' => 'ARM-JKT-001',
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
            ['code' => 'validation_failed', 'http_status' => 422, 'message' => 'customer_code kosong/lebih dari 10 karakter, atau salah satu field lain tidak valid.'],
            ['code' => 'duplicate_ref_id', 'http_status' => 422, 'message' => 'customer_code sudah dipakai pelanggan/armada lain (baik yang masih aktif maupun yang sudah dihapus) — pakai PUT untuk memperbarui armada yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Hanya customer_code yang wajib diisi; customer_pic, customer_pic_phone, dan customer_notes boleh dikosongkan.',
            'id pada respons adalah id pelanggan yang dibuat Pegasus sendiri (customers.customer_id, auto-increment) — hanya untuk referensi, tidak dibutuhkan endpoint lain pada modul ini (semuanya memakai customer_code).',
            'Bukan upsert: mengirim customer_code yang sudah dipakai (aktif maupun yang armadanya sudah dihapus) selalu ditolak dengan duplicate_ref_id, tidak pernah menimpa data yang sudah ada. Pakai PUT /master/armada/{customer_code} untuk memperbarui armada yang kodenya sudah ada.',
            'Tidak ada endpoint "connect" pada modul ini (berbeda dengan sales/satuan): setiap pelanggan di Pegasus SELALU sudah punya customer_code (di-generate otomatis saat dibuat lewat halaman admin), jadi tidak pernah ada baris "belum tersambung" yang perlu dihubungkan belakangan.',
            'customer_name, customer_address, customer_email, sales_id, dan wilayah (area/city/district) ada di tabel pelanggan tapi tidak dikelola endpoint ini — mengikuti cakupan yang sama dengan form pelanggan di halaman admin.',
        ];
    }
}
