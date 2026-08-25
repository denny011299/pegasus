<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/armada (API-002 lanjutan).
 */
class MasterArmadaCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'armada-create';
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
        return '/armada';
    }

    public function group(): string
    {
        return 'armada';
    }

    public function description(): string
    {
        return 'Membuat armada baru di Pegasus, dengan id universal (code) ditentukan sendiri oleh pemanggil. Selalu membuat baris baru — bukan upsert; code yang sudah dipakai ditolak.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'code', 'type' => 'string', 'required' => true, 'description' => 'id universal untuk armada ini, ditentukan sendiri oleh pemanggil (maks. 10 karakter). Wajib belum pernah dipakai pelanggan/armada lain di Pegasus.'],
            ['name' => 'pic', 'type' => 'string', 'required' => false, 'description' => 'Nama penanggung jawab/pemilik armada (maks. 255 karakter). Boleh dikosongkan.'],
            ['name' => 'pic_phone', 'type' => 'string', 'required' => false, 'description' => 'Nomor telepon penanggung jawab (maks. 50 karakter). Boleh dikosongkan.'],
            ['name' => 'nomor_polisi', 'type' => 'string', 'required' => false, 'description' => 'Nomor polisi kendaraan, mis. "W 9518 PG". Boleh dikosongkan.'],
            ['name' => 'category', 'type' => 'string', 'required' => false, 'description' => 'Kategori/jenis armada, mis. "Truk Engkel" (maks. 100 karakter). Teks bebas, tidak divalidasi terhadap daftar tertentu. Boleh dikosongkan.'],
            ['name' => 'merk_model', 'type' => 'string', 'required' => false, 'description' => 'Merk dan model kendaraan, mis. "Mitsubishi Colt Diesel" (maks. 255 karakter). Boleh dikosongkan.'],
            ['name' => 'tahun_kendaraan', 'type' => 'string', 'required' => false, 'description' => 'Tahun kendaraan sebagai teks, mis. "2019" (maks. 20 karakter). Boleh dikosongkan.'],
            ['name' => 'lokasi', 'type' => 'string', 'required' => false, 'description' => 'Lokasi/pool armada, mis. "Pool Surabaya" (maks. 255 karakter). Teks bebas, tidak dihubungkan ke data gudang. Boleh dikosongkan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'code' => 'ARM-JKT-001',
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
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422, 'message' => 'code kosong/lebih dari 10 karakter, atau salah satu field lain tidak valid.'],
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422, 'message' => 'code sudah dipakai pelanggan/armada lain (baik yang masih aktif maupun yang sudah dihapus) — pakai PUT untuk memperbarui armada yang sudah ada.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Hanya code yang wajib diisi; seluruh field lain boleh dikosongkan.',
            'id pada respons adalah id pelanggan yang dibuat Pegasus sendiri (customers.customer_id, auto-increment) — hanya untuk referensi, tidak dibutuhkan endpoint lain pada modul ini (semuanya memakai code).',
            'Bukan upsert: mengirim code yang sudah dipakai (aktif maupun yang armadanya sudah dihapus) selalu ditolak dengan DUPLICATE_REF_ID, tidak pernah menimpa data yang sudah ada. Pakai PUT /armada/{code} untuk memperbarui armada yang kodenya sudah ada.',
            'Tidak ada endpoint "connect" pada modul ini (berbeda dengan sales/satuan): setiap pelanggan di Pegasus SELALU sudah punya customer_code (di-generate otomatis saat dibuat lewat halaman admin), jadi tidak pernah ada baris "belum tersambung" yang perlu dihubungkan belakangan.',
            'category, merk_model, tahun_kendaraan, dan lokasi disimpan apa adanya sebagai teks bebas — tidak ada daftar nilai yang sah dan tidak ada hubungan ke data master lain.',
            'customer_name, customer_address, customer_email, sales_id, dan wilayah (area/city/district) ada di tabel pelanggan tapi tidak dikelola endpoint ini — mengikuti cakupan yang sama dengan form pelanggan di halaman admin.',
        ];
    }
}
