<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Schema lokal sempat pakai production_muat_id; kode WO butuh production_skala_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_planning_items')) {
            return;
        }

        if (Schema::hasColumn('production_planning_items', 'production_muat_id')
            && ! Schema::hasColumn('production_planning_items', 'production_skala_id')) {
            Schema::table('production_planning_items', function (Blueprint $table) {
                $table->renameColumn('production_muat_id', 'production_skala_id');
            });
        } elseif (! Schema::hasColumn('production_planning_items', 'production_skala_id')) {
            Schema::table('production_planning_items', function (Blueprint $table) {
                $table->unsignedInteger('production_skala_id')->nullable()->after('unit_label');
                $table->index('production_skala_id', 'ppi_skala_idx');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('production_planning_items')) {
            return;
        }

        if (Schema::hasColumn('production_planning_items', 'production_skala_id')
            && ! Schema::hasColumn('production_planning_items', 'production_muat_id')) {
            Schema::table('production_planning_items', function (Blueprint $table) {
                $table->renameColumn('production_skala_id', 'production_muat_id');
            });
        }
    }
};
