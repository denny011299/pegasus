<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_armada_details', function (Blueprint $table) {
            $table->charset('latin1');
            $table->collation('latin1_swedish_ci');

            $table->integer('crd_id', true);
            $table->integer('cr_id');
            $table->string('crd_notes', 255)->nullable();
            $table->integer('crd_nominal');
            $table->integer('crd_type')->comment('1 = Debit, 2 = Keluar, 3 = Keluar 1');
            $table->integer('status')->default(1);
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_armada_details');
    }
};
