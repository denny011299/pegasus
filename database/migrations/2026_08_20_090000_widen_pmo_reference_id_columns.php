<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ref_unit_id (units) dan ref_product_id (products) dari INT ke BIGINT
 * UNSIGNED.
 *
 * Kedua kolom menyimpan id PMO apa adanya (bukan diterjemahkan ke id lokal),
 * dan id PMO nyatanya 16 digit — mis. unit_id 9506012026014615,
 * ref_product_id 4328012026102327 — jauh melewati batas INT (~2,1 miliar,
 * 10 digit). Begitu PMO benar-benar dipakai, Sinkronisasi Satuan
 * (SyncUnitStep) crash "SQLSTATE[22003]: Numeric value out of range... Out
 * of range value for column 'ref_unit_id'"; Sinkronisasi Produk
 * (SyncProductStep) akan crash sama persis begitu jalan (ref_product_id
 * polanya identik, cuma belum sempat dicoba).
 *
 * Raw DB::statement (bukan ->change()) sama seperti migrasi
 * 2026_08_17_090100_make_customer_return_details_warehouse_id_nullable —
 * doctrine/dbal tidak terpasang.
 *
 * down() melebar→sempit: kalau sudah ada baris dengan id PMO asli
 * tersimpan (16 digit), rollback ini akan gagal/terpotong pada MODIFY-nya —
 * itu risiko yang melekat pada penyempitan tipe kolom, bukan bug migrasi
 * ini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('units')) {
            DB::statement(
                "ALTER TABLE `units` MODIFY `ref_unit_id` BIGINT UNSIGNED NULL COMMENT 'unit_id pada sistem PMO'"
            );
        }

        if (Schema::hasTable('products')) {
            DB::statement(
                "ALTER TABLE `products` MODIFY `ref_product_id` BIGINT UNSIGNED NULL COMMENT 'ref_product_id pada sistem PMO'"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('units')) {
            DB::statement(
                "ALTER TABLE `units` MODIFY `ref_unit_id` INT NULL COMMENT 'unit_id pada sistem PMO'"
            );
        }

        if (Schema::hasTable('products')) {
            DB::statement(
                "ALTER TABLE `products` MODIFY `ref_product_id` INT NULL COMMENT 'ref_product_id pada sistem PMO'"
            );
        }
    }
};
