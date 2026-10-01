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
            .'beserta rinciannya. Bersifat idempoten: mengirim ulang permintaan dengan '
            .'ref_payment_id yang sama tidak membuat transaksi kedua.';
    }

    public function bodyParameters(): array
    {
        return [
            ['name' => 'ref_payment_id', 'type' => 'string', 'required' => true,
                'description' => 'Penanda unik milik sistem pemanggil. Dipakai sebagai kunci idempotensi.'],
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
            ['name' => 'photos', 'type' => 'array', 'required' => false,
                'description' => 'Bukti transaksi sebagai data URI base64. Hanya PNG dan JPEG.'],
            ['name' => 'auto_accept', 'type' => 'boolean', 'required' => false,
                'description' => 'Bila true, pembayaran langsung disetujui dan saldo armada/sales ikut disesuaikan.'],
        ];
    }

    public function requestExample(): ?array
    {
        return [
            'ref_payment_id' => 'PMO-2026-000123',
            'payment_type' => 1,
            'armada_code' => 'ARM-JKT-001',
            'payment_date' => '2026-07-29',
            'payment_amount' => 150000,
            'auto_accept' => false,
            'items' => [
                ['amount' => 100000, 'type' => 2, 'notes' => 'BBM'],
                ['amount' => 50000, 'type' => 2, 'notes' => 'Uang makan'],
            ],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                'ref_payment_id' => 'PMO-2026-000123',
                'payment_id' => 512,
                'payment_type' => 1,
                'payment_date' => '2026-07-29',
                'payment_amount' => 150000,
                'notes' => 'Pengeluaran armada W 9518 PG (Agus)',
                'armada_code' => 'ARM-JKT-001',
                'armada_id' => 4,
                'staff_id' => null,
                'status' => 'pending',
                'items' => [
                    ['amount' => 100000, 'notes' => 'BBM', 'type' => 2],
                    ['amount' => 50000, 'notes' => 'Uang makan', 'type' => 2],
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
            'Idempotensi: bila ref_payment_id sudah pernah dipakai, permintaan dianggap kiriman ulang. Pembayaran yang lama dikembalikan apa adanya dengan meta.idempotent_replay bernilai true, dan tidak ada transaksi baru yang dibuat. Isi permintaan tidak dibandingkan — ref_payment_id yang menentukan.',
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
