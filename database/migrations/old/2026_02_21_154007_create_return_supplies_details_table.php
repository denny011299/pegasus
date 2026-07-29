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
        Schema::create('return_supplies_details', function (Blueprint $table) {
            $table->integerIncrements('rsd_id');
            $table->integer('rs_id');
            $table->integer('supplies_variant_id');
            $table->integer('rsd_qty');
            $table->integer('rsd_price');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_supplies_details');
    }
};
