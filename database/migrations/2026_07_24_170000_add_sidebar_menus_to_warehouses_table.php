<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('warehouses', 'sidebar_menus')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->json('sidebar_menus')->nullable()->after('warehouse_address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('warehouses', 'sidebar_menus')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('sidebar_menus');
            });
        }
    }
};
