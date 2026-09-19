<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Penanda "baris ini terakhir ditulis lewat Platform API Eksternal"
 * (App\Http\Controllers\ExternalApi\V1\MasterArmadaController), untuk tabel
 * `customers` yang menyimpan armada (lihat catatan kelas controller itu).
 *
 * Armada TIDAK punya kolom rujukan PMO yang ditulis Platform API Eksternal
 * seperti products.ref_product_id/units.ref_unit_id/staffs.external_ref_id —
 * customers.ref_armada_id memang ada, tapi HANYA ditulis Pusat Sinkronisasi
 * (SyncArmadaStep), tidak pernah oleh endpoint eksternal ini (endpoint ini
 * hanya menerima `code`, tidak pernah armada_id numerik PMO — lihat
 * MasterArmadaController). Tanpa kolom terpisah, baris yang dibuat/diubah
 * lewat endpoint eksternal ini tidak bisa dibedakan dari baris buatan
 * admin di halaman "Dibuat Oleh" (keduanya sama-sama bisa punya created_by
 * kosong).
 *
 * Ditulis ulang (bukan cuma diisi sekali) setiap kali MasterArmadaController
 * membuat ATAU memperbarui baris — nilainya karena itu juga berguna sebagai
 * "terakhir disentuh Platform API Eksternal kapan", bukan cuma penanda
 * ya/tidak.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers') || Schema::hasColumn('customers', 'external_api_synced_at')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->timestamp('external_api_synced_at')->nullable()->after('ref_armada_id')
                ->comment('Waktu terakhir baris ini ditulis lewat Platform API Eksternal (MasterArmadaController)');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('customers') || ! Schema::hasColumn('customers', 'external_api_synced_at')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('external_api_synced_at');
        });
    }
};
