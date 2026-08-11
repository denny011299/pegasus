<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/shipments/{ref_shipment_id}/cancel.
 */
class ShipmentCancelDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'shipment-cancel';
    }

    public function title(): string
    {
        return 'Batalkan Shipment';
    }

    public function method(): string
    {
        return 'PUT';
    }

    public function path(): string
    {
        return '/shipments/{ref_shipment_id}/cancel';
    }

    public function group(): string
    {
        return 'pengiriman';
    }

    public function description(): string
    {
        return 'Membatalkan (pembatalan) satu shipment. Kalau shipment ini sebelumnya sudah '
            .'"Berjalan" (stok sudah dipotong), stoknya DIKEMBALIKAN dulu sebelum status '
            .'diubah menjadi "Dibatalkan" — proses pembatalan sungguhan, bukan sekadar menulis '
            .'status seperti PATCH .../change-status. Idempoten: shipment yang sudah dibatalkan '
            .'dijawab sukses apa adanya, tidak mengembalikan stok lagi.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Referensi yang dikirim pemanggil saat membuat shipment ini.'],
        ];
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'reason', 'type' => 'string', 'required' => false,
                'description' => 'Alasan pembatalan, disimpan sebagai sales_orders.cancel_reason.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'reason' => 'Dibatalkan di PMO karena armada rusak',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_shipment_id' => 'SHP-7788',
                'shipment_internal_id' => 4521,
                'ipm_status' => -1,
                'ipm_status_label' => 'Dibatalkan',
                'message' => 'Dokumen berhasil dibatalkan dan stok telah dikembalikan.',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'SHIPMENT_NOT_FOUND', 'http_status' => 404,
                'message' => 'Pengiriman dengan referensi SHP-7788 tidak ditemukan.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Kalau shipment sebelumnya sudah ipm_status "Berjalan" (stok sudah dipotong — baik lewat POST /shipments/shipped maupun konfirmasi manual di halaman admin), stok DIKEMBALIKAN dulu sebelum status berubah — message-nya menyebutkan itu secara eksplisit ("...dan stok telah dikembalikan."). Kalau belum pernah "Berjalan" (mis. masih "Dijadwalkan"/"Belum terkirim"/"Sudah terkirim"), tidak ada stok yang perlu dikembalikan — message-nya cuma "Dokumen berhasil dibatalkan." tanpa menyebut stok.',
            'Idempoten: shipment yang statusnya SUDAH "Dibatalkan" dijawab sukses apa adanya (meta.already_cancelled=true), TIDAK mengembalikan stok lagi — mencegah stok gudang tergelembung kalau permintaan yang sama dikirim ulang.',
            'ipm_status -1 "Dibatalkan" HANYA bisa dihasilkan lewat endpoint ini — PATCH .../change-status sengaja tidak menerima label ini (4 label lain saja: Dijadwalkan/Berjalan/Belum terkirim/Sudah terkirim).',
            'ref_shipment_id yang sama sekali belum pernah dipakai dijawab SHIPMENT_NOT_FOUND, sama seperti endpoint Shipment lain — existensi baris dan status "aktif" adalah dua hal berbeda.',
            'reason bersifat opsional — kalau tidak dikirim, sales_orders.cancel_reason disimpan null.',
        ];
    }
}
