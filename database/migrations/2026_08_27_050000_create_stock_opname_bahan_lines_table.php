<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel detail Stock Opname BAHAN versi baru -- kembaran persis 2026_08_27_030000_create_
 * stock_opname_lines_table (Produk), disesuaikan untuk Supplies (satu identitas per baris,
 * tidak ada varian/SKU seperti Produk -- lihat tabel `supplies`: cuma supplies_name).
 * stock_opname_detail_bahans versi lama TIDAK disentuh dan TIDAK dimigrasikan.
 *
 * Sama seperti stock_opname_lines: sobl_counted_qty NULL = tidak dihitung, selisih tidak pernah
 * disimpan (selalu diturunkan saat tampil), tanpa foreign key (referensi longgar, snapshot teks
 * yang dipakai mencetak).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_opname_bahan_lines')) {
            return;
        }

        Schema::create('stock_opname_bahan_lines', function (Blueprint $table) {
            $table->integerIncrements('sobl_id');
            $table->integer('stob_id');

            // Referensi longgar: untuk filter/analitik, boleh basi, tidak pernah dipakai mencetak.
            $table->integer('supplies_id')->nullable();
            $table->integer('unit_id')->nullable();

            $table->integer('sobl_counted_qty')->nullable()->comment('NULL = satuan ini tidak dihitung');
            $table->text('sobl_notes')->nullable();

            // Snapshot identitas -- ditulis saat publish.
            $table->string('sobl_supplies_name', 255)->nullable();
            $table->string('sobl_unit_short_name', 50)->nullable()->comment('token yang dicetak: "DOS", "pcs"');
            $table->string('sobl_unit_name', 100)->nullable();

            // Snapshot stok sistem -- ditulis HANYA saat dokumen diputuskan.
            $table->integer('sobl_system_qty_final')->nullable();

            $table->integer('status')->default(1);
            $table->timestamps();

            $table->unique(['stob_id', 'supplies_id', 'unit_id'], 'stock_opname_bahan_lines_line_unique');
            $table->index('stob_id', 'stock_opname_bahan_lines_stob_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_bahan_lines');
    }
};
