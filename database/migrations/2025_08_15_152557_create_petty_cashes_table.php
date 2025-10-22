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
        Schema::create('petty_cashes', function (Blueprint $table) {
            $table->integerIncrements('pc_id');
            $table->date('pc_date');
            $table->tinyInteger('pc_type')->comment('1 = debit, 2 = credit');
            $table->integer('cc_id');
            $table->string('pc_description', 255);
            $table->integer('pc_nominal');
            $table->integer('staff_id');
            $table->tinyInteger('status')->default(1)
                  ->comment('3 = declined, 2 = accepted, 1 = pending, 0 = inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cashes');
    }
};
