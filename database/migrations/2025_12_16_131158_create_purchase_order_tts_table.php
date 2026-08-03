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
        Schema::create('purchase_order_tts', function (Blueprint $table) {
            $table->integerIncrements('tt_id');
            $table->text('tt_date');
            $table->text('tt_kode');
            $table->integer('tt_total');
            $table->integer('staff_id');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_tts');
    }
};
