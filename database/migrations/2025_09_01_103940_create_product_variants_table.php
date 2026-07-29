<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('product_variant_id', true);
            $table->integer('product_id');
            $table->string('product_variant_name', 100)->nullable();
            $table->string('product_variant_sku', 100)->nullable();
            $table->bigInteger('product_variant_price')->default(0);
            $table->text('product_variant_barcode')->nullable();
            $table->integer('product_variant_stock')->nullable();
            $table->integer('product_variant_alert')->nullable();
            $table->integer('unit_id')->nullable();
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
