<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi dokumen pengiriman PMO pada detail Kas Gudang (terima/penyerahan armada).
 * Nullable: baris lama & aktivitas non-API tetap kosong — tidak mengubah form/validasi existing.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cash_gudang_details')) {
            return;
        }
        if (Schema::hasColumn('cash_gudang_details', 'ref_shipment_id')) {
            return;
        }

        Schema::table('cash_gudang_details', function (Blueprint $table) {
            $table->string('ref_shipment_id', 100)->nullable()->index()->after('cgd_notes');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cash_gudang_details')
            || ! Schema::hasColumn('cash_gudang_details', 'ref_shipment_id')) {
            return;
        }

        Schema::table('cash_gudang_details', function (Blueprint $table) {
            $table->dropColumn('ref_shipment_id');
        });
    }
};
