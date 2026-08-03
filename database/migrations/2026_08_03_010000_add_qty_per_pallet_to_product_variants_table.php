<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            if (! Schema::hasColumn('product_variants', 'qty_per_pallet')) {
                // 1 Pallet = N satuan default produk (biasanya DOS). Hanya dipakai di form Produksi.
                $table->unsignedInteger('qty_per_pallet')->nullable()->after('unit_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('product_variants')) {
            return;
        }

        Schema::table('product_variants', function (Blueprint $table) {
            if (Schema::hasColumn('product_variants', 'qty_per_pallet')) {
                $table->dropColumn('qty_per_pallet');
            }
        });
    }
};
