<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('log_stocks')) {
            return;
        }
        if (! Schema::hasColumn('log_stocks', 'log_saldo')) {
            Schema::table('log_stocks', function (Blueprint $table) {
                $table->decimal('log_saldo', 18, 4)->nullable()->after('log_jumlah');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('log_stocks')) {
            return;
        }
        if (Schema::hasColumn('log_stocks', 'log_saldo')) {
            Schema::table('log_stocks', function (Blueprint $table) {
                $table->dropColumn('log_saldo');
            });
        }
    }
};
