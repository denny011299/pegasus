<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/shipments/returns (GitHub #58).
 */
class ShipmentReturnCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'shipment-returns-create';
    }

    public function title(): string
    {
        return 'Buat Pengembalian';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/shipments/returns';
    }

    public function group(): string
    {
        return 'pengiriman';
    }

    public function description(): string
    {
        return 'Membuat satu dokumen pengembalian (bahan mentah/kemasan dan/atau produk jadi) '
            .'dari armada — proses yang sama dengan tombol "Tambah Pengembalian" di halaman '
            .'admin Pengiriman > Pengembalian. Dokumen dibuat berstatus Pending, gudang tujuan '
            .'BELUM diisi (lihat catatan) sampai staf mengisinya lewat halaman admin sebelum '
            .'menerimanya.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'return_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal pengembalian, format YYYY-MM-DD.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'customers.customer_code — id universal Armada, sama field yang dipakai POST /shipments/scheduled dan /shipments/shipped. Harus armada aktif.'],
            ['name' => 'ref_number', 'type' => 'string', 'required' => false,
                'description' => 'Nomor referensi bebas, catatan saja — bukan kunci idempotensi (endpoint ini TIDAK idempoten, lihat catatan).'],
            ['name' => 'notes', 'type' => 'string', 'required' => false,
                'description' => 'Catatan bebas.'],
            ['name' => 'proof', 'type' => 'file', 'required' => true,
                'description' => 'Bukti foto pengembalian, berkas sungguhan lewat multipart/form-data. WAJIB kirim salah satu dari proof ATAU proof_base64. JPEG/PNG/WebP, maksimal 5MB.'],
            ['name' => 'proof_base64', 'type' => 'string', 'required' => true,
                'description' => 'Alternatif proof untuk pemanggil JSON murni: data URI base64 ("data:image/jpeg;base64,..."). WAJIB kirim salah satu dari proof ATAU proof_base64.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Daftar barang yang dikembalikan, minimal satu.'],
            ['name' => 'items[].type', 'type' => 'integer', 'required' => true,
                'description' => '1 = bahan mentah/kemasan, 2 = produk jadi.'],
            ['name' => 'items[].ref_id', 'type' => 'integer atau string', 'required' => true,
                'description' => 'type=1: supplies.ref_supplies_id (integer) — daftarkan/hubungkan dulu lewat POST /bahan atau PATCH /bahan/connect (grup Data Bahan). type=2: product_variants.product_variant_sku (string) — SAMA field dipakai items[].variant_sku pada POST /shipments/shipped, BUKAN products.ref_product_id.'],
            ['name' => 'items[].qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah yang dikembalikan, dalam satuan items[].satuan_id.'],
            ['name' => 'items[].satuan_id', 'type' => 'integer', 'required' => true,
                'description' => 'Rujukan units.ref_unit_id (id satuan pada sistem PMO), BUKAN id internal Pegasus — sama pola dipakai items[].unit_id di seluruh modul Shipment/Stok. Harus satuan aktif YANG TERDAFTAR untuk bahan/produk itu.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'return_date' => '2026-08-17',
            'armada_code' => 'L8533N',
            'ref_number' => 'RTN-7788',
            'notes' => 'Sisa muatan setelah rute selesai',
            'proof_base64' => 'data:image/jpeg;base64,/9j/4AAQSkZJRg...',
            'items' => [
                ['type' => 1, 'ref_id' => 12, 'qty' => 5, 'satuan_id' => 2],
                ['type' => 2, 'ref_id' => 'AAHK400ML', 'qty' => 3, 'satuan_id' => 2],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'return_number' => 'PKR0003',
                'return_type' => 'mixed',
                'supply_return_id' => 15,
                'product_return_id' => 9,
                'armada_code' => 'L8533N',
                'message' => 'Pengembalian berhasil disimpan, menunggu gudang tujuan diisi sebelum diterima.',
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'armada_code tidak ditemukan atau tidak aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.0.ref_id (type=1) tidak ditemukan sebagai ref_supplies_id yang aktif — daftarkan/hubungkan dulu lewat POST atau PATCH /bahan/connect.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.1.ref_id (type=2) tidak ditemukan sebagai product_variant_sku yang aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'items.*.satuan_id tidak terdaftar untuk bahan/produk pada baris itu.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'proof/proof_base64 kosong, atau bukan gambar JPEG/PNG/WebP yang valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Fiturnya sama dengan menu admin Pengiriman > Pengembalian — endpoint ini cuma jalur masuk baru untuk PMO memicunya langsung, bukan alur baru.',
            'TIDAK idempoten (beda dengan /shipments/shipped dan /payments/cash) — tidak ada field acuan unik pada kontrak ini. Setiap permintaan yang lolos validasi selalu membuat dokumen pengembalian BARU, sama seperti /shipments/scheduled. Kirim ulang permintaan yang sama akan menghasilkan dua dokumen.',
            'Gudang tujuan (warehouse) SENGAJA tidak ditanyakan sama sekali oleh endpoint ini — modul Gudang masih dalam pengembangan. Dokumen dibuat berstatus Pending seperti biasa, tapi baru bisa DITERIMA (ACC, memotong/menambah stok) setelah staf gudang mengisi gudang tujuan tiap baris lewat halaman admin Pengiriman > Pengembalian. Sampai saat itu, dokumen tetap terlihat di daftar Pengembalian dengan status Pending.',
            'items[] boleh campuran type=1 dan type=2 dalam satu permintaan yang sama — satu dokumen pengembalian bisa berisi bahan mentah dan produk jadi sekaligus (return_type "mixed" pada respons), sama seperti form admin.',
            'Baris items[] dengan type + ref_id + satuan_id yang sama digabung otomatis (qty dijumlah) sebelum disimpan — mengirim baris duplikat tidak menghasilkan baris tersimpan ganda.',
            'items[].satuan_id divalidasi benar-benar terdaftar untuk bahan/produk pada baris itu (satuan default, satuan tambahan, atau hasil konversi) — mengirim satuan yang valid secara umum tapi tidak pernah didaftarkan untuk bahan/produk itu tetap ditolak VALIDATION_FAILED.',
            'proof/proof_base64 disimpan dengan aturan yang SAMA PERSIS dengan form admin (folder public/customer_returns/, validasi isi berkas benar-benar gambar) — BUKAN mekanisme photos[] milik /shipments/shipped, itu fitur yang berbeda.',
            'return_number pada respons adalah return_group (format PKR####) — nomor gabungan yang sama dipakai sisi bahan (supply_return_id) maupun sisi produk (product_return_id) pada dokumen ini, ditampilkan sebagai satu baris "Campuran" di daftar admin kalau keduanya terisi.',
        ];
    }
}
