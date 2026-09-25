<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi PUT /api/external/v1/shipments/scheduled.
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
        return 'PUT';
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
        return 'Mengecek stok (logika yang sama dengan POST /stock/check) lalu menjadwalkan atau '
            .'memperbarui shipment berdasarkan ref_shipment_id: BELUM ada -> membuat baris baru '
            .'ber-ipm_status "Dijadwalkan" (1); SUDAH ada dan masih "Dijadwalkan" -> baris yang '
            .'sama diperbarui (armada, tanggal, dan seluruh item ditimpa dengan yang dikirim); '
            .'SUDAH ada tapi statusnya sudah maju (mis. sudah dikirim/dibatalkan) -> ditolak, '
            .'tidak ada yang berubah. Shipment TETAP dijadwalkan/diperbarui meski ada item yang '
            .'kurang stok — pemotongan stok sungguhan baru terjadi di POST /shipments/shipped. '
            .'Bila diminta, kekurangan dicatat sebagai dokumen terpisah untuk staf gudang/pembelian.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda unik milik sistem pemanggil. Menentukan apakah permintaan ini membuat shipment baru atau memperbarui yang sudah ada — lihat description().'],
            ['name' => 'scheduled_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal shipment dijadwalkan, format YYYY-MM-DD. Disimpan sebagai sales_orders.so_date.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'customers.customer_code — id universal Armada (lihat modul Data Armada). Kalau belum ada di Pegasus, dibuat otomatis (upsert minimal, tanpa profil) — tidak wajib disinkronkan/dibuat lewat endpoint Armada lebih dulu.'],
            ['name' => 'auto_create_shortage_doc', 'type' => 'boolean', 'required' => false,
                'description' => 'true = buat dokumen kekurangan stok otomatis BILA ada item yang shortage-nya > 0 (berlaku juga saat memperbarui shipment yang sudah ada — dokumen baru dibuat, bukan menimpa dokumen sebelumnya). Tidak dikirim/false = tidak pernah membuat dokumen.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Daftar item yang akan dikirim, minimal satu. Bentuk identik dengan POST /stock/check. Saat memperbarui shipment yang sudah ada, daftar ini MENGGANTI seluruh item lama, bukan digabung.'],
            ['name' => 'items[].sku', 'type' => 'string', 'required' => true,
                'description' => 'product_variants.product_variant_sku. Harus merujuk varian produk aktif.'],
            ['name' => 'items[].qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah yang akan dikirim, dalam satuan items[].unit_id.'],
            ['name' => 'items[].unit_id', 'type' => 'integer', 'required' => true,
                'description' => 'Rujukan units.ref_unit_id (id satuan pada sistem PMO), BUKAN id internal Pegasus. Harus merujuk satuan aktif.'],
            ['name' => 'items[].ref_nota_id', 'type' => 'integer', 'required' => false,
                'description' => 'Id nota (oms_order.id) pada sistem PMO asal baris item ini. Murni untuk penelusuran/relasi di sisi IPM — tidak divalidasi dan tidak mempengaruhi pengecekan stok maupun penjadwalan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_shipment_id' => 'SHP-7788',
            'scheduled_date' => '2026-07-25',
            'armada_code' => 'ARM-JKT-001',
            'auto_create_shortage_doc' => true,
            'items' => [
                ['sku' => 'AAHK400ML', 'qty' => 24, 'unit_id' => 5, 'ref_nota_id' => 4328012026102327],
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
            ['code' => 'SHIPMENT_NOT_UPDATABLE', 'http_status' => 409,
                'message' => 'Pengiriman dengan referensi SHP-7788 sudah berstatus "Sudah Terkirim" dan tidak bisa diperbarui lagi lewat endpoint ini — hanya bisa selama masih berstatus "Dijadwalkan".'],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini bisa dinonaktifkan sewaktu-waktu lewat menu Status API Eksternal (galat API_ENDPOINT_DISABLED bila dinonaktifkan) — gunakan POST /shipments/shipped sebagai gantinya untuk mengirim/memperbarui shipment.',
            'Upsert lewat ref_shipment_id: BELUM ada -> dibuat baru (respons 201). SUDAH ada dan masih "Dijadwalkan" -> diperbarui, seluruh field (armada_code/scheduled_date/items) ditimpa dengan yang dikirim permintaan ini (respons 200). SUDAH ada tapi statusnya sudah maju (bukan "Dijadwalkan" lagi, mis. sudah dikirim lewat /shipments/shipped atau berubah lewat change-status) -> ditolak SHIPMENT_NOT_UPDATABLE, tidak ada yang berubah.',
            'Memperbarui shipment MENGGANTI seluruh items[] lama dengan yang dikirim permintaan ini, bukan menggabungkan — kirim daftar item LENGKAP tiap kali memperbarui, bukan hanya item yang berubah.',
            'Shipment SELALU dijadwalkan/diperbarui (SO dibuat/ditulis), baik ada shortage atau tidak — shortage TIDAK menolak permintaan. auto_create_shortage_doc hanya mengatur apakah kekurangan itu dicatat sebagai dokumen terpisah; setiap permintaan yang memicunya membuat dokumen BARU, tidak menimpa dokumen dari permintaan sebelumnya.',
            'ipm_status/ipm_status_label dipetakan secara terpisah, bukan status baku yang dipakai alur Pengiriman manual apa adanya. Shipment hasil endpoint ini dianggap "belum dikonfirmasi", sama seperti shipment yang baru dibuat manual lewat halaman admin dan belum di-ACC, sehingga dipetakan ke ipm_status 1 ("Dijadwalkan").',
            'Stok yang dicek adalah gudang utama (tidak ada parameter gudang pada endpoint ini) — sama seperti default POST /stock/check ketika gudang_id tidak dikirim.',
            'Endpoint ini murni penjadwalan logistik, tidak membawa informasi harga — harga/subtotal item maupun total shipment seluruhnya tersimpan 0.',
            'Satu baris detail dibuat per item yang dikirim di items[], disimpan dengan cara yang sama persis dengan yang dipakai halaman admin Pengiriman.',
            'Dokumen kekurangan stok murni catatan untuk staf gudang/pembelian saat ini — belum ada endpoint atau halaman admin untuk membacanya balik.',
            'items[].ref_nota_id disimpan apa adanya per baris (tidak digabung meski beberapa baris berbagi sku+unit_id yang sama) — kirim satu baris item per nota asal kalau satu shipment membundel beberapa nota.',
            'armada_code yang belum ada di Pegasus dibuat otomatis (upsert minimal, hanya customer_code) alih-alih ditolak — tidak wajib memanggil endpoint Armada atau Pusat Sinkronisasi lebih dulu. Baris yang dibuat begini tidak punya PIC/No Pol/telepon/saldo sampai armada itu benar-benar disinkronkan/diisi lewat jalur lain.',
        ];
    }
}
