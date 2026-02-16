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
        Schema::create('petty_cash_details', function (Blueprint $table) {
            $table->integerIncrements('pcd_id');
            $table->string('pcd_notes', 255);
            $table->integer('cc_id');
            $table->integer('pcd_nominal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_details');
    }
};
