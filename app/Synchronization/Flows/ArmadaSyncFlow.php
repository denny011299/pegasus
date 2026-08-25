<?php

namespace App\Synchronization\Flows;

use App\Synchronization\Steps\ArmadaFlow\FetchArmadaStep;
use App\Synchronization\Steps\ArmadaFlow\ResolveArmadaConflictsStep;
use App\Synchronization\Steps\ArmadaFlow\SyncArmadaStep;
use App\Synchronization\SyncFlow;
use App\Synchronization\SyncStep;

/**
 * Alur Sinkronisasi Armada (PMO → Pegasus).
 *
 * Contoh response GET /getArmada dikonfirmasi 2026-08-22 (flowchart "API
 * Armada" + getArmada.example.json langsung dari pemilik produk — belum ada
 * design doc terpisah seperti alur Produk).
 *
 * Endpoint ini TIDAK mewajibkan parameter query apa pun selain "page" —
 * beda dengan /getShipments, sama seperti /getProducts — jadi langkah
 * "Ambil Data Armada" TIDAK perlu SyncStep::$queryFields.
 *
 * "Armada" bukan tabel tersendiri — tersimpan sebagai baris `customers`,
 * kolom yang SAMA dengan yang sudah dipakai
 * App\Http\Controllers\ExternalApi\V1\MasterArmadaController (disepakati
 * 2026-08-22: satu pool data, bukan dipisahkan). Rujukan PMO-nya sendiri
 * (`customers.ref_armada_id`) sengaja terpisah dari `customer_code` (id
 * universal milik modul External API itu, sumbernya beda).
 *
 * TIGA langkah, bukan dua seperti alur Pengiriman: flowchart cuma
 * mendefinisikan "ambil" dan "cocokkan/insert", tapi PMO mengirim armada_id
 * yang stabil sehingga langkah 2 dua-fase (id dulu, No Pol+PIC kalau belum
 * tersambung — lihat docblock SyncArmadaStep) dan baris yang cocok ke LEBIH
 * DARI SATU pelanggan sekaligus butuh keputusan manusia, bukan ditebak —
 * karena itu ada langkah 3 (ResolveArmadaConflictsStep), sebuah antrean
 * konfirmasi yang tampil langsung di wizard, bukan halaman terpisah.
 */
class ArmadaSyncFlow extends SyncFlow
{
    public function key(): string
    {
        return 'armada';
    }

    public function title(): string
    {
        return 'Sinkronisasi Armada';
    }

    public function description(): string
    {
        return 'Menarik data armada (kendaraan) dari PMO, mencocokkannya dengan pelanggan/armada yang '
            .'sudah ada di Pegasus, lalu menuliskan yang baru.';
    }

    public function purpose(): string
    {
        return 'Menyamakan data armada di Pegasus dengan data di PMO — nama PIC, No Pol, telepon, dan '
            .'saldo armada mengikuti PMO begitu tersambung.';
    }

    public function dataSynced(): array
    {
        return [
            'Armada (tersimpan sebagai baris customers): PIC, No Pol, telepon, saldo',
        ];
    }

    public function whenToRun(): string
    {
        return 'Saat pertama kali menghubungkan Pegasus dengan PMO, dan setiap kali data armada di PMO '
            .'berubah (armada baru, PIC/No Pol berganti, saldo berubah).';
    }

    public function warnings(): array
    {
        return [
            'PMO adalah sumber kebenaran begitu satu armada tersambung (ref_armada_id) — PIC, No Pol, '
                .'telepon, dan saldo akan mengikuti PMO pada setiap sinkronisasi berikutnya.',
            '"Armada" tersimpan sebagai baris pada tabel customers yang sama dipakai modul admin '
                .'Pelanggan dan Platform API Eksternal (/api/external/v1/armada) — satu pool data, '
                .'bukan dipisahkan.',
            'Baris yang No Pol + PIC-nya cocok ke lebih dari satu pelanggan/armada Pegasus sekaligus '
                .'TIDAK ditebak — diantrekan ke langkah "Konfirmasi Armada Ambigu" untuk diputuskan manusia.',
            'Data Pegasus yang tidak dikirim PMO tidak pernah dihapus atau dinonaktifkan otomatis.',
        ];
    }

