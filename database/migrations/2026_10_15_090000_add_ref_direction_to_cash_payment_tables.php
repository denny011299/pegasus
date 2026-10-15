<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * POST /payments/cash sekarang menerima item campuran arah (Masuk / Keluar / Keluar 1) dalam
     * satu pembayaran dan memecahnya jadi satu baris kas per arah. Akibatnya satu
     * ref_payment_id + penerima bisa punya lebih dari satu baris, jadi arah ikut masuk kunci
     * idempotensi: ref_payment_id + penerima + ref_direction.
     *
     * ref_direction hanya terisi untuk baris dari External API (baris admin tetap NULL, dan
     * ref_payment_id-nya juga NULL, jadi tidak tersentuh unique index). Baris API lama diisi dari
     * tipe rincian pertamanya — sebelum perubahan ini seluruh rinciannya dijamin searah.
     */
    private array $tables = [
        // fallback: arah dari header bila baris tidak punya rincian aktif sama sekali, supaya tidak
        // ada baris API yang tertinggal NULL (NULL tidak dicegah unique index MySQL). cash_sales
        // menyimpan arah di cs_transaction; cash_armadas hanya membedakan Masuk (cr_type 1) dari
        // keluar, jadi selain itu dianggap Keluar (2).
        'cash_armadas' => ['fallback' => 'IF(h.cr_type = 1, 1, 2)', 'recipient' => 'customer_id', 'pk' => 'cr_id', 'detail' => 'cash_armada_details', 'detail_pk' => 'crd_id', 'detail_type' => 'crd_type',
            'old_unique' => 'cash_armadas_ref_payment_id_customer_id_unique', 'new_unique' => 'cash_armadas_ref_payment_recipient_direction_unique'],
        'cash_sales' => ['fallback' => 'h.cs_transaction', 'recipient' => 'staff_id', 'pk' => 'cs_id', 'detail' => 'cash_sales_details', 'detail_pk' => 'csd_id', 'detail_type' => 'csd_type',
            'old_unique' => 'cash_sales_ref_payment_id_staff_id_unique', 'new_unique' => 'cash_sales_ref_payment_recipient_direction_unique'],
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName => $t) {
            if (Schema::hasColumn($tableName, 'ref_direction')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->tinyInteger('ref_direction')->nullable()->after('ref_payment_id')
                    ->comment('Arah rincian (1 Masuk, 2 Keluar, 3 Keluar 1) — hanya baris External API');
            });

            DB::statement("
                UPDATE {$tableName} h
                SET h.ref_direction = COALESCE((
                    SELECT d.{$t['detail_type']} FROM {$t['detail']} d
                    WHERE d.{$t['pk']} = h.{$t['pk']}
                    ORDER BY d.{$t['detail_pk']} LIMIT 1
                ), {$t['fallback']})
                WHERE h.ref_payment_id IS NOT NULL
            ");

            Schema::table($tableName, function (Blueprint $table) use ($t) {
                $table->dropUnique($t['old_unique']);
                $table->unique(['ref_payment_id', $t['recipient'], 'ref_direction'], $t['new_unique']);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName => $t) {
            if (! Schema::hasColumn($tableName, 'ref_direction')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($t) {
                $table->dropUnique($t['new_unique']);
                $table->unique(['ref_payment_id', $t['recipient']], $t['old_unique']);
                $table->dropColumn('ref_direction');
            });
        }
    }
};
