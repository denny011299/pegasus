<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Pembelian multi-gudang: PO mengikat warehouse_id.
 * Backfill lama → gudang utama. ACC/tolak baca kolom ini (bukan session).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            return;
        }
        if (! Schema::hasColumn('purchase_orders', 'warehouse_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('po_supplier');
                $table->index('warehouse_id', 'purchase_orders_warehouse_id_index');
            });
        }

        $mainId = null;
        if (Schema::hasTable('warehouses') && Schema::hasTable('warehouse_types')) {
            $mainId = DB::table('warehouses')
                ->join('warehouse_types', 'warehouse_types.id', '=', 'warehouses.warehouse_type_id')
                ->where('warehouses.status', 1)
                ->where('warehouse_types.is_main_warehouse', 1)
                ->orderBy('warehouses.id')
                ->value('warehouses.id');
        }
        if ($mainId) {
            DB::table('purchase_orders')
                ->where(function ($q) {
                    $q->whereNull('warehouse_id')->orWhere('warehouse_id', 0);
                })
                ->update(['warehouse_id' => (int) $mainId]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_orders')) {
            return;
        }
        if (Schema::hasColumn('purchase_orders', 'warehouse_id')) {
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->dropIndex('purchase_orders_warehouse_id_index');
                $table->dropColumn('warehouse_id');
            });
        }
    }
};
