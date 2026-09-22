<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom approval 2 tahap (Staf QC & Gudang -> Kepala Operasional) untuk sales_orders — lihat
 * cdocs/docs/specs/shipment-external-api-approval-flow.md. Pola sama persis dengan
 * 2026_08_24_003700_add_approval_columns_to_stock_transfers_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'qc_approved_by')) {
                $table->unsignedInteger('qc_approved_by')->nullable()->after('acc_by');
            }
            if (! Schema::hasColumn('sales_orders', 'qc_approved_at')) {
                $table->timestamp('qc_approved_at')->nullable()->after('qc_approved_by');
            }
            if (! Schema::hasColumn('sales_orders', 'ops_approved_by')) {
                $table->unsignedInteger('ops_approved_by')->nullable()->after('qc_approved_at');
            }
            if (! Schema::hasColumn('sales_orders', 'ops_approved_at')) {
                $table->timestamp('ops_approved_at')->nullable()->after('ops_approved_by');
            }
            if (! Schema::hasColumn('sales_orders', 'rejected_by')) {
                $table->unsignedInteger('rejected_by')->nullable()->after('ops_approved_at');
            }
            if (! Schema::hasColumn('sales_orders', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('sales_orders', 'reject_stage')) {
                $table->string('reject_stage', 10)->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('sales_orders', 'reject_reason')) {
                $table->text('reject_reason')->nullable()->after('reject_stage');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            foreach ([
                'qc_approved_by', 'qc_approved_at',
                'ops_approved_by', 'ops_approved_at',
                'rejected_by', 'rejected_at', 'reject_stage', 'reject_reason',
            ] as $col) {
                if (Schema::hasColumn('sales_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
