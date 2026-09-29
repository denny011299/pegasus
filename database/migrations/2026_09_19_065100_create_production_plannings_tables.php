<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_plannings', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('production_planning_id');
            $table->string('pp_number', 50);
            $table->date('pp_date');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->string('pp_status', 20)->default('draft')->comment('draft | released | work_order | inprod | done');
            $table->unsignedInteger('shipment_shortage_document_id')->nullable();
            $table->unsignedInteger('so_id')->nullable();
            $table->text('notes')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->integer('status')->default(1)->comment('1 = aktif, 0 = soft-delete');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'pp_number'], 'production_plannings_wh_pp_number_unique');
            $table->unique('shipment_shortage_document_id', 'production_plannings_shortage_doc_unique');
            $table->index('pp_status');
            $table->index('pp_date');
            $table->index('so_id');
            $table->index(['warehouse_id', 'status', 'pp_status'], 'pp_wh_status_idx');
        });

        Schema::create('production_planning_items', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('ppi_id');
            $table->unsignedInteger('production_planning_id');
            $table->unsignedInteger('product_variant_id')->nullable();
            $table->string('sku', 100)->nullable();
            $table->string('product_name', 255);
            $table->decimal('qty', 18, 2)->default(0);
            $table->unsignedInteger('unit_id')->nullable();
            $table->string('unit_label', 100)->nullable();
            $table->unsignedInteger('production_skala_id')->nullable();
            $table->unsignedInteger('armada_customer_id')->nullable()->comment('customers.customer_id (armada)');
            $table->unsignedInteger('pic_staff_id')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();

            $table->index('production_planning_id', 'ppi_planning_id_idx');
            $table->index('product_variant_id', 'ppi_variant_idx');
            $table->index('production_skala_id', 'ppi_skala_idx');
            $table->index('pic_staff_id', 'ppi_pic_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_planning_items');
        Schema::dropIfExists('production_plannings');
    }
};
