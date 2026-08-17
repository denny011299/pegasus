<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dukungan PUT /api/external/v1/shipments/{ref_shipment_id}/cancel.
     *
     * status = 7 baru: "Dibatalkan (API)" - satu-satunya cara baris sales_orders bisa punya nilai
     * ini adalah lewat endpoint ini (App\Http\Controllers\ExternalApi\V1\ShipmentController::
     * cancel(), lewat App\Support\SalesOrderCancellation::cancel()). Dipilih nomor baru, BUKAN
     * memakai status = 3 yang sudah ada ("Ditolak" lewat CustomerController::declineSO()/
     * deleteSalesOrder()) - keduanya SEKILAS mirip secara bisnis (SO yang tidak jadi berjalan),
     * tapi menyamakan angkanya akan ikut memicu efek samping SalesOrder::updateSalesOrder() yang
     * sudah ada untuk status = 3 ("dianggap input ulang, kembali ke antrean ACC" - lihat baris
     * itu) setiap kali shipment yang dibatalkan lewat API diedit dari halaman admin - bukan
     * perilaku yang diinginkan endpoint ini. Sama pola dengan status = 4/5/6 yang ditambah
     * sebelumnya di modul Shipment - lihat migrasi 2026_08_11_130000_* dan 2026_08_12_110000_*.
     *
     * cancel_reason: alasan pembatalan dari body permintaan (reason), disimpan terpisah dari
     * notes (yang sudah dipakai catatan pengiriman biasa) supaya keduanya tidak saling menimpa.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('sales_orders', 'cancel_reason')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->text('cancel_reason')->nullable()->after('notes')
                    ->comment('Alasan pembatalan (PUT /shipments/{ref}/cancel, body.reason)');
            });
        }

        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), "
            ."5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), "
            ."6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), "
            ."7 = Dibatalkan (API, lewat PUT /shipments/{ref}/cancel, stok dikembalikan kalau sebelumnya Confirmed)'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), "
            ."5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), "
            ."6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status)'"
        );

        if (Schema::hasColumn('sales_orders', 'cancel_reason')) {
            Schema::table('sales_orders', function (Blueprint $table) {
                $table->dropColumn('cancel_reason');
            });
        }
    }
};
