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
        Schema::create('cash_armadas', function (Blueprint $table) {
            $table->integerIncrements('cr_id');
            $table->integer('customer_id');
            $table->integer('cash_id');
            $table->integer('cr_nominal');
            $table->string('cr_notes', 255)->nullable();
            $table->integer('cr_type')->comment('1 = saldo, 2 = operasional');
            $table->integer('cr_aksi')->comment('1 = pengajuan, 2 = pengembalian');
            $table->text('cr_img')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_armadas');
    }
};
