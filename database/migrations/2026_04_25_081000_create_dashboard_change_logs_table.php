<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_change_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module_key', 60)->index();
            $table->string('module_label', 120);
            $table->string('reference', 191)->nullable();
            $table->string('what_changed', 255);
            $table->text('summary')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('url_label', 80)->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_change_logs');
    }
};
