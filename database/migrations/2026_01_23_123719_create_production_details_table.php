<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_details', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('pd_id', true);
            $table->integer('production_id');
            $table->integer('product_variant_id');
            $table->integer('pd_qty');
            $table->integer('unit_id');
            $table->integer('bom_id');
            $table->text('list_bahan')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_details');
    }
};
