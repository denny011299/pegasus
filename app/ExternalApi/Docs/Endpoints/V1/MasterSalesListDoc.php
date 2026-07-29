<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/sales (API-002).
 */
class MasterSalesListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-sales';
    }

    public function title(): string
    {
        return 'Daftar Sales';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/sales';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil daftar staf sales yang berstatus aktif. Dipakai sistem '
            .'eksternal antara lain untuk mengisi staff_id pada endpoint Pembayaran Kas.';
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'staff_id' => 20,
                    'nama' => 'Bisma',
                    'kode' => null,
                    'email' => 'bisma@contoh.com',
                    'telepon' => '08123456789',
                    'alamat' => 'Jl. Rungkut Industri No. 12, Surabaya',
                    'role' => 'Sales',
                ],
                [
                    'staff_id' => 26,
                    'nama' => 'Sales Counter',
                    'kode' => null,
                    'email' => null,
                    'telepon' => null,
                    'alamat' => null,
                    'role' => 'Sales',
                ],
            ],
            'meta' => [
                'total' => 7,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini tidak menerima parameter apa pun.',
            'Sales bukan tabel tersendiri: yang dikembalikan adalah staf yang nama perannya mengandung kata "sales". Penyaringan memakai nama peran, bukan id peran, sehingga peran baru yang mengandung kata itu otomatis ikut terbawa.',
            'Hanya staf berstatus aktif yang muncul.',
            'staff_id di sini adalah nilai yang diminta endpoint Pembayaran Kas pada field staff_id ketika payment_type bernilai 2.',
            'role berisi nama peran staf yang bersangkutan apa adanya seperti tersimpan di Pegasus, misalnya "Sales".',
            'kode, email, telepon, dan alamat boleh bernilai null bila datanya memang belum diisi di Pegasus.',
            'Kata sandi, nama pengguna, saldo staf, dan hak akses tidak pernah dikembalikan.',
            'Urutan daftar bersifat tetap.',
        ];
    }
}
