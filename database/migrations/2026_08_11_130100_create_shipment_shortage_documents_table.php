<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dokumen kekurangan stok, dibuat opsional (auto_create_shortage_doc) oleh
     * POST /api/external/v1/shipments/scheduled saat ada item yang shortage-nya > 0.
     * Murni catatan/notice untuk staf gudang/pembelian - tidak dikonsumsi modul lain saat ini.
     */
    public function up(): void
    {
        Schema::create('shipment_shortage_documents', function (Blueprint $table) {
            $table->charset('utf8mb4');
            $table->collation('utf8mb4_0900_ai_ci');

            $table->increments('id');
            $table->string('doc_number', 50)->comment('Format BG-0001, lihat ShipmentShortageDocument::generateDocNumber()');
            $table->unsignedInteger('so_id')->comment('sales_orders.so_id - shipment yang kekurangan stoknya dicatat di sini');
            $table->string('ref_shipment_id', 100)->comment('Salinan sales_orders.ref_shipment_id, memudahkan pencarian tanpa join');
            $table->json('items')->comment('Snapshot item yang shortage-nya > 0 saat dokumen dibuat: sku, unit_id, requested, available, shortage');
            $table->integer('status')->default(1)->comment('1 = active, 0 = inactive');
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique('doc_number', 'shipment_shortage_documents_doc_number_unique');
            $table->index('so_id');
            $table->index('ref_shipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_shortage_documents');
    }
};
