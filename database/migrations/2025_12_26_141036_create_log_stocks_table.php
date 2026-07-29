<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('log_stocks', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->id('log_id');
            $table->dateTime('log_date');
            $table->string('log_kode', 50);
            $table->integer('log_type')->default(1)->comment('1 = Produk, 2 = Bahan Mentah');
            $table->integer('log_category')->nullable()->default(1)->comment('1 = Masuk, 2 = Keluar');
            $table->integer('log_item_id')->comment('id product / supplies');
            $table->text('log_notes')->nullable();
            $table->integer('log_jumlah');
            $table->unsignedBigInteger('unit_id');
            $table->integer('status')->default(1);
            $table->integer('staff_id')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_stocks');
    }
};
