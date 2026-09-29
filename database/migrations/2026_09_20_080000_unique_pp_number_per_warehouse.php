<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_plannings')) {
            return;
        }

        Schema::table('production_plannings', function (Blueprint $table) {
            // Unique global bentrok dengan generate per-gudang — ganti ke (warehouse_id, pp_number).
            $table->dropUnique('production_plannings_pp_number_unique');
        });

        Schema::table('production_plannings', function (Blueprint $table) {
            $table->unique(['warehouse_id', 'pp_number'], 'production_plannings_wh_pp_number_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('production_plannings')) {
            return;
        }

        Schema::table('production_plannings', function (Blueprint $table) {
            $table->dropUnique('production_plannings_wh_pp_number_unique');
        });

        Schema::table('production_plannings', function (Blueprint $table) {
            $table->unique('pp_number', 'production_plannings_pp_number_unique');
        });
    }
};
