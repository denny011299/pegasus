<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean armada PMO yang cocok ke LEBIH DARI SATU pelanggan/armada Pegasus
 * saat dicocokkan lewat No Pol + PIC (ref_armada_id belum diketahui) —
 * App\Synchronization\Steps\ArmadaFlow\SyncArmadaStep sengaja tidak menebak,
 * baris begini diantrekan ke sini untuk dikonfirmasi manual oleh operator
 * lewat langkah "Konfirmasi Armada Ambigu" di wizard (bukan halaman
 * terpisah — App\Synchronization\Contracts\ReviewQueueStepHandler).
 *
 * Satu baris PMO (ref_armada_id) hanya boleh punya satu baris di sini —
 * disinkronkan ulang berkali-kali tetap memperbarui baris yang sama, bukan
 * menumpuk duplikat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('armada_match_reviews', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->bigIncrements('armada_match_review_id');
            $table->unsignedBigInteger('ref_armada_id')->comment('armada_id pada sistem PMO');
            $table->string('pic_name', 250)->nullable();
            $table->string('nomer_pol', 100)->nullable();
            $table->string('nomer_telp', 50)->nullable();
            $table->integer('saldo_armada')->nullable()->default(0);
            $table->json('candidate_customer_ids')->comment('customer_id yang bentrok saat pencocokan No Pol + PIC');
            $table->string('status', 20)->default('pending')->comment('pending, connected, discarded');
            $table->integer('resolved_customer_id')->nullable();
            $table->integer('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique('ref_armada_id', 'armada_match_reviews_ref_armada_id_unique');
            $table->index('status', 'armada_match_reviews_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('armada_match_reviews');
    }
};
