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
        Schema::create('supplies_relations', function (Blueprint $table) {
            $table->id('sr_id');
            $table->integer('su_id_1');
            $table->integer('su_id_2');
            $table->decimal('sr_value_1', 10, 2)->default(0);
            $table->decimal('sr_value_2', 10, 2)->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=active, 0=inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies_relations');
    }
};
