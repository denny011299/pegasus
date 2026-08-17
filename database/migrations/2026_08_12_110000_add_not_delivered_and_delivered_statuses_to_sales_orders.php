<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Dukungan PATCH /api/external/v1/shipments/{ref_shipment_id}/change-status.
     *
     * status = 5, 6 baru: "Belum Terkirim (API)" dan "Sudah Terkirim (API)" - satu-satunya cara
     * baris sales_orders bisa punya nilai ini adalah dipaksa lewat endpoint ini (lihat
     * App\Http\Controllers\ExternalApi\V1\ShipmentController::changeStatus() dan
     * App\ExternalApi\Support\ShipmentStatusMap). Dipilih nomor baru, BUKAN memakai status = 3
     * yang sudah ada, karena 3 di kosakata internal sudah dipakai ambigu (Completed/Ditolak/
     * soft-delete di alur admin) - menimpanya lewat API akan tabrakan makna dengan alur itu.
     *
     * ipm_status (kontrak API) <-> sales_orders.status (internal) TIDAK simetris di sini:
     * ipm_status 1 "Dijadwalkan" bisa datang dari status 1 ATAU 4, tapi change-status yang
     * menargetkan "Dijadwalkan" SELALU menulis status = 4 (dijadwalkan lewat API), tidak pernah
     * status = 1 (dibuat manual lewat admin - beda alur). Lihat docblock ShipmentStatusMap.
     *
     * PENTING - butuh konfirmasi pemilik produk (dicatat di KNOWN_ISSUES.md): change-status
     * SAAT INI cuma memaksa kolom status, TIDAK menjalankan efek samping apa pun (mis. menuju
     * "Berjalan" tidak memotong stok lewat SalesOrderApproval::confirm() seperti /shipments/shipped,
     * menuju "Sudah Terkirim" tidak melakukan apa pun selain menyetel labelnya). Ini pilihan
     * sengaja untuk rilis pertama endpoint ini ("for now only force change the status") - perlu
     * ditinjau ulang begitu alur bisnis tiap transisi status sudah jelas.
     *
     * Tidak ada doctrine/dbal di proyek ini, jadi comment kolom diubah lewat SQL mentah (MODIFY),
     * bukan Blueprint::change() - sama seperti migrasi 2026_08_11_130000_*.
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), "
            ."5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), "
            ."6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status)'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong)'"
        );
    }
};
