<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_admin_details', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('cad_id', true);
            $table->integer('ca_id');
            $table->string('cad_notes', 255);
            $table->integer('cad_nominal');
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_admin_details');
    }
};
