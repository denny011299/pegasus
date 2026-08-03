<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_armadas', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('cr_id', true);
            $table->integer('customer_id');
            $table->integer('cash_id');
            $table->date('cr_date');
            $table->integer('cr_nominal');
            $table->integer('cr_type')->comment('1 = debit, 2 = credit');
            $table->integer('cr_aksi')->comment('1 = saldo, 2 = operasional');
            $table->string('cr_notes', 255);
            $table->text('cr_img')->nullable();
            $table->integer('status')->default(1);
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->integer('acc_by')->nullable()->comment('staff_id');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_armadas');
    }
};
