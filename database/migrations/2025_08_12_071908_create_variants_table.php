<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('variants', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->increments('variant_id');
            $table->string('variant_name', 250);
            $table->string('variant_attribute', 250);
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->integer('acc_by')->nullable()->comment('staff_id');
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
