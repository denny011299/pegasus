<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->integer('so_id', true);
            $table->string('so_number', 50);
            $table->date('so_date');
            $table->string('so_customer', 255);
            $table->string('so_invoice_no', 200);
            $table->string('so_ref_number', 255)->nullable();
            $table->integer('so_total')->default(0);
            $table->integer('so_discount')->nullable()->default(0);
            $table->integer('so_ppn')->nullable()->default(0);
            $table->integer('so_cost')->nullable()->default(0);
            $table->longText('so_img');
            $table->tinyInteger('status')->default(1)->comment('1 = Created, 2 = Confirmed, 3 = Completed');
            $table->integer('so_paid')->nullable()->default(0);
            $table->integer('so_difference')->nullable()->default(0);
            $table->integer('so_cashier')->nullable();
            $table->integer('acc_by')->nullable()->comment('staff_id');
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
