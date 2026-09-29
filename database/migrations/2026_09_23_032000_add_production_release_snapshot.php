<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void { Schema::table('production_plannings', function(Blueprint $t) { $t->string('spkp_number',60)->nullable()->unique(); $t->json('approval_snapshot')->nullable(); }); }
 public function down(): void { Schema::table('production_plannings', fn(Blueprint $t) => $t->dropColumn(['spkp_number','approval_snapshot'])); }
};
