<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * warehouse_id jadi nullable di kedua tabel detail pengembalian — dibutuhkan
 * App\Http\Controllers\ExternalApi\V1\ShipmentReturnController::store() (endpoint baru
 * POST /shipments/returns, GitHub #58): PMO memicu pengembalian tanpa pernah mengirim
 * gudang tujuan (modul Gudang/Warehouse masih WIP fase 2 — DIKONFIRMASI pemilik produk lewat
 * WhatsApp: "diperbolehkan skip auto insert ke gudang/warehouse"). Baris dibuat dengan
 * warehouse_id kosong, staf gudang mengisinya lewat halaman admin Pengiriman > Pengembalian
 * sebelum ACC — App\Http\Controllers\CustomerReturnController::validateSupplyDetails()/
 * validateProductDetails() tetap mewajibkan warehouse_id valid sebelum accept() memotong stok,
 * jadi baris berwarehouse kosong TIDAK bisa langsung di-ACC.
 *
 * Alur admin (CustomerReturnController::store()/update()) TIDAK berubah — keduanya tetap selalu
 * mengirim warehouse_id terisi, cuma kolomnya sekarang mengizinkan NULL untuk baris yang datang
 * dari External API. Raw DB::statement (bukan ->change()) sama seperti migrasi
 * 2026_08_03_160000_make_customer_supply_returns_so_id_nullable — doctrine/dbal tidak terpasang.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customer_supply_return_details')) {
            DB::statement('ALTER TABLE `customer_supply_return_details` MODIFY `warehouse_id` INT NULL');
        }

        if (Schema::hasTable('customer_product_return_details')) {
            DB::statement('ALTER TABLE `customer_product_return_details` MODIFY `warehouse_id` INT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customer_supply_return_details')) {
            DB::table('customer_supply_return_details')->whereNull('warehouse_id')->update(['warehouse_id' => 0]);
            DB::statement('ALTER TABLE `customer_supply_return_details` MODIFY `warehouse_id` INT NOT NULL');
        }

        if (Schema::hasTable('customer_product_return_details')) {
            DB::table('customer_product_return_details')->whereNull('warehouse_id')->update(['warehouse_id' => 0]);
            DB::statement('ALTER TABLE `customer_product_return_details` MODIFY `warehouse_id` INT NOT NULL');
        }
    }
};
