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
        return 'Kirim / Perbarui Shipment';
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
        return 'Satu-satunya pintu masuk shipment dari sisi kami sejak POST /shipments/scheduled '
            .'dinonaktifkan. Membuat shipment baru (bila ref_shipment_id belum ada) atau '
            .'memperbarui shipment yang sudah ada, dengan status hasil selalu "Pending" — '
            .'menunggu proses persetujuan 2 tahap secara internal sebelum stok benar-benar '
            .'dipotong. Stok TIDAK dipotong oleh permintaan ini.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => true,
                'description' => 'Kunci pencarian/upsert. Belum ada -> dibuat baru. Sudah ada -> baris itu yang diperbarui, dengan syarat (lihat catatan).'],
            ['name' => 'shipment_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal pengiriman, format YYYY-MM-DD.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'Id universal Armada. Kalau belum ada di kami, dibuat otomatis (upsert minimal, tanpa profil) — tidak wajib disinkronkan/dibuat lewat endpoint Armada lebih dulu.'],
            ['name' => 'status', 'type' => 'string', 'required' => true,
                'description' => 'Wajib "onprocess" ("Berjalan"), baik untuk membuat maupun memperbarui. Nilai lain ditolak (INVALID_STATUS) — ini penanda dari kontrak, bukan penentu status akhir shipment di sini (lihat catatan).'],
            ['name' => 'notes', 'type' => 'string', 'required' => false,
                'description' => 'Catatan bebas.'],
            ['name' => 'detail_handler', 'type' => 'string', 'required' => false,
                'description' => '"force" (bawaan) = timpa data tersimpan dengan permintaan ini bila berbeda. "validate" = tolak dengan galat SHIPMENT_DETAIL_MISMATCH bila berbeda, tidak ada yang berubah.'],
            ['name' => 'auto_create_shortage_doc', 'type' => 'boolean', 'required' => false,
                'description' => 'true = kalau ada item yang stok tersedianya kurang dari yang diminta, dibuatkan satu catatan kekurangan (untuk staf gudang/pembelian) — murni informasi, tidak menghalangi shipment tetap dibuat/diperbarui walau ada kekurangan.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Daftar item yang dikirim, minimal satu.'],
            ['name' => 'items[].variant_sku', 'type' => 'string', 'required' => true,
                'description' => 'Kode SKU varian produk. Harus merujuk varian produk aktif.'],
            ['name' => 'items[].qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah yang dikirim, dalam satuan items[].unit_id.'],
            ['name' => 'items[].unit_id', 'type' => 'integer', 'required' => true,
                'description' => 'Rujukan id satuan pada sistem PMO. Harus merujuk satuan aktif.'],
            ['name' => 'items[].product_name', 'type' => 'string', 'required' => true,
                'description' => 'Nama produk, dipakai apa adanya (tidak di-lookup dari database).'],
            ['name' => 'items[].variant_name', 'type' => 'string', 'required' => false,
                'description' => 'Nama varian, dipakai apa adanya.'],
            ['name' => 'items[].ref_nota_id', 'type' => 'integer', 'required' => false,
                'description' => 'Id nota pada sistem PMO asal baris item ini. Murni untuk penelusuran/relasi di sisi kami — tidak divalidasi.'],
            ['name' => 'photos', 'type' => 'array', 'required' => false,
                'description' => 'Bukti pengiriman. Kirim sebagai berkas sungguhan lewat multipart/form-data (photos[] sebagai file upload, field lain tetap sebagai field form biasa — array items[] memakai notasi kurung standar HTML form: items[0][variant_sku], items[0][qty], dst.), ATAU sebagai data URI base64 lewat JSON murni. Hanya PNG/JPEG, maksimal 5MB per berkas.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_shipment_id' => 'SHP-7788',
            'shipment_date' => '2026-07-25',
            'armada_code' => 'ARM-JKT-001',
            'status' => 'onprocess',
            'notes' => 'Pengiriman SHP-7788',
            'detail_handler' => 'force',
            'items' => [
                [
                    'variant_sku' => 'AAHK400ML',
                    'qty' => 24,
                    'unit_id' => 2,
                    'product_name' => 'AIR AKI HIKARI',
                    'variant_name' => '20 x 400ml',
                    'ref_nota_id' => 4328012026102327,
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
                'shortage_doc_created' => false,
                'shortage_doc_number' => null,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.variant_sku tidak ditemukan sebagai varian produk aktif.'],
            ['code' => 'INVALID_STATUS', 'http_status' => 422,
                'message' => 'Field status harus "onprocess". Nilai lain tidak diterima endpoint ini.'],
            ['code' => 'SHIPMENT_DETAIL_MISMATCH', 'http_status' => 409,
                'message' => 'Data shipment untuk ref_shipment_id ini sudah tersimpan dan berbeda dari permintaan ini (items). Kirim detail_handler: "force" untuk menimpa, atau samakan data permintaan dengan yang sudah tersimpan.'],
            ['code' => 'SHIPMENT_NOT_UPDATABLE', 'http_status' => 409,
                'message' => 'Pengiriman dengan referensi SHP-7788 sudah berstatus "Pending (sudah disetujui Staf QC & Gudang)" di sisi kami dan tidak bisa diperbarui lagi lewat endpoint ini — hanya bisa selama masih "Pending" dan belum ada approval/penolakan sama sekali.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Sejak pembaruan ini, POST /shipments/scheduled dinonaktifkan — endpoint ini SATU-SATUNYA pintu masuk shipment. Dipanggil saat shipment yang tadinya "Dijadwalkan" berubah menjadi "Berjalan".',
            'Hasil insert maupun update SELALU ipm_status "Berjalan" (2), TAPI stok BELUM dipotong sama sekali — shipment menunggu proses persetujuan internal 2 tahap sebelum benar-benar dipotong. status="onprocess" di permintaan hanya kewajiban kontrak, bukan berarti stok langsung diproses.',
            'Update HANYA diterima selama shipment ini belum tersentuh proses persetujuan sama sekali. Begitu proses persetujuan sudah berjalan (sebagian atau seluruhnya), atau shipment sudah lanjut/berubah status, permintaan update ditolak SHIPMENT_NOT_UPDATABLE — riwayatnya tidak bisa ditulis ulang dari sisi ini lagi.',
            'auto_create_shortage_doc dihitung terhadap stok TERSEDIA SAAT INI, murni untuk info operasional — bukan jaminan stok masih tersedia saat proses persetujuan selesai nanti. Kalau ternyata stok tidak cukup di tahap akhir persetujuan, shipment tetap "Pending" (menunggu stok) sampai persetujuan berhasil, ini terjadi di luar permintaan ini.',
            'items[].product_name/variant_name dipakai apa adanya dari permintaan (tidak di-lookup ke database produk).',
            'Gudang yang dipakai tiap item, maupun perhitungan stoknya, selalu gudang utama — endpoint ini tidak menerima parameter gudang.',
            'items[].ref_nota_id disimpan apa adanya per baris dan ikut dibandingkan saat detail_handler menentukan ada tidaknya SHIPMENT_DETAIL_MISMATCH — dua baris boleh berbagi variant_sku+unit_id yang sama selama ref_nota_id-nya beda (tidak digabung).',
            'armada_code yang belum ada di kami dibuat otomatis (upsert minimal, hanya kode armada) alih-alih ditolak — tidak wajib memanggil endpoint Armada atau Pusat Sinkronisasi lebih dulu.',
        ];
    }
}
