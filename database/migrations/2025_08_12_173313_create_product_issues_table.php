<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_issues')) {
            return;
        }

        Schema::create('product_issues', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_issues');
    }
};
