<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders_details', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('pod_id', true);
            $table->integer('po_id');
            $table->integer('supplies_variant_id');
            $table->string('pod_nama', 255);
            $table->string('pod_variant', 255)->nullable();
            $table->integer('unit_id');
            $table->string('pod_sku', 100)->nullable();
            $table->integer('pod_harga');
            $table->integer('pod_qty');
            $table->integer('pod_subtotal');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders_details');
    }
};
