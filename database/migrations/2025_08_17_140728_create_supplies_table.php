<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('supplies_id', true);
            $table->string('supplies_name', 255);
            $table->string('supplies_image', 255)->nullable();
            $table->text('supplies_desc')->nullable();
            $table->integer('supplies_min_stock')->nullable();
            $table->text('supplies_unit');
            $table->integer('supplies_alert')->default(0);
            $table->integer('supplies_default_unit');
            $table->boolean('status')->nullable()->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
