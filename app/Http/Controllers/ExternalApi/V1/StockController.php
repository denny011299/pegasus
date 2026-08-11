<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\ChecksStockAvailability;
use App\Models\ProductStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Modul Stok untuk sistem eksternal.
 *
 * Berbeda dengan modul lain (units, sales, armada, produk): modul ini tidak
 * membuat/mengubah/menghapus data, hanya membaca — jadi tidak ada konsep
 * "dikelola API ini" atau endpoint connect di sini.
 */
class StockController extends Controller
{
    use ChecksStockAvailability;

    /**
     * POST /api/external/v1/stock/check
     *
     * Mengecek kekurangan stok (shortage) per item berdasarkan SKU. Logika perhitungannya —
     * termasuk validasi items[] — ada di Concerns\ChecksStockAvailability, dipakai bersama
     * dengan ShipmentController::scheduled() yang menerima bentuk items[] persis sama
     * (sku + qty + unit_id). Lihat docblock trait itu untuk detail lengkap, termasuk kenapa
     * unit_id di sini adalah units.ref_unit_id, bukan id internal Pegasus, dan kenapa bongkar
     * satuan hanya berjalan satu arah (besar -> kecil).
     *
     * gudang_id opsional, merujuk warehouses.id (id yang sama dipakai
     * PUT/DELETE /master/warehouses/{gudang_id}) — BUKAN kolom rujukan
     * eksternal seperti ref_unit_id/ref_product_id, karena gudang sendiri
     * tidak disinkronkan PMO. Tidak dikirim -> memakai
     * ProductStock::resolveWarehouseId(null): gudang utama (is_main_warehouse
     * = 1), sama seperti default seluruh fitur stok lain di aplikasi ini.
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate(array_merge([
            'ref_shipment_id' => ['required', 'string', 'max:100'],
            'gudang_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('status', 1)],
        ], $this->stockItemValidationRules()));

        $warehouseId = ProductStock::resolveWarehouseId($data['gudang_id'] ?? null);
        $check = $this->checkStockAvailability($warehouseId, $data['items']);

        return ApiResponse::success([
            'ref_shipment_id' => (string) $data['ref_shipment_id'],
            'has_shortage' => $check['has_shortage'],
            'items' => array_map(static fn (array $item) => [
                'sku' => $item['sku'],
                'unit_id' => $item['unit_id'],
                'requested' => $item['requested'],
                'available' => $item['available'],
                'shortage' => $item['shortage'],
            ], $check['items']),
        ]);
    }
}
