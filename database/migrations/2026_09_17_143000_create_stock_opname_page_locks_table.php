<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exclusive lock halaman Input Stok Opname (-1) per gudang + domain.
     * Satu baris = satu holder; last_seen_at + TTL menentukan live.
     */
    public function up(): void
    {
        if (Schema::hasTable('stock_opname_page_locks')) {
            return;
        }

        Schema::create('stock_opname_page_locks', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('id');
            $table->unsignedBigInteger('warehouse_id');
            $table->string('domain', 20)->comment('product | supplies');
            $table->unsignedInteger('staff_id');
            $table->string('staff_name', 255)->nullable();
            $table->string('token', 64);
            $table->timestamp('locked_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['warehouse_id', 'domain'], 'sop_locks_wh_domain_unique');
            $table->unique('token', 'sop_locks_token_unique');
            $table->index(['warehouse_id', 'domain', 'last_seen_at'], 'sop_locks_live_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_page_locks');
    }
};
