<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('staffs')) {
            return;
        }

        Schema::table('staffs', function (Blueprint $table) {
            if (!Schema::hasColumn('staffs', 'last_active_warehouse_id')) {
                $table->unsignedBigInteger('last_active_warehouse_id')->nullable()->after('role_id');
                $table->index('last_active_warehouse_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('staffs')) {
            return;
        }

        Schema::table('staffs', function (Blueprint $table) {
            if (Schema::hasColumn('staffs', 'last_active_warehouse_id')) {
                $table->dropIndex(['last_active_warehouse_id']);
                $table->dropColumn('last_active_warehouse_id');
            }
        });
    }
};
