<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('supplies_variants')
            && Schema::hasColumn('supplies_variants', 'safety_stock')) {
            DB::statement(
                'ALTER TABLE `supplies_variants` MODIFY `safety_stock` INT UNSIGNED NOT NULL DEFAULT 0'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('supplies_variants')
            && Schema::hasColumn('supplies_variants', 'safety_stock')) {
            DB::statement(
                'ALTER TABLE `supplies_variants` MODIFY `safety_stock` DECIMAL(15,4) UNSIGNED NOT NULL DEFAULT 0'
            );
        }
    }
};
