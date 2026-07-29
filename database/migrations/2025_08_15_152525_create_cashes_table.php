<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashes', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('cash_id', true);
            $table->integer('person_id')->default(0)->comment('Customer / Staff ID');
            $table->date('cash_date');
            $table->tinyInteger('cash_type')->comment('1 = debit, 2 = credit 1, 3 = credit 2');
            $table->integer('cash_tujuan')->default(0)->comment('1 = Admin, 2 = Gudang, 3 = Armada, 4 = Sales');
            $table->string('cash_description', 255);
            $table->integer('cash_nominal');
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->integer('acc_by')->nullable()->comment('staff_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashes');
    }
};
