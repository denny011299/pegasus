<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No. Dokumen Pembayaran PMO (GitHub #216), mis. "PB/BUD/121026/003".
     *
     * ref_payment_id adalah id grup internal PMO yang tidak dikenal user; nomor ini yang tercetak
     * di dokumen pembayaran PMO, jadi dipakai user untuk mencocokkan transaksi IPM dengan dokumen
     * fisik/PDF. Nullable (data PMO lama tidak punya nomor ini) dan TIDAK unik: satu dokumen PMO
     * bisa berisi banyak grup, split per penerima, dan split per alur kas — idempotensi tetap
     * lewat ref_payment_id. Diberi index biasa karena bisa dicari dari halaman Kas Operasional
     * dan Pengembalian.
     *
     * Ikut disimpan di dokumen Pengembalian yang lahir dari potongan barang pada pembayaran yang
     * sama (POST /payments/cash), supaya returnya juga bisa ditelusuri ke dokumen PMO.
     */
    private array $tables = [
        'cash_armadas' => 'ref_payment_id',
        'cash_sales' => 'ref_payment_id',
        'customer_supply_returns' => 'ref_number',
        'customer_product_returns' => 'ref_number',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName => $after) {
            if (Schema::hasColumn($tableName, 'ref_payment_number')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName, $after) {
                $table->string('ref_payment_number', 64)->nullable()->after($after)
                    ->comment('No. Dokumen Pembayaran PMO (PB/...), tidak unik');
                $table->index('ref_payment_number', $tableName.'_ref_payment_number_index');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName => $after) {
            if (! Schema::hasColumn($tableName, 'ref_payment_number')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex($tableName.'_ref_payment_number_index');
                $table->dropColumn('ref_payment_number');
            });
        }
    }
};
