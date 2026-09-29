<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('production_work_orders', function (Blueprint $table) {
            $table->string('execution_status', 30)->default('inprod');
            $table->string('production_line', 100)->nullable();
            $table->timestamp('production_completed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
        });
        Schema::create('production_output_reports', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('production_work_order_id')->index();
            $table->string('request_id', 64);
            $table->json('items');
            $table->unsignedInteger('created_by');
            $table->json('actor_snapshot');
            $table->timestamp('confirmed_at');
            $table->timestamps();
            $table->unique(['production_work_order_id', 'request_id'], 'por_wo_request_unique');
        });
        Schema::create('production_execution_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('production_work_order_id')->index();
            $table->unsignedInteger('warehouse_id')->index();
            $table->string('type', 30);
            $table->string('number', 60)->nullable()->unique();
            $table->string('request_id', 64);
            $table->string('document_status', 30)->default('awaiting_ops');
            $table->json('items');
            $table->json('signatures');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamp('confirmed_at');
            $table->unsignedInteger('ops_approved_by')->nullable();
            $table->timestamp('ops_approved_at')->nullable();
            $table->unsignedInteger('qc_approved_by')->nullable();
            $table->timestamp('qc_approved_at')->nullable();
            $table->timestamp('warehouse_at')->nullable();
            $table->string('tally_number', 60)->nullable()->unique();
            $table->timestamps();
            $table->unique(['production_work_order_id', 'type', 'request_id'], 'ped_wo_type_request_unique');
        });
        Schema::create('production_execution_events', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('production_work_order_id')->index();
            $table->unsignedBigInteger('document_id')->nullable()->index();
            $table->string('action', 50);
            $table->unsignedInteger('staff_id');
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_execution_events');
        Schema::dropIfExists('production_execution_documents');
        Schema::dropIfExists('production_output_reports');
        Schema::table('production_work_orders', fn (Blueprint $table) => $table->dropColumn([
            'execution_status', 'production_line', 'production_completed_at', 'closed_at',
        ]));
    }
};
