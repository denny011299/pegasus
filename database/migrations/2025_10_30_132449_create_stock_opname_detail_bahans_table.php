<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_detail_bahans', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_general_ci');

            $table->increments('stobd_id');
            $table->integer('stob_id');
            $table->integer('supplies_id');
            $table->longText('stobd_system')->nullable();
            $table->longText('stobd_real')->nullable();
            $table->longText('stobd_selisih')->nullable();
            $table->longText('stobd_notes')->nullable();
            $table->boolean('status')->default(1)->comment('1=active, 0=inactive');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_detail_bahans');
    }
};
