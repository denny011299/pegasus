<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;
use App\ExternalApi\Support\ShipmentStatusMap;

/**
 * Dokumentasi PATCH /api/external/v1/shipments/{ref_shipment_id}/change-status.
 */
class ShipmentChangeStatusDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'shipment-change-status';
    }

    public function title(): string
    {
        return 'Ubah Status Shipment';
    }

    public function method(): string
    {
        return 'PATCH';
    }

    public function path(): string
    {
        return '/shipments/{ref_shipment_id}/change-status';
    }

    public function group(): string
    {
        return 'pengiriman';
    }

    public function description(): string
    {
        return 'FORCE mengubah status shipment ke salah satu dari 4 label yang disepakati kontrak. '
            .'BELUM ada aturan transisi (status apa pun bisa dipaksa ke label apa pun yang sah) '
            .'maupun efek samping per transisi (mis. memaksa ke "Berjalan" TIDAK memotong stok) — '
            .'lihat catatan.';
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
            ['name' => 'status', 'type' => 'string', 'required' => true,
                'description' => 'Label status baru — SALAH SATU dari: '
                    .implode(', ', array_map(static fn ($l) => '"'.$l.'"', ShipmentStatusMap::validLabels()))
                    .'. Bukan angka, bukan ipm_status — persis salah satu label itu.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'status' => 'Berjalan',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_shipment_id' => 'SHP-7788',
                'status' => 'Berjalan',
                'message' => 'Status Pengiriman dengan referensi SHP-7788 menjadi Berjalan',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'SHIPMENT_NOT_FOUND', 'http_status' => 404,
                'message' => 'Pengiriman dengan referensi SHP-7788 tidak ditemukan.'],
            ['code' => 'INVALID_STATUS', 'http_status' => 422,
                'message' => 'Field status tidak valid. Hanya menerima [Dijadwalkan, Berjalan, Belum terkirim, Sudah terkirim]'],
        ];
    }

    public function notes(): array
    {
        return [
            'status pada body adalah LABEL (String, sama seperti ipm_status_label pada respons endpoint Shipment lain), BUKAN angka ipm_status maupun sales_orders.status internal.',
            'BELUM ada aturan transisi — status shipment saat ini APA PUN bisa dipaksa ke label APA PUN yang sah, termasuk melompat atau mundur. PERLU DITINJAU ULANG pemilik produk (dicatat di KNOWN_ISSUES.md), belum dikonfirmasi untuk rilis ini.',
            'HANYA menulis kolom status — TIDAK menjalankan efek samping apa pun yang menyertai perubahan status di endpoint lain. Contoh: memaksa ke "Berjalan" TIDAK memotong stok lewat App\\Support\\SalesOrderApproval::confirm() seperti POST /shipments/shipped — kalau dipakai untuk shipment yang stoknya belum pernah dipotong, ipm_status akan tampak "Berjalan" padahal stok gudang belum tersentuh.',
            '"Belum terkirim" dan "Sudah terkirim" hanya bisa dihasilkan lewat endpoint ini — POST /shipments/scheduled dan /shipments/shipped hanya menghasilkan "Dijadwalkan"/"Berjalan".',
            'ref_shipment_id yang sama sekali belum pernah dipakai dijawab SHIPMENT_NOT_FOUND, sama seperti GET /shipments/{ref_shipment_id} — existensi baris dan status "aktif" adalah dua hal berbeda.',
        ];
    }
}
