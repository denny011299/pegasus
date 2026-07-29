<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_delivery_orders', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('pdo_id', true);
            $table->integer('po_id');
            $table->string('pdo_number', 10);
            $table->string('pdo_receiver', 150);
            $table->integer('staff_id')->nullable();
            $table->date('pdo_date');
            $table->string('pdo_phone', 50)->nullable();
            $table->text('pdo_desc')->nullable();
            $table->tinyInteger('status')->nullable()->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_delivery_orders');
    }
};
