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
        Schema::create('return_supplies', function (Blueprint $table) {
            $table->integerIncrements('rs_id');
            $table->integer('supplier_id');
            $table->integer('pi_id');
            $table->date('rs_date');
            $table->integer('rs_total');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_supplies');
    }
};
