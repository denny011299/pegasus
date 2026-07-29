<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_delivery_orders', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('sdo_id', true);
            $table->integer('so_id');
            $table->string('sdo_number', 10);
            $table->string('sdo_receiver', 150);
            $table->date('sdo_date');
            $table->string('sdo_phone', 50);
            $table->text('sdo_desc')->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_delivery_orders');
    }
};
