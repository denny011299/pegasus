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
        Schema::create('bom_details', function (Blueprint $table) {
            $table->integerIncrements('bom_detail_id');
            $table->integer('bom_id');
            $table->integer('supplies_id');
            $table->integer('bom_detail_qty')->default(1);
            $table->integer('unit_id');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bom_details');
    }
};
