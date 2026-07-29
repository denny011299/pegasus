<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplies_relations', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_general_ci');

            $table->increments('sr_id');
            $table->integer('supplies_id');
            $table->integer('su_id_1');
            $table->integer('su_id_2');
            $table->integer('sr_value_1');
            $table->integer('sr_value_2');
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplies_relations');
    }
};
