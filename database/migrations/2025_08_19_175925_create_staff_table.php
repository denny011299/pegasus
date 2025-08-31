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
        Schema::create('staff', function (Blueprint $table) {
            $table->integerIncrements('staff_id');
            $table->string('staff_first_name', 150);
            $table->string('staff_last_name', 150)->nullable();
            $table->string('staff_email', 255)->unique();
            $table->string('staff_phone', 50)->nullable();
            $table->date('staff_birthdate')->nullable();
            $table->tinyInteger('staff_gender')->nullable()->comment('1 = Male, 2 = Female');
            $table->string('staff_nationality', 100)->nullable();
            $table->date('staff_join_date')->nullable();
            $table->string('staff_shift', 100)->nullable();
            $table->string('staff_departement', 150)->nullable();
            $table->string('staff_position', 150)->nullable();
            $table->string('staff_blood', 3)->nullable();
            $table->string('staff_emergency1', 150)->nullable();
            $table->string('staff_emergency2', 150)->nullable();
            $table->text('staff_address')->nullable();
            $table->integer('country_id')->nullable();
            $table->integer('state_id')->nullable();
            $table->integer('city_id')->nullable();
            $table->string('staff_zipcode', 20)->nullable();
            $table->string('staff_password', 255);
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
