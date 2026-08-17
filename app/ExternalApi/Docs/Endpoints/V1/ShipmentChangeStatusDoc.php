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
        return 'Mengubah status shipment ke salah satu dari 4 label yang disepakati kontrak. '
            .'HANYA SATU transisi yang diizinkan saat ini: "Dijadwalkan" ke "Sudah terkirim" — '
            .'kombinasi lain ditolak. Transisi ini memotong stok sungguhan, proses yang sama '
            .'dengan POST /shipments/shipped — lihat catatan.';
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
                    .'. Bukan angka, bukan ipm_status — persis salah satu label itu. Untuk saat ini hanya "Sudah terkirim" yang bisa berhasil, dan hanya kalau status shipment saat ini "Dijadwalkan".'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'status' => 'Sudah terkirim',
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_shipment_id' => 'SHP-7788',
                'status' => 'Sudah terkirim',
                'message' => 'Status Pengiriman dengan referensi SHP-7788 menjadi Sudah terkirim',
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
            ['code' => 'INVALID_STATUS_TRANSITION', 'http_status' => 409,
                'message' => 'Perubahan status dari "Berjalan" ke "Dijadwalkan" belum diizinkan. Transisi yang didukung saat ini: Dijadwalkan -> Sudah terkirim.'],
            ['code' => 'INSUFFICIENT_STOCK', 'http_status' => 409,
                'message' => 'Stok tidak mencukupi untuk satu atau lebih item.'],
        ];
    }

    public function notes(): array
    {
        return [
            'status pada body adalah LABEL (String, sama seperti ipm_status_label pada respons endpoint Shipment lain), BUKAN angka ipm_status maupun status internal sistem.',
            'HANYA SATU transisi yang diizinkan untuk saat ini: dari "Dijadwalkan" ke "Sudah terkirim". Mengirim label yang valid tapi bukan bagian dari transisi ini (mis. shipment yang statusnya sudah "Sudah terkirim" dikirim ulang "Sudah terkirim", atau shipment "Dijadwalkan" diminta jadi "Berjalan"/"Belum terkirim") ditolak dengan galat INVALID_STATUS_TRANSITION — beda dengan INVALID_STATUS yang berarti labelnya sendiri tidak dikenali sama sekali.',
            'Transisi "Dijadwalkan" ke "Sudah terkirim" MEMOTONG STOK sungguhan, proses yang sama dengan konfirmasi di POST /shipments/shipped. Kalau stok tidak mencukupi, permintaan gagal INSUFFICIENT_STOCK dan status shipment TETAP "Dijadwalkan".',
            'ref_shipment_id yang sama sekali belum pernah dipakai dijawab SHIPMENT_NOT_FOUND, sama seperti GET /shipments/{ref_shipment_id} — existensi baris dan status "aktif" adalah dua hal berbeda.',
        ];
    }
}
