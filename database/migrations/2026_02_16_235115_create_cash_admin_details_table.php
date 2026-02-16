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
        Schema::create('cash_admin_details', function (Blueprint $table) {
            $table->integerIncrements('cad_id');
            $table->integer('ca_id');
            $table->string('cad_notes', 255);
            $table->integer('cad_nominal');
            $table->text('cad_img')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_admin_details');
    }
};
