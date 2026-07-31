<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sama seperti add_is_draft_to_stock_opnames_table: flag terpisah dari
     * `status`, bukan nilai baru di kolomnya. Lihat komentar di migration
     * itu untuk alasan lengkapnya.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('stock_opname_bahans', 'is_draft')) {
            Schema::table('stock_opname_bahans', function (Blueprint $table) {
                $table->boolean('is_draft')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stock_opname_bahans', 'is_draft')) {
            Schema::table('stock_opname_bahans', function (Blueprint $table) {
                $table->dropColumn('is_draft');
            });
        }
    }
};
