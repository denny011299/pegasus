<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_stocks', 'ps_alert_stock')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->integer('ps_alert_stock')->default(0)->after('ps_safety_stock');
            });
        }

        // Backfill: salin product_variant_alert → ps_alert_stock di unit default variant
        if (Schema::hasColumn('product_stocks', 'ps_alert_stock')
            && Schema::hasColumn('product_variants', 'product_variant_alert')) {
            DB::statement("
                UPDATE product_stocks ps
                INNER JOIN product_variants pv
                    ON pv.product_variant_id = ps.product_variant_id
                   AND pv.unit_id = ps.unit_id
                   AND pv.status = 1
                SET ps.ps_alert_stock = COALESCE(pv.product_variant_alert, 0)
                WHERE COALESCE(ps.ps_alert_stock, 0) = 0
                  AND COALESCE(pv.product_variant_alert, 0) > 0
                  AND ps.status = 1
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_stocks', 'ps_alert_stock')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->dropColumn('ps_alert_stock');
            });
        }
    }
};
