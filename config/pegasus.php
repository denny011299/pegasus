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
     * Blokir insert Pengiriman manual dari IPM (tombol "Tambah Pengiriman" + modal
     * #add_sales_order di Sales_Order.blade.php, POST /insertSalesOrder di CustomerController).
     * DIMATIKAN sejak 2026-09 (keputusan PM, lihat
     * cdocs/docs/specs/shipment-external-api-approval-flow.md §4.5) — Pengiriman baru hanya
     * boleh datang dari POST /api/external/v1/shipments/shipped (PMO). Set true lewat
     * .env (PEGASUS_SHIPMENT_INTERNAL_INSERT_ENABLED=true) untuk membuka lagi.
     */
    'shipment_internal_insert_enabled' => filter_var(
        env('PEGASUS_SHIPMENT_INTERNAL_INSERT_ENABLED', false),
        FILTER_VALIDATE_BOOLEAN
    ),
];
