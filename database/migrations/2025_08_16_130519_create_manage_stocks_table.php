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
         Schema::create('manage_stocks', function (Blueprint $table) {
            $table->integerIncrements('ms_id');
            $table->integer('ms_type');
            $table->integer('product_variant_id')->comment('Variant ID')->nullable();
            $table->integer('ms_stock')->nullable();
            $table->integer('ms_created_by')->nullable();
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('manage_stocks');
    }
};
