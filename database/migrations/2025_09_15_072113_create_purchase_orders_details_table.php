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
        Schema::create('purchase_orders_details', function (Blueprint $table) {
            $table->integerIncrements('pod_id');
            $table->integer('po_id');
            $table->integer('supplies_variant_id');
            $table->string('pod_nama');
            $table->string('pod_variant')->nullable();
            $table->string('pod_sku', 100)->nullable();
            $table->integer('pod_harga');
            $table->integer('pod_qty');
            $table->integer('pod_subtotal');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders_details');
    }
};
