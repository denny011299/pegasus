<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenis item pembayaran dari External API POST /payments/cash (PMO issue #28):
     * cash = uang tunai, potongan = potongan nominal (mis. cash diskon 3%), potongan_barang =
     * potongan berupa barang yang juga melahirkan dokumen Pengembalian (mis. jerigen).
     *
     * Semua jenis ikut dihitung di cr_nominal/cs_nominal supaya total terbayar sama dengan PMO;
     * kolom ini yang membedakan tunai dari potongan di laporan. Baris lama dan baris dari admin
     * otomatis 'cash'.
     */
    public function up(): void
    {
        Schema::table('cash_armada_details', function (Blueprint $table) {
            $table->string('crd_kind', 20)->default('cash')->after('crd_type')
                ->comment('cash | potongan | potongan_barang');
        });

        Schema::table('cash_sales_details', function (Blueprint $table) {
            $table->string('csd_kind', 20)->default('cash')->after('csd_type')
                ->comment('cash | potongan | potongan_barang');
        });
    }

    public function down(): void
    {
        Schema::table('cash_armada_details', function (Blueprint $table) {
            $table->dropColumn('crd_kind');
        });

        Schema::table('cash_sales_details', function (Blueprint $table) {
            $table->dropColumn('csd_kind');
        });
    }
};
