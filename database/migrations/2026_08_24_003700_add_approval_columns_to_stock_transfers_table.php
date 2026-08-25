<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_transfers')) {
            return;
        }

        Schema::table('stock_transfers', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_transfers', 'qc_approved_by')) {
                $table->unsignedInteger('qc_approved_by')->nullable()->after('acc_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'qc_approved_at')) {
                $table->timestamp('qc_approved_at')->nullable()->after('qc_approved_by');
            }
            if (! Schema::hasColumn('stock_transfers', 'ops_approved_by')) {
                $table->unsignedInteger('ops_approved_by')->nullable()->after('qc_approved_at');
            }
            if (! Schema::hasColumn('stock_transfers', 'ops_approved_at')) {
                $table->timestamp('ops_approved_at')->nullable()->after('ops_approved_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_transfers')) {
            return;
        }

        Schema::table('stock_transfers', function (Blueprint $table) {
            foreach (['qc_approved_by', 'qc_approved_at', 'ops_approved_by', 'ops_approved_at'] as $col) {
                if (Schema::hasColumn('stock_transfers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
