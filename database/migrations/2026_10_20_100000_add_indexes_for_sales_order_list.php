<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // List Pengiriman (SalesOrder::applyActiveWarehouseFilter) memakai correlated
    // EXISTS per baris sales_orders ke sales_order_details.so_id — tanpa index ini
    // tiap baris = full scan details (endpoint list belasan detik).
    public function up(): void
    {
        if (Schema::hasTable('sales_order_details') && ! $this->indexExists('sales_order_details', 'sod_so_status_idx')) {
            Schema::table('sales_order_details', function (Blueprint $table) {
                $table->index(['so_id', 'status'], 'sod_so_status_idx');
            });
        }

        // Urutan default list: status ASC, created_at DESC.
        if (Schema::hasTable('sales_orders') && ! $this->indexExists('sales_orders', 'so_status_created_idx')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->index(['status', 'created_at'], 'so_status_created_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sales_order_details') && $this->indexExists('sales_order_details', 'sod_so_status_idx')) {
            Schema::table('sales_order_details', function (Blueprint $table) {
                $table->dropIndex('sod_so_status_idx');
            });
        }
        if (Schema::hasTable('sales_orders') && $this->indexExists('sales_orders', 'so_status_created_idx')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropIndex('so_status_created_idx');
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(Schema::getIndexes($table))->contains('name', $index);
    }
};
