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
        return 'Mengambil satu pembayaran kas beserta rincian dan bukti fotonya, '
            .'dicari memakai referensi milik sistem pemanggil.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_payment_id', 'type' => 'string', 'required' => true,
                'description' => 'Referensi yang dikirim saat pembayaran dibuat.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_payment_id' => 'PMO-2026-000123',
                'payment_id' => 512,
                'payment_type' => 1,
                'payment_date' => '2026-07-29',
                'payment_amount' => 150000,
                'notes' => 'Pengeluaran armada W 9518 PG (Agus)',
                'armada_id' => 4,
                'staff_id' => null,
                'status' => 'accepted',
                'items' => [
                    ['amount' => 100000, 'notes' => 'BBM', 'type' => 2],
                    ['amount' => 50000, 'notes' => 'Uang makan', 'type' => 2],
                ],
                'photos' => [
                    'https://pegasus.example.com/kas_admin/armada/photo_69e6fa26e0bf1.png',
                ],
                'created_at' => '2026-07-29T03:15:00+07:00',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'not_found', 'http_status' => 404,
                'message' => 'Pembayaran dengan ref_payment_id tersebut tidak ditemukan.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Pencarian memakai referensi milik sistem pemanggil, bukan id internal Pegasus, sehingga pemanggil tidak perlu menyimpan id Pegasus.',
            'status bernilai: pending (menunggu persetujuan), accepted (disetujui), declined (ditolak), atau deleted (dihapus di halaman admin).',
            'Kedua jenis pembayaran dicari sekaligus, jadi payment_type tidak perlu disertakan.',
            'Kolom internal seperti pembuat, penyetuju, dan saldo berjalan tidak ikut dikembalikan.',
        ];
    }
}
