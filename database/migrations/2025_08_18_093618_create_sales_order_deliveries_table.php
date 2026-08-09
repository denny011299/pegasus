<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sales_order_deliveries')) {
            return;
        }

        Schema::create('sales_order_deliveries', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_deliveries');
    }
};
