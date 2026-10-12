<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Path relatif di disk local (storage/app/…) supaya bubble history
        // bisa menampilkan ulang lampiran tanpa simpan base64 di DB.
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->string('image_path', 255)->nullable()->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('ai_chat_logs', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
