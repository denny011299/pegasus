<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GitHub #53: dashboard Changelog perlu mencatat kapan staf membuka sebuah menu dan berapa lama,
 * bukan cuma mutasi data (lihat LogDashboardActivity). `activity_type` membedakan baris "open"
 * (menu dibuka) dari baris "change" (mutasi data, perilaku lama) SUPAYA
 * ReportController::dashboardChangeLogCounts() -- yang menghitung SEMUA baris dashboard_change_logs
 * di periode sebagai "menunggu ACC Direktur" -- tidak ikut menghitung page-view biasa.
 * `duration_seconds` diisi belakangan (saat request berikutnya dari staf yang sama terdeteksi),
 * jadi harus nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('dashboard_change_logs', 'activity_type')) {
            Schema::table('dashboard_change_logs', function (Blueprint $table) {
                $table->string('activity_type', 20)->default('change')->after('module_key');
            });
        }

        if (! Schema::hasColumn('dashboard_change_logs', 'duration_seconds')) {
            Schema::table('dashboard_change_logs', function (Blueprint $table) {
                $table->unsignedInteger('duration_seconds')->nullable()->after('meta');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('dashboard_change_logs', 'duration_seconds')) {
            Schema::table('dashboard_change_logs', function (Blueprint $table) {
                $table->dropColumn('duration_seconds');
            });
        }

        if (Schema::hasColumn('dashboard_change_logs', 'activity_type')) {
            Schema::table('dashboard_change_logs', function (Blueprint $table) {
                $table->dropColumn('activity_type');
            });
        }
    }
};