    public function icon(): string
    {
        return 'truck';
    }

    public function steps(): array
    {
        return [
            new SyncStep(
                key: 'fetch',
                title: 'Ambil & Periksa Data Armada PMO',
                description: 'Menarik data armada dari PMO dan memeriksanya sebelum ada satu pun data yang ditulis.',
                dataSynced: 'Belum ada data yang ditulis. Data PMO disimpan sebagai acuan untuk langkah berikutnya.',
                why: 'Memastikan PMO terjangkau dan datanya memenuhi syarat minimum, sekaligus menampilkan '
                    .'kejanggalan lebih dulu.',
                dependents: 'Sinkronisasi Armada.',
                expectation: 'Muncul jumlah armada yang dikirim PMO, berikut daftar baris bermasalah bila ada.',
                prerequisites: [],
                handler: FetchArmadaStep::class,
                notes: [
                    'Langkah ini aman dijalankan kapan saja — tidak mengubah data apa pun.',
                    'Jalankan ulang langkah ini bila ingin mengambil data terbaru dari PMO.',
                    '/getArmada berbasis halaman — langkah ini menariknya halaman demi halaman secara '
                        .'otomatis, progresnya terlihat berjalan di layar ini.',
                ],
                paginated: true,
            ),
            new SyncStep(
                key: 'sync',
                title: 'Sinkronisasi Armada',
                description: 'Mencocokkan armada PMO dengan pelanggan/armada di Pegasus lewat armada_id, '
                    .'lalu No Pol + PIC untuk armada yang belum tersambung.',
                dataSynced: 'PIC, No Pol, telepon, dan saldo pada baris customers yang cocok atau baru dibuat.',
                why: 'Data armada yang dipakai transaksi (pengiriman, pembayaran) seharusnya sama persis '
                    .'antara PMO dan Pegasus.',
                dependents: 'Konfirmasi Armada Ambigu (untuk baris yang cocok ganda).',
                expectation: 'Setiap armada PMO tersambung ke satu baris customers — baru atau yang sudah ada.',
                prerequisites: ['fetch'],
                handler: SyncArmadaStep::class,
                notes: [
                    'Armada yang armada_id-nya sudah tersambung sebelumnya selalu diperbarui langsung — '
                        .'No Pol/PIC tidak lagi dicek untuk baris ini.',
                    'Armada baru (belum tersambung) yang No Pol + PIC-nya cocok ke tepat satu pelanggan/'
                        .'armada Pegasus akan diadopsi ke baris itu, bukan dibuat ganda.',
                    'Armada yang cocok ke lebih dari satu pelanggan/armada sekaligus TIDAK ditebak — lihat '
                        .'langkah berikutnya.',
                ],
            ),
            new SyncStep(
                key: 'resolve',
                title: 'Konfirmasi Armada Ambigu',
                description: 'Menyambungkan atau mengabaikan armada PMO yang cocok ke lebih dari satu '
                    .'pelanggan/armada Pegasus sekaligus.',
                dataSynced: 'PIC, No Pol, telepon, saldo, dan armada_id pada baris customers yang dipilih '
                    .'operator untuk disambungkan.',
                why: 'No Pol + PIC yang sama pada lebih dari satu baris Pegasus tidak bisa dipilih otomatis '
                    .'tanpa risiko menyambungkan ke baris yang salah — keputusan tetap di tangan operator.',
                dependents: 'Tidak ada.',
                expectation: 'Daftar di bawah kosong setelah seluruh baris diputuskan — dibiarkan menggantung '
                    .'juga sah, tidak memblokir apa pun.',
                prerequisites: ['sync'],
                handler: ResolveArmadaConflictsStep::class,
                notes: [
                    'Menyambungkan (Hubungkan) MENIMPA data pelanggan/armada yang dipilih dengan data PMO.',
                    'Mengabaikan (Abaikan) bersifat permanen — armada itu tidak akan diantrekan ulang pada '
                        .'sinkronisasi berikutnya.',
                    'Tidak melakukan apa pun pada satu baris berarti membiarkannya menggantung — baris itu '
                        .'tetap ada di sini sampai benar-benar diselesaikan.',
                ],
                reviewQueue: true,
            ),
        ];
    }
}
