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
        Schema::create('sales_order_delivery_details', function (Blueprint $table) {
            $table->integerIncrements('sdod_id');
            $table->integer('sdo_id');
            $table->integer('product_variant_id');
            $table->string('sdod_sku', 50);
            $table->integer('sdod_qty')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_delivery_details');
    }
};
