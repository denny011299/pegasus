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
     * #add_sales_order, POST /insertSalesOrder). Default ON agar tombol tampil;
     * set PEGASUS_SHIPMENT_INTERNAL_INSERT_ENABLED=false di .env untuk blokir
     * (hanya terima dari PMO /shipments/shipped).
     */
    'shipment_internal_insert_enabled' => filter_var(
        env('PEGASUS_SHIPMENT_INTERNAL_INSERT_ENABLED', true),
        FILTER_VALIDATE_BOOLEAN
    ),
];
