<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('staffs', function (Blueprint $table) {
            $table->longText('signature_data_uri')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('staffs', fn (Blueprint $table) => $table->dropColumn('signature_data_uri'));
    }
};
