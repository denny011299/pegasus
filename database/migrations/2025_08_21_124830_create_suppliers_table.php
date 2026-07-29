<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('supplier_id', true);
            $table->string('supplier_name', 255);
            $table->string('supplier_code', 10)->nullable();
            $table->string('supplier_email', 255)->nullable();
            $table->string('supplier_phone', 50)->nullable();
            $table->string('supplier_pic', 255);
            $table->string('supplier_address', 255)->nullable();
            $table->text('supplier_notes')->nullable();
            $table->integer('state_id')->nullable()->default(0);
            $table->integer('city_id')->nullable()->default(0);
            $table->string('supplier_bank', 255)->nullable();
            $table->string('supplier_branch', 255)->nullable();
            $table->string('supplier_account_name', 255)->nullable();
            $table->string('supplier_account_number', 100)->nullable();
            $table->integer('supplier_top');
            $table->integer('bank_id');
            $table->integer('supplier_payment')->nullable()->default(0);
            $table->text('supplier_image')->nullable();
            $table->boolean('status')->nullable()->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
