<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * customers.customer_code (dipakai sebagai id universal Data Armada External
 * API, lihat migrasi 2026_08_11_120000_add_unique_index_to_customers_customer_code)
 * dari VARCHAR(10) ke VARCHAR(64).
 *
 * GitHub #171 (PMO): oms_vehicle.kode pada sistem PMO adalah varchar(64) dan
 * tidak dibatasi 10 karakter di sisi PMO — kode armada PMO saat ini (mis.
 * "DOUBLEAG", "DOUBLEPIR") kebetulan muat di 10 karakter, tapi tidak ada
 * jaminan itu akan selalu begitu. Begitu admin PMO memasukkan kode lebih
 * panjang, POST/PUT /api/external/v1/armada akan menolaknya
 * (validation_failed dari 'max:10' — lihat
 * MasterArmadaController::validateCreatePayload()) atau memotongnya diam-diam
 * kalau validasinya lewat. Pola sama dengan pelebaran ref_unit_id/
 * ref_product_id pada migrasi 2026_08_20_090000_widen_pmo_reference_id_columns.
 *
 * Raw DB::statement (bukan ->change()) sama seperti migrasi itu — doctrine/dbal
 * tidak terpasang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers')) {
            DB::statement(
                "ALTER TABLE `customers` MODIFY `customer_code` VARCHAR(64) NULL"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers')) {
            DB::statement(
                "ALTER TABLE `customers` MODIFY `customer_code` VARCHAR(10) NULL"
            );
        }
    }
};
