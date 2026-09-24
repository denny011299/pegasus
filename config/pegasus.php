<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Toggle fitur internal Pegasus
    |--------------------------------------------------------------------------
    |
    | Saklar tunggal yang dipakai bersama oleh blade DAN controller — jangan sembunyikan sesuatu
    | hanya di satu sisi, keduanya wajib membaca flag yang sama.
    */

    /**
     * Insert Pengiriman manual dari IPM (tombol "Tambah Pengiriman" + modal
     * #add_sales_order, POST /insertSalesOrder). Default OFF (keputusan PM, 2026-09) — Pengiriman
     * baru hanya boleh datang dari PMO lewat POST /shipments/shipped; set
     * PEGASUS_SHIPMENT_INTERNAL_INSERT_ENABLED=true di .env untuk mengaktifkan lagi tombolnya.
     */
    'shipment_internal_insert_enabled' => filter_var(
        env('PEGASUS_SHIPMENT_INTERNAL_INSERT_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),
];
