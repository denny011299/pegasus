<?php

namespace App\Synchronization\Flows;

use App\Synchronization\Steps\ProductFlow\FetchPmoDataStep;
use App\Synchronization\Steps\ProductFlow\PrepareProductStockStep;
use App\Synchronization\Steps\ProductFlow\ReportDifferenceStep;
use App\Synchronization\Steps\ProductFlow\SyncCategoryStep;
use App\Synchronization\Steps\ProductFlow\SyncProductRelationStep;
use App\Synchronization\Steps\ProductFlow\SyncProductStep;
use App\Synchronization\Steps\ProductFlow\SyncProductVariantStep;
use App\Synchronization\Steps\ProductFlow\SyncUnitStep;
use App\Synchronization\SyncFlow;
use App\Synchronization\SyncStep;

/**
 * Alur Sinkronisasi Produk (PMO → Pegasus).
 *
 * Rancangan lengkap: cdocs/specs/202607260130-product-sync-flow.design.md
 *
 * Urutan langkah diturunkan dari ketergantungan tabel yang benar-benar ada di
 * database Pegasus:
 *
 *   units ──┬──────────────────────────────────────────────┐
 *           │                                              │
 *   categories ──┐                                         │
 *                ▼                                         ▼
 *             products ──► product_variants ──┬──► product_relations
 *                                             └──► product_stocks
 *
 * Satu-satunya endpoint yang dipanggil adalah /getProducts, ditarik sekali
 * per sesi lalu dipakai bersama seluruh langkah (lihat PmoSnapshotStore).
 * PMO tidak menyediakan endpoint satuan terpisah — master satuan diturunkan
 * dari items[].units[] pada respons yang sama (lihat ProductFlowStep::units()).
 */
class ProductSyncFlow extends SyncFlow
{
    public function key(): string
    {
        return 'product';
    }

    public function title(): string
    {
        return 'Sinkronisasi Produk';
    }

    public function description(): string
    {
        return 'Menarik data master produk dari PMO ke Pegasus secara berurutan, mulai dari '
            .'satuan sampai penyiapan baris stok.';
    }

    public function purpose(): string
    {
        return 'Menyamakan data master produk di Pegasus dengan data di PMO, sehingga transaksi '
            .'pembelian, pengiriman, produksi, dan stok opname memakai produk, satuan, dan '
            .'konversi satuan yang sama persis dengan PMO.';
    }

    public function dataSynced(): array
    {
        return [
            'Satuan (units)',
            'Kategori produk (categories)',
            'Produk (products)',
            'Varian produk (product_variants)',
            'Konversi satuan produk (product_relations)',
            'Baris stok per satuan (product_stocks) — hanya barisnya, bukan angkanya',
        ];
    }

    public function whenToRun(): string
    {
        return 'Saat pertama kali menghubungkan Pegasus dengan PMO, dan setiap kali data master '
            .'produk di PMO berubah (satuan baru, kategori baru, produk baru, atau perubahan '
            .'konversi satuan).';
    }

    public function warnings(): array
    {
        return [
            'PMO adalah sumber kebenaran untuk data master produk. Nama, kategori, satuan, dan '
                .'status akan mengikuti PMO.',
            'Harga jual dan angka stok tidak pernah diubah oleh sinkronisasi — keduanya milik Pegasus.',
            'Data Pegasus yang tidak dikirim PMO tidak pernah dihapus atau dinonaktifkan otomatis. '
                .'Selisihnya dilaporkan pada langkah terakhir.',
            'Jalankan langkah sesuai urutan. Melewati satu langkah membuat langkah berikutnya gagal '
                .'karena data induknya belum ada.',
        ];
    }

    public function icon(): string
    {
        return 'package';
    }

