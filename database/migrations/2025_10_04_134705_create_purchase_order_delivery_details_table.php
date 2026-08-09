<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_order_delivery_details')) {
            return;
        }

        Schema::create('purchase_order_delivery_details', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_delivery_details');
    }
};
