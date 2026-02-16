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
        Schema::create('cash_gudangs', function (Blueprint $table) {
            $table->integerIncrements('cg_id');
            $table->integer('staff_id');
            $table->integer('cg_nominal');
            $table->string('cg_notes', 255);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_gudangs');
    }
};
