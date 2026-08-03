<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_transfer_details')
            && ! Schema::hasColumn('stock_transfer_details', 'received_unit_id')) {
            Schema::table('stock_transfer_details', function (Blueprint $table) {
                $table->unsignedInteger('received_unit_id')->nullable()->after('unit_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stock_transfer_details')
            && Schema::hasColumn('stock_transfer_details', 'received_unit_id')) {
            Schema::table('stock_transfer_details', function (Blueprint $table) {
                $table->dropColumn('received_unit_id');
            });
        }
    }
};
