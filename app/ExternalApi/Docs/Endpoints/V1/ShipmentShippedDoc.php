<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/shipments/shipped.
 */
class ShipmentShippedDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'shipment-shipped';
    }

    public function title(): string
    {
        return 'Konfirmasi Shipment Terkirim';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/shipments/shipped';
    }

    public function group(): string
    {
        return 'pengiriman';
    }

    public function description(): string
    {
        return 'Membuat (bila ref_shipment_id belum ada) atau memperbarui shipment yang sudah '
            .'dijadwalkan, lalu langsung mengonfirmasinya: memotong stok sungguhan dan '
            .'mengubah ipm_status menjadi "Berjalan" (2). Idempoten lewat ref_shipment_id — '
            .'mengirim ulang permintaan yang sama tidak memotong stok dua kali.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Kunci pencarian/idempotensi. Sudah ada (dari POST /shipments/scheduled atau panggilan /shipments/shipped sebelumnya) -> baris itu yang dipakai. Belum ada -> dibuat baru.'],
            ['name' => 'shipment_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal pengiriman, format YYYY-MM-DD. Disimpan sebagai sales_orders.so_date.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'customers.customer_code — id universal Armada. Harus armada aktif.'],
            ['name' => 'notes', 'type' => 'string', 'required' => false,
                'description' => 'Catatan bebas, disimpan sebagai sales_orders.notes.'],
            ['name' => 'gudang_id', 'type' => 'integer', 'required' => false,
                'description' => 'Merujuk warehouses.id (sama seperti pada POST /stock/check dan POST /shipments/scheduled). Tidak dikirim -> gudang utama. Untuk item satuan ECERAN, ikut menentukan gudang mana yang benar-benar dipotong. Untuk item satuan LAIN (bulk), TETAP disimpan di sales_order_details.warehouse_id, tapi stok yang benar-benar dipotong selalu gudang utama, tidak peduli gudang_id apa yang dikirim (lihat catatan). Dianggap field substantif oleh detail_handler — lihat catatan.'],
            ['name' => 'detail_handler', 'type' => 'string', 'required' => false,
                'description' => '"force" (bawaan) = timpa data tersimpan dengan permintaan ini bila berbeda. "validate" = tolak dengan galat SHIPMENT_DETAIL_MISMATCH bila berbeda, tidak ada yang berubah. Hanya berlaku saat ref_shipment_id sudah ada DAN belum ipm_status "Berjalan" — lihat catatan.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Daftar item yang dikirim, minimal satu.'],
            ['name' => 'items[].variant_sku', 'type' => 'string', 'required' => true,
                'description' => 'product_variants.product_variant_sku. Harus merujuk varian produk aktif. BEDA nama field dengan POST /stock/check dan /shipments/scheduled (di sana: "sku").'],
            ['name' => 'items[].qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah yang dikirim, dalam satuan items[].unit_id.'],
            ['name' => 'items[].unit_id', 'type' => 'integer', 'required' => true,
                'description' => 'Rujukan units.ref_unit_id (id satuan pada sistem PMO), BUKAN id internal Pegasus. Harus merujuk satuan aktif.'],
            ['name' => 'items[].product_name', 'type' => 'string', 'required' => true,
                'description' => 'Nama produk, dipakai APA ADANYA (tidak di-lookup dari database) untuk sales_order_details.sod_nama.'],
            ['name' => 'items[].variant_name', 'type' => 'string', 'required' => false,
                'description' => 'Nama varian, dipakai apa adanya untuk sales_order_details.sod_variant.'],
            ['name' => 'photos', 'type' => 'array', 'required' => false,
                'description' => 'Bukti pengiriman. Kirim sebagai berkas sungguhan lewat multipart/form-data (photos[] sebagai file upload, field lain tetap sebagai field form biasa — array items[] memakai notasi kurung standar HTML form: items[0][variant_sku], items[0][qty], dst.), ATAU sebagai data URI base64 lewat JSON murni. Hanya PNG/JPEG, maksimal 5MB per berkas.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_shipment_id' => 'SHP-7788',
            'shipment_date' => '2026-07-25',
            'armada_code' => 'L8533N',
            'notes' => 'Pengiriman PMO SHP-7788',
            'gudang_id' => 1,
            'detail_handler' => 'force',
            'items' => [
                [
                    'variant_sku' => 'AAHK400ML',
                    'qty' => 24,
                    'unit_id' => 2,
                    'product_name' => 'AIR AKI HIKARI',
                    'variant_name' => '20 x 400ml',
                ],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_shipment_id' => 'SHP-7788',
                'shipment_internal_id' => 4521,
                'ipm_status' => 2,
                'ipm_status_label' => 'Berjalan',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.variant_sku tidak ditemukan sebagai varian produk aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'armada_code tidak ditemukan atau tidak aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'gudang_id tidak ditemukan atau tidak aktif.'],
            ['code' => 'SHIPMENT_DETAIL_MISMATCH', 'http_status' => 409,
                'message' => 'Data shipment untuk ref_shipment_id ini sudah tersimpan dan berbeda dari permintaan ini (items). Kirim detail_handler: "force" untuk menimpa, atau samakan data permintaan dengan yang sudah tersimpan.'],
            ['code' => 'SHIPMENT_DETAIL_MISMATCH', 'http_status' => 409,
                'message' => 'Data shipment untuk ref_shipment_id ini sudah tersimpan dan berbeda dari permintaan ini (gudang_id). Kirim detail_handler: "force" untuk menimpa, atau samakan data permintaan dengan yang sudah tersimpan.'],
            ['code' => 'INSUFFICIENT_STOCK', 'http_status' => 409,
                'message' => 'Stok tidak mencukupi untuk satu atau lebih item.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Idempoten lewat ref_shipment_id — BEDA dengan POST /shipments/scheduled yang menolak duplikat. Kalau baris dengan ref_shipment_id ini SUDAH ipm_status "Berjalan" (sudah pernah shipped sebelumnya), permintaan ini dijawab apa adanya lewat meta.idempotent_replay=true — isi permintaan TIDAK dibandingkan sama sekali, tidak ada stok yang dipotong dua kali, tidak ada detail yang diubah.',
            'Kalau ref_shipment_id sudah ada TAPI belum "Berjalan" (masih "Dijadwalkan", atau dibuat manual lewat halaman admin dan belum di-ACC): data yang berbeda dari yang tersimpan ditangani lewat detail_handler — "force" (bawaan) menimpa, "validate" menolak dengan SHIPMENT_DETAIL_MISMATCH. Kalau datanya sudah identik, detail_handler tidak berpengaruh — langsung lanjut ke konfirmasi.',
            'ref_shipment_id belum ada sama sekali -> baris baru dibuat (ipm_status awal "Dijadwalkan"), lalu langsung dikonfirmasi dalam permintaan yang sama.',
            'Konfirmasi menjalankan proses yang PERSIS sama dengan ACC yang dipakai halaman admin Pengiriman (potong stok dulu, baru status berubah). Kalau stok tidak cukup, permintaan gagal (INSUFFICIENT_STOCK) dan shipment TETAP ada di ipm_status "Dijadwalkan" — tidak hilang, bisa dicoba lagi dengan ref_shipment_id yang sama setelah stok tersedia.',
            'items[].variant_sku (BUKAN "sku") di-resolve ke produk yang sama seperti /stock/check dan /shipments/scheduled — sekadar nama field bodinya beda di endpoint ini, mengikuti kontrak yang diminta.',
            'items[].product_name/variant_name dipakai APA ADANYA dari permintaan (tidak di-lookup ke tabel products/product_variants) untuk mengisi sales_order_details.sod_nama/sod_variant — sama seperti field yang diterima form admin Pengiriman.',
            'notes disimpan di sales_orders.notes (kolom baru, terpisah dari so_ref_number yang sudah ada — so_ref_number adalah field "Ref Number" bebas yang bisa diedit staf lewat halaman admin, tidak dipakai kontrak Shipment ini).',
            'gudang_id opsional, sama seperti POST /stock/check dan /shipments/scheduled — merujuk warehouses.id langsung, tidak dikirim berarti gudang utama. Satu gudang berlaku untuk sales_order_details.warehouse_id seluruh item permintaan ini (bukan per-item). Diikutkan sebagai field substantif dalam pembandingan detail_handler: mengirim ulang ref_shipment_id yang sama dengan gudang_id berbeda pada dokumen yang belum "Berjalan" ditolak SHIPMENT_DETAIL_MISMATCH ("force" menimpa ke gudang baru, "validate" menolak).',
            'gudang_id hanya benar-benar memindahkan gudang PEMOTONGAN STOK untuk item satuan ECERAN. Untuk item satuan bulk, stok Pegasus hanya pernah dikelola di gudang utama — gudang_id non-utama TETAP tersimpan di sales_order_details.warehouse_id (dan tetap ikut dibandingkan detail_handler di atas), tapi potongan stok sesungguhnya selalu terjadi di gudang utama lewat App\Support\SalesOrderStock::buildPlan(), tidak peduli gudang_id yang dikirim.',
        ];
    }
}
