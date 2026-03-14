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
        Schema::create('cash_sales', function (Blueprint $table) {
            $table->integerIncrements('cs_id');
            $table->integer('cash_id');
            $table->integer('staff_id');
            $table->integer('bank_id');
            $table->integer('cs_nominal');
            $table->integer('cs_type')->comment('1 = saldo, 2 = operasional');
            $table->integer('cs_aksi')->comment('1 = pengajuan, 2 = pengembalian');
            $table->string('cs_notes', 255)->nullable();
            $table->text('cs_img')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_sales');
    }
};
