<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Potong / kembalikan stok pengiriman (Sales Order):
 * - satuan eceran (product_variants.retail_unit) → gudang eceran per detail
 *   (sales_order_details.warehouse_id; header lama sebagai fallback)
 * - satuan lain → gudang utama
 */
class SalesOrderStock
{
    public static function mainWarehouseId(): int
    {
        $id = Warehouse::query()
            ->active()
            ->whereHas('type', fn($q) => $q->where('is_main_warehouse', 1))
            ->value('id');

        return (int) ($id ?? 0);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines  product_variant_id, unit_id, qty (sod_qty|so_qty)
     * @return array{
     *   ok: bool,
     *   status?: int,
     *   header?: string,
     *   message?: string,
     *   products?: array<int, string>,
     *   recommendations?: array<int, array>,
     *   plan?: array<int, array{warehouse_id:int, product_variant_id:int, unit_id:int, qty:float, is_retail:bool}>
     * }
     */
    public static function buildPlan(array $lines, ?int $retailWarehouseId): array
    {
        $mainId = self::mainWarehouseId();
        if ($mainId <= 0) {
            return [
                'ok' => false,
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => 'Gudang utama belum dikonfigurasi',
            ];
        }

        $retailId = (int) ($retailWarehouseId ?? 0);
        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');

        /** @var array<string, array{warehouse_id:int, product_variant_id:int, unit_id:int, qty:float, is_retail:bool}> $agg */
        $agg = [];
        $needsRetail = false;

        foreach ($lines as $line) {
            $variantId = (int) ($line['product_variant_id'] ?? 0);
            $unitId = (int) ($line['unit_id'] ?? 0);
            $qty = (float) ($line['qty'] ?? $line['sod_qty'] ?? $line['so_qty'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0 || $qty <= 0) {
                continue;
            }

            $retailUnit = 0;
            if ($hasRetailCol) {
                $retailUnit = (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0);
            }

            $isRetail = $retailUnit > 0 && $unitId === $retailUnit;
            if ($isRetail) {
                $needsRetail = true;
                $warehouseId = (int) ($line['warehouse_id'] ?? 0) ?: $retailId;
            } else {
                $warehouseId = $mainId;
            }

            if ($isRetail && $warehouseId <= 0) {
                return [
                    'ok' => false,
                    'status' => 0,
                    'header' => 'Gudang eceran wajib',
                    'message' => 'Pilih gudang eceran pada setiap item yang memakai satuan eceran.',
                ];
            }
            if ($isRetail && ! Warehouse::query()
                ->active()
                ->whereKey($warehouseId)
                ->whereHas('type', fn($q) => $q->where('is_main_warehouse', 0))
                ->exists()) {
                return [
                    'ok' => false,
                    'status' => 0,
                    'header' => 'Gudang tidak valid',
                    'message' => 'Gudang pada item satuan eceran harus berupa gudang eceran aktif.',
                ];
            }

            $key = $warehouseId . ':' . $variantId . ':' . $unitId;
            if (! isset($agg[$key])) {
                $agg[$key] = [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                    'unit_id' => $unitId,
                    'qty' => 0.0,
                    'is_retail' => $isRetail,
                ];
            }
            $agg[$key]['qty'] += $qty;
        }

        if ($agg === []) {
            return [
                'ok' => false,
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => 'Tidak ada item pengiriman',
            ];
        }

        ProductUnitStock::clearCache();

        $shortProducts = [];
        $recommendations = [];

        foreach ($agg as $item) {
            $whId = $item['warehouse_id'];
            $variantId = $item['product_variant_id'];
            $unitId = $item['unit_id'];
            $qty = $item['qty'];

            if (ProductUnitStock::canFulfill($whId, $variantId, $unitId, $qty)) {
                continue;
            }

            $name = self::productLabel($variantId);
            $unitName = self::unitLabel($unitId);
            $shortProducts[] = $name;
            $available = ProductUnitStock::totalAvailable($whId, $variantId, $unitId);
            $whName = Warehouse::where('id', $whId)->value('warehouse_name') ?? ('Gudang #' . $whId);

            $alts = self::recommendWarehouses(
                $variantId,
                $unitId,
                $qty,
                $whId,
                $item['is_retail']
            );

            $recommendations[] = [
                'product_variant_id' => $variantId,
                'product' => $name,
                'unit_id' => $unitId,
                'unit' => $unitName,
                'need' => $qty,
                'warehouse_id' => $whId,
                'warehouse_name' => $whName,
                'available' => $available,
                'is_retail' => $item['is_retail'],
                'available_at' => $alts,
            ];
        }

        if ($recommendations !== []) {
            $msgs = [];
            foreach ($recommendations as $r) {
                $line = $r['product'] . ' (' . $r['unit'] . '): stok di ' . $r['warehouse_name']
                    . ' tidak cukup (butuh ' . self::fmtQty($r['need'])
                    . ', tersedia ' . self::fmtQty($r['available']) . ')';
                if ($r['available_at'] !== []) {
                    $opts = array_map(
                        fn($a) => $a['warehouse_name'] . ' (' . self::fmtQty($a['available']) . ')',
                        $r['available_at']
                    );
                    $line .= '. Rekomendasi: ' . implode(', ', $opts);
                } else {
                    $line .= '. Tidak ada gudang lain dengan stok cukup.';
                }
                $msgs[] = $line;
            }

            return [
                'ok' => false,
                'status' => 0,
                'header' => 'Stok tidak cukup',
                'message' => implode("\n", $msgs),
                'products' => array_values(array_unique($shortProducts)),
                'recommendations' => $recommendations,
            ];
        }

        return [
            'ok' => true,
            'plan' => array_values($agg),
            'needs_retail' => $needsRetail,
        ];
    }

    /**
     * @param  array<int, array{warehouse_id:int, product_variant_id:int, unit_id:int, qty:float}>  $plan
     * @return array{ok: bool, message?: string}
     */
    public static function executeDeduct(array $plan, string $logCode, string $logNotes = 'Pengiriman produk'): array
    {
        ProductUnitStock::clearCache();

        return DB::transaction(function () use ($plan, $logCode, $logNotes) {
            foreach ($plan as $item) {
                $res = ProductUnitStock::deductQty(
                    (int) $item['warehouse_id'],
                    (int) $item['product_variant_id'],
                    (int) $item['unit_id'],
                    (float) $item['qty'],
                    $logCode,
                    $logNotes
                );
                if (! ($res['ok'] ?? false)) {
                    throw new \RuntimeException($res['message'] ?? 'Gagal potong stok');
                }
            }

            return ['ok' => true];
        });
    }

    /**
     * Kembalikan stok (update ACC / batal) — routing sama, tanpa cek kecukupan.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public static function executeRestore(array $lines, ?int $retailWarehouseId, string $logCode, string $logNotes = 'Update Pengiriman'): array
    {
        $plan = self::routeLinesOnly($lines, $retailWarehouseId);
        if ($plan === []) {
            return ['ok' => true];
        }

        ProductUnitStock::clearCache();

        return DB::transaction(function () use ($plan, $logCode, $logNotes) {
            foreach ($plan as $item) {
                $variant = ProductVariant::find($item['product_variant_id']);
                $productId = (int) ($variant->product_id ?? 0);
                $res = ProductUnitStock::addQty(
                    (int) $item['warehouse_id'],
                    $productId,
                    (int) $item['product_variant_id'],
                    (int) $item['unit_id'],
                    (float) $item['qty'],
                    $logCode,
                    $logNotes
                );
                if (! ($res['ok'] ?? false)) {
                    throw new \RuntimeException($res['message'] ?? 'Gagal kembalikan stok');
                }
            }

            return ['ok' => true];
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{warehouse_id:int, product_variant_id:int, unit_id:int, qty:float, is_retail:bool}>
     */
    protected static function routeLinesOnly(array $lines, ?int $retailWarehouseId): array
    {
        $mainId = self::mainWarehouseId();
        $retailId = (int) ($retailWarehouseId ?? 0);
        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');
        $agg = [];

        foreach ($lines as $line) {
            $variantId = (int) ($line['product_variant_id'] ?? 0);
            $unitId = (int) ($line['unit_id'] ?? 0);
            $qty = (float) ($line['qty'] ?? $line['sod_qty'] ?? $line['so_qty'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0 || $qty <= 0) {
                continue;
            }
            $retailUnit = $hasRetailCol
                ? (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0)
                : 0;
            $isRetail = $retailUnit > 0 && $unitId === $retailUnit;
            $warehouseId = $isRetail
                ? ((int) ($line['warehouse_id'] ?? 0) ?: $retailId)
                : $mainId;
            if ($warehouseId <= 0) {
                continue;
            }
            $key = $warehouseId . ':' . $variantId . ':' . $unitId;
            if (! isset($agg[$key])) {
                $agg[$key] = [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                    'unit_id' => $unitId,
                    'qty' => 0.0,
                    'is_retail' => $isRetail,
                ];
            }
            $agg[$key]['qty'] += $qty;
        }

        return array_values($agg);
    }

    /**
     * @return array<int, array{warehouse_id:int, warehouse_name:string, available:float}>
     */
    public static function recommendWarehouses(
        int $variantId,
        int $unitId,
        float $qty,
        int $excludeWarehouseId,
        bool $preferRetail = false
    ): array {
        $query = Warehouse::query()
            ->active()
            ->with(['type' => fn($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
            ->orderBy('warehouse_name');

        $rows = $query->get(['id', 'warehouse_name', 'warehouse_type_id']);
        $out = [];

        foreach ($rows as $wh) {
            $wid = (int) $wh->id;
            if ($wid === $excludeWarehouseId) {
                continue;
            }
            $isMain = (int) ($wh->type->is_main_warehouse ?? 0) === 1;
            if ($preferRetail && $isMain) {
                continue;
            }

            $available = ProductUnitStock::totalAvailable($wid, $variantId, $unitId);
            if ($available + 1e-9 < $qty) {
                continue;
            }

            $out[] = [
                'warehouse_id' => $wid,
                'warehouse_name' => (string) $wh->warehouse_name,
                'available' => $available,
                'is_main_warehouse' => $isMain ? 1 : 0,
            ];
        }

        usort($out, fn($a, $b) => $b['available'] <=> $a['available']);

        return $out;
    }

    public static function productLabel(int $variantId): string
    {
        $pvr = ProductVariant::find($variantId);
        if (! $pvr) {
            return 'Produk #' . $variantId;
        }
        $pr = Product::find($pvr->product_id);
        $name = trim(($pr->product_name ?? '') . ' ' . ($pvr->product_variant_name ?? ''));

        return $name !== '' ? $name : ('Varian #' . $variantId);
    }

    public static function unitLabel(int $unitId): string
    {
        $u = Unit::find($unitId);

        return $u->unit_short_name ?? $u->unit_name ?? ('Unit #' . $unitId);
    }

    protected static function fmtQty(float $qty): string
    {
        return number_format($qty, 0, ',', '.');
    }

    /**
     * Cek stok saat buat/update pengiriman (belum ACC).
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<string, mixed>|null  response error, atau null jika OK
     */
    public static function assertStockAvailable(array $products, $retailWarehouseId): ?array
    {
        $lines = [];
        foreach ($products as $p) {
            $lines[] = [
                'product_variant_id' => $p['product_variant_id'] ?? 0,
                'unit_id' => $p['unit_id'] ?? 0,
                'warehouse_id' => $p['warehouse_id'] ?? null,
                'qty' => (float) ($p['so_qty'] ?? $p['sod_qty'] ?? $p['qty'] ?? 0),
            ];
        }

        $plan = self::buildPlan($lines, (int) ($retailWarehouseId ?? 0) ?: null);
        if ($plan['ok'] ?? false) {
            return null;
        }

        return [
            'status' => $plan['status'] ?? 0,
            'header' => $plan['header'] ?? 'Stok tidak cukup',
            'message' => $plan['message'] ?? 'Stok tidak mencukupi',
            'products' => $plan['products'] ?? [],
            'recommendations' => $plan['recommendations'] ?? [],
        ];
    }

    /**
     * Validasi form simpan: item eceran butuh retail_warehouse_id.
     *
     * @param  array<int, array<string, mixed>>  $products
     */
    public static function validateRetailSelection(array $products, $retailWarehouseId): ?string
    {
        if (! Schema::hasColumn('product_variants', 'retail_unit')) {
            return null;
        }
        $retailId = (int) ($retailWarehouseId ?? 0);
        foreach ($products as $p) {
            $variantId = (int) ($p['product_variant_id'] ?? 0);
            $unitId = (int) ($p['unit_id'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0) {
                continue;
            }
            $retailUnit = (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0);
            $lineWarehouseId = (int) ($p['warehouse_id'] ?? 0);
            if ($retailUnit > 0 && $unitId === $retailUnit && $lineWarehouseId <= 0 && $retailId <= 0) {
                return 'Pilih gudang eceran pada setiap item yang memakai satuan eceran';
            }
        }

        return null;
    }
}
