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
        return 'Mencatat satu transaksi kas operasional atas nama armada atau sales, '
            .'beserta rinciannya. Bersifat idempoten per pasangan ref_payment_id dan penerima '
            .'(armada_code atau staff_id) — bukan per ref_payment_id sendirian.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_payment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda group pembayaran milik sistem pemanggil. Boleh dipakai berulang untuk penerima (armada_code/staff_id) yang berbeda dalam group yang sama — lihat catatan idempotensi.'],
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
            ['name' => 'payment_amount', 'type' => 'integer', 'required' => true,
                'description' => 'Total nominal. Harus sama persis dengan jumlah seluruh items[].amount.'],
            ['name' => 'items', 'type' => 'array', 'required' => true,
                'description' => 'Rincian transaksi, minimal satu baris.'],
            ['name' => 'items[].amount', 'type' => 'integer', 'required' => true,
                'description' => 'Nominal satu rincian.'],
            ['name' => 'items[].type', 'type' => 'integer', 'required' => true,
                'description' => '1 = Masuk (setoran), 2 = Keluar, 3 = Keluar 1. Seluruh item harus bertype sama.'],
            ['name' => 'items[].notes', 'type' => 'string', 'required' => false,
                'description' => 'Keterangan rincian, maksimal 255 karakter.'],
            ['name' => 'items[].ref_nota_id', 'type' => 'string', 'required' => false,
                'description' => 'Id nota/faktur milik sistem pemanggil yang menjadi asal item ini, maksimal 100 karakter. Disimpan apa adanya dan ikut dikembalikan per item di response, tidak divalidasi ke data lain. Satu pembayaran boleh menggabungkan item dari beberapa nota berbeda.'],
            ['name' => 'photos', 'type' => 'array', 'required' => false,
                'description' => 'Bukti transaksi sebagai data URI base64. Hanya PNG dan JPEG.'],
            ['name' => 'auto_accept', 'type' => 'boolean', 'required' => false,
                'description' => 'Bila true, pembayaran langsung disetujui dan saldo armada/sales ikut disesuaikan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_payment_id' => 'PMO-GROUP-000123',
            'payment_type' => 2,
            'staff_id' => 'SLS-0007',
            'payment_date' => '2026-07-29',
            'payment_amount' => 150000,
            'auto_accept' => false,
            'items' => [
                ['amount' => 100000, 'type' => 1, 'notes' => 'Pelunasan nota A', 'ref_nota_id' => 'NOTA-2026-000456'],
                ['amount' => 50000, 'type' => 1, 'notes' => 'Pelunasan nota B', 'ref_nota_id' => 'NOTA-2026-000457'],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_payment_id' => 'PMO-GROUP-000123',
                'payment_id' => 512,
                'payment_type' => 2,
                'payment_date' => '2026-07-29',
                'payment_amount' => 150000,
                'notes' => 'Setoran sales Budi',
                'armada_code' => null,
                'staff_id' => 'SLS-0007',
                'status' => 'pending',
                'items' => [
                    ['amount' => 100000, 'notes' => 'Pelunasan nota A', 'type' => 1, 'ref_nota_id' => 'NOTA-2026-000456'],
                    ['amount' => 50000, 'notes' => 'Pelunasan nota B', 'type' => 1, 'ref_nota_id' => 'NOTA-2026-000457'],
                ],
                'photos' => [],
                'created_at' => '2026-07-29T03:15:00+07:00',
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
                'message' => 'Seluruh item harus memiliki type yang sama dalam satu pembayaran.'],
            ['code' => 'PAYMENT_NOT_ACCEPTABLE', 'http_status' => 409,
                'message' => 'Pembayaran sudah diterima/ditolak sebelumnya.'],
        ];
    }

    public function notes(): array
    {
        return [
            'items[].ref_nota_id bersifat opsional dan tidak divalidasi ke data lain — hanya disimpan dan dikembalikan apa adanya per item, untuk membantu rekonsiliasi keuangan menelusuri balik ke nota/faktur asal tiap item tanpa mem-parsing notes. Satu pembayaran boleh berisi item dari beberapa nota berbeda.',
            'Idempotensi memakai pasangan ref_payment_id + armada_code (payment_type 1) atau ref_payment_id + staff_id (payment_type 2) — BUKAN ref_payment_id sendirian. Ini sengaja: satu group pembayaran Sales bisa mencakup beberapa sales penerima sekaligus, dan PMO mengirimnya sebagai beberapa POST dengan ref_payment_id yang SAMA, satu call per penerima. Mengirim ulang pasangan yang sama dianggap kiriman ulang: pembayaran yang lama dikembalikan apa adanya dengan meta.idempotent_replay bernilai true, dan tidak ada transaksi baru yang dibuat. Isi permintaan tidak dibandingkan — hanya pasangan itu yang menentukan.',
            'staff_id (payment_type=2) adalah external_ref_id sales milik PMO — SAMA nilai staff_id di /master/sales. Bukan PK internal staffs.staff_id. Sales harus sudah terdaftar/tersinkron (status aktif) sebelum POST /payments/cash.',
            'payment_type=1: utamakan armada_code (= customers.customer_code, sama field di /shipments/*). armada_id (PK customer) masih diterima sebagai legacy. Armada harus aktif.',
            'Pembuatan berhasil menjawab 201; kiriman ulang menjawab 200.',
            'Istilah "armada": di Pegasus, armada dicatat sebagai data pelanggan (customer_code + nomor polisi di keterangan), bukan tabel armada tersendiri.',
            'payment_amount wajib sama dengan jumlah seluruh items[].amount. Bila berbeda, permintaan ditolak 422 dan tidak ada yang tersimpan.',
            'Seluruh baris pada items harus bertype sama. Pencatatan kas menurunkan jenis transaksi dari item pertama, sehingga campuran masuk dan keluar dalam satu pembayaran akan tercatat keliru.',
            'Endpoint ini hanya untuk transaksi operasional (pengeluaran atau setoran berbutir). Penambahan saldo tetap dilakukan lewat halaman admin.',
            'Seluruh penyimpanan berjalan dalam satu transaksi database. Bila ada bagian yang gagal, tidak ada satu pun baris maupun berkas foto yang tertinggal.',
            'auto_accept = true menjalankan persetujuan yang sama dengan tombol ACC di halaman admin, termasuk penyesuaian saldo armada atau sales. Pemakaian tanpa auto_accept menyisakan pembayaran berstatus pending untuk disetujui petugas.',
            'Pembayaran yang dibuat lewat API tidak memiliki pembuat internal (created_by kosong), karena tidak ada pengguna yang login. Jejaknya tersedia di Log API Eksternal, lengkap dengan aplikasi dan kunci yang memanggil.',
        ];
    }
}
