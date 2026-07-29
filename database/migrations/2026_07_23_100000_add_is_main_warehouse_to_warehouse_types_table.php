<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warehouse_types')) {
            return;
        }

        Schema::table('warehouse_types', function (Blueprint $table) {
            if (!Schema::hasColumn('warehouse_types', 'is_main_warehouse')) {
                $table->tinyInteger('is_main_warehouse')
                    ->default(0)
                    ->after('warehouse_type_name')
                    ->comment('1 = tipe gudang utama (hanya 1)');
                $table->index('is_main_warehouse');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('warehouse_types')) {
            return;
        }

        Schema::table('warehouse_types', function (Blueprint $table) {
            if (Schema::hasColumn('warehouse_types', 'is_main_warehouse')) {
                $table->dropIndex(['is_main_warehouse']);
                $table->dropColumn('is_main_warehouse');
            }
        });
    }
};
