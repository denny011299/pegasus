<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->engine('MyISAM');
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('prov_id');
            $table->string('prov_name', 255)->nullable();
            $table->integer('locationid')->nullable();
            $table->integer('status')->nullable()->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
