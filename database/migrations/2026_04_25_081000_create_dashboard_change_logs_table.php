<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_change_logs', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_unicode_ci');

            $table->id('id');
            $table->string('module_key', 60);
            $table->string('module_label', 120);
            $table->string('reference', 191)->nullable();
            $table->string('what_changed', 255);
            $table->text('summary')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('url_label', 80)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->longText('meta')->collation('utf8mb4_bin')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->index('created_by', 'dashboard_change_logs_created_by_index');
            $table->index('module_key', 'dashboard_change_logs_module_key_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_change_logs');
    }
};
