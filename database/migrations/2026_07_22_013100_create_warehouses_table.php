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
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->string('warehouse_name', 250);
            $table->foreignId('warehouse_type_id')
                ->constrained('warehouse_types')
                ->restrictOnDelete();
            $table->text('warehouse_address')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1 = active, 0 = deleted');
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('warehouse_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
