<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * QC7: after declineTt, purchase_orders.tt_id is nulled so POs can join a new TT.
 * viewTandaTerima only loads invoices via live tt_id, so rejected TT PDFs lost "Rincian Tagihan".
 * Snapshot the PO ids onto the TT at decline time; print falls back to this when status=0.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('purchase_order_tts')) {
            return;
        }

        Schema::table('purchase_order_tts', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_tts', 'declined_po_ids')) {
                $table->text('declined_po_ids')->nullable()->after('tt_desc')
                    ->comment('JSON array of po_id linked at decline time; for print after tt_id cleared');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('purchase_order_tts')) {
            return;
        }

        Schema::table('purchase_order_tts', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_order_tts', 'declined_po_ids')) {
                $table->dropColumn('declined_po_ids');
            }
        });
    }
};
