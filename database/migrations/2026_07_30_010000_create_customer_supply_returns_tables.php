<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_supply_returns')) {
            Schema::create('customer_supply_returns', function (Blueprint $table) {
                $table->integerIncrements('return_id');
                $table->string('return_number', 40)->unique();
                $table->integer('so_id')->index();
                $table->integer('customer_id')->index();
                $table->date('return_date')->index();
                $table->string('ref_number', 100)->nullable();
                $table->text('notes')->nullable();
                $table->string('proof_path', 255);
                $table->tinyInteger('status')->default(1)->index()
                    ->comment('0=deleted, 1=pending, 2=accepted, 3=declined');
                $table->integer('created_by')->nullable()->index();
                $table->integer('acc_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('customer_supply_return_details')) {
            Schema::create('customer_supply_return_details', function (Blueprint $table) {
                $table->integerIncrements('return_detail_id');
                $table->integer('return_id')->index();
                $table->integer('supplies_id')->index();
                $table->integer('unit_id')->index();
                $table->integer('warehouse_id')->index();
                $table->unsignedBigInteger('qty');
                $table->tinyInteger('status')->default(1)->index();
                $table->timestamps();
                $table->unique(
                    ['return_id', 'supplies_id', 'unit_id', 'warehouse_id'],
                    'csr_detail_item_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_supply_return_details');
        Schema::dropIfExists('customer_supply_returns');
    }
};
