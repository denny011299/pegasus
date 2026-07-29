<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->increments('po_id');
            $table->string('po_number', 250);
            $table->date('po_date');
            $table->integer('po_supplier');
            $table->integer('po_total')->nullable();
            $table->string('jenis_discount', 10)->default('persen');
            $table->integer('po_discount')->default(0);
            $table->integer('po_ppn')->default(0);
            $table->integer('po_cost')->default(0);
            $table->string('po_desc', 255)->nullable();
            $table->longText('po_img');
            $table->integer('pembayaran')->default(1);
            $table->integer('tt_id')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=Created, 2=Confirmed, 3=Completed');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('acc_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
