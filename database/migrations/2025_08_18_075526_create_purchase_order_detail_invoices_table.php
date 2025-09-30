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
        Schema::create('purchase_order_detail_invoices', function (Blueprint $table) {
            $table->integerIncrements('poi_id');
            $table->string('poi_date',100);
            $table->string('poi_due',100);
            $table->string('poi_code',100);
            $table->integer('poi_total');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_detail_invoices');
    }
};
