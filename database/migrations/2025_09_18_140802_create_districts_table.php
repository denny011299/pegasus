<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('id', true);
            $table->integer('city_id');
            $table->string('name', 255);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
