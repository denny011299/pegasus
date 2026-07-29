<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_supplies_detail', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('rsd_id', true);
            $table->integer('rs_id');
            $table->integer('pid_id');
            $table->integer('supplies_variant_id');
            $table->integer('unit_id');
            $table->integer('rsd_qty');
            $table->integer('rsd_price');
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_supplies_detail');
    }
};
