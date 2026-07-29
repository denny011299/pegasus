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
        Schema::create('purchase_order_deliveries', function (Blueprint $table) {
            $table->integerIncrements('pdo_id');
            $table->string('pdo_number', 10);
            $table->string('pdo_receiver', 150);
            $table->date('pdo_date');
            $table->string('pdo_phone', 50);
            $table->text('pdo_address');
            $table->text('pdo_desc');
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_deliveries');
    }
};
