<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies_variants', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('supplies_variant_id', true);
            $table->integer('supplier_id')->nullable();
            $table->integer('supplies_id');
            $table->string('supplies_variant_name', 100);
            $table->string('supplies_variant_sku', 100);
            $table->integer('supplies_variant_price');
            $table->string('supplies_variant_barcode', 100);
            $table->integer('supplies_variant_stock');
            $table->integer('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies_variants');
    }
};
