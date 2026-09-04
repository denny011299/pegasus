<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Jenis" dokumen Stock Opname (Produk + Bahan), untuk fitur "Clean Up Data" (2026-09-04).
 *
 * 1 (default) = Stock Opname biasa -- staf menghitung manual, alur tidak berubah sama sekali.
 * 2 = Bersihkan Data -- tidak ada hitungan manual, dokumen dibuat langsung menggulung ulang
 * ps_stock/ss_stock yang sudah ada ke tangga satuannya lewat OpnameLifecycle::rollUpFromLiveStock()
 * / BahanOpnameLifecycle::rollUpFromLiveStock() (App\Support\UnitRollUp yang sama, cuma diisi dari
 * stok live, bukan ketikan staf). Gudang utama saja -- ditegakkan di controller, bukan di kolom ini.
 *
 * Nama kolom SAMA di kedua tabel (sto_type, bukan stob_type) -- mengikuti pola is_draft/
 * is_old_version yang juga tidak diberi prefix beda per tabel (lihat
 * 2026_07_31_010000_add_is_draft_to_stock_opnames_table.php dan
 * 2026_08_27_010000_add_is_old_version_to_stock_opname_tables.php).
 */
return new class extends Migration
{
    private const TABLES = ['stock_opnames', 'stock_opname_bahans'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'sto_type')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedTinyInteger('sto_type')->default(1)->after('is_old_version');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sto_type')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('sto_type');
                });
            }
        }
    }
};
