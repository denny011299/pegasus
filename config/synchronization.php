<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sumber Data PMO
    |--------------------------------------------------------------------------
    |
    | Setiap request ke PMO mengirim header X-API-Key berisi PMO_API_KEY.
    | Header ini ditambahkan di App\Synchronization\Pmo\PmoClient.
    |
    */
    'pmo' => [
        'base_url' => env('PMO_BASE_URL', ''),
        'api_key' => env('PMO_API_KEY', ''),
        'timeout' => (int) env('PMO_TIMEOUT', 60),
        'connect_timeout' => (int) env('PMO_CONNECT_TIMEOUT', 10),
        'verify_ssl' => filter_var(env('PMO_VERIFY_SSL', true), FILTER_VALIDATE_BOOLEAN),

        // Batas aman saat PMO mengembalikan data terpaginasi.
        'max_pages' => (int) env('PMO_MAX_PAGES', 200),
    ],

    /*
    |--------------------------------------------------------------------------
    | Endpoint PMO
    |--------------------------------------------------------------------------
    |
    | Sesuai "Dokumen Teknis Integrasi API — Endpoint yang Disiapkan PMO untuk
    | IPM". Hanya base_url yang diambil dari .env; path endpoint ditetapkan di
    | sini karena sudah didefinisikan pada dokumen tersebut.
    |
    | Catatan: /getProducts memuat kategori, varian, dan konversi satuan
    | sekaligus, jadi hampir semua langkah alur Produk memakai endpoint itu.
    | Master satuan diambil dari endpoint terpisah (/getUnits) yang sedang
    | disiapkan PMO — format yang diminta ada di
    | cdocs/specs/202607260130-product-sync-flow.design.md (§8.1).
    |
    */
    'endpoints' => [
        'products' => '/getProducts',
        'units' => '/getUnits',
        'shipments' => '/getShipments',
        'armada' => '/getArmada',
    ],

    /*
    |--------------------------------------------------------------------------
    | Umur Potret Data PMO
    |--------------------------------------------------------------------------
    |
    | Satu endpoint hanya ditarik sekali per sesi sinkronisasi lalu dipakai
    | bersama seluruh langkah. Lewat batas menit ini, wizard memberi peringatan
    | bahwa datanya sudah lama — potret tidak pernah kedaluwarsa sendiri,
    | penyegaran tetap keputusan operator lewat langkah "Ambil & Periksa".
    |
    */
    'snapshot_stale_after_minutes' => (int) env('PMO_SNAPSHOT_STALE_MINUTES', 1440),

    /*
    |--------------------------------------------------------------------------
    | Daftar Alur Sinkronisasi
    |--------------------------------------------------------------------------
    |
    | Alur bersifat statis dan didefinisikan di kode. Untuk menambah alur baru
    | (Customer, Vendor, Warehouse, ...), buat kelas turunan SyncFlow lalu
    | daftarkan di sini. Tidak ada perubahan lain yang dibutuhkan.
    |
    */
    'flows' => [
        \App\Synchronization\Flows\ProductSyncFlow::class,
    ],
];
