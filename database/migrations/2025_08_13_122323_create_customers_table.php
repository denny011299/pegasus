<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->integerIncrements('customer_id');
            $table->integer('area_id')->nullable();
            $table->string('customer_name', 255);
            $table->string('customer_code', 100)->unique();
            $table->string('customer_email', 255)->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->integer('subdistrict_id')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_pic', 255)->nullable();
            $table->string('customer_pic_phone', 50)->nullable();
            $table->text('customer_notes')->nullable();
            $table->integer('sales_id')->nullable();
            $table->integer('customer_payment')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
