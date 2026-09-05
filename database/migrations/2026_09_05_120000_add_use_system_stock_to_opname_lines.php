<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Draft menyimpan flag centang "ikut stock sistem" per satuan — tanpa menulis stok live ke counted.
 * Autofill + hangus/roll-up baru saat dokumen keluar draft (ajukan / simpan non-draft).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_opname_lines') && ! Schema::hasColumn('stock_opname_lines', 'sol_use_system_stock')) {
            Schema::table('stock_opname_lines', function (Blueprint $table) {
                $table->boolean('sol_use_system_stock')->default(false)->after('sol_counted_qty')
                    ->comment('1 = draft: pakai stok sistem saat ajukan; counted tetap null di draft');
            });
        }

        if (Schema::hasTable('stock_opname_bahan_lines') && ! Schema::hasColumn('stock_opname_bahan_lines', 'sobl_use_system_stock')) {
            Schema::table('stock_opname_bahan_lines', function (Blueprint $table) {
                $table->boolean('sobl_use_system_stock')->default(false)->after('sobl_counted_qty')
                    ->comment('1 = draft: pakai stok sistem saat ajukan; counted tetap null di draft');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_opname_lines') && Schema::hasColumn('stock_opname_lines', 'sol_use_system_stock')) {
            Schema::table('stock_opname_lines', function (Blueprint $table) {
                $table->dropColumn('sol_use_system_stock');
            });
        }

        if (Schema::hasTable('stock_opname_bahan_lines') && Schema::hasColumn('stock_opname_bahan_lines', 'sobl_use_system_stock')) {
            Schema::table('stock_opname_bahan_lines', function (Blueprint $table) {
                $table->dropColumn('sobl_use_system_stock');
            });
        }
    }
};
