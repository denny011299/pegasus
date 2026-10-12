<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/payments/cash/{ref_payment_id} (API-005).
 */
class CashPaymentShowDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'pembayaran-kas-detail';
    }

    public function title(): string
    {
        return 'Detail Pembayaran Kas';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/payments/cash/{ref_payment_id}';
    }

    public function group(): string
    {
        return 'pembayaran';
    }

    public function description(): string
    {
        return 'Mengambil seluruh pembayaran kas dengan referensi ini beserta rincian dan bukti '
            .'fotonya. data selalu berbentuk array: satu ref_payment_id bisa mencocokkan lebih '
            .'dari satu pembayaran, karena idempotensi kini per pasangan ref_payment_id dan '
            .'penerima (armada_code/staff_id), bukan per ref_payment_id sendirian.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_payment_id', 'type' => 'string', 'required' => true,
                'description' => 'Referensi yang dikirim saat pembayaran dibuat.'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'armada_code', 'type' => 'string', 'required' => false,
                'description' => 'Saring ke satu pembayaran armada dengan code ini. Tidak bisa dipakai bersamaan dengan staff_id.'],
            ['name' => 'staff_id', 'type' => 'string', 'required' => false,
                'description' => 'Saring ke satu pembayaran sales dengan referensi ini. Tidak bisa dipakai bersamaan dengan armada_code.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'ref_payment_id' => 'PMO-GROUP-000123',
                    'payment_id' => 512,
                    'payment_type' => 2,
                    'payment_date' => '2026-07-29',
                    'payment_amount' => 150000,
                    'notes' => 'Setoran sales Budi',
                    'armada_code' => null,
                    'staff_id' => 'SLS-0007',
                    'status' => 'accepted',
                    'items' => [
                        ['amount' => 100000, 'notes' => 'Pelunasan nota A', 'type' => 1, 'kind' => 'cash', 'ref_nota_id' => 'NOTA-2026-000456'],
                        ['amount' => 50000, 'notes' => 'Pelunasan nota B', 'type' => 1, 'kind' => 'cash', 'ref_nota_id' => 'NOTA-2026-000457'],
                    ],
                    'photos' => [
                        'https://pegasus.example.com/kas_admin/sales/photo_69e6fa26e0bf1.png',
                    ],
                    'created_at' => '2026-07-29T03:15:00+07:00',
                ],
                [
                    'ref_payment_id' => 'PMO-GROUP-000123',
                    'payment_id' => 513,
                    'payment_type' => 2,
                    'payment_date' => '2026-07-29',
                    'payment_amount' => 75000,
                    'notes' => 'Setoran sales Dewi',
                    'armada_code' => null,
                    'staff_id' => 'SLS-0011',
                    'status' => 'accepted',
                    'items' => [
                        ['amount' => 75000, 'notes' => 'Pelunasan nota C', 'type' => 1, 'kind' => 'cash', 'ref_nota_id' => 'NOTA-2026-000458'],
                    ],
                    'photos' => [],
                    'created_at' => '2026-07-29T03:16:00+07:00',
                ],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'NOT_FOUND', 'http_status' => 404,
                'message' => 'Pembayaran dengan ref_payment_id tersebut tidak ditemukan.'],
        ];
    }

    public function notes(): array
    {
        return [
            'data selalu array, sekalipun hanya satu pembayaran yang cocok — bentuknya tidak berubah tergantung jumlah hasil.',
            'Tanpa filter, seluruh pembayaran armada MAUPUN sales dengan ref_payment_id ini dikembalikan sekaligus. Kirim armada_code atau staff_id untuk menyaring ke satu penerima tertentu (lihat catatan idempotensi pada dokumentasi pembuatan pembayaran).',
            'Pencarian memakai referensi milik sistem pemanggil, bukan id internal Pegasus, sehingga pemanggil tidak perlu menyimpan id Pegasus.',
            'status bernilai: pending (menunggu persetujuan), accepted (disetujui), declined (ditolak), atau deleted (dihapus di halaman admin).',
            'Kolom internal seperti pembuat, penyetuju, dan saldo berjalan tidak ikut dikembalikan.',
        ];
    }
}
