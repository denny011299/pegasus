<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_warehouses', function (Blueprint $table) {
            $table->id();
            // cocokkan tipe staffs.staff_id (INT signed)
            $table->integer('staff_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->timestamps();

            $table->unique(['staff_id', 'warehouse_id']);
            $table->index('staff_id');
            $table->index('warehouse_id');

            $table->foreign('staff_id')
                ->references('staff_id')
                ->on('staffs')
                ->cascadeOnDelete();

            $table->foreign('warehouse_id')
                ->references('id')
                ->on('warehouses')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_warehouses');
    }
};
