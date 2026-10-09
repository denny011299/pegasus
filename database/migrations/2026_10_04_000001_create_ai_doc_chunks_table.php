<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_doc_chunks', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 120);
            $table->string('heading', 255)->nullable();
            $table->text('content');
            $table->timestamps();
            $table->index('feature');
        });

        // FULLTEXT via raw SQL — Blueprint::fullText is fine on MySQL 5.7+,
        // but raw keeps the intent obvious for ops docs.
        DB::statement('ALTER TABLE ai_doc_chunks ADD FULLTEXT ai_doc_chunks_content_fulltext (content)');
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_doc_chunks');
    }
};
