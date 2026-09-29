<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_skalas', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('production_skala_id');
            $table->string('code', 20)->comment('Kode skala SPK: 1, M1, 2, M2, …');
            $table->string('name', 100)->comment('Nama skala: Pagi, Sore, …');
            $table->string('combo_label', 150)->nullable()->comment('Keterangan: Mobil 1, …');
            $table->integer('status')->default(1)->comment('1 = aktif, 0 = soft-delete');
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();

            $table->unique('code', 'production_skalas_code_unique');
        });

        $now = now();
        // Kode angka = slot muat; kode M* = armada/mobil.
        $rows = [
            ['code' => '1',  'name' => 'Muat Pagi',  'combo_label' => null],
            ['code' => '2',  'name' => 'Muat Sore',  'combo_label' => null],
            ['code' => '3',  'name' => 'Muat Besok', 'combo_label' => null],
            ['code' => '4',  'name' => 'Stock',      'combo_label' => null],
            ['code' => 'M1', 'name' => 'Mobil 1',    'combo_label' => null],
            ['code' => 'M2', 'name' => 'Mobil 2',    'combo_label' => null],
            ['code' => 'M3', 'name' => 'Mobil 3',    'combo_label' => null],
            ['code' => 'M4', 'name' => 'Mobil 4',    'combo_label' => null],
        ];
        foreach ($rows as $row) {
            DB::table('production_skalas')->insert([
                'code' => $row['code'],
                'name' => $row['name'],
                'combo_label' => $row['combo_label'],
                'status' => 1,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('production_skalas');
    }
};
