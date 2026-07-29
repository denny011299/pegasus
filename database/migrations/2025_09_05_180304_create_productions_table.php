<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('production_id', true);
            $table->date('production_date');
            $table->string('production_code', 10);
            $table->string('production_desc', 255)->nullable();
            $table->integer('production_created_by');
            $table->text('notes')->nullable();
            $table->integer('cancel_requested_by')->nullable()->comment('staff_id pengaju batal');
            $table->integer('status')->nullable()->default(1)->comment('1 = pending, 2 = diproses, 3 = selesai');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('acc_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productions');
    }
};