    public function steps(): array
    {
        return [
            new SyncStep(
                key: 'fetch',
                title: 'Ambil & Periksa Data PMO',
                description: 'Menarik data produk dari PMO dan memeriksanya sebelum ada satu pun data yang ditulis.',
                dataSynced: 'Belum ada data yang ditulis. Data PMO disimpan sebagai acuan untuk langkah berikutnya.',
                why: 'Memastikan PMO terjangkau dan datanya memenuhi syarat minimum, sekaligus menampilkan '
                    .'kejanggalan lebih dulu. Data yang diambil di sini juga dipakai ulang oleh langkah '
                    .'berikutnya, sehingga PMO cukup dipanggil sekali.',
                dependents: 'Seluruh langkah selain Sinkronisasi Satuan dan Penyiapan Stok Produk.',
                expectation: 'Muncul jumlah produk, satuan, kategori, varian, dan konversi satuan yang dikirim PMO, '
                    .'berikut daftar data yang bermasalah bila ada.',
                prerequisites: [],
                handler: FetchPmoDataStep::class,
                notes: [
                    'Langkah ini aman dijalankan kapan saja — tidak mengubah data apa pun.',
                    'Jalankan ulang langkah ini bila ingin mengambil data terbaru dari PMO.',
                    '/getProducts berbasis halaman — langkah ini menariknya halaman demi halaman '
                        .'secara otomatis, progresnya terlihat berjalan di layar ini.',
                ],
                paginated: true,
            ),
            new SyncStep(
                key: 'unit',
                title: 'Sinkronisasi Satuan',
                description: 'Menurunkan master satuan dari daftar satuan tiap produk pada data PMO.',
                dataSynced: 'Nama satuan. Singkatan dan status aktif tidak pernah diubah — lihat catatan.',
                why: 'Satuan adalah fondasi seluruh data produk. Produk, varian produk, konversi satuan, '
                    .'dan stok semuanya menunjuk ke satuan.',
                dependents: 'Produk, Varian Produk, Konversi Satuan, dan Stok Produk.',
                expectation: 'Daftar satuan di menu Master → Satuan akan sesuai dengan satuan di PMO.',
                prerequisites: ['fetch'],
                handler: SyncUnitStep::class,
                notes: [
                    'PMO tidak menyediakan endpoint satuan terpisah — daftarnya diturunkan dari satuan '
                        .'yang dipakai tiap produk pada langkah "Ambil & Periksa Data PMO".',
                    'Pada sinkronisasi pertama, satuan Pegasus yang namanya sama akan dipakai ulang, '
                        .'bukan dibuat ganda.',
                    'Satuan dengan nama ganda di Pegasus dilaporkan gagal — rapikan dulu, lalu jalankan ulang.',
                    'PMO tidak mengirim singkatan maupun status aktif per satuan, jadi keduanya tidak '
                        .'pernah dinonaktifkan otomatis oleh sinkronisasi ini.',
                ],
            ),
            new SyncStep(
                key: 'category',
                title: 'Sinkronisasi Kategori',
                description: 'Menyiapkan kategori produk berdasarkan nama kategori yang dikirim PMO.',
                dataSynced: 'Nama kategori produk.',
                why: 'Setiap produk wajib memiliki kategori. Tanpa kategori, produk tidak bisa disimpan.',
                dependents: 'Produk, dan seluruh laporan yang dikelompokkan per kategori.',
                expectation: 'Semua kategori yang dipakai produk PMO tersedia di menu Master → Kategori.',
                prerequisites: ['fetch'],
                handler: SyncCategoryStep::class,
                notes: [
                    'PMO belum mengirimkan id kategori, jadi pencocokan memakai nama kategori.',
                ],
            ),
            new SyncStep(
                key: 'product',
                title: 'Sinkronisasi Produk',
                description: 'Menarik produk induk dari PMO beserta kategori dan daftar satuan yang dipakainya.',
                dataSynced: 'Nama produk, kategori, satuan dasar, daftar satuan yang dipakai, dan status aktif.',
                why: 'Produk induk menjadi wadah bagi varian produk, konversi satuan, dan stok.',
                dependents: 'Varian Produk, Konversi Satuan, Stok Produk, Resep (BOM), Produksi, Pembelian, '
                    .'dan Pengiriman.',
                expectation: 'Daftar produk di menu Produk → Daftar Produk akan sesuai dengan PMO, lengkap '
                    .'dengan kategori dan satuannya.',
                prerequisites: ['unit', 'category'],
                handler: SyncProductStep::class,
                notes: [
                    'Produk yang menunjuk ke kategori atau satuan yang belum ada dilaporkan gagal, '
                        .'bukan dipaksa masuk.',
                    'Produk Pegasus yang namanya ganda dilaporkan gagal — gabungkan dulu, lalu jalankan ulang.',
                ],
            ),
            new SyncStep(
                key: 'product_variant',
                title: 'Sinkronisasi Varian Produk',
                description: 'Menarik varian produk dari PMO beserta SKU, barcode, dan batas peringatan stoknya.',
                dataSynced: 'Nama varian, SKU, barcode, batas peringatan stok, dan satuan varian.',
                why: 'Semua transaksi Pegasus (pembelian, pengiriman, produksi, opname) mengacu ke varian '
                    .'produk, bukan ke produk induk.',
                dependents: 'Konversi Satuan dan Stok Produk.',
                expectation: 'Setiap produk memiliki varian yang sesuai dengan PMO, dan pemindaian barcode '
                    .'atau SKU menemukan varian yang benar.',
                prerequisites: ['product'],
                handler: SyncProductVariantStep::class,
                notes: [
                    'Harga jual dan angka stok tidak pernah ditulis — PMO tidak mengirimkannya.',
                    'SKU adalah kunci varian. SKU yang berubah di PMO dianggap varian baru; varian lama '
                        .'muncul pada Laporan Selisih.',
                ],
            ),
            new SyncStep(
                key: 'product_relation',
                title: 'Sinkronisasi Konversi Satuan',
                description: 'Menarik aturan konversi antar satuan untuk setiap varian produk '
                    .'(misalnya 1 dus = 24 pcs).',
                dataSynced: 'Pasangan satuan beserta nilai konversinya per varian produk.',
                why: 'Tanpa konversi satuan, stok tidak bisa dihitung ulang antar satuan dan angka stok '
                    .'pada satuan besar maupun kecil akan salah.',
                dependents: 'Stok Produk, perhitungan stok opname, mutasi stok, dan laporan stok.',
                expectation: 'Setiap varian produk memiliki aturan konversi satuan yang sesuai dengan PMO.',
                prerequisites: ['unit', 'product_variant'],
                handler: SyncProductRelationStep::class,
                notes: [
                    'Konversi satuan disimpan per varian produk, sehingga langkah ini dijalankan setelah '
                        .'varian produk.',
                    'PMO mengirim satu aturan konversi per varian. Aturan lain yang sudah ada di Pegasus '
                        .'dibiarkan apa adanya.',
                ],
            ),
            new SyncStep(
                key: 'product_stock',
                title: 'Penyiapan Stok Produk',
                description: 'Menyiapkan baris stok untuk setiap kombinasi varian dan satuan.',
                dataSynced: 'Baris stok per varian per satuan. Angka stoknya sendiri tidak diubah.',
                why: 'Layar stok, stok opname, dan peringatan stok membaca baris ini. Tanpa barisnya, '
                    .'produk hasil sinkronisasi tidak akan muncul dengan benar di layar stok.',
                dependents: 'Tidak ada langkah lain yang bergantung pada langkah ini.',
                expectation: 'Setiap varian punya baris stok untuk semua satuan yang dipakai produknya, '
                    .'dengan stok awal 0 untuk baris yang baru dibuat.',
                prerequisites: ['product_variant'],
                handler: PrepareProductStockStep::class,
                notes: [
                    'Langkah ini tidak memanggil PMO — PMO tidak mengirimkan angka stok.',
                    'Jalankan Sinkronisasi Konversi Satuan lebih dulu agar urutan satuan pada layar stok benar.',
                    'Angka stok yang sudah ada tidak pernah diubah.',
                ],
            ),
            new SyncStep(
                key: 'difference',
                title: 'Laporan Selisih Pegasus vs PMO',
                description: 'Menampilkan data Pegasus yang sudah tidak dikirim PMO, tanpa mengubah apa pun.',
                dataSynced: 'Tidak ada. Langkah ini hanya melaporkan.',
                why: 'Sinkronisasi sengaja tidak pernah menghapus atau menonaktifkan data Pegasus secara '
                    .'otomatis. Keputusan itu tetap di tangan operator.',
                dependents: 'Tidak ada.',
                expectation: 'Muncul daftar produk dan varian yang ada di Pegasus tetapi tidak ada pada data PMO.',
                prerequisites: ['product'],
                handler: ReportDifferenceStep::class,
                notes: [
                    'Aman dijalankan kapan saja — tidak mengubah data apa pun.',
                    'Varian yang SKU-nya diganti di PMO akan muncul di sini sebagai varian lama.',
                ],
            ),
        ];
    }
}
