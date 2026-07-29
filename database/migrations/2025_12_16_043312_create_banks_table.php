<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->increments('bank_id');
            $table->string('bank_kode', 200);
            $table->tinyInteger('status')->default(1);
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banks');
    }
};
