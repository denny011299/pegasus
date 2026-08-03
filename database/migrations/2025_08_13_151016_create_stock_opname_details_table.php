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
        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->integerIncrements('stod_id');
            $table->integer('sto_id');
            $table->integer('product_id');
            $table->integer('product_variant_id');
            $table->longText('stod_system')->nullable();
            $table->longText('stod_real')->nullable();
            $table->longText('stod_selisih')->nullable();
            $table->longText('stod_notes')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_details');
    }
};
