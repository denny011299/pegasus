<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/shipments/{ref_shipment_id}.
 */
class ShipmentShowDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'shipment-show';
    }

    public function title(): string
    {
        return 'Detail Shipment';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/shipments/{ref_shipment_id}';
    }

    public function group(): string
    {
        return 'pengiriman';
    }

    public function description(): string
    {
        return 'Mengambil detail satu shipment berdasarkan ref_shipment_id — baik yang baru '
            .'dijadwalkan lewat POST /shipments/scheduled maupun yang sudah dikonfirmasi lewat '
            .'POST /shipments/shipped.';
    }

    public function pathParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Referensi yang dikirim pemanggil saat membuat shipment ini.'],
        ];
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'show_unit', 'type' => 'boolean', 'required' => false,
                'description' => 'true = tambahkan field "unit": {id, unit_name, unit_short_name} hasil resolusi items[].unit_id di tiap item. Satu objek per item, bukan daftar — satu item cuma punya satu satuan.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'shipment_internal_id' => 4521,
                'ref_shipment_id' => '32134',
                'ipm_status' => 2,
                'ipm_status_label' => 'Berjalan',
                'shipment_date' => '2026-07-23',
                'armada_code' => 'L8533N',
                'notes' => 'Pengiriman PMO SHP-7788',
                'photos' => ['https://pegasus.test/issue/photo_64f1a2b3c4d5e.jpg'],
                'created_at' => '2026-07-23T09:15:00+07:00',
                'items' => [
                    [
                        'variant_sku' => 'AAHK400ML',
                        'qty' => 24,
                        'unit_id' => 2,
                        'product_name' => 'AIR AKI HIKARI',
                        'variant_name' => '20 x 400ml',
                        'unit' => ['id' => 9, 'unit_name' => 'Piece', 'unit_short_name' => 'pcs'],
                    ],
                ],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'SHIPMENT_NOT_FOUND', 'http_status' => 404,
                'message' => 'Pengiriman dengan referensi 32134 tidak ditemukan.'],
        ];
    }

    public function notes(): array
    {
        return [
            'ref_shipment_id yang sama sekali belum pernah dipakai (baik lewat /shipments/scheduled maupun /shipments/shipped) dijawab SHIPMENT_NOT_FOUND. Baris yang SUDAH ada tetap ditemukan berapa pun ipm_status-nya — existensi baris dan status "aktif" adalah dua hal berbeda di sini.',
            'armada_code, items[].variant_sku, items[].product_name, items[].variant_name dikembalikan apa adanya dari yang tersimpan (bentuk field sama dengan yang diterima POST /shipments/shipped, bukan format /shipments/scheduled).',
            'items[].unit_id adalah units.ref_unit_id (rujukan sistem PMO), diresolusi dari unit_id internal yang tersimpan — konsisten dengan konvensi unit_id di seluruh modul Shipment/Stok.',
            'photos berisi URL publik berkas yang tersimpan di sales_orders.so_img (folder public/issue/), bukan data mentah/base64.',
            'ipm_status/ipm_status_label dipetakan secara terpisah — sama seperti POST /shipments/scheduled dan /shipments/shipped, BUKAN status internal sistem apa adanya.',
        ];
    }
}
