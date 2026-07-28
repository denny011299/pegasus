<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sistem eksternal yang memakai External API.
     *
     * Satu baris mewakili satu sistem (ERP, marketplace, aplikasi mitra).
     * API Key selalu dimiliki oleh salah satu aplikasi di tabel ini, jadi
     * menonaktifkan aplikasi otomatis mematikan seluruh kuncinya.
     *
     * Dua kolom status sengaja dipisah:
     * - `application_status` = keadaan bisnis (active / disabled), diatur admin.
     * - `status`             = penanda baris hidup/terhapus mengikuti konvensi
     *                          tabel lain di proyek ini (1 = aktif, 0 = dihapus).
     */
    public function up(): void
    {
        Schema::create('external_applications', function (Blueprint $table) {
            $table->bigIncrements('external_application_id');
            $table->string('application_code', 100)->comment('Identitas stabil, tidak ikut berubah saat nama diganti');
            $table->string('application_name', 150);
            $table->string('company', 150)->nullable();
            $table->string('contact_name', 150)->nullable();
            $table->string('contact_email', 150)->nullable();
            $table->text('description')->nullable();
            $table->string('application_status', 20)->default('active')->comment('active, disabled');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('status')->default(1)->comment('1 = active, 0 = dead');
            $table->timestamps();

            $table->unique('application_code', 'external_applications_code_unique');
            $table->index(['status', 'application_status'], 'external_applications_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_applications');
    }
};
