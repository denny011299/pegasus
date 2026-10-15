<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi POST /api/external/v1/payments/cash (API-005).
 */
class CashPaymentCreateDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'pembayaran-kas-buat';
    }

    public function title(): string
    {
        return 'Buat Pembayaran Kas';
    }

    public function method(): string
    {
        return 'POST';
    }

    public function path(): string
    {
        return '/payments/cash';
    }

    public function group(): string
    {
        return 'pembayaran';
    }

    public function description(): string
    {
        return 'Mencatat transaksi kas operasional atas nama armada atau sales, beserta rinciannya. '
            .'Item boleh campuran Masuk/Keluar/Keluar 1: Pegasus membuat satu pembayaran per type, '
            .'sehingga data respons berupa array. Hanya create, tidak pernah mengubah pembayaran tersimpan. Idempoten per ref_payment_id + penerima '
            .'(armada_code atau staff_id) — bukan per ref_payment_id sendirian. Potongan (nominal maupun '
            .'barang) ikut dicatat di pembayaran yang sama; potongan barang otomatis membuat dokumen '
            .'Pengembalian, sehingga tidak perlu memanggil POST /shipments/returns terpisah.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_payment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda group pembayaran milik sistem pemanggil. Boleh dipakai berulang untuk penerima (armada_code/staff_id) yang berbeda dalam group yang sama — lihat catatan idempotensi.'],
            ['name' => 'ref_payment_number', 'type' => 'string', 'required' => false,
                'description' => 'No. Dokumen Pembayaran yang tercetak di dokumen pembayaran sistem pemanggil, mis. PB/BUD/121026/003. Maks. 64 karakter. Opsional dan tidak unik — hanya disimpan dan ditampilkan sebagai "No. Pembayaran PMO", bukan kunci idempotensi.'],
            ['name' => 'payment_type', 'type' => 'integer', 'required' => true,
                'description' => '1 = Armada, 2 = Sales.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => false,
                'description' => 'Wajib bila payment_type = 1 (kecuali kirim armada_id). customers.customer_code — SAMA field armada_code pada /shipments/scheduled dan /shipments/shipped.'],
            ['name' => 'armada_id', 'type' => 'integer', 'required' => false,
                'description' => 'Legacy: PK customers.customer_id. Lebih baik pakai armada_code. Boleh salah satu dari armada_code atau armada_id bila payment_type = 1.'],
            ['name' => 'staff_id', 'type' => 'string atau integer', 'required' => false,
                'description' => 'Wajib bila payment_type = 2. Id sales milik sistem pemanggil (= staffs.external_ref_id), SAMA field staff_id pada /master/sales — BUKAN id internal Pegasus.'],
            ['name' => 'payment_date', 'type' => 'date', 'required' => true,
                'description' => 'Tanggal transaksi, format YYYY-MM-DD.'],
            ['name' => 'payment_amount', 'type' => 'integer', 'required' => false,
                'description' => 'Opsional, hanya angka pengecekan. Bila dikirim, harus sama persis dengan jumlah seluruh items[].amount (semua type dijumlah). Nominal tiap pembayaran selalu dihitung Pegasus dari item-nya.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Rincian transaksi, minimal satu baris.'],
            ['name' => 'items[].amount', 'type' => 'integer', 'required' => true,
                'description' => 'Nominal satu rincian.'],
            ['name' => 'items[].type', 'type' => 'integer', 'required' => true,
                'description' => '1 = Masuk (setoran), 2 = Keluar, 3 = Keluar 1. Boleh campuran dalam satu permintaan — item dikelompokkan per type dan tiap kelompok menjadi satu pembayaran tersendiri.'],
            ['name' => 'items[].notes', 'type' => 'string', 'required' => false,
                'description' => 'Keterangan rincian, maksimal 255 karakter.'],
            ['name' => 'items[].ref_nota_id', 'type' => 'string', 'required' => false,
                'description' => 'Id nota/faktur milik sistem pemanggil yang menjadi asal item ini, maksimal 100 karakter. Disimpan apa adanya dan ikut dikembalikan per item di response, tidak divalidasi ke data lain. Satu pembayaran boleh menggabungkan item dari beberapa nota berbeda.'],
            ['name' => 'items[].kind', 'type' => 'string', 'required' => false,
                'description' => 'Jenis item: cash (uang tunai, bawaan bila tidak dikirim), potongan (potongan nominal, mis. cash diskon 3%), potongan_barang (potongan berupa barang yang dikembalikan armada, mis. jerigen). Potongan boleh pada alur kas mana pun (type 1, 2, atau 3); potongan_barang tetap membuat dokumen Pengembalian.'],
            ['name' => 'items[].goods', 'type' => 'object', 'required' => false,
                'description' => 'Wajib bila kind = potongan_barang, dan hanya boleh dikirim untuk kind itu. Barang yang dikembalikan.'],
            ['name' => 'items[].goods.item_type', 'type' => 'integer', 'required' => true,
                'description' => '1 = bahan mentah/kemasan, 2 = produk jadi — sama arti dengan items[].type pada POST /shipments/returns.'],
            ['name' => 'items[].goods.ref_id', 'type' => 'string', 'required' => true,
                'description' => 'item_type 1: ref_supplies_id bahan. item_type 2: SKU varian produk.'],
            ['name' => 'items[].goods.qty', 'type' => 'integer', 'required' => true,
                'description' => 'Jumlah barang, minimal 1.'],
            ['name' => 'items[].goods.satuan_id', 'type' => 'integer', 'required' => true,
                'description' => 'units.ref_unit_id satuan barang.'],
            ['name' => 'items[].goods.armada_code', 'type' => 'string', 'required' => false,
                'description' => 'Armada yang mengembalikan barang (customers.customer_code). Wajib pada payment_type = 2; pada payment_type = 1 bila kosong memakai armada_code pembayaran. Harus armada aktif atau disertakan profilnya di armadas[].'],
            ['name' => 'items[].goods.ref_shipment_id', 'type' => 'string', 'required' => false,
                'description' => 'Pengiriman asal barang (sama dengan ref_shipment_id pada /shipments/*). Bila kosong memakai ref_shipment_id di header; salah satunya wajib ada.'],
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => false,
                'description' => 'Pengiriman bawaan untuk items[].goods yang tidak mengisi ref_shipment_id sendiri.'],
            ['name' => 'armadas', 'type' => 'array', 'required' => false,
                'description' => 'Profil armada yang dirujuk items[].goods.armada_code, untuk dibuat/diperbarui otomatis bila belum tersinkron. Bentuk tiap objek sama dengan body.armada pada POST /shipments/returns (code wajib; pic, pic_phone, nomor_polisi, category, merk_model, tahun_kendaraan, lokasi opsional).'],
            ['name' => 'photos', 'type' => 'array', 'required' => false,
                'description' => 'Bukti transaksi sebagai data URI base64. Hanya PNG dan JPEG. Foto pertama juga dipakai sebagai bukti dokumen Pengembalian dari potongan barang.'],
            ['name' => 'auto_accept', 'type' => 'boolean', 'required' => false,
                'description' => 'Bila true, seluruh pembayaran yang terbentuk langsung disetujui satu per satu dan saldo armada/sales ikut disesuaikan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_payment_id' => 'G7913082026154630',
            'ref_payment_number' => 'PB/BUD/121026/003',
            'payment_type' => 1,
            'armada_code' => 'TRK-01',
            'payment_date' => '2026-10-07',
            'ref_shipment_id' => '5429092026170947263',
            'payment_amount' => 1720000,
            'armadas' => [
                ['code' => 'TRK-02', 'nomor_polisi' => 'B 5678 YY', 'category' => 'Truk Engkel'],
            ],
            'items' => [
                ['kind' => 'cash', 'type' => 1, 'amount' => 1500000, 'notes' => 'Pelunasan INV/001', 'ref_nota_id' => '4328012026102327'],
                ['kind' => 'potongan', 'type' => 1, 'amount' => 45000, 'notes' => 'Cash Diskon 3%', 'ref_nota_id' => '4328012026102327'],
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 105000, 'notes' => 'Jerigen 25kg', 'ref_nota_id' => '4328012026102327',
                    'goods' => ['item_type' => 1, 'ref_id' => '242', 'qty' => 5, 'satuan_id' => 9506012026014616]],
                ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 70000, 'notes' => 'Jerigen 35kg', 'ref_nota_id' => '4328012026102400',
                    'goods' => ['item_type' => 1, 'ref_id' => '241', 'qty' => 2, 'satuan_id' => 9506012026014616,
                        'armada_code' => 'TRK-02', 'ref_shipment_id' => '5429092026170947300']],
            ],
        ];
    }


    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'ref_payment_id' => 'G7913082026154630',
                    'ref_payment_number' => 'PB/BUD/121026/003',
                    'payment_id' => 512,
                    'payment_type' => 1,
                    'payment_date' => '2026-10-07',
                    'payment_amount' => 1720000,
                    'notes' => 'Setoran armada B 1234 XX',
                    'armada_code' => 'TRK-01',
                    'armada_id' => 4,
                    'staff_id' => null,
                    'status' => 'pending',
                    'items' => [
                        ['amount' => 1500000, 'notes' => 'Pelunasan INV/001', 'type' => 1, 'kind' => 'cash', 'ref_nota_id' => '4328012026102327'],
                        ['amount' => 45000, 'notes' => 'Cash Diskon 3%', 'type' => 1, 'kind' => 'potongan', 'ref_nota_id' => '4328012026102327'],
                        ['amount' => 105000, 'notes' => 'Jerigen 25kg', 'type' => 1, 'kind' => 'potongan_barang', 'ref_nota_id' => '4328012026102327'],
                        ['amount' => 70000, 'notes' => 'Jerigen 35kg', 'type' => 1, 'kind' => 'potongan_barang', 'ref_nota_id' => '4328012026102400'],
                    ],
                    'photos' => [],
                    'created_at' => '2026-10-07T03:15:00+07:00',
                ],
            ],
            'meta' => [
                'replayed_types' => [],
                'returns' => [
                    ['armada_code' => 'TRK-01', 'ref_shipment_id' => '5429092026170947263', 'return_number' => 'PKR0087',
                        'supply_return_id' => 88, 'product_return_id' => null, 'pending_warehouse_items' => 1],
                    ['armada_code' => 'TRK-02', 'ref_shipment_id' => '5429092026170947300', 'return_number' => 'PKR0088',
                        'supply_return_id' => 89, 'product_return_id' => null, 'pending_warehouse_items' => 1],
                ],
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'payment_amount tidak sama dengan jumlah item.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'Armada dengan id tersebut tidak ditemukan.'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'armada_code wajib diisi untuk potongan barang pada pembayaran Sales. (path items.N.goods.armada_code)'],
            ['code' => 'VALIDATION_FAILED', 'http_status' => 422,
                'message' => 'Bahan dengan ref_supplies_id tersebut tidak ditemukan atau tidak aktif. (path items.N.goods.ref_id)'],
            ['code' => 'PAYMENT_CONTENT_MISMATCH', 'http_status' => 409,
                'message' => 'Pembayaran dengan ref_payment_id ini untuk penerima ini sudah tersimpan dengan rincian type N yang berbeda. (details.type = type yang berbeda)'],
            ['code' => 'PAYMENT_NOT_ACCEPTABLE', 'http_status' => 409,
                'message' => 'Pembayaran sudah diterima/ditolak sebelumnya.'],
        ];
    }

    public function notes(): array
    {
        return [
            'items[].ref_nota_id bersifat opsional dan tidak divalidasi ke data lain — hanya disimpan dan dikembalikan apa adanya per item, untuk membantu rekonsiliasi keuangan menelusuri balik ke nota/faktur asal tiap item tanpa mem-parsing notes. Satu pembayaran boleh berisi item dari beberapa nota berbeda.',
            'Endpoint ini hanya untuk create — pembayaran yang sudah tersimpan tidak pernah diubah. Idempotensi memakai ref_payment_id + penerima (armada_code untuk payment_type 1, staff_id untuk payment_type 2) + type, BUKAN ref_payment_id sendirian: satu group pembayaran Sales bisa mencakup beberapa sales penerima, dan PMO mengirimnya sebagai beberapa POST dengan ref_payment_id yang SAMA, satu call per penerima. Saat kiriman dengan ref_payment_id + penerima yang sama datang lagi, tiap type dicek: type yang sudah tersimpan harus berisi item yang sama persis (amount, type, kind, notes, ref_nota_id, urutan sama), type yang belum ada dibuat. Bila ada type tersimpan yang isinya berbeda atau tidak ikut dikirim lagi, seluruh kiriman ditolak 409 PAYMENT_CONTENT_MISMATCH dan tidak ada yang dibuat. Bila semua type sudah ada dan sama, dijawab 200 dengan meta.idempotent_replay = true. Bila sebagian type baru, dijawab 201 dan meta.replayed_types berisi type yang sudah ada sebelumnya.',
            'ref_payment_number (No. Dokumen Pembayaran) bersifat opsional dan tidak unik: satu dokumen pembayaran boleh menghasilkan beberapa pembayaran di Pegasus (beberapa group, per penerima, atau per alur kas) dengan nomor yang sama. Nomor ini disimpan di pembayaran beserta dokumen Pengembalian dari potongan barangnya, ditampilkan di halaman Kas Operasional dan Pengembalian, dan dapat dicari di sana. Nilai kosong disimpan sebagai null. Kiriman ulang TIDAK memperbarui nomor yang sudah tersimpan.',
            'staff_id (payment_type=2) adalah external_ref_id sales milik PMO — SAMA nilai staff_id di /master/sales. Bukan PK internal staffs.staff_id. Sales harus sudah terdaftar/tersinkron (status aktif) sebelum POST /payments/cash.',
            'payment_type=1: utamakan armada_code (= customers.customer_code, sama field di /shipments/*). armada_id (PK customer) masih diterima sebagai legacy. Armada harus aktif.',
            'Pembuatan berhasil menjawab 201; kiriman ulang menjawab 200.',
            'Istilah "armada": di Pegasus, armada dicatat sebagai data pelanggan (customer_code + nomor polisi di keterangan), bukan tabel armada tersendiri.',
            'payment_amount opsional. Bila dikirim dan tidak sama dengan jumlah seluruh items[].amount, permintaan ditolak 422 dan tidak ada yang tersimpan.',
            'Item campuran type: satu pembayaran di Pegasus hanya bisa mencatat satu arah kas, jadi item dikelompokkan per type (urutan sesuai kemunculan pertama) dan tiap kelompok menjadi satu pembayaran dengan payment_amount = jumlah item kelompok itu. Type boleh ditambahkan belakangan lewat kiriman berikutnya dengan ref_payment_id + penerima yang sama (lihat catatan idempotensi). data selalu berupa array pembayaran — berisi satu elemen bila seluruh item bertype sama. Semua kelompok tersimpan dalam satu transaksi: berhasil semua atau gagal semua.',
            'Potongan: semua item (cash, potongan, potongan_barang) dihitung di payment_amount dan tercatat sebagai rincian kas, sehingga total terbayar sama dengan sistem pemanggil. items[].kind membedakan tunai dari potongan di laporan. Payload tanpa kind tetap valid dan diperlakukan sebagai tunai.',
            'Potongan barang: Pegasus membuat SATU dokumen Pengembalian per type + pasangan (goods.armada_code, goods.ref_shipment_id), dalam transaksi yang sama dengan kas — bila satu baris barang ditolak, pembayaran juga tidak tersimpan. Dokumen berstatus Pending; ref_number berisi ref_payment_id. Daftar dokumen dikembalikan di meta.returns (array kosong bila tidak ada potongan barang).',
            'Gudang tujuan barang dari potongan barang tidak dikirim pemanggil: petugas gudang memilihnya (gudang utama atau eceran) di halaman Pengembalian sebelum dokumen diterima. pending_warehouse_items menunjukkan jumlah baris yang menunggu pilihan gudang.',
            'Kiriman ulang mengembalikan pembayaran dan dokumen Pengembalian yang sudah ada, tanpa membuat dokumen baru.',
            'Endpoint ini hanya untuk transaksi operasional (pengeluaran atau setoran berbutir). Penambahan saldo tetap dilakukan lewat halaman admin.',
            'Seluruh penyimpanan berjalan dalam satu transaksi database. Bila ada bagian yang gagal, tidak ada satu pun baris maupun berkas foto yang tertinggal.',
            'auto_accept = true menjalankan persetujuan yang sama dengan tombol ACC di halaman admin, termasuk penyesuaian saldo armada atau sales. Pemakaian tanpa auto_accept menyisakan pembayaran berstatus pending untuk disetujui petugas.',
            'Pembayaran yang dibuat lewat API tidak memiliki pembuat internal (created_by kosong), karena tidak ada pengguna yang login. Jejaknya tersedia di Log API Eksternal, lengkap dengan aplikasi dan kunci yang memanggil.',
        ];
    }
}
