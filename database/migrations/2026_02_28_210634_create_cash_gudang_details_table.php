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
        Schema::create('cash_gudang_details', function (Blueprint $table) {
            $table->integerIncrements('cgd_id');
            $table->integer('cg_id');
            $table->integer('customer_id');
            $table->string('cgd_notes', 255)->nullable();
            $table->integer('cgd_nominal');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_gudang_details');
    }
};
