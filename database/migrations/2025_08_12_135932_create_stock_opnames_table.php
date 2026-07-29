<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->increments('sto_id');
            $table->date('sto_date');
            $table->string('sto_code', 6);
            $table->integer('staff_id');
            $table->integer('category_id');
            $table->longText('sto_notes')->nullable();
            $table->boolean('status')->default(1)->comment('1=active, 0=inactive');
            $table->integer('created_by')->nullable()->comment('staff_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('acc_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};
