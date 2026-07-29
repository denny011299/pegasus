<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_tts', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->increments('tt_id');
            $table->text('tt_date');
            $table->date('tt_due')->nullable();
            $table->text('tt_kode');
            $table->integer('tt_total');
            $table->string('staff_name', 250)->nullable();
            $table->string('staffFinance_name', 250)->nullable();
            $table->integer('supplier_id')->nullable();
            $table->text('tt_image')->nullable();
            $table->string('tt_desc', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('acc_by')->nullable()->comment('staff_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_tts');
    }
};
