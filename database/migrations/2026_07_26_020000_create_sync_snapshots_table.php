<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
<<<<<<< Updated upstream
    /**
     * Simpanan satu kali tarik data dari PMO.
     *
     * Satu endpoint hanya dipanggil sekali per sesi sinkronisasi; seluruh
     * langkah berikutnya membaca simpanan ini. Selain menghemat panggilan,
     * cara ini memastikan semua langkah bekerja pada potret data yang sama,
     * sehingga tidak ada langkah yang melihat katalog berbeda.
     */
    public function up(): void
    {
        Schema::create('sync_snapshots', function (Blueprint $table) {
            $table->bigIncrements('sync_snapshot_id');
=======
    public function up(): void
    {
        Schema::create('sync_snapshots', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id('sync_snapshot_id');
>>>>>>> Stashed changes
            $table->string('flow_key', 100);
            $table->string('endpoint_key', 100);
            $table->string('url', 500)->nullable();
            $table->longText('payload')->comment('JSON terkompresi gzip');
            $table->unsignedInteger('row_count')->default(0);
            $table->timestamp('fetched_at')->nullable();
            $table->integer('fetched_by')->nullable();
<<<<<<< Updated upstream
            $table->timestamps();
=======
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
>>>>>>> Stashed changes

            $table->unique(['flow_key', 'endpoint_key'], 'sync_snapshots_flow_endpoint_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_snapshots');
    }
};
