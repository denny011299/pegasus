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
        Schema::create('cash_sales_details', function (Blueprint $table) {
            $table->integerIncrements('csd_id');
            $table->integer('cs_id');
            $table->string('csd_notes', 255)->nullable();
            $table->integer('csd_nominal');
            $table->integer('csd_type')->comment('1 = Masuk, 2 = Keluar, 3 = Keluar 1');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sales_details');
    }
};
