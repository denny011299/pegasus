<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('production_details')
            && ! Schema::hasColumn('production_details', 'destination_warehouse_id')) {
            Schema::table('production_details', function (Blueprint $table) {
                $table->unsignedBigInteger('destination_warehouse_id')->nullable()->after('unit_id');
                $table->index('destination_warehouse_id', 'production_details_destination_wh_idx');
            });
        }

        if (Schema::hasTable('stock_transfers')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                if (! Schema::hasColumn('stock_transfers', 'source_type')) {
                    $table->string('source_type', 30)->nullable()->after('accept_note');
                    $table->index('source_type', 'stock_transfers_source_type_idx');
                }
                if (! Schema::hasColumn('stock_transfers', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                    $table->index('source_id', 'stock_transfers_source_id_idx');
                }
                if (! Schema::hasColumn('stock_transfers', 'disposition')) {
                    $table->string('disposition', 30)->nullable()->after('source_id');
                }
            });
        }

        if (Schema::hasTable('product_issues')) {
            Schema::table('product_issues', function (Blueprint $table) {
                if (! Schema::hasColumn('product_issues', 'source_type')) {
                    $table->string('source_type', 30)->nullable()->after('pi_notes');
                    $table->index('source_type', 'product_issues_source_type_idx');
                }
                if (! Schema::hasColumn('product_issues', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
                    $table->index('source_id', 'product_issues_source_id_idx');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_issues')) {
            Schema::table('product_issues', function (Blueprint $table) {
                if (Schema::hasColumn('product_issues', 'source_id')) {
                    $table->dropIndex('product_issues_source_id_idx');
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('product_issues', 'source_type')) {
                    $table->dropIndex('product_issues_source_type_idx');
                    $table->dropColumn('source_type');
                }
            });
        }

        if (Schema::hasTable('stock_transfers')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                if (Schema::hasColumn('stock_transfers', 'disposition')) {
                    $table->dropColumn('disposition');
                }
                if (Schema::hasColumn('stock_transfers', 'source_id')) {
                    $table->dropIndex('stock_transfers_source_id_idx');
                    $table->dropColumn('source_id');
                }
                if (Schema::hasColumn('stock_transfers', 'source_type')) {
                    $table->dropIndex('stock_transfers_source_type_idx');
                    $table->dropColumn('source_type');
                }
            });
        }

        if (Schema::hasTable('production_details')
            && Schema::hasColumn('production_details', 'destination_warehouse_id')) {
            Schema::table('production_details', function (Blueprint $table) {
                $table->dropIndex('production_details_destination_wh_idx');
                $table->dropColumn('destination_warehouse_id');
            });
        }
    }
};
