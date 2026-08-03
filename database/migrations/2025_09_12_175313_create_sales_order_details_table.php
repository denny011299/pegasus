<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('sod_id', true);
            $table->integer('so_id');
            $table->integer('product_variant_id');
            $table->integer('unit_id');
            $table->string('sod_nama', 255);
            $table->string('sod_variant', 255)->nullable();
            $table->string('sod_sku', 100)->nullable();
            $table->integer('sod_harga');
            $table->integer('sod_qty');
            $table->integer('sod_subtotal');
            $table->tinyInteger('status')->nullable()->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_details');
    }
};
