<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot header dokumen Stock Opname versi baru (langkah 2 dari rancang ulang 2026-08-27).
 *
 * Dokumen yang sudah diputuskan adalah BUKTI keadaan pada satu tanggal, bukan tampilan live --
 * jadi tidak boleh bergantung pada baris lain yang masih bisa berubah/dihapus. Yang sekarang
 * terjadi di generateStockOpname(): `Staff::find($stockOpname['staff_id'])` lalu blade langsung
 * `{{ $staff_name['staff_name'] }}` (Backoffice/PDF/Opname.blade.php) -- kalau staff penanggung
 * jawab dihapus, MENCETAK ULANG dokumen yang sudah disetujui error, padahal isinya sudah final.
 *
 * Kapan diisi (aturan siklus hidup, sama seperti stock_opname_lines):
 *  - sto_staff_name  : saat dokumen PERTAMA KALI keluar dari draft (publish). Untuk UI sekarang
 *                      itu terjadi langsung di insert, karena tombol simpan mengirim is_draft = 0
 *                      (tombol draft belum dipasang di CreateStockOpname.blade.php).
 *  - sto_acc_name    : saat diputuskan (disetujui ATAU ditolak).
 *  - sto_decided_at  : idem -- penanda bahwa snapshot stok sistem di baris detail sudah beku.
 *
 * Selama masih draft ketiganya NULL: draft itu dokumen kerja, wajar kalau ikut nama terkini.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_opnames')) {
            return;
        }

        Schema::table('stock_opnames', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_opnames', 'sto_staff_name')) {
                $table->string('sto_staff_name', 255)->nullable()->after('staff_id');
            }
            if (! Schema::hasColumn('stock_opnames', 'sto_acc_name')) {
                $table->string('sto_acc_name', 255)->nullable()->after('acc_by');
            }
            if (! Schema::hasColumn('stock_opnames', 'sto_decided_at')) {
                $table->timestamp('sto_decided_at')->nullable()->after('sto_acc_name');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_opnames')) {
            return;
        }

        foreach (['sto_staff_name', 'sto_acc_name', 'sto_decided_at'] as $column) {
            if (Schema::hasColumn('stock_opnames', $column)) {
                Schema::table('stock_opnames', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
