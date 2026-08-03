<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel sudah ada di DB produksi/dev (dibuat di luar migrasi / migrasi belum tercatat)
        if (Schema::hasTable('product_issues')) {
            return;
        }

        Schema::create('product_issues', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('pi_id', true);
            $table->string('pi_code', 10);
            $table->integer('ref_num');
            $table->integer('po_id');
            $table->integer('pi_type')->nullable()->comment('1 = return, 2= damaged');
            $table->integer('tipe_return')->comment('1 = Bahan Mentah, 2 = Produk');
            $table->date('pi_date')->nullable();
            $table->text('pi_notes');
            $table->text('pi_img')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->integer('created_by')->nullable();
            $table->integer('acc_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_issues');
    }
};
