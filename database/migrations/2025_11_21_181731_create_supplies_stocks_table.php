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
        Schema::create('supplies_stocks', function (Blueprint $table) {
            $table->integerIncrements('ss_id');
            $table->integer('supplies_id');
            $table->integer('unit_id');
            $table->integer('ss_stock');
            $table->integer('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies_stocks');
    }
};
