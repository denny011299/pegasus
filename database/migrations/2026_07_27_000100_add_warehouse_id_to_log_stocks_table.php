<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * log_stocks per gudang — untuk filter histori stok.
     * Backfill Stock Transfer dari stock_transfers + catatan log.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('log_stocks', 'warehouse_id')) {
            Schema::table('log_stocks', function (Blueprint $table) {
                $table->unsignedBigInteger('warehouse_id')->nullable()->after('staff_id');
                $table->index('warehouse_id', 'log_stocks_warehouse_id_index');
            });
        }

        if (! Schema::hasTable('stock_transfers')) {
            return;
        }

        // Keluar / bongkar / kembalikan → gudang asal
        DB::statement("
            UPDATE log_stocks l
            INNER JOIN stock_transfers st ON st.transfer_code = l.log_kode
            SET l.warehouse_id = st.from_warehouse_id
            WHERE l.warehouse_id IS NULL
              AND l.log_kode LIKE 'ST%'
              AND (
                l.log_notes LIKE '%keluar gudang asal%'
                OR l.log_notes LIKE '%kembalikan stok%'
                OR l.log_notes LIKE '%bongkar%'
                OR l.log_notes LIKE '%hasil bongkar%'
                OR l.log_notes LIKE '%koreksi edit%'
              )
        ");

        // Masuk → gudang tujuan
        DB::statement("
            UPDATE log_stocks l
            INNER JOIN stock_transfers st ON st.transfer_code = l.log_kode
            SET l.warehouse_id = st.to_warehouse_id
            WHERE l.warehouse_id IS NULL
              AND l.log_kode LIKE 'ST%'
              AND l.log_notes LIKE '%masuk gudang tujuan%'
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('log_stocks', 'warehouse_id')) {
            Schema::table('log_stocks', function (Blueprint $table) {
                $table->dropIndex('log_stocks_warehouse_id_index');
                $table->dropColumn('warehouse_id');
            });
        }
    }
};
