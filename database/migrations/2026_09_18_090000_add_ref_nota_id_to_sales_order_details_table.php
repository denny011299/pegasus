<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_order_details') || Schema::hasColumn('sales_order_details', 'ref_nota_id')) {
            return;
        }

        Schema::table('sales_order_details', function (Blueprint $table) {
            // BIGINT UNSIGNED, sama seperti units.ref_unit_id/products.ref_product_id — id PMO
            // aslinya 16 digit, jauh melewati batas INT (lihat migrasi
            // widen_pmo_reference_id_columns).
            $table->unsignedBigInteger('ref_nota_id')->nullable()->after('sod_sku')
                ->comment('oms_order.id pada sistem PMO — nota asal baris item ini, GitHub #180');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_order_details') || ! Schema::hasColumn('sales_order_details', 'ref_nota_id')) {
            return;
        }

        Schema::table('sales_order_details', function (Blueprint $table) {
            $table->dropColumn('ref_nota_id');
        });
    }
};
