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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->integerIncrements('po_id');
            $table->string('po_number', 250);
            $table->date('po_date');
            $table->string('po_customer',250);
            $table->integer('po_total')->nullable();
            $table->integer('po_discount')->default(0);
            $table->integer('po_ppn')->default(0);
            $table->integer('po_cost')->default(0);
            $table->tinyInteger('status')->default(1)->comment('1=Created, 2=Confirmed, 3=Completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
