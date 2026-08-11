<?php

namespace App\Http\Controllers\ExternalApi\V1\Concerns;

use App\Models\ProductVariant;
use App\Models\Unit;
use App\Support\ProductUnitStock;
use Illuminate\Validation\Rule;

/**
 * Logika cek ketersediaan stok per item (sku + qty + unit_id), dipakai bersama oleh
 * StockController::check() dan ShipmentController::scheduled() — keduanya menerima bentuk
 * items[] yang identik (sku, qty, unit_id-sebagai-ref_unit_id) dan perlu jawaban yang sama:
 * berapa yang tersedia, berapa yang kurang.
 *
 * unit_id di sini SELALU units.ref_unit_id (rujukan sistem PMO), bukan id internal Pegasus —
 * sama seperti dijelaskan di StockController. Pemanggil yang butuh id internal (mis. untuk
 * membuat sales_order_details) memakai product_variant_id/internal_unit_id/product_id yang ikut
 * dikembalikan di setiap baris hasil, bukan mem-parsing ulang sendiri.
 */
trait ChecksStockAvailability
{
    /**
     * Aturan validasi items[] yang identik di kedua endpoint. Ditulis sebagai potongan array
     * supaya tinggal di-merge dengan field lain milik masing-masing endpoint lewat
     * array_merge(), bukan dipanggil sebagai validasi berdiri sendiri.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function stockItemValidationRules(): array
    {
        return [
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
        ];
    }

    /**
     * @param  array<int, array{sku:string, qty:int, unit_id:int}>  $items  sudah lolos
     *   stockItemValidationRules() — sku/unit_id di sini dijamin ada di database, jadi variant/
     *   unit null di bawah cuma mungkin lewat race condition (baris dinonaktifkan tepat setelah
     *   validate()), diperlakukan sebagai shortage penuh, bukan galat 500.
     * @return array{
     *     has_shortage: bool,
     *     items: array<int, array{
     *         sku: string, unit_id: int, requested: int, available: int, shortage: int,
     *         product_id: ?int, product_variant_id: ?int, product_variant_name: ?string,
     *         internal_unit_id: ?int,
     *     }>,
     * }
     */
    protected function checkStockAvailability(int $warehouseId, array $items): array
    {
        $variantsBySku = ProductVariant::whereIn('product_variant_sku', array_column($items, 'sku'))
            ->where('status', 1)
            ->orderBy('product_variant_id')
            ->get(['product_variant_id', 'product_id', 'product_variant_sku', 'product_variant_name'])
            ->keyBy('product_variant_sku');

        $unitsByRef = Unit::whereIn('ref_unit_id', array_column($items, 'unit_id'))
            ->where('status', 1)
            ->get(['unit_id', 'ref_unit_id'])
            ->keyBy('ref_unit_id');

        $hasShortage = false;
        $results = array_map(function (array $item) use ($variantsBySku, $unitsByRef, $warehouseId, &$hasShortage) {
            $variant = $variantsBySku->get($item['sku']);
            $unit = $unitsByRef->get((int) $item['unit_id']);
            $requested = (int) $item['qty'];

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
                'product_id' => $variant?->product_id !== null ? (int) $variant->product_id : null,
                'product_variant_id' => $variant?->product_variant_id !== null ? (int) $variant->product_variant_id : null,
                'product_variant_name' => $variant?->product_variant_name,
                'internal_unit_id' => $unit?->unit_id !== null ? (int) $unit->unit_id : null,
            ];
        }, $items);

        return ['has_shortage' => $hasShortage, 'items' => $results];
    }
}
