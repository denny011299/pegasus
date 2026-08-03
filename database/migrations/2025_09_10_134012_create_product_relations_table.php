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
        Schema::create('product_relations', function (Blueprint $table) {
            $table->integerIncrements('pr_id');
            $table->date('product_id');
            $table->integer('pr_unit_id_1');
            $table->integer('pr_unit_value_1');
            $table->integer('pr_unit_id_2');
            $table->integer('pr_unit_value_2');
            $table->integer('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_relations');
    }
};
