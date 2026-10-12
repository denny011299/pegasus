<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_chat_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id')->index();
            $table->unsignedInteger('staff_id')->nullable()->index();
            $table->string('role', 20); // user | assistant | tool
            $table->mediumText('content')->nullable();
            $table->string('tool_name', 80)->nullable();
            $table->json('tool_args')->nullable();
            $table->json('tool_result')->nullable();
            $table->string('model', 120)->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_chat_logs');
    }
};
