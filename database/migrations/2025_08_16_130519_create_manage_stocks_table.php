<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manage_stocks', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('ms_id', true);
            $table->integer('ms_type')->nullable();
            $table->integer('product_variant_id')->nullable()->comment('Variant ID');
            $table->integer('supplies_id')->nullable();
            $table->integer('ms_stock')->nullable();
            $table->integer('ms_created_by')->nullable();
            $table->integer('status')->nullable()->default(1)->comment('1 = active, 0 = dead');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manage_stocks');
    }
};
