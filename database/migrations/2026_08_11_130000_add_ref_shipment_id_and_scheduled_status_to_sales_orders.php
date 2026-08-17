<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dukungan POST /api/external/v1/shipments/scheduled.
     *
     * ref_shipment_id: referensi dari sistem eksternal (PMO), pola kolomnya sama dengan
     * ref_payment_id pada cash_armadas/cash_sales - unique (nullable, jadi seluruh baris SO lama
     * yang bukan dari API tetap sah bernilai NULL), varchar karena bentuknya ditentukan sistem
     * luar.
     *
     * status = 4 baru: "Dijadwalkan" - dibuat lewat External API, BELUM di-ACC (stok belum
     * dipotong). Beda dengan status = 1 "Created" yang dibuat manual lewat halaman admin -
     * dipisah supaya asal SO (API vs manual) tetap bisa dibedakan dari status-nya sendiri tanpa
     * kolom tambahan. Tidak ada doctrine/dbal di proyek ini, jadi comment kolom diubah lewat SQL
     * mentah (MODIFY), bukan Blueprint::change().
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sales_orders', 'ref_shipment_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->string('ref_shipment_id', 100)->nullable()->after('so_ref_number')
                    ->comment('Referensi shipment dari sistem eksternal (POST /shipments/scheduled)');
            });
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->unique('ref_shipment_id', 'sales_orders_ref_shipment_id_unique');
            });
        }

        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong)'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed'"
        );

        if (Schema::hasColumn('sales_orders', 'ref_shipment_id')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropUnique('sales_orders_ref_shipment_id_unique');
                $table->dropColumn('ref_shipment_id');
            });
        }
    }
};
