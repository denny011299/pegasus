<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemisah versi skema Stock Opname (Produk + Bahan), langkah 1 dari rancang ulang 2026-08-27.
 *
 * Dokumen LAMA menyimpan kuantitas sebagai kalimat siap-cetak di longtext (stod_system/stod_real/
 * stod_selisih, "16 DOS, 0 pcs") dan sama sekali tidak menyimpan angka per satuan -- angka yang
 * benar-benar menimpa ps_stock cuma ada di DOM browser dan dikirim ulang saat ACC. Dari situ
 * seluruh rentetan bug feature ini lahir: GitHub #53 (butuh flag stod_touched karena "belum
 * dihitung" tidak bisa diwakili string), #78 (input kosong terpaksa fallback ke stok sistem lalu
 * menimpa stok live), token "-" + humanizeUntouchedForPdf() + StockOpnameUntouchedUnitHealer +
 * migration backfill 2026_08_21 (empat mekanisme berbeda untuk menyelundupkan NULL ke dalam
 * string), sampai highlight PDF yang bisa bertentangan dengan angka di sebelahnya (SP0071).
 *
 * Dokumen BARU pindah ke stock_opname_lines: satu baris per satuan, angka betulan, counted_qty
 * NULL = tidak dihitung.
 *
 * DEFAULT SENGAJA `true`. Semua dokumen yang sudah ada memang versi lama, jadi default ini
 * membuat migration murni ADD COLUMN -- tanpa backfill, tanpa UPDATE, tanpa peluang salah
 * melabeli data historis. Konsekuensinya: alur insert baru WAJIB menulis is_old_version = false
 * secara eksplisit, tidak boleh mengandalkan default model.
 */
return new class extends Migration
{
    private const TABLES = ['stock_opnames', 'stock_opname_bahans'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'is_old_version')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->boolean('is_old_version')->default(true)->after('is_draft');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'is_old_version')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('is_old_version');
                });
            }
        }
    }
};
