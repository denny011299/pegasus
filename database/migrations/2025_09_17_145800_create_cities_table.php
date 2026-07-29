<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('city_id')->default(0);
            $table->string('city_name', 255);
            $table->integer('prov_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
