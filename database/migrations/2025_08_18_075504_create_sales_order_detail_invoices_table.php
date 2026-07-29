<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_detail_invoices', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->increments('soi_id');
            $table->unsignedInteger('so_id');
            $table->string('soi_date', 100);
            $table->string('soi_due', 100);
            $table->string('soi_code', 100);
            $table->integer('soi_total');
            $table->tinyInteger('status')->default(1);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_detail_invoices');
    }
};
