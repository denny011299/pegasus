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
    | Mau memanggil salah satu endpoint ini langsung (di luar alur
    | sinkronisasi)? Pakai App\Synchronization\Pmo\PmoApi, jangan panggil
    | PmoClient langsung — satu method per endpoint di sana, jauh lebih
    | mudah dibaca (mis. PmoApi::getProducts(), PmoApi::getProduct($id)).
    |
    | Hanya 3 endpoint yang benar-benar disediakan PMO (dikonfirmasi
    | 2026-08-20): products, shipments, armada. PMO TIDAK menyediakan
    | /getUnits — master satuan Pegasus diturunkan dari items[].units[] pada
    | /getProducts, bukan dipanggil terpisah. Lihat
    | App\Synchronization\Steps\ProductFlow\ProductFlowStep::units() dan
    | cdocs/integrations/202607260130-product-sync-flow.design.md (§8.1).
    |
    */
    'endpoints' => [
        'products' => '/getProducts',
        'shipments' => '/getShipments',
        'armada' => '/getArmada',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kunci Pembungkus Daftar Baris (per endpoint)
    |--------------------------------------------------------------------------
    |
    | Default "items" untuk endpoint yang tidak disebut di sini. Dikonfirmasi
    | 2026-08-22: /getArmada membalas "data", bukan "items", beda dari
    | /getProducts dan /getShipments. Lihat App\Synchronization\Pmo\PmoEndpoints::itemsKey().
    |
    */
    'endpoint_items_keys' => [
        'armada' => 'data',
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
        \App\Synchronization\Flows\ShipmentSyncFlow::class,
        \App\Synchronization\Flows\ArmadaSyncFlow::class,
    ],
];
