<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Work Order (FORM-OPS-09): 1 dokumen per PIC (SPV) dalam satu Production Planning.
 * Item tetap di production_planning_items; kolom production_work_order_id menautkannya.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('production_work_orders')) {
            Schema::create('production_work_orders', function (Blueprint $table) {
                $table->charset('utf8mb4');
                $table->collation('utf8mb4_0900_ai_ci');

                $table->increments('production_work_order_id');
                $table->unsignedInteger('production_planning_id');
                $table->unsignedInteger('warehouse_id')->nullable();
                $table->unsignedInteger('pic_staff_id');
                $table->string('wo_number', 50);
                $table->date('wo_date');
                $table->integer('status')->default(1)->comment('1 = aktif, 0 = soft-delete');
                $table->integer('created_by')->nullable();
                $table->integer('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['production_planning_id', 'pic_staff_id'], 'pwo_pp_pic_unique');
                $table->unique(['warehouse_id', 'wo_number'], 'pwo_wh_wo_number_unique');
                $table->index('production_planning_id', 'pwo_pp_idx');
                $table->index('pic_staff_id', 'pwo_pic_idx');
            });
        }

        if (Schema::hasTable('production_planning_items')
            && ! Schema::hasColumn('production_planning_items', 'production_work_order_id')) {
            Schema::table('production_planning_items', function (Blueprint $table) {
                $table->unsignedInteger('production_work_order_id')->nullable()->after('pic_staff_id');
                $table->index('production_work_order_id', 'ppi_pwo_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('production_planning_items')
            && Schema::hasColumn('production_planning_items', 'production_work_order_id')) {
            Schema::table('production_planning_items', function (Blueprint $table) {
                $table->dropIndex('ppi_pwo_idx');
                $table->dropColumn('production_work_order_id');
            });
        }
        Schema::dropIfExists('production_work_orders');
    }
};
