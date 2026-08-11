<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Awalan Alamat External API
    |--------------------------------------------------------------------------
    |
    | Segmen alamat di depan versi. Alamat akhir sebuah endpoint berbentuk:
    |
    |     /{base_path}/{versi}/{endpoint}
    |     /api/external/v1/products
    |
    | Ini SATU-SATUNYA tempat awalan itu ditentukan. Rute, penjaga penanganan
    | error, Base URL di halaman dokumentasi, dan alamat contoh pada tiap
    | dokumentasi endpoint semuanya membacanya dari sini — jadi mengubah nilai
    | ini memindahkan seluruh External API sekaligus, tanpa ada alamat yang
    | tertinggal di tempat lain.
    |
    | Dipisah dari rute internal (/api/internal/... bila suatu saat dibuat)
    | supaya jelas mana permukaan yang menjadi kontrak dengan pihak ketiga.
    |
    | CATATAN: mengubah nilai ini memutus seluruh klien yang sudah berjalan.
    | Untuk perubahan yang tidak merusak, tambah versi baru — jangan pindahkan
    | awalannya.
    |
    */
    'base_path' => 'api/external',

    /*
    |--------------------------------------------------------------------------
    | Versi API
    |--------------------------------------------------------------------------
    |
    | Versi baru cukup ditambahkan ke daftar ini lalu dibuatkan file rute
    | routes/external-api/{versi}.php — tidak ada perubahan lain yang perlu
    | dilakukan. Versi lama tetap bisa jalan berdampingan dengan versi baru.
    |
    | 'default' hanya dipakai dokumentasi untuk menampilkan Base URL utama.
    |
    */
    'versions' => ['v1'],
    'default_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | Format API Key
    |--------------------------------------------------------------------------
    |
    | Kunci berbentuk: {prefix}_{environment}_{selector}{secret}
    | Contoh          : ext_live_a1b2c3d4e5f6...  (total ~48 karakter)
    |
    | `selector_length` karakter pertama sesudah label environment dipakai
    | sebagai penanda pencarian di database (kolom key_prefix), sisanya rahasia.
    | Pemisahan ini membuat autentikasi cukup satu query terindeks, tanpa perlu
    | memindai seluruh tabel kunci.
    |
    | `header` adalah nama header tempat klien mengirim kunci.
    |
    */
    'key' => [
        'prefix' => 'ext',
        'selector_length' => 12,
        'secret_length' => 32,
        'header' => 'X-API-Key',

        // Label environment yang muncul di dalam kunci itu sendiri.
        'environments' => [
            'production' => 'live',
            'staging' => 'stg',
            'development' => 'dev',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pencatatan Permintaan
    |--------------------------------------------------------------------------
    |
    | Saklar utama ada di tabel `settings` (kunci di bawah) supaya bisa diubah
    | admin lewat UI tanpa deploy ulang. Nilai di sini hanya default awal saat
    | barisnya belum pernah dibuat.
    |
    | Pencatatan berjalan di tahap terminable middleware — respons sudah
    | terkirim ke klien sebelum log ditulis, jadi tidak menambah waktu tunggu
    | dan tidak butuh queue worker yang harus dijaga hidup. Kalau nanti volume
    | permintaan menuntut, penulisan tinggal dipindah ke queue job dengan
    | mengganti isi App\ExternalApi\Logging\RequestLogger saja.
    |
    */
    'logging' => [
        'setting_key' => 'external_api_logging_enabled',
        'enabled_by_default' => true,

        // Perkiraan ukuran satu baris log, dipakai untuk estimasi penyimpanan
        // di halaman administrasi. Angka kasar, bukan hasil hitung database.
        'estimated_row_bytes' => 512,

        // Batas panjang agar user agent yang sangat panjang tidak memotong
        // baris di tengah karakter multibyte.
        'max_user_agent_length' => 255,
    ],

    /*
    |--------------------------------------------------------------------------
    | Strategi Pembersihan Log
    |--------------------------------------------------------------------------
    |
    | Pilihan yang muncul di halaman Log API Eksternal. Menambah cara baru
    | cukup menambah satu baris di sini — UI dan endpoint pembersihan ikut
    | menyesuaikan sendiri.
    |
    | 'days' => angka : hapus log yang lebih tua dari sekian hari
    | 'days' => 0     : hapus seluruh log
    | 'days' => null  : admin memilih sendiri tanggal & waktunya
    |
    */
    'log_cleanup' => [
        'before_date' => ['label' => 'Sebelum Tanggal & Waktu Tertentu', 'days' => null],
        'older_than_30' => ['label' => 'Lebih Lama dari 30 Hari', 'days' => 30],
        'older_than_90' => ['label' => 'Lebih Lama dari 90 Hari', 'days' => 90],
        'all' => ['label' => 'Hapus Seluruh Log', 'days' => 0],
    ],

    /*
    |--------------------------------------------------------------------------
    | Daftar Dokumentasi Endpoint
    |--------------------------------------------------------------------------
    |
    | Satu endpoint = satu kelas turunan App\ExternalApi\Docs\ApiEndpointDoc
    | yang didaftarkan di sini. Halaman Dokumentasi API Eksternal dibangun
    | sepenuhnya dari daftar ini, jadi tidak ada halaman HTML yang perlu
    | ditulis manual saat API baru ditambahkan.
    |
    | Fase 1 belum memuat endpoint bisnis apa pun, sehingga daftar ini sengaja
    | dibiarkan kosong. Cara mengisinya ada di
    | .claude/skills/external-api-endpoint/SKILL.md.
    |
    */
    'docs' => [
        // API-001 — Data Master
        \App\ExternalApi\Docs\Endpoints\V1\MasterUnitListDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterUnitCreateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterUnitUpdateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterUnitDeleteDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterUnitLinkDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterCashCategoryListDoc::class,

        // API-002 — Data Master (batch 2)
        \App\ExternalApi\Docs\Endpoints\V1\MasterWarehouseListDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterWarehouseCreateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterWarehouseUpdateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterWarehouseDeleteDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterWarehouseTypeListDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterSalesListDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterSalesCreateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterSalesUpdateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterSalesDeleteDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterSalesLinkDoc::class,

        // API-005 — Pembayaran Kas
        \App\ExternalApi\Docs\Endpoints\V1\CashPaymentCreateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\CashPaymentShowDoc::class,

        // Data Armada — modul tersendiri, bukan bagian Data Master. "Armada"
        // memakai tabel customers yang sama dengan pelanggan biasa, tapi
        // dilayani lewat rute dan halaman dokumentasi sendiri karena
        // konsepnya beda dari data master yang statis (satuan, gudang, dst.)
        \App\ExternalApi\Docs\Endpoints\V1\MasterArmadaListDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterArmadaCreateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterArmadaUpdateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterArmadaDeleteDoc::class,

        // Data Produk — modul tersendiri, sama seperti Data Armada. Beda
        // dengan Armada: produk punya endpoint connect, karena
        // products.ref_product_id nullable dan sering kosong (sama seperti
        // units.ref_unit_id), bukan selalu terisi seperti customer_code.
        \App\ExternalApi\Docs\Endpoints\V1\MasterProductListDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterProductCreateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterProductUpdateDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterProductDeleteDoc::class,
        \App\ExternalApi\Docs\Endpoints\V1\MasterProductLinkDoc::class,

        // Stok — modul baru, hanya baca (tidak ada create/update/delete).
        \App\ExternalApi\Docs\Endpoints\V1\StockCheckDoc::class,

        // Shipment — modul baru. Baru /shipments/scheduled; /shipments/shipped,
        // /shipments/status, /shipments/cancel, GET /shipments/{ref_shipment_id} menyusul
        // terpisah (lihat API Contract v1 di "private docs/Open API/").
        \App\ExternalApi\Docs\Endpoints\V1\ShipmentScheduledDoc::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Kelompok Dokumentasi
    |--------------------------------------------------------------------------
    |
    | Urutan kelompok pada daftar isi dokumentasi. Endpoint yang memakai
    | kelompok di luar daftar ini tetap tampil, diletakkan di bagian bawah.
    |
    */
    'doc_groups' => [
        'master' => 'Data Master',
        'armada' => 'Data Armada',
        'pembayaran' => 'Pembayaran',
        'produk' => 'Data Produk',
        'stok' => 'Stok',
        'pengiriman' => 'Pengiriman',
        'pesanan' => 'Pesanan',
        'pelanggan' => 'Pelanggan',
    ],
];
