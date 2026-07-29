<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->increments('unit_id');
            $table->integer('ref_unit_id')->nullable()->comment('unit_id pada sistem PMO');
            $table->string('unit_name', 250);
            $table->string('unit_short_name', 250);
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();

            $table->unique('ref_unit_id', 'units_ref_unit_id_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
