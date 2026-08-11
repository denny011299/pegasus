<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Support\ProductUnitStock;
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
    /**
     * POST /api/external/v1/stock/check
     *
     * Mengecek kekurangan stok (shortage) per item berdasarkan SKU, memakai
     * ulang App\Support\ProductUnitStock::totalAvailable() — persis logika
     * yang sama dipakai Sales Order (App\Support\SalesOrderStock) untuk
     * menghitung stok tersedia setara satu satuan, termasuk bongkar satuan
     * lebih besar (mis. stok di DOS ikut dihitung untuk permintaan dalam
     * Piece) selama satu chain product_relations. TIDAK menyusun ulang
     * (packing) stok satuan kecil ke satuan besar — hanya arah bongkar.
     *
     * unit_id pada tiap item BUKAN units.unit_id (id internal Pegasus),
     * melainkan units.ref_unit_id (rujukan sistem PMO) — sama seperti
     * konvensi PUT/DELETE /master/units/{ref_unit_id}. Namanya sengaja
     * tetap "unit_id" di body/respons endpoint ini (bukan diganti jadi
     * "ref_unit_id") sesuai kontrak yang diminta pemanggil.
     *
     * gudang_id opsional, merujuk warehouses.id (id yang sama dipakai
     * PUT/DELETE /master/warehouses/{gudang_id}) — BUKAN kolom rujukan
     * eksternal seperti ref_unit_id/ref_product_id, karena gudang sendiri
     * tidak disinkronkan PMO. Tidak dikirim -> memakai
     * ProductStock::resolveWarehouseId(null): gudang utama (is_main_warehouse
     * = 1), sama seperti default seluruh fitur stok lain di aplikasi ini.
     *
     * sku dan unit_id divalidasi harus benar-benar ada (aktif) sebelum
     * dihitung sama sekali — permintaan yang memuat SKU tak dikenal atau
     * unit_id yang tidak merujuk satuan aktif manapun ditolak validation_failed
     * secara keseluruhan, tidak diam-diam dianggap shortage penuh. Kombinasi
     * SKU + unit_id yang keduanya valid tapi tidak sechain (mis. satuan produk
     * lain) TETAP dihitung — itu bukan data tidak dikenal, hasilnya wajar
     * available: 0 lewat totalAvailable().
     */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ref_shipment_id' => ['required', 'string', 'max:100'],
            'gudang_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('status', 1)],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => [
                'required', 'string',
                Rule::exists('product_variants', 'product_variant_sku')->where('status', 1),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_id' => [
                'required', 'integer',
                Rule::exists('units', 'ref_unit_id')->where('status', 1),
            ],
        ]);

        $warehouseId = ProductStock::resolveWarehouseId($data['gudang_id'] ?? null);

        $variantsBySku = ProductVariant::whereIn('product_variant_sku', array_column($data['items'], 'sku'))
            ->where('status', 1)
            ->orderBy('product_variant_id')
            ->get(['product_variant_id', 'product_variant_sku'])
            ->keyBy('product_variant_sku');

        $unitsByRef = Unit::whereIn('ref_unit_id', array_column($data['items'], 'unit_id'))
            ->where('status', 1)
            ->get(['unit_id', 'ref_unit_id'])
            ->keyBy('ref_unit_id');

        $hasShortage = false;
        $items = array_map(function (array $item) use ($variantsBySku, $unitsByRef, $warehouseId, &$hasShortage) {
            $variant = $variantsBySku->get($item['sku']);
            $unit = $unitsByRef->get((int) $item['unit_id']);
            $requested = (int) $item['qty'];

            // Sudah divalidasi ada di atas — null di sini hanya kemungkinan
            // race condition (baris dinonaktifkan tepat setelah validate()).
            // Diperlakukan sebagai shortage penuh, bukan galat 500.
            $available = ($variant && $unit)
                ? (int) round(ProductUnitStock::totalAvailable(
                    $warehouseId,
                    (int) $variant->product_variant_id,
                    (int) $unit->unit_id,
                ))
                : 0;

            $shortage = max(0, $requested - $available);
            if ($shortage > 0) {
                $hasShortage = true;
            }

            return [
                'sku' => (string) $item['sku'],
                'unit_id' => (int) $item['unit_id'],
                'requested' => $requested,
                'available' => $available,
                'shortage' => $shortage,
            ];
        }, $data['items']);

        return ApiResponse::success([
            'ref_shipment_id' => (string) $data['ref_shipment_id'],
            'has_shortage' => $hasShortage,
            'items' => $items,
        ]);
    }
}
