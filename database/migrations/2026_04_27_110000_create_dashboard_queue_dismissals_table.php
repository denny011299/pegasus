<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_queue_dismissals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('queue_section', 32);
            $table->string('queue_key', 120);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index(['staff_id', 'queue_section', 'status'], 'dash_qd_staff_section_status_idx');
            $table->unique(['staff_id', 'queue_section', 'queue_key'], 'dash_qd_staff_section_key_unq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_queue_dismissals');
    }
};

