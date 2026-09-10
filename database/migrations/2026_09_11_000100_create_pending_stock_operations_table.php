<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Antrian mutasi stok saat gudang punya Stock Opname open (draft/menunggu).
     * Status: 1=pending, 2=applied, 3=cancelled, 0=soft-delete
     */
    public function up(): void
    {
        if (Schema::hasTable('pending_stock_operations')) {
            return;
        }

        Schema::create('pending_stock_operations', function (Blueprint $table) {
            $table->bigIncrements('pso_id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('domain', 20)->comment('product|supplies');
            $table->string('source_type', 40)->comment('production_acc|stock_transfer_ship|stock_transfer_accept');
            $table->unsignedBigInteger('source_id');
            $table->string('source_code')->nullable();
            $table->json('payload')->nullable();
            $table->tinyInteger('status')->default(1)->comment('1=pending,2=applied,3=cancelled,0=soft-delete');
            $table->string('blocked_by_opname_type', 20)->nullable()->comment('produk|bahan');
            $table->unsignedBigInteger('blocked_by_opname_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('applied_by')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('warehouse_id', 'pso_warehouse_id_idx');
            $table->index(['warehouse_id', 'status'], 'pso_warehouse_status_idx');
            $table->index(['source_type', 'source_id', 'status'], 'pso_source_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_stock_operations');
    }
};
