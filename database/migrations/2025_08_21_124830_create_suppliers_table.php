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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->integerIncrements('supplier_id');
            $table->string('supplier_name', 255);
            $table->string('supplier_email', 255);
            $table->string('supplier_phone', 50);
            $table->string('supplier_address', 255)->nullable();
            $table->text('supplier_notes')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->string('supplier_zipcode', 20)->nullable();
            $table->string('supplier_bank', 255)->nullable();
            $table->string('supplier_branch', 255)->nullable();
            $table->string('supplier_account_name', 255)->nullable();
            $table->string('supplier_account_number', 100)->nullable();
            $table->string('supplier_ifsc', 100)->nullable();
            $table->string('supplier_image', 255)->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
