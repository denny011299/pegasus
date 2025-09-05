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
        Schema::create('product_issues', function (Blueprint $table) {
            $table->integerIncrements('pi_id'); 
            $table->integer('pi_type')->comment('1 = return, 2= damaged'); 
            $table->integer('pi_qty'); 
            $table->date('pi_date'); 
            $table->text('pi_notes'); 
            $table->integer('pvr_id')->comment('Product ID')->nullable(); 
            $table->integer('store_id')->comment('Store ID')->nullable(); 
            $table->integer('warehouse_id')->comment('Store ID')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_issues');
    }
};
