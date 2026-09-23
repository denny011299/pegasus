<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom untuk Sinkronisasi Pengiriman PMO (Spec B, lihat
 * cdocs/docs/specs/shipment-pmo-sync-flow.md) — App\Synchronization\Steps\ShipmentFlow\
 * SyncShipmentsStep.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_orders', 'pmo_sync_note')) {
                $table->text('pmo_sync_note')->nullable()->after('reject_reason');
            }
            if (! Schema::hasColumn('sales_orders', 'pmo_synced_at')) {
                $table->timestamp('pmo_synced_at')->nullable()->after('pmo_sync_note');
            }
            if (! Schema::hasColumn('sales_orders', 'pmo_bukti_foto')) {
                $table->text('pmo_bukti_foto')->nullable()->after('pmo_synced_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_orders')) {
            return;
        }

        Schema::table('sales_orders', function (Blueprint $table) {
            foreach (['pmo_sync_note', 'pmo_synced_at', 'pmo_bukti_foto'] as $col) {
                if (Schema::hasColumn('sales_orders', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
