<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/shipments/scheduled.
 */
class ShipmentScheduledDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'shipment-scheduled';
    }

    public function title(): string
    {
        return 'Jadwalkan Shipment';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/shipments/scheduled';
    }

    public function group(): string
    {
        return 'pengiriman';
    }

    public function description(): string
    {
        return 'Mengecek stok (logika yang sama dengan POST /stock/check) lalu menjadwalkan '
            .'shipment: membuat satu baris Pengiriman (sales_orders) berstatus "scheduled". '
            .'Shipment TETAP dijadwalkan meski ada item yang kurang stok — pemotongan stok '
            .'sungguhan baru terjadi di POST /shipments/shipped (belum tersedia). Bila diminta, '
            .'kekurangan dicatat sebagai dokumen terpisah untuk staf gudang/pembelian.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda unik milik sistem pemanggil. UNIK di seluruh shipment — mengirim ulang nilai yang sama ditolak duplicate_ref_id, bukan idempotent replay.'],
            ['name' => 'scheduled_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal shipment dijadwalkan, format YYYY-MM-DD. Disimpan sebagai sales_orders.so_date.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'customers.customer_code — id universal Armada (lihat modul Data Armada). Harus armada aktif.'],
            ['name' => 'auto_create_shortage_doc', 'type' => 'boolean', 'required' => false,
                'description' => 'true = buat dokumen kekurangan stok otomatis BILA ada item yang shortage-nya > 0. Tidak dikirim/false = tidak pernah membuat dokumen, shipment tetap dijadwalkan seperti biasa.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Daftar item yang akan dikirim, minimal satu. Bentuk identik dengan POST /stock/check.'],
            ['name' => 'items[].sku', 'type' => 'string', 'required' => true,
                'description' => 'product_variants.product_variant_sku. Harus merujuk varian produk aktif.'],
            ['name' => 'items[].qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah yang akan dikirim, dalam satuan items[].unit_id.'],
            ['name' => 'items[].unit_id', 'type' => 'integer', 'required' => true,
                'description' => 'Rujukan units.ref_unit_id (id satuan pada sistem PMO), BUKAN id internal Pegasus. Harus merujuk satuan aktif.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_shipment_id' => 'SHP-7788',
            'scheduled_date' => '2026-07-25',
            'armada_code' => 'L8533N',
            'auto_create_shortage_doc' => true,
            'items' => [
                ['sku' => 'AAHK400ML', 'qty' => 24, 'unit_id' => 5],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'shipment_internal_id' => 505,
                'ref_shipment_id' => 'SHP-7788',
                'status' => 'scheduled',
                'shortage_doc_created' => true,
                'shortage_doc_number' => 'BG-0101',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'validation_failed', 'http_status' => 422,
                'message' => 'items harus berisi minimal satu baris.'],
            ['code' => 'validation_failed', 'http_status' => 422,
                'message' => 'items.0.sku tidak ditemukan sebagai varian produk aktif.'],
            ['code' => 'validation_failed', 'http_status' => 422,
                'message' => 'items.0.unit_id tidak merujuk satuan aktif manapun.'],
            ['code' => 'validation_failed', 'http_status' => 422,
                'message' => 'armada_code tidak ditemukan atau tidak aktif.'],
            ['code' => 'duplicate_ref_id', 'http_status' => 422,
                'message' => 'ref_shipment_id SHP-7788 sudah dipakai shipment lain.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Shipment SELALU dijadwalkan (SO dibuat), baik ada shortage atau tidak — shortage TIDAK menolak permintaan. auto_create_shortage_doc hanya mengatur apakah kekurangan itu dicatat sebagai dokumen terpisah.',
            'ref_shipment_id unik permanen — beda dengan POST /payments/cash yang idempoten. Mengirim ulang ref_shipment_id yang sama (retry jaringan dsb.) ditolak duplicate_ref_id; pemanggil wajib memakai ref_shipment_id baru per percobaan.',
            'sales_orders.status yang dipakai di sini (4 = "Dijadwalkan") BARU, terpisah dari status 1/2/3 yang dipakai alur Pengiriman manual lewat halaman admin — SO hasil endpoint ini bisa dibedakan asalnya dari status itu sendiri.',
            'Stok yang dicek adalah gudang utama (tidak ada parameter gudang pada endpoint ini) — sama seperti default POST /stock/check ketika gudang_id tidak dikirim.',
            'Endpoint ini murni penjadwalan logistik, tidak membawa informasi harga — so_total/sod_harga/sod_subtotal seluruhnya tersimpan 0.',
            'sales_order_details dibuat satu baris per items[], memakai ulang App\\Models\\SalesOrderDetail::insertSalesOrderDetail() — sama persis dengan yang dipakai halaman admin Pengiriman.',
            'Dokumen kekurangan stok (App\\Models\\ShipmentShortageDocument) murni catatan untuk staf gudang/pembelian saat ini — belum ada endpoint atau halaman admin untuk membacanya balik.',
        ];
    }
}
