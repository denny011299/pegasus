<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Riwayat eksekusi langkah sinkronisasi.
     *
     * Dipakai untuk tiga hal: menampilkan status & hasil terakhir setiap
     * langkah di wizard, memvalidasi prasyarat antar langkah di sisi server,
     * dan jejak audit siapa menjalankan sinkronisasi apa.
     */
    public function up(): void
    {
        Schema::create('sync_executions', function (Blueprint $table) {
            $table->bigIncrements('sync_execution_id');
            $table->string('flow_key', 100);
            $table->string('step_key', 100);
            $table->string('status', 20)->comment('not_executed, running, success, failed');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedBigInteger('duration_ms')->default(0);
            $table->unsignedInteger('total_processed')->default(0);
            $table->unsignedInteger('total_inserted')->default(0);
            $table->unsignedInteger('total_updated')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->unsignedInteger('total_skipped')->default(0);
            $table->text('message')->nullable();
            $table->json('details')->nullable()->comment('Informasi tambahan dari respons PMO');
            $table->json('errors')->nullable();
            $table->integer('executed_by')->nullable();
            $table->timestamps();

            $table->index(['flow_key', 'step_key', 'sync_execution_id'], 'sync_executions_flow_step_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_executions');
    }
};
