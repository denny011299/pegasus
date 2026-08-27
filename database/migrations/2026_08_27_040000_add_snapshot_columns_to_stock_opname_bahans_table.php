<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot header dokumen Stock Opname Bahan versi baru -- kembaran persis
 * 2026_08_27_020000_add_snapshot_columns_to_stock_opnames_table (Produk). Lihat migration itu
 * untuk alasan lengkap (dokumen final tidak boleh gagal cetak cuma karena staff-nya dihapus).
 *
 * Kapan diisi: sama seperti Produk --
 *  - stob_staff_name : saat dokumen pertama kali keluar dari draft (publish).
 *  - stob_acc_name   : saat diputuskan (disetujui ATAU ditolak).
 *  - stob_decided_at : idem -- penanda snapshot stok sistem di stock_opname_bahan_lines beku.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_opname_bahans')) {
            return;
        }

        Schema::table('stock_opname_bahans', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_opname_bahans', 'stob_staff_name')) {
                $table->string('stob_staff_name', 255)->nullable()->after('staff_id');
            }
            if (! Schema::hasColumn('stock_opname_bahans', 'stob_acc_name')) {
                $table->string('stob_acc_name', 255)->nullable()->after('acc_by');
            }
            if (! Schema::hasColumn('stock_opname_bahans', 'stob_decided_at')) {
                $table->timestamp('stob_decided_at')->nullable()->after('stob_acc_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_opname_bahans')) {
            return;
        }

        foreach (['stob_staff_name', 'stob_acc_name', 'stob_decided_at'] as $column) {
            if (Schema::hasColumn('stock_opname_bahans', $column)) {
                Schema::table('stock_opname_bahans', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
