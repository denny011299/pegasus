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
            .'shipment: membuat satu baris Pengiriman (sales_orders) ber-ipm_status "Dijadwalkan" '
            .'(1). Shipment TETAP dijadwalkan meski ada item yang kurang stok — pemotongan stok '
            .'sungguhan baru terjadi di POST /shipments/shipped. Bila diminta, kekurangan dicatat '
            .'sebagai dokumen terpisah untuk staf gudang/pembelian.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda unik milik sistem pemanggil. UNIK di seluruh shipment — mengirim ulang nilai yang sama ditolak DUPLICATE_REF_ID, bukan idempotent replay.'],
            ['name' => 'scheduled_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal shipment dijadwalkan, format YYYY-MM-DD. Disimpan sebagai sales_orders.so_date.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'customers.customer_code — id universal Armada (lihat modul Data Armada). Harus armada aktif.'],
            ['name' => 'gudang_id', 'type' => 'integer', 'required' => false,
                'description' => 'Merujuk warehouses.id (sama seperti pada POST /stock/check dan PUT/DELETE /master/warehouses). Tidak dikirim -> gudang utama. Untuk item satuan ECERAN, dipakai apa adanya untuk cek stok maupun sales_order_details.warehouse_id. Untuk item satuan LAIN (bulk), hanya berlaku bila gudang_id yang dikirim memang bertipe gudang utama -- kalau bukan, cek stok maupun warehouse_id tetap jatuh ke gudang utama (lihat catatan).'],
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
            'gudang_id' => 1,
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
                'ipm_status' => 1,
                'ipm_status_label' => 'Dijadwalkan',
                'shortage_doc_created' => true,
                'shortage_doc_number' => 'BG-0101',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items harus berisi minimal satu baris.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.sku tidak ditemukan sebagai varian produk aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.unit_id tidak merujuk satuan aktif manapun.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'armada_code tidak ditemukan atau tidak aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'gudang_id tidak ditemukan atau tidak aktif.'],
            ['code' => 'DUPLICATE_REF_ID', 'http_status' => 422,
                'message' => 'ref_shipment_id SHP-7788 sudah dipakai shipment lain.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Shipment SELALU dijadwalkan (SO dibuat), baik ada shortage atau tidak — shortage TIDAK menolak permintaan. auto_create_shortage_doc hanya mengatur apakah kekurangan itu dicatat sebagai dokumen terpisah.',
            'ref_shipment_id unik permanen — beda dengan POST /payments/cash (dan POST /shipments/shipped) yang idempoten. Mengirim ulang ref_shipment_id yang sama (retry jaringan dsb.) ditolak DUPLICATE_REF_ID; pemanggil wajib memakai ref_shipment_id baru per percobaan.',
            'ipm_status/ipm_status_label dipetakan secara terpisah, bukan status baku yang dipakai alur Pengiriman manual apa adanya. Shipment hasil endpoint ini dianggap "belum dikonfirmasi", sama seperti shipment yang baru dibuat manual lewat halaman admin dan belum di-ACC, sehingga dipetakan ke ipm_status 1 ("Dijadwalkan").',
            'gudang_id opsional, sama seperti POST /stock/check — merujuk warehouses.id langsung (bukan kolom rujukan eksternal seperti ref_unit_id/ref_product_id), tidak dikirim berarti gudang utama (warehouse_types.is_main_warehouse = 1). Satu gudang berlaku untuk cek stok DAN sales_order_details.warehouse_id seluruh item permintaan ini, tidak per-item seperti POST /shipments/returns.',
            'gudang_id hanya benar-benar memindahkan gudang untuk item satuan ECERAN (product_variants.retail_unit) produk itu. Untuk item satuan lain (bulk/DOS/dsb.), stok Pegasus hanya pernah dikelola di SATU gudang utama — mengirim gudang_id yang bukan gudang utama untuk item bulk tidak menghasilkan galat, tapi cek stok dan penyimpanan warehouse_id-nya tetap jatuh ke gudang utama, bukan gudang yang diminta.',
            'Endpoint ini murni penjadwalan logistik, tidak membawa informasi harga — harga/subtotal item maupun total shipment seluruhnya tersimpan 0.',
            'Satu baris detail dibuat per item yang dikirim di items[], disimpan dengan cara yang sama persis dengan yang dipakai halaman admin Pengiriman.',
            'Dokumen kekurangan stok murni catatan untuk staf gudang/pembelian saat ini — belum ada endpoint atau halaman admin untuk membacanya balik.',
        ];
    }
}
