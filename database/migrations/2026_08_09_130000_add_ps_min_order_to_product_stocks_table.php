<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `product_stocks.ps_min_order` (manual "Pemesanan Min." override, read/written by
 * App\Models\StockAlert::getStockAlert()/updateMinOrder()) existed on the developer's own
 * database (see the ps_min_order column/data in database/okeh8644_pegasus.sql) but had no
 * migration and no database/sql/*.sql patch anywhere in the repo — so a fresh clone, CI, the
 * pegasus_testing DB, and any shared-hosting deploy run through DeployController's
 * `/deploy/migrate` would never get this column. StockAlert::updateMinOrder() already guards
 * for its absence (returns a friendly "Kolom ps_min_order belum tersedia" error instead of a
 * 500), so the feature degraded silently rather than crashing — see KNOWN_ISSUES.md.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('product_stocks', 'ps_min_order')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->integer('ps_min_order')->nullable()->after('ps_alert_stock');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_stocks', 'ps_min_order')) {
            Schema::table('product_stocks', function (Blueprint $table) {
                $table->dropColumn('ps_min_order');
            });
        }
    }
};
