<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

/**
 * Potong / kembalikan stok pengiriman (Sales Order):
 * - satuan eceran = default produk (SKU 1 satuan) → gudang utama ATAU eceran
 * - satuan eceran ≠ default → gudang eceran only (flow lama #116)
 * - satuan lain → gudang utama aktif (session) atau warehouse_id pada detail
 */
class SalesOrderStock
{
    public static function mainWarehouseId(): int
    {
        $id = Warehouse::query()
            ->active()
            ->whereHas('type', fn($q) => $q->where('is_main_warehouse', 1))
            ->orderBy('id')
            ->value('id');

        return (int) ($id ?? 0);
    }

    public static function isMainWarehouse(int $warehouseId): bool
    {
        if ($warehouseId <= 0) {
            return false;
        }

        return Warehouse::query()
            ->active()
            ->whereKey($warehouseId)
            ->whereHas('type', fn($q) => $q->where('is_main_warehouse', 1))
            ->exists();
    }

    /** Gudang utama untuk satuan non-eceran: prefer eksplisit → session aktif → fallback DB. */
    public static function resolveBulkWarehouseId(?int $preferredId = null): int
    {
        if ($preferredId !== null && $preferredId > 0 && self::isMainWarehouse($preferredId)) {
            return $preferredId;
        }

        $active = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($active > 0 && self::isMainWarehouse($active)) {
            return $active;
        }

        return self::mainWarehouseId();
    }

