<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remap status Stock Transfer ke flow 3 tahap:
     * 1=Pending, 2=Kirim, 3=Ditolak, 4=Diterima
     *
     * Data lama: status 1 sudah potong stok → jadi Kirim (2)
     * Data lama: status 2 = diterima → jadi Diterima (4)
     */
    public function up(): void
    {
        // Diterima lama (2) → 4 dulu supaya tidak bentrok saat 1→2
        DB::table('stock_transfers')->where('status', 2)->update(['status' => 4]);
        // Pending lama (1, stok sudah keluar) → Kirim (2)
        DB::table('stock_transfers')->where('status', 1)->update(['status' => 2]);
    }

    public function down(): void
    {
        // Balikkan: Kirim (2) → Pending (1), Diterima (4) → 2
        DB::table('stock_transfers')->where('status', 2)->update(['status' => 1]);
        DB::table('stock_transfers')->where('status', 4)->update(['status' => 2]);
    }
};
