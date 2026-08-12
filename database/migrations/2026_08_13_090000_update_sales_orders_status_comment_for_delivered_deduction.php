<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Perubahan komentar kolom saja — DIKONFIRMASI pemilik produk 2026-08-13: transisi
     * PATCH /shipments/{ref}/change-status (status = 6 "Sudah Terkirim") sekarang MEMOTONG STOK
     * sungguhan (lewat App\Support\SalesOrderApproval::confirm(), sama seperti /shipments/shipped),
     * bukan cuma tulis status seperti sebelumnya. Aturan lengkap: satu-satunya transisi yang
     * diizinkan saat ini adalah Dijadwalkan (1/4) -> Sudah Terkirim (6); target lain (5 "Belum
     * Terkirim") belum bisa dihasilkan endpoint manapun, dipertahankan untuk kalau dibuka nanti.
     *
     * Tidak ada doctrine/dbal di proyek ini, jadi comment kolom diubah lewat SQL mentah (MODIFY).
     */
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), "
            ."5 = Belum Terkirim (API, belum ada endpoint yang menghasilkan ini), "
            ."6 = Sudah Terkirim (API, lewat PATCH /shipments/{ref}/change-status dari status 4 — MEMOTONG STOK sungguhan), "
            ."7 = Dibatalkan (API, lewat PUT /shipments/{ref}/cancel, stok dikembalikan kalau sebelumnya Confirmed atau Sudah Terkirim)'"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE sales_orders MODIFY status TINYINT NOT NULL DEFAULT 1 COMMENT "
            ."'1 = Created, 2 = Confirmed, 3 = Completed, 4 = Dijadwalkan (dibuat lewat External API /shipments/scheduled, stok belum dipotong), "
            ."5 = Belum Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), "
            ."6 = Sudah Terkirim (API, dipaksa lewat PATCH /shipments/{ref}/change-status), "
            ."7 = Dibatalkan (API, lewat PUT /shipments/{ref}/cancel, stok dikembalikan kalau sebelumnya Confirmed)'"
        );
    }
};
