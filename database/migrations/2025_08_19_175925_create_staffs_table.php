<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staffs', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('staff_id', true);
            $table->string('staff_name', 255);
            $table->string('staff_code', 255)->nullable();
            $table->string('staff_email', 255)->nullable();
            $table->string('staff_phone', 50)->nullable();
            $table->text('staff_address')->nullable();
            $table->text('staff_notes')->nullable();
            $table->string('staff_username', 250)->nullable();
            $table->string('staff_password', 250)->nullable();
            $table->integer('staff_saldo')->default(0);
            $table->integer('role_id')->nullable();
            $table->boolean('status')->nullable()->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staffs');
    }
};
