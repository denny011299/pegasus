<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Alur ACC produksi: QC dulu → Kepala Ops.
 * Dokumen antrean yang belum di-ACC Ops (legacy awaiting_ops) digeser ke awaiting_qc.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('production_execution_documents')
            ->whereIn('type', ['warehouse', 'material_issue', 'material_return'])
            ->where('document_status', 'awaiting_ops')
            ->whereNull('ops_approved_at')
            ->whereNull('qc_approved_at')
            ->update(['document_status' => 'awaiting_qc']);
    }

    public function down(): void
    {
        DB::table('production_execution_documents')
            ->whereIn('type', ['warehouse', 'material_issue', 'material_return'])
            ->where('document_status', 'awaiting_qc')
            ->whereNull('ops_approved_at')
            ->whereNull('qc_approved_at')
            ->update(['document_status' => 'awaiting_ops']);
    }
};
