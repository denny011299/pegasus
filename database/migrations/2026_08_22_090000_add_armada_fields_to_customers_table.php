<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom tambahan untuk armada (kendaraan) yang disimpan pada tabel customers.
 *
 * Semuanya nullable dan hanya diisi lewat External API modul armada —
 * pelanggan biasa yang dibuat lewat halaman admin membiarkannya kosong.
 */
return new class extends Migration
{
    /** @var array<string, int> */
    private array $columns = [
        'customer_category' => 100,
        'customer_merk_model' => 255,
        'customer_tahun_kendaraan' => 20,
        'customer_lokasi' => 255,
    ];

    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        $after = 'customer_notes';

        foreach ($this->columns as $column => $length) {
            if (Schema::hasColumn('customers', $column)) {
                $after = $column;

                continue;
            }

            $previous = $after;
            Schema::table('customers', function (Blueprint $table) use ($column, $length, $previous) {
                $table->string($column, $length)->nullable()->after($previous);
            });

            $after = $column;
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers')) {
            return;
        }

        foreach (array_keys($this->columns) as $column) {
            if (! Schema::hasColumn('customers', $column)) {
                continue;
            }

            Schema::table('customers', function (Blueprint $table) use ($column) {
                $table->dropColumn($column);
            });
        }
    }
};
