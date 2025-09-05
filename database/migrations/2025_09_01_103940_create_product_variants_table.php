<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
             $table->integerIncrements('product_variant_id');
            $table->integer('product_id');
            $table->string('product_variant_sku', 100);
            $table->string('product_variant_name', 255);
            $table->integer('product_variant_price');
            $table->string('product_variant_barcode',100);
            $table->integer('product_variant_stock');
              $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
