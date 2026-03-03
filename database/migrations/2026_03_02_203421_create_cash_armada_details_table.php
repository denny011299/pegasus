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
        Schema::create('cash_armada_details', function (Blueprint $table) {
            $table->integerIncrements('crd_id');
            $table->integer('cr_id');
            $table->string('crd_notes', 255)->nullable();
            $table->integer('crd_nominal');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_armada_details');
    }
};
