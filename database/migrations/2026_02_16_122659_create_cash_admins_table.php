<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_admins', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('ca_id', true);
            $table->integer('staff_id');
            $table->integer('cash_id');
            $table->date('ca_date');
            $table->integer('ca_nominal');
            $table->string('ca_notes', 255);
            $table->integer('ca_type')->comment('1 = saldo, 2 = operasional');
            $table->integer('ca_aksi')->default(0)->comment('1 = Pengajuan, 2 = Pengembalian');
            $table->text('ca_img')->nullable();
            $table->integer('status')->default(1);
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->integer('acc_by')->nullable()->comment('staff_id');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_admins');
    }
};
