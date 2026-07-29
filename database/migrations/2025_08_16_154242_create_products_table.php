<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('product_id');
            $table->integer('ref_product_id')->nullable()->comment('ref_product_id pada sistem PMO');
            $table->string('product_name', 250);
            $table->integer('category_id');
            $table->text('product_unit');
            $table->integer('product_alert')->default(0);
            $table->integer('unit_id')->comment('Default Yang dipakai product');
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();

            $table->unique('ref_product_id', 'products_ref_product_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
