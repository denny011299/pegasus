<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Perubahan komentar kolom saja (GitHub #184) — App\Synchronization\SyncStatus menambah status
 * "partial" (sebagian baris berhasil, sebagian gagal, tapi tetap memenuhi prasyarat langkah
 * berikutnya — lihat PrerequisiteChecker). Tidak ada doctrine/dbal di proyek ini, jadi comment
 * kolom diubah lewat SQL mentah (MODIFY), sama seperti migrasi
 * 2026_08_13_090000_update_sales_orders_status_comment_for_delivered_deduction.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sync_executions')) {
            return;
        }

        DB::statement(
            "ALTER TABLE sync_executions MODIFY status VARCHAR(20) NOT NULL "
            ."COMMENT 'not_executed, running, success, partial, failed'"
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('sync_executions')) {
            return;
        }

        DB::statement(
            "ALTER TABLE sync_executions MODIFY status VARCHAR(20) NOT NULL "
            ."COMMENT 'not_executed, running, success, failed'"
        );
    }
};
