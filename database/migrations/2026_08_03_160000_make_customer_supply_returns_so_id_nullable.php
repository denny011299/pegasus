<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_supply_returns')) {
            return;
        }

        DB::statement('ALTER TABLE `customer_supply_returns` MODIFY `so_id` INT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_supply_returns')) {
            return;
        }

        DB::statement('ALTER TABLE `customer_supply_returns` MODIFY `so_id` INT NOT NULL');
    }
};
