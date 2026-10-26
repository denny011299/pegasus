<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Snapshot hasil produksi ke item SPK/PP (diisi saat WO konfirmasi hasil). */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_planning_items')) {
            return;
        }
        Schema::table('production_planning_items', function (Blueprint $table) {
            if (! Schema::hasColumn('production_planning_items', 'actual_qty')) {
                $table->decimal('actual_qty', 18, 4)->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('production_planning_items')) {
            return;
        }
        Schema::table('production_planning_items', function (Blueprint $table) {
            if (Schema::hasColumn('production_planning_items', 'actual_qty')) {
                $table->dropColumn('actual_qty');
            }
        });
    }
};
