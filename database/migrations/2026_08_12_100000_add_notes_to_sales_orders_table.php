<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dukungan POST /api/external/v1/shipments/shipped (body.notes) - tidak ada kolom
     * catatan bebas yang sudah ada di sales_orders untuk dipakai ulang (so_ref_number
     * adalah field "Ref Number" terpisah yang sudah dipakai halaman admin, bukan catatan
     * bebas). Nullable - seluruh SO lama maupun yang dibuat lewat halaman admin tidak
     * mengisi field ini.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sales_orders', 'notes')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->text('notes')->nullable()->after('ref_shipment_id')
                    ->comment('Catatan shipment, diisi lewat POST /shipments/shipped (body.notes)');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sales_orders', 'notes')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('notes');
            });
        }
    }
};
