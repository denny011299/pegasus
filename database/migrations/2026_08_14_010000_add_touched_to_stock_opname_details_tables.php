<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * GitHub #53: PDF Stock Opname perlu membedakan baris yang benar-benar diisi staf (real-stock
 * di-input manual) dari baris yang cuma ikut tersimpan dengan real qty auto = stok sistem karena
 * dibiarkan kosong (lihat CreateStockOpname.js/CreateStockOpnameSupplies.js insertData()) --
 * tanpa kolom ini keduanya sama-sama terlihat "cocok, tidak ada selisih" di PDF.
 *
 * Kolom ini murni tampilan/PDF -- accStockOpname(Bahan) tidak membaca stock_opname_details sama
 * sekali (lihat cdocs/docs/flows/stock-opname/FLOW.md), jadi tidak menyentuh logika approval/stok.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('stock_opname_details', 'stod_touched')) {
            Schema::table('stock_opname_details', function (Blueprint $table) {
                $table->boolean('stod_touched')->default(false)->after('stod_notes');
            });
        }

        if (! Schema::hasColumn('stock_opname_detail_bahans', 'stobd_touched')) {
            Schema::table('stock_opname_detail_bahans', function (Blueprint $table) {
                $table->boolean('stobd_touched')->default(false)->after('stobd_notes');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_opname_details', 'stod_touched')) {
            Schema::table('stock_opname_details', function (Blueprint $table) {
                $table->dropColumn('stod_touched');
            });
        }

        if (Schema::hasColumn('stock_opname_detail_bahans', 'stobd_touched')) {
            Schema::table('stock_opname_detail_bahans', function (Blueprint $table) {
                $table->dropColumn('stobd_touched');
            });
        }
    }
};
