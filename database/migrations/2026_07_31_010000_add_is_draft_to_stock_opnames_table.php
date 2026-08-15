<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draft disimpan sebagai flag terpisah dari `status`, bukan nilai baru di
     * kolomnya. `status` sudah dipakai luas sebagai enum alur approval
     * (1=Menunggu, 2=Disetujui, 3=Ditolak, 0=terhapus) oleh accStockOpname,
     * tolakStockOpname, PDF, dan halaman daftar — menambah nilai baru di sana
     * berarti mengubah makna existing checks di semua tempat itu. Draft itu
     * sendiri secara konsep independen dari status approval: dokumen draft
     * tetap berstatus 1 (Menunggu) sampai benar-benar diajukan, hanya belum
     * boleh dilihat/diproses siapa pun selain pembuatnya.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('stock_opnames', 'is_draft')) {
            Schema::table('stock_opnames', function (Blueprint $table) {
                $table->boolean('is_draft')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_opnames', 'is_draft')) {
            Schema::table('stock_opnames', function (Blueprint $table) {
                $table->dropColumn('is_draft');
            });
        }
    }
};
