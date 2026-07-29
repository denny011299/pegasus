<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Stock Transfer header + detail.
     * Status: 0=deleted, 1=pending, 2=success (ACC), 3=rejected
     */
    public function up(): void
    {
        if (! Schema::hasTable('stock_transfers')) {
            Schema::create('stock_transfers', function (Blueprint $table) {
                $table->integerIncrements('st_id');
                $table->string('transfer_code', 30)->unique();
                $table->date('transfer_date');
                $table->unsignedInteger('sender_id');
                $table->unsignedInteger('receiver_id')->nullable();
                $table->unsignedBigInteger('from_warehouse_id');
                $table->unsignedBigInteger('to_warehouse_id');
                $table->longText('note')->nullable();
                $table->longText('accept_note')->nullable();
                $table->tinyInteger('status')->default(1)->comment('0=deleted,1=pending,2=success,3=rejected');
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('acc_by')->nullable();
                $table->timestamps();

                $table->index('from_warehouse_id', 'stock_transfers_from_wh_idx');
                $table->index('to_warehouse_id', 'stock_transfers_to_wh_idx');
                $table->index('status', 'stock_transfers_status_idx');
            });
        }

        if (! Schema::hasTable('stock_transfer_details')) {
            Schema::create('stock_transfer_details', function (Blueprint $table) {
                $table->integerIncrements('std_id');
                $table->unsignedInteger('st_id');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('product_variant_id');
                $table->unsignedInteger('unit_id');
                $table->decimal('qty', 18, 4)->default(0);
                $table->decimal('qty_received', 18, 4)->nullable();
                $table->tinyInteger('status')->default(1)->comment('1=active,0=inactive');
                $table->timestamps();

                $table->index('st_id', 'stock_transfer_details_st_id_idx');
                $table->index('product_variant_id', 'stock_transfer_details_pv_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_details');
        Schema::dropIfExists('stock_transfers');
    }
};
