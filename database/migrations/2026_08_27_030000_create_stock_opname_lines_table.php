<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel detail Stock Opname Produk versi baru (langkah 3 dari rancang ulang 2026-08-27).
 * stock_opname_details versi lama TIDAK disentuh sama sekali dan tidak dimigrasikan -- dokumen
 * lama tetap dibaca apa adanya lewat pembaca legacy (lihat is_old_version).
 *
 * Tiga perubahan inti dibanding stock_opname_details:
 *
 * 1. SATU BARIS PER SATUAN, angka betulan. Dulu satu baris per varian dengan tiga longtext
 *    berisi kalimat "16 DOS, 0 pcs" yang harus di-regex tiap kali dibaca, dan angka per satuan
 *    yang sebenarnya (units[]) dibuang saat insert lalu dikirim ulang dari browser saat ACC.
 *
 * 2. sol_counted_qty NULL = TIDAK DIHITUNG. NULL beneran, bukan token "-" di dalam string dan
 *    bukan flag stod_touched terpisah. Ini menghapus akar GitHub #53 dan #78 sekaligus:
 *    "belum dihitung" jadi keadaan yang bisa diwakili tipe datanya sendiri.
 *
 * 3. SELISIH TIDAK PERNAH DISIMPAN. Selalu diturunkan (counted - sistem) di titik tampil, jadi
 *    mustahil bertentangan dengan kolom Sistem/Real yang tercetak di sebelahnya -- persis bug
 *    SP0071 yang memicu rancang ulang ini.
 *
 * RELASI SENGAJA LONGGAR (tidak ada foreign key -- konsisten dengan seluruh repo ini, 0 dari 76
 * migration memakai ->foreign()). product_id/product_variant_id/unit_id disimpan hanya untuk
 * filter/analitik dan boleh basi; yang dipakai MENCETAK adalah kolom snapshot teks. Menghapus
 * atau mengganti nama satuan/produk tidak boleh mengubah, mengosongkan, apalagi menghapus
 * berantai dokumen yang sudah diputuskan -- dan manusia tetap bisa membaca "DOS" walau unit_id
 * yang dulu menunjuk ke sana sudah tidak ada.
 *
 * KAPAN TIAP KOLOM DIISI (aturan siklus hidup, keputusan PM 2026-08-27):
 *  - draft            : sol_counted_qty + sol_notes saja. TIDAK ADA snapshot sama sekali;
 *                       halaman draft menampilkan nama & stok sistem live.
 *  - publish          : snapshot identitas (sol_product_name .. sol_unit_name). Stok sistem
 *                       SENGAJA TIDAK ikut dibekukan di sini -- membekukan stok sistem terlalu
 *                       dini persis mekanisme yang melahirkan #78.
 *  - menunggu         : TIDAK MENULIS APA PUN. Stok sistem dibaca live tiap kali PDF dibuat atau
 *                       halaman detail dibuka, lalu dibuang. (refreshLiveSystemQty() versi lama
 *                       justru menyimpannya balik saat PDF di-download -- itu yang membuat
 *                       selisih SP0071 bergeser setelah dokumen dibuat.)
 *  - disetujui/ditolak: sol_system_qty_final diisi = stok sistem SAAT ITU, lalu dokumen beku.
 *                       Ditolak pun ikut dibekukan supaya angkanya berhenti bergerak.
 *
 * Invarian yang bisa diperiksa: kolom snapshot NULL jika dan hanya jika dokumennya masih draft.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_opname_lines')) {
            return;
        }

        Schema::create('stock_opname_lines', function (Blueprint $table) {
            $table->integerIncrements('sol_id');
            $table->integer('sto_id');

            // Referensi longgar: untuk filter/analitik, boleh basi, tidak pernah dipakai mencetak.
            $table->integer('product_id')->nullable();
            $table->integer('product_variant_id')->nullable();
            $table->integer('unit_id')->nullable();

            // Input staf -- ada sejak draft.
            $table->integer('sol_counted_qty')->nullable()->comment('NULL = satuan ini tidak dihitung');
            $table->text('sol_notes')->nullable();

            // Snapshot identitas -- ditulis saat publish, sesudah itu tidak pernah di-resolve ulang.
            $table->string('sol_product_name', 255)->nullable();
            $table->string('sol_variant_name', 255)->nullable();
            $table->string('sol_variant_sku', 100)->nullable();
            $table->string('sol_unit_short_name', 50)->nullable()->comment('token yang dicetak: "DOS", "pcs"');
            $table->string('sol_unit_name', 100)->nullable();

            // Snapshot stok sistem -- ditulis HANYA saat dokumen diputuskan.
            $table->integer('sol_system_qty_final')->nullable();

            $table->integer('status')->default(1);
            $table->timestamps();

            // Identitas baris yang stabil. Bug duplikasi di alur lama (updateStockOpname() cuma
            // memperbarui header, dan JS tidak pernah mengirim stod_id sehingga tiap simpan
            // menyisipkan ulang SEMUA baris) jadi mustahil diwakili: id selalu valid saat ditulis,
            // basi hanya mungkin terjadi belakangan saat dibaca.
            $table->unique(['sto_id', 'product_variant_id', 'unit_id'], 'stock_opname_lines_line_unique');
            $table->index('sto_id', 'stock_opname_lines_sto_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_lines');
    }
};
