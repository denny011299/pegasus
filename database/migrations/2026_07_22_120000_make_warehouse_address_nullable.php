<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pastikan warehouse_address boleh kosong (nullable).
     */
    public function up(): void
    {
        if (!Schema::hasTable('warehouses')) {
            return;
        }

        DB::statement('ALTER TABLE `warehouses` MODIFY `warehouse_address` TEXT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('warehouses')) {
            return;
        }

        DB::statement('ALTER TABLE `warehouses` MODIFY `warehouse_address` TEXT NOT NULL');
    }
};
