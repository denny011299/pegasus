<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot manual programmer lama (31 Mar 2026) — bukan tabel runtime.
 * Dibutuhkan supaya import dump data okeh8644_pegasus_data.sql tidak gagal
 * setelah migrate fresh (INSERT INTO backup_* ...).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('backup_log_stocks_20260331')) {
            Schema::create('backup_log_stocks_20260331', function (Blueprint $table) {
                $table->charset('latin1');
                $table->collation('latin1_swedish_ci');

                // Salinan log_stocks tanpa AUTO_INCREMENT (pola CREATE TABLE AS SELECT).
                $table->unsignedBigInteger('log_id')->default(0);
                $table->dateTime('log_date');
                $table->string('log_kode', 50);
                $table->integer('log_type')->default(1)->comment('1 = Produk, 2 = Bahan Mentah');
                $table->integer('log_category')->nullable()->default(1)->comment('1 = Masuk, 2 = Keluar');
                $table->integer('log_item_id')->comment('id product / supplies');
                $table->text('log_notes')->nullable();
                $table->integer('log_jumlah');
                $table->unsignedBigInteger('unit_id');
                $table->integer('status')->default(1);
                $table->integer('staff_id')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('backup_product_stocks_20260331')) {
            Schema::create('backup_product_stocks_20260331', function (Blueprint $table) {
                $table->charset('latin1');
                $table->collation('latin1_swedish_ci');

                $table->unsignedInteger('ps_id')->default(0);
                $table->integer('product_variant_id');
                $table->integer('product_id');
                $table->integer('unit_id');
                $table->integer('ps_stock');
                $table->integer('status')->default(1)->comment('1 = active, 0 = inactive');
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_product_stocks_20260331');
        Schema::dropIfExists('backup_log_stocks_20260331');
    }
};
