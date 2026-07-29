<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_sales', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_general_ci');

            $table->increments('cs_id');
            $table->integer('cash_id');
            $table->integer('staff_id');
            $table->integer('bank_id')->default(0);
            $table->date('cs_date');
            $table->integer('cs_nominal');
            $table->integer('cs_type')->comment('1 = saldo, 2 = operasional');
            $table->integer('cs_aksi')->comment('1 = pengajuan, 2 = setor ke bank, 3 = pengembalian');
            $table->integer('cs_transaction')->comment('1 = Masuk, 2 = Keluar, 3 = Keluar 1');
            $table->string('cs_notes', 255)->nullable();
            $table->text('cs_img')->nullable();
            $table->integer('status')->default(1);
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->integer('acc_by')->nullable()->comment('staff_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_sales');
    }
};
