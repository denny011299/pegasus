<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti foto pengiriman (GitHub #140) — wajib diisi saat Kirim (status=2),
     * disimpan di public/stock_transfers/.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('stock_transfers', 'ship_proof_path')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->string('ship_proof_path', 255)->nullable()->after('acc_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_transfers', 'ship_proof_path')) {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropColumn('ship_proof_path');
            });
        }
    }
};
