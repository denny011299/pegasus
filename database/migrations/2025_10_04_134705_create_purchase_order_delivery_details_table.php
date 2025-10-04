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
        Schema::create('purchase_order_delivery_details', function (Blueprint $table) {
            $table->integerIncrements('pdod_id');
            $table->integer('pdo_id');
            $table->integer('product_variant_id');
            $table->string('pdod_sku', 50);
            $table->integer('category_id');
            $table->integer('pdod_qty')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_delivery_details');
    }
};