    /**
     * Isi warehouse_id kosong dari gudang utama aktif.
     * Eceran ≠ default → biarkan kosong (wajib pilih eceran). Eceran = default → isi utama.
     *
     * @param  array<int, array<string, mixed>>  $products
     * @return array<int, array<string, mixed>>
     */
    public static function assignBulkWarehouseToProducts(array $products): array
    {
        $bulkId = self::resolveBulkWarehouseId(null);
        if ($bulkId <= 0) {
            return $products;
        }

        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');

        foreach ($products as &$p) {
            if ((int) ($p['warehouse_id'] ?? 0) > 0) {
                continue;
            }
            $variantId = (int) ($p['product_variant_id'] ?? 0);
            $unitId = (int) ($p['unit_id'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0) {
                continue;
            }
            if ($hasRetailCol) {
                $retailUnit = (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0);
                // Flow lama: eceran ≠ default harus pilih gudang eceran dulu.
                if ($retailUnit > 0 && $unitId === $retailUnit && ! self::isRetailSameAsDefault($variantId, $retailUnit)) {
                    continue;
                }
            }
            $p['warehouse_id'] = $bulkId;
        }
        unset($p);

        return $products;
    }

    /** products.unit_id = satuan default produk untuk varian. */
    public static function productDefaultUnitId(int $variantId): int
    {
        if ($variantId <= 0) {
            return 0;
        }
        $productId = (int) (ProductVariant::where('product_variant_id', $variantId)->value('product_id') ?? 0);
        if ($productId <= 0) {
            return 0;
        }

        return (int) (Product::where('product_id', $productId)->value('unit_id') ?? 0);
    }

    /** retail_unit sama default produk → SKU 1 satuan. */
    public static function isRetailSameAsDefault(int $variantId, int $retailUnit): bool
    {
        if ($variantId <= 0 || $retailUnit <= 0) {
            return false;
        }
        $defaultUnit = self::productDefaultUnitId($variantId);

        return $defaultUnit > 0 && $defaultUnit === $retailUnit;
    }

    public static function isRetailWarehouse(int $warehouseId): bool
    {
        if ($warehouseId <= 0) {
            return false;
        }

        return Warehouse::query()
            ->active()
            ->whereKey($warehouseId)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0))
            ->exists();
    }

    /**
     * Gudang boleh untuk satuan eceran.
     * $allowMain true (eceran = default) → utama atau eceran; false → eceran only.
     */
    public static function isAllowedRetailSourceWarehouse(int $warehouseId, bool $allowMain = false): bool
    {
        if ($warehouseId <= 0) {
            return false;
        }
        if ($allowMain) {
            return Warehouse::query()
                ->active()
                ->whereKey($warehouseId)
                ->exists();
        }

        return self::isRetailWarehouse($warehouseId);
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
        $defaultBulkId = self::resolveBulkWarehouseId(self::inferBulkWarehouseFromLines($lines));
        if ($defaultBulkId <= 0) {
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
            $retailSameAsDefault = $isRetail && self::isRetailSameAsDefault($variantId, $retailUnit);
            if ($isRetail) {
                $needsRetail = true;
                // Eceran = default → boleh utama; eceran ≠ default → eceran only (lama).
                $warehouseId = (int) ($line['warehouse_id'] ?? 0) ?: $retailId;
                if ($warehouseId <= 0 && $retailSameAsDefault) {
                    $warehouseId = $defaultBulkId;
                }
            } else {
                $lineWh = (int) ($line['warehouse_id'] ?? 0);
                $warehouseId = ($lineWh > 0 && self::isMainWarehouse($lineWh))
                    ? $lineWh
                    : $defaultBulkId;
            }

            if ($isRetail && $warehouseId <= 0) {
                return [
                    'ok' => false,
                    'status' => 0,
                    'header' => $retailSameAsDefault ? 'Gudang sumber wajib' : 'Gudang eceran wajib',
                    'message' => $retailSameAsDefault
                        ? 'Pilih gudang sumber pada setiap item yang memakai satuan eceran.'
                        : 'Pilih gudang eceran pada setiap item yang memakai satuan eceran.',
                ];
            }
            if ($isRetail && ! self::isAllowedRetailSourceWarehouse($warehouseId, $retailSameAsDefault)) {
                return [
                    'ok' => false,
                    'status' => 0,
                    'header' => 'Gudang tidak valid',
                    'message' => $retailSameAsDefault
                        ? 'Gudang pada item satuan eceran harus gudang utama atau eceran aktif.'
                        : 'Gudang pada item satuan eceran harus berupa gudang eceran aktif.',
                ];
            }

            if ($deny = self::denyIfWarehouseNotAssigned($warehouseId)) {
                return $deny;
            }

            $key = $warehouseId . ':' . $variantId . ':' . $unitId;
            if (! isset($agg[$key])) {
                $agg[$key] = [
                    'warehouse_id' => $warehouseId,
                    'product_variant_id' => $variantId,
                    'unit_id' => $unitId,
                    'qty' => 0.0,
                    'is_retail' => $isRetail,
                    'retail_same_as_default' => $retailSameAsDefault,
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
                (bool) $item['is_retail'],
                // Eceran ≠ default: rekomendasi eceran only (flow lama).
                (bool) $item['is_retail'] && empty($item['retail_same_as_default'])
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
     * $rollUp (merged from main's PR #75, 2026-08-28): kalau true, tiap baris yang dikembalikan
     * dinaikkan berjenjang lewat ProductUnitStock::addQty()'s $rollUp -- dipakai saat
     * pengembaliannya FINAL (pembatalan penuh lewat SalesOrderCancellation, atau baris yang
     * dihapus dari SO saat edit). Default false: dipakai saat baris yang sama akan langsung
     * dipotong lagi dalam request yang sama (edit SO yang mempertahankan baris itu) -- roll-up di
     * situ cuma churn (naik lalu langsung dibongkar lagi) plus sepasang log konversi yang
     * menyesatkan.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public static function executeRestore(array $lines, ?int $retailWarehouseId, string $logCode, string $logNotes = 'Update Pengiriman', bool $rollUp = false): array
    {
        $plan = self::routeLinesOnly($lines, $retailWarehouseId);
        if ($plan === []) {
            return ['ok' => true];
        }

        ProductUnitStock::clearCache();

        return DB::transaction(function () use ($plan, $logCode, $logNotes, $rollUp) {
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
                    $logNotes,
                    $rollUp
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
        $defaultBulkId = self::resolveBulkWarehouseId(self::inferBulkWarehouseFromLines($lines));
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
            $retailSameAsDefault = $isRetail && self::isRetailSameAsDefault($variantId, $retailUnit);
            if ($isRetail) {
                $warehouseId = (int) ($line['warehouse_id'] ?? 0) ?: $retailId;
                if ($warehouseId <= 0 && $retailSameAsDefault) {
                    $warehouseId = $defaultBulkId;
                }
            } else {
                $lineWh = (int) ($line['warehouse_id'] ?? 0);
                $warehouseId = ($lineWh > 0 && self::isMainWarehouse($lineWh))
                    ? $lineWh
                    : $defaultBulkId;
            }
            if ($warehouseId <= 0) {
                continue;
            }
            // Eceran ≠ default tidak boleh tersimpan ke gudang utama.
            if ($isRetail && ! self::isAllowedRetailSourceWarehouse($warehouseId, $retailSameAsDefault)) {
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
        bool $preferRetail = false,
        bool $retailOnly = false
    ): array {
        $query = Warehouse::query()
            ->active()
            ->with(['type' => fn($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
            ->orderBy('warehouse_name');

        $assignedIds = self::assignedWarehouseIds();
        if ($assignedIds !== []) {
            $query->whereIn('id', $assignedIds);
        }

        $rows = $query->get(['id', 'warehouse_name', 'warehouse_type_id']);
        $out = [];

        foreach ($rows as $wh) {
            $wid = (int) $wh->id;
            if ($wid === $excludeWarehouseId) {
                continue;
            }
            $isMain = (int) ($wh->type->is_main_warehouse ?? 0) === 1;
            if ($retailOnly && $isMain) {
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

        usort($out, function ($a, $b) use ($preferRetail) {
            if ($preferRetail && ((int) $a['is_main_warehouse'] !== (int) $b['is_main_warehouse'])) {
                return (int) $a['is_main_warehouse'] <=> (int) $b['is_main_warehouse'];
            }

            return $b['available'] <=> $a['available'];
        });

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
     * TIDAK DIPAKAI LAGI sejak GitHub #99 (2026-09-01) — sengaja dibiarkan, jangan dipasang ulang
     * di jalur pra-ACC. Dulu dipanggil insertSalesOrder()/updateSalesOrder() (cabang status != 2)
     * dan memblokir pembuatan dokumen pengiriman kalau stok saat itu kurang. Itu salah waktunya:
     * membuat pengiriman hanya mengajukan dokumen, stok baru dicek + dipotong di accSO()
     * (SalesOrderApproval::confirm() -> buildPlan() -> executeDeduct(), satu transaksi).
     * Kalau butuh pratinjau ketersediaan stok yang TIDAK memblokir, pakai buildPlan() langsung.
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
     * Gudang eceran aktif: hanya boleh satuan eceran default per varian.
     *
     * @param  array<int, array<string, mixed>>  $products
     */
    public static function validateRetailWarehouseUnits(array $products): ?string
    {
        if (! Schema::hasColumn('product_variants', 'retail_unit')) {
            return null;
        }

        $activeWh = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($activeWh <= 0) {
            return null;
        }

        $isRetailWh = Warehouse::query()
            ->active()
            ->whereKey($activeWh)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0))
            ->exists();

        if (! $isRetailWh) {
            return null;
        }

        foreach ($products as $p) {
            $variantId = (int) ($p['product_variant_id'] ?? 0);
            $unitId = (int) ($p['unit_id'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0) {
                continue;
            }

            $retailUnit = (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0);
            if ($retailUnit <= 0) {
                return 'Produk belum memiliki satuan eceran default';
            }
            if ($unitId !== $retailUnit) {
                return 'Gudang eceran hanya boleh memakai satuan eceran default';
            }
        }

        return null;
    }

    /**
     * Validasi form simpan: item eceran butuh gudang.
     * Eceran = default → utama/eceran; eceran ≠ default → eceran only.
     *
     * @param  array<int, array<string, mixed>>  $products
     */
    public static function validateRetailSelection(array $products, $retailWarehouseId): ?string
    {
        if (! Schema::hasColumn('product_variants', 'retail_unit')) {
            return null;
        }
        $retailId = (int) ($retailWarehouseId ?? 0);
        $bulkId = self::resolveBulkWarehouseId(null);
        $assignedIds = self::assignedWarehouseIds();
        foreach ($products as $p) {
            $variantId = (int) ($p['product_variant_id'] ?? 0);
            $unitId = (int) ($p['unit_id'] ?? 0);
            if ($variantId <= 0 || $unitId <= 0) {
                continue;
            }
            $retailUnit = (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0);
            if ($retailUnit <= 0 || $unitId !== $retailUnit) {
                continue;
            }
            $sameAsDefault = self::isRetailSameAsDefault($variantId, $retailUnit);
            $lineWarehouseId = (int) ($p['warehouse_id'] ?? 0);
            $whId = $lineWarehouseId > 0
                ? $lineWarehouseId
                : ($retailId > 0 ? $retailId : ($sameAsDefault ? $bulkId : 0));
            if ($whId <= 0) {
                return $sameAsDefault
                    ? 'Pilih gudang sumber pada setiap item yang memakai satuan eceran'
                    : 'Pilih gudang eceran pada setiap item yang memakai satuan eceran';
            }
            if (! self::isAllowedRetailSourceWarehouse($whId, $sameAsDefault)) {
                return $sameAsDefault
                    ? 'Gudang pada item satuan eceran harus gudang utama atau eceran aktif'
                    : 'Gudang pada item satuan eceran harus berupa gudang eceran aktif';
            }
            if ($assignedIds !== [] && ! in_array($whId, $assignedIds, true)) {
                return 'Gudang yang dipilih tidak termasuk gudang Anda';
            }
        }

        return null;
    }

    /**
     * Ambil gudang utama dari warehouse_id detail non-eceran (untuk SO yang sudah disimpan).
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    protected static function inferBulkWarehouseFromLines(array $lines): ?int
    {
        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');

        foreach ($lines as $line) {
            $wh = (int) ($line['warehouse_id'] ?? 0);
            if ($wh <= 0 || ! self::isMainWarehouse($wh)) {
                continue;
            }
            if ($hasRetailCol) {
                $variantId = (int) ($line['product_variant_id'] ?? 0);
                $unitId = (int) ($line['unit_id'] ?? 0);
                if ($variantId > 0 && $unitId > 0) {
                    $retailUnit = (int) (ProductVariant::where('product_variant_id', $variantId)->value('retail_unit') ?? 0);
                    if ($retailUnit > 0 && $unitId === $retailUnit) {
                        continue;
                    }
                }
            }

            return $wh;
        }

        return null;
    }

    /** @return array<int, int> */
    protected static function assignedWarehouseIds(): array
    {
        return Staff::assignedWarehouseIds(Session::get('user'));
    }

    /** @return array<string, mixed>|null */
    protected static function denyIfWarehouseNotAssigned(int $warehouseId): ?array
    {
        if ($warehouseId <= 0) {
            return null;
        }

        $assignedIds = self::assignedWarehouseIds();
        if ($assignedIds === [] || in_array($warehouseId, $assignedIds, true)) {
            return null;
        }

        return [
            'ok' => false,
            'status' => 0,
            'header' => 'Gudang tidak diizinkan',
            'message' => 'Anda tidak memiliki akses ke gudang yang dipilih.',
        ];
    }
}
