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
        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->integerIncrements('sod_id');
            $table->integer('so_id');
            $table->integer('pr_id');
            $table->string('sod_nama', 255);
            $table->string('sod_variant', 255)->nullable();
            $table->string('sod_sku', 100)->nullable();
            $table->integer('sod_harga');
            $table->integer('sod_qty');
            $table->integer('sod_subtotal');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_order_details');
    }
};
