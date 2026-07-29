<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('ps_id');
            $table->integer('product_variant_id');
            $table->integer('product_id');
            $table->integer('unit_id');
            $table->integer('ps_stock');
            $table->integer('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
