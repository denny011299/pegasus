<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_product_return_details')) {
            return;
        }

        if (! Schema::hasColumn('customer_product_return_details', 'destination_warehouse_id')) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->unsignedBigInteger('destination_warehouse_id')->nullable()->after('warehouse_id');
                $table->index('destination_warehouse_id', 'cpr_detail_dest_wh_idx');
            });
        }

        DB::table('customer_product_return_details')
            ->whereNull('destination_warehouse_id')
            ->update([
                'destination_warehouse_id' => DB::raw('warehouse_id'),
            ]);

        $indexes = $this->indexNames();
        if (in_array('cpr_detail_item_unique', $indexes, true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->dropUnique('cpr_detail_item_unique');
            });
        }
        if (! in_array('cpr_detail_item_dest_unique', $indexes, true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->unique(
                    ['return_id', 'product_variant_id', 'unit_id', 'warehouse_id', 'destination_warehouse_id'],
                    'cpr_detail_item_dest_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_product_return_details')) {
            return;
        }

        $indexes = $this->indexNames();
        if (in_array('cpr_detail_item_dest_unique', $indexes, true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->dropUnique('cpr_detail_item_dest_unique');
            });
        }

        if (Schema::hasColumn('customer_product_return_details', 'destination_warehouse_id')) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->dropIndex('cpr_detail_dest_wh_idx');
                $table->dropColumn('destination_warehouse_id');
            });
        }

        $indexes = $this->indexNames();
        if (! in_array('cpr_detail_item_unique', $indexes, true)) {
            Schema::table('customer_product_return_details', function (Blueprint $table) {
                $table->unique(
                    ['return_id', 'product_variant_id', 'unit_id', 'warehouse_id'],
                    'cpr_detail_item_unique'
                );
            });
        }
    }

    /** @return array<int, string> */
    private function indexNames(): array
    {
        return collect(DB::select('SHOW INDEX FROM customer_product_return_details'))
            ->pluck('Key_name')
            ->map(fn ($name) => (string) $name)
            ->unique()
            ->values()
            ->all();
    }
};
