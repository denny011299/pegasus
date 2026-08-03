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
        Schema::create('cashes', function (Blueprint $table) {
            $table->integerIncrements('cash_id');
            $table->date('cash_date');
            $table->tinyInteger('customer_id');
            $table->tinyInteger('cash_type')->comment('1 = Masuk, 2 = Keluar, 3 = Keluar 1');
            $table->tinyInteger('cash_tujuan')->comment('1 = Admin, 2 = Gudang, 3 = Armada, 4 = Sales');
            $table->string('cash_description', 255);
            $table->integer('cash_nominal');
            $table->integer('cash_balance');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashes');
    }
};
