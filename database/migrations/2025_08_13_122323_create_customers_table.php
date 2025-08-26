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
            $table->id('customer_id');
            $table->string('customer_name', 255);
            $table->string('customer_code', 10)->unique()->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->date('customer_birthdate')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->string('customer_address', 255)->nullable();
            $table->text('customer_notes')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->string('customer_zipcode', 20)->nullable();
            $table->string('customer_bank', 255)->nullable();
            $table->string('customer_branch', 255)->nullable();
            $table->string('customer_account_name', 255)->nullable();
            $table->string('customer_account_number', 100)->nullable();
            $table->string('customer_ifsc', 100)->nullable();
            $table->integer('customer_payment')->default(0);
            $table->string('customer_image', 255)->nullable();
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
