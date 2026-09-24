<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/shipments/returns (GitHub #58, diperluas GitHub #203).
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
            .'admin Pengiriman > Pengembalian. Dokumen dibuat berstatus Pending. Gudang tujuan '
            .'tiap baris HANYA terisi kalau items[].gudang_id dikirim (lihat catatan) — kalau '
            .'tidak, baris itu dibiarkan kosong dan staf gudang wajib mengisinya manual lewat '
            .'halaman admin sebelum dokumen bisa diterima.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'return_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal pengembalian, format YYYY-MM-DD.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => true,
                'description' => 'customers.customer_code — id universal Armada, sama field yang dipakai POST /shipments/scheduled dan /shipments/shipped. Harus armada aktif.'],
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => false,
                'description' => 'sales_orders.ref_shipment_id milik shipment asal (dipakai PUT /shipments/scheduled dan POST /shipments/shipped) — hubungkan retur ini ke shipment/SO yang memicunya. Mengirim field ini juga membuat proof/proof_base64 opsional dan permintaan ini idempoten (lihat catatan).'],
            ['name' => 'ref_number', 'type' => 'string', 'required' => false,
                'description' => 'Nomor referensi bebas, catatan saja — bukan kunci idempotensi.'],
            ['name' => 'notes', 'type' => 'string', 'required' => false,
                'description' => 'Catatan bebas.'],
            ['name' => 'proof', 'type' => 'file', 'required' => false,
                'description' => 'Bukti foto pengembalian, berkas sungguhan lewat multipart/form-data. WAJIB kirim salah satu dari proof ATAU proof_base64 KECUALI ref_shipment_id dikirim (lihat catatan). JPEG/PNG/WebP, maksimal 5MB.'],
            ['name' => 'proof_base64', 'type' => 'string', 'required' => false,
                'description' => 'Alternatif proof untuk pemanggil JSON murni: data URI base64 ("data:image/jpeg;base64,..."). WAJIB kirim salah satu dari proof ATAU proof_base64 KECUALI ref_shipment_id dikirim (lihat catatan).'],
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
            ['name' => 'items[].gudang_id', 'type' => 'integer', 'required' => false,
                'description' => 'Id gudang tujuan — nilai LANGSUNG dari id gudang (bukan kolom rujukan eksternal seperti satuan_id/ref_id, karena gudang tidak disinkronkan sistem PMO), ambil daftarnya dari GET /master/warehouses (grup Data Master). Berlaku untuk SEMUA tipe baris (bahan maupun produk). Belum wajib — PMO memang tidak pernah mengirimnya untuk kasus pengembalian; baris tanpa gudang_id dibiarkan tanpa gudang tujuan, lihat catatan.'],
            ['name' => 'items[].ref_nota_id', 'type' => 'integer', 'required' => false,
                'description' => 'Id nota PMO asal baris retur ini — pola sama dengan items[].ref_nota_id pada POST /shipments/shipped, murni untuk penelusuran, tidak divalidasi. Ikut menentukan penggabungan baris: dua baris item+satuan yang sama tapi ref_nota_id berbeda TIDAK digabung.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'return_date' => '2026-08-17',
            'armada_code' => 'L8533N',
            'ref_shipment_id' => 'PMO-SHP-20889',
            'ref_number' => 'RTN-7788',
            'notes' => 'Nota dibatalkan ulang admin PMO, foto tidak tersedia dari sisi PMO',
            'items' => [
                // Tidak kirim gudang_id -- baris ini dibiarkan tanpa gudang tujuan, dihitung ke
                // pending_warehouse_items pada respons.
                ['type' => 1, 'ref_id' => 12, 'qty' => 5, 'satuan_id' => 2, 'ref_nota_id' => 55201],
                ['type' => 2, 'ref_id' => 'AAHK400ML', 'qty' => 3, 'satuan_id' => 2, 'ref_nota_id' => 55201],
                // gudang_id dikirim -- warehouse_id baris ini langsung terisi, tidak menunggu admin.
                ['type' => 2, 'ref_id' => 'AAHK400ML', 'qty' => 2, 'satuan_id' => 7, 'gudang_id' => 4, 'ref_nota_id' => 55202],
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
                'pending_warehouse_items' => 2,
                'message' => 'Pengembalian berhasil disimpan. 2 baris belum punya gudang tujuan, menunggu diisi lewat halaman admin sebelum bisa diterima.',
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
                'message' => 'items.*.gudang_id tidak ditemukan sebagai id gudang yang aktif.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'proof/proof_base64 kosong, atau bukan gambar JPEG/PNG/WebP yang valid.'],
        ];
    }

    public function notes(): array
    {
        return [
            'Fiturnya sama dengan menu admin Pengiriman > Pengembalian — endpoint ini cuma jalur masuk baru untuk PMO memicunya langsung, bukan alur baru.',
            'GitHub #203: retur per-nota dari PMO. Saat shipment yang sudah "Berjalan" diedit dan sebagian/semua notanya ditandai "Belum dikirim", panggil endpoint ini dengan ref_shipment_id terisi — TERLEPAS dari shipment asal itu sudah di tahap approval mana pun (termasuk yang sudah full-approved dan stoknya sudah terpotong). Endpoint ini TIDAK menyentuh status maupun stok shipment asal sama sekali — ia murni mencatat dokumen pengembalian yang tertaut ke shipment itu lewat ref_shipment_id/items[].ref_nota_id.',
            'IDEMPOTEN HANYA ketika ref_shipment_id dikirim — key-nya dihitung dari ref_shipment_id + return_date + isi items[] (urutan baris tidak berpengaruh). Permintaan identik yang dikirim ulang (mis. retry PMO setelah timeout jaringan) mengembalikan dokumen yang SUDAH ada (HTTP 200, meta.idempotent_replay: true), TIDAK membuat dokumen kedua. Permintaan TANPA ref_shipment_id (retur manual, bukan dari alur ini) TETAP TIDAK idempoten seperti semula — setiap permintaan yang lolos validasi selalu membuat dokumen BARU.',
            'proof/proof_base64 jadi OPSIONAL ketika ref_shipment_id dikirim — form edit pengiriman PMO tidak membawa foto untuk kasus retur ini. Kalau ref_shipment_id tidak dikirim, salah satu dari proof/proof_base64 tetap WAJIB seperti semula.',
            'Gudang tujuan tiap baris TIDAK PERNAH ditentukan otomatis (revisi GitHub #203, 2026-09-25 — sebelumnya bahan mentah dan produk non-eceran otomatis ke gudang utama, aturan itu sudah tidak berlaku). Berlaku sama untuk SEMUA tipe baris: pakai items[].gudang_id kalau dikirim, kalau tidak baris itu dibiarkan tanpa gudang tujuan.',
            'items[].gudang_id TIDAK wajib pada baris mana pun — PMO memang tidak pernah mengirimnya untuk kasus pengembalian. Baris tanpa gudang_id tetap tersimpan (dokumen tetap dibuat berstatus Pending dan tetap terlihat di daftar Pengembalian), tapi baru bisa DITERIMA (ACC, menambah stok) setelah staf gudang mengisi gudang tujuan SEMUA baris yang masih kosong lewat halaman admin Pengiriman > Pengembalian (modal Edit — dropdown gudang per baris). Jumlah baris yang masih kosong dilaporkan lewat pending_warehouse_items pada respons.',
            'items[] boleh campuran type=1 dan type=2 dalam satu permintaan yang sama — satu dokumen pengembalian bisa berisi bahan mentah dan produk jadi sekaligus (return_type "mixed" pada respons), sama seperti form admin.',
            'Baris items[] dengan type + ref_id + satuan_id + ref_nota_id yang sama digabung otomatis (qty dijumlah) sebelum disimpan — mengirim baris duplikat tidak menghasilkan baris tersimpan ganda. Baris yang item+satuannya sama TAPI ref_nota_id berbeda tetap disimpan sebagai baris terpisah, supaya keterlacakan per-nota tidak hilang.',
            'items[].satuan_id divalidasi benar-benar terdaftar untuk bahan/produk pada baris itu (satuan default, satuan tambahan, atau hasil konversi) — mengirim satuan yang valid secara umum tapi tidak pernah didaftarkan untuk bahan/produk itu tetap ditolak VALIDATION_FAILED.',
            'proof/proof_base64 disimpan dengan aturan yang SAMA PERSIS dengan form admin (folder public/customer_returns/, validasi isi berkas benar-benar gambar) — BUKAN mekanisme photos[] milik /shipments/shipped, itu fitur yang berbeda.',
            'return_number pada respons adalah return_group (format PKR####) — nomor gabungan yang sama dipakai sisi bahan (supply_return_id) maupun sisi produk (product_return_id) pada dokumen ini, ditampilkan sebagai satu baris "Campuran" di daftar admin kalau keduanya terisi.',
            'Perlakuan stok terhadap tahap approval shipment asal (apakah stok dikembalikan, item SO dikurangi, dsb) dan validasi qty retur terhadap qty yang pernah dikirim per nota BELUM ditangani endpoint ini — di luar cakupan GitHub #203, akan menyusul lewat perubahan terpisah.',
        ];
    }
}
