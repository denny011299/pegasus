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
        // Tabel sudah ada di DB produksi/dev (dibuat di luar migrasi / migrasi belum tercatat)
        if (Schema::hasTable('product_issues')) {
            return;
        }

        Schema::create('product_issues', function (Blueprint $table) {
            $table->integerIncrements('pi_id');
            $table->string('pi_code', 50);
            $table->integer('pi_type')->comment('1 = return, 2 = damaged');
            $table->integer('tipe_return');
            $table->date('pi_date');
            $table->text('pi_notes');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_issues');
    }
};
