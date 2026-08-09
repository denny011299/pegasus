<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inward_outwards')) {
            return;
        }

        Schema::create('inward_outwards', function (Blueprint $t) {
            $t->id();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inward_outwards');
    }
};
