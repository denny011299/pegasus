<?php

namespace App\Synchronization\Flows;

use App\Synchronization\Steps\ShipmentFlow\FetchShipmentsStep;
use App\Synchronization\Steps\ShipmentFlow\SyncShipmentsStep;
use App\Synchronization\SyncFlow;
use App\Synchronization\SyncStep;

/**
 * Alur Sinkronisasi Pengiriman (PMO → Pegasus).
 *
 * Contoh response GET /getShipments dikonfirmasi 2026-08-21 (percakapan
 * langsung dengan pemilik produk — belum ada design doc terpisah seperti
 * alur Produk, cdocs/integrations/202607260130-product-sync-flow.design.md
 * §10 baru mengonfirmasi kontrak query-nya lebih dulu, date_start/date_end/
 * page).
 *
 * Rancangan cuma 2 langkah (disepakati 2026-08-20, beda dengan alur Produk
 * yang 8 langkah): /getShipments hanya berujung ke satu entitas Pegasus
 * (rekonsiliasi sales_orders/sales_delivery_orders lewat ref_shipment_id,
 * bukan mengisi beberapa tabel master berjenjang seperti Produk), jadi
 * "ambil" dan "sinkronkan" cukup dua langkah berurutan — pola yang sama
 * dipakai tiap langkah tunggal pada alur Produk (mis. SyncUnitStep:
 * cocokkan + tulis + laporkan sekaligus dalam satu langkah).
 *
 * Beda penting dari alur Produk: /getShipments TIDAK punya mode "ambil
 * semua tanpa filter" — date_start/date_end wajib, dipilih operator lewat
 * input di wizard (lihat SyncStep::$queryFields pada langkah "fetch" di
 * bawah). Karena itu langkah ini TIDAK "aman dijalankan kapan saja tanpa
 * berpikir" seperti fetch Produk — rentang tanggalnya keputusan operator.
 *
 * Tiga field dari PMO SENGAJA belum ditulis ke Pegasus (armada_id,
 * bukti_foto, status) — dilaporkan sebagai catatan saja. Alasan lengkap
 * per field ada di docblock App\Synchronization\Steps\ShipmentFlow\SyncShipmentsStep.
 */
class ShipmentSyncFlow extends SyncFlow
{
    public function key(): string
    {
        return 'shipment';
    }

    public function title(): string
    {
        return 'Sinkronisasi Pengiriman';
    }

    public function description(): string
    {
        return 'Menarik data pengiriman dari PMO pada rentang tanggal tertentu, lalu merekonsiliasinya '
            .'dengan data pengiriman di Pegasus.';
    }

    public function purpose(): string
    {
        return 'Menyamakan data pengiriman di Pegasus dengan yang tercatat di PMO, sebagai pemeriksaan '
            .'silang atas pengiriman yang dibuat lewat Platform API Eksternal '
            .'(POST /shipments/scheduled, /shipments/shipped, dst).';
    }

    public function dataSynced(): array
    {
        return [
            'Baris pengiriman per sales_orders yang cocok (sales_delivery_orders/sales_delivery_orders_details)',
            'Baris produk per pengiriman, dicocokkan lewat SKU varian dan satuan (ref_unit_id)',
        ];
    }

    public function whenToRun(): string
    {
        return 'Secara berkala, untuk memeriksa pengiriman yang statusnya berubah di PMO pada rentang '
            .'tanggal tertentu — mis. setiap hari untuk rentang "kemarin".';
    }

    public function warnings(): array
    {
        return [
            '/getShipments mewajibkan rentang tanggal (date_start/date_end) pada setiap halaman — '
                .'tidak ada mode "ambil semua". Pilih rentangnya dengan sadar, bukan asal isi.',
            'Alur ini merekonsiliasi pengiriman yang SUDAH ada di Pegasus (dibuat lewat Platform API '
                .'Eksternal, ref_shipment_id) — tidak pernah membuat sales_orders baru dari sini. '
                .'PMO shipment tanpa sales_orders yang cocok dilaporkan gagal, bukan diadopsi.',
            'armada_id, bukti_foto, dan status dari PMO belum ditulis ke Pegasus (belum ada tempat/'
                .'kepastian bentuknya) — hanya dilaporkan sebagai catatan. Lihat docblock SyncShipmentsStep.',
            'Menjalankan ulang untuk shipment yang sama MENGGANTI seluruh baris detail pengirimannya '
                .'(dihapus lalu ditulis ulang dari PMO), bukan menambah baris baru.',
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
                title: 'Ambil Data Pengiriman PMO',
                description: 'Menarik data pengiriman dari PMO pada rentang tanggal yang dipilih, sebelum '
                    .'ada satu pun data yang ditulis.',
                dataSynced: 'Belum ada data yang ditulis. Data PMO disimpan sebagai acuan untuk langkah berikutnya.',
                why: 'Memastikan PMO terjangkau dan datanya bisa diambil pada rentang tanggal yang dipilih, '
                    .'sebelum langkah "Sinkronisasi Pengiriman" membacanya.',
                dependents: 'Sinkronisasi Pengiriman.',
                expectation: 'Muncul jumlah baris pengiriman yang dikirim PMO pada rentang tanggal tersebut.',
                prerequisites: [],
                handler: FetchShipmentsStep::class,
                notes: [
                    'Wajib mengisi Tanggal Mulai dan Tanggal Akhir — /getShipments tidak punya mode '
                        .'"ambil semua tanpa filter" seperti alur Produk.',
                    'Jalankan ulang dengan rentang berbeda untuk mengambil data pengiriman lain — setiap '
                        .'jalan menimpa potret sebelumnya untuk alur ini.',
                ],
                paginated: true,
                queryFields: [
                    ['key' => 'date_start', 'label' => 'Tanggal Mulai', 'type' => 'date', 'required' => true],
                    ['key' => 'date_end', 'label' => 'Tanggal Akhir', 'type' => 'date', 'required' => true],
                ],
            ),
            new SyncStep(
                key: 'sync',
                title: 'Sinkronisasi Pengiriman',
                description: 'Mencocokkan data pengiriman PMO dengan sales_orders di Pegasus lewat '
                    .'ref_shipment_id, lalu menuliskan baris pengiriman dan produknya.',
                dataSynced: 'Baris sales_delivery_orders + sales_delivery_orders_details per shipment yang '
                    .'cocok. Lihat peringatan alur ini untuk field yang belum ditulis.',
                why: 'Pengiriman yang dibuat lewat Platform API Eksternal seharusnya tercermin sama '
                    .'persis antara PMO dan Pegasus, termasuk rincian produk yang benar-benar terkirim.',
                dependents: 'Tidak ada.',
                expectation: 'sales_orders yang ref_shipment_id-nya cocok akan punya baris pengiriman '
                    .'(Sales → Pengiriman) sesuai data PMO pada rentang tanggal yang diambil.',
                prerequisites: ['fetch'],
                handler: SyncShipmentsStep::class,
                notes: [
                    'variant_sku yang tidak ditemukan di Pegasus, atau ambigu (dipakai lebih dari satu '
                        .'varian aktif), dilaporkan gagal per baris item — baris item lain pada shipment '
                        .'yang sama tetap disinkronkan.',
                    'unit_id PMO yang belum ada di units.ref_unit_id dilaporkan gagal — jalankan '
                        .'Sinkronisasi Satuan pada alur Produk lebih dulu.',
                    'sdo_receiver diisi dari so_customer pada sales_orders yang cocok, sdo_phone dikosongkan '
                        .'— PMO tidak mengirim nama/telepon penerima.',
                ],
            ),
        ];
    }
}
