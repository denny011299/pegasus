<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_detail_invoices', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->increments('poi_id');
            $table->unsignedInteger('po_id');
            $table->string('poi_date', 100);
            $table->string('poi_due', 100);
            $table->string('poi_code', 100);
            $table->integer('poi_total');
            $table->integer('bank_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_detail_invoices');
    }
};
