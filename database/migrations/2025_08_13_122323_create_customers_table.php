<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('customer_id', true);
            $table->integer('area_id')->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_code', 10)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_pic', 255)->nullable();
            $table->string('customer_pic_phone', 50)->nullable();
            $table->string('customer_address', 255)->nullable();
            $table->text('customer_notes')->nullable();
            $table->integer('sales_id')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('district_id')->nullable();
            $table->string('customer_zipcode', 20)->nullable();
            $table->integer('customer_saldo')->nullable()->default(0);
            $table->boolean('status')->nullable()->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
