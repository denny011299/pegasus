<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/armada/{code} (API-002 lanjutan).
 */
class MasterArmadaUpdateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'armada-update';
    }

    public function title(): string
    {
        return 'Ubah/Buat Armada (Upsert)';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/armada/{code}';
    }

    public function group(): string
    {
        return 'armada';
    }

    public function description(): string
    {
        return 'Upsert: mengubah data armada yang sudah ada, dicari lewat code, atau membuat armada baru dengan code itu kalau belum pernah ada (sama seperti POST). Bersifat penggantian penuh — field yang tidak dikirim disimpan kosong, bukan mempertahankan nilai lama. Armada yang sudah ada tapi nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'code', 'type' => 'string', 'required' => true, 'description' => 'id universal armada yang akan diubah, lihat GET /armada.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'pic', 'type' => 'string', 'required' => false, 'description' => 'Nama penanggung jawab/pemilik armada (maks. 255 karakter). Boleh dikosongkan.'],
            ['name' => 'pic_phone', 'type' => 'string', 'required' => false, 'description' => 'Nomor telepon penanggung jawab (maks. 50 karakter). Boleh dikosongkan.'],
            ['name' => 'nomor_polisi', 'type' => 'string', 'required' => false, 'description' => 'Nomor polisi kendaraan, mis. "W 9518 PG". Boleh dikosongkan.'],
            ['name' => 'category', 'type' => 'string', 'required' => false, 'description' => 'Kategori/jenis armada, mis. "Truk Engkel" (maks. 100 karakter). Teks bebas. Boleh dikosongkan.'],
            ['name' => 'merk_model', 'type' => 'string', 'required' => false, 'description' => 'Merk dan model kendaraan, mis. "Mitsubishi Colt Diesel" (maks. 255 karakter). Boleh dikosongkan.'],
            ['name' => 'tahun_kendaraan', 'type' => 'string', 'required' => false, 'description' => 'Tahun kendaraan sebagai teks, mis. "2019" (maks. 20 karakter). Boleh dikosongkan.'],
            ['name' => 'lokasi', 'type' => 'string', 'required' => false, 'description' => 'Lokasi/pool armada, mis. "Pool Surabaya" (maks. 255 karakter). Teks bebas. Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'pic' => 'Agus',
            'pic_phone' => '08123456789',
            'nomor_polisi' => 'W 9518 PG',
            'category' => 'Truk Engkel',
            'merk_model' => 'Mitsubishi Colt Diesel',
            'tahun_kendaraan' => '2019',
            'lokasi' => 'Pool Surabaya',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => 12,
                'code' => 'ARM-JKT-001',
                'pic' => 'Agus',
                'pic_phone' => '08123456789',
                'nomor_polisi' => 'W 9518 PG',
                'category' => 'Truk Engkel',
                'merk_model' => 'Mitsubishi Colt Diesel',
                'tahun_kendaraan' => '2019',
                'lokasi' => 'Pool Surabaya',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'Salah satu field tidak valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Seluruh field body bersifat opsional (nullable) — tapi karena bersifat penggantian penuh, field yang tidak ikut dikirim akan DISIMPAN KOSONG, bukan mempertahankan nilai yang sudah ada. Kirim ulang nilai lama untuk field yang tidak ingin diubah.',
            'code tidak bisa diubah lewat endpoint ini — pakai DELETE + POST kalau memang perlu mengganti id universal armada ini.',
            'Upsert: code yang belum pernah ada membuat armada baru (respons 201). code yang sudah ada dan sedang nonaktif/sudah dihapus diaktifkan kembali sekaligus diperbarui, bukan dijawab NOT_FOUND.',
        ];
    }
}
