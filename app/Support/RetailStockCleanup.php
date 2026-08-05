<?php

namespace App\Support;

use App\Models\LogStock;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

/**
 * Cleanup satu-kali: gudang eceran (is_main_warehouse=0) HANYA boleh menyimpan stok
 * di retail_unit varian. Baris product_stocks di satuan lain (DOS/Jerigen sisa data
 * lama sebelum aturan "eceran cuma pegang retail_unit" berlaku) dikonversi ke
 * retail_unit bila memungkinkan (satu chain product_relations), atau di-nol-kan +
 * dicatat di log_stocks kalau tidak bisa dikonversi (retail_unit belum diatur untuk
 * varian tersebut, atau beda chain).
 *
 * Lihat docs/backlog-stock-multi-gudang.md item #7.
 */
class RetailStockCleanup
{
    private const LOG_CODE = 'CLEANUP-RETAIL';

    /**
     * @return array{
     *   converted: int,
     *   zeroed_no_chain: int,
     *   skipped_no_retail_unit: int,
     *   details: array<int, array<string, mixed>>
     * }
     */
    public static function run(bool $dryRun = false): array
    {
        $summary = [
            'converted' => 0,
            'zeroed_no_chain' => 0,
            'skipped_no_retail_unit' => 0,
            'details' => [],
        ];

        $retailWarehouseIds = Warehouse::query()
            ->active()
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($retailWarehouseIds === []) {
            return $summary;
        }

        $rows = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->whereIn('warehouse_id', $retailWarehouseIds)
            ->where('ps_stock', '>', 0)
            ->get();

        if ($rows->isEmpty()) {
            return $summary;
        }

        $variantIds = $rows->pluck('product_variant_id')->map(fn ($id) => (int) $id)->unique()->all();
        $variants = ProductVariant::query()
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');

        ProductUnitStock::clearCache();

        $run = function () use ($rows, $variants, $dryRun, &$summary) {
            foreach ($rows as $row) {
                self::processRow($row, $variants, $dryRun, $summary);
            }
        };

        if ($dryRun) {
            $run();
        } else {
            DB::transaction($run);
        }

        ProductUnitStock::clearCache();

        return $summary;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ProductVariant>  $variants
     * @param  array{converted:int, zeroed_no_chain:int, skipped_no_retail_unit:int, details:array<int,array<string,mixed>>}  &$summary
     */
    private static function processRow(ProductStock $row, $variants, bool $dryRun, array &$summary): void
    {
        $variantId = (int) $row->product_variant_id;
        $variant = $variants->get($variantId);
        $retailUnitId = (int) ($variant->retail_unit ?? 0);
        $currentUnitId = (int) $row->unit_id;
        $warehouseId = (int) $row->warehouse_id;
        $qty = (float) $row->ps_stock;

        if ($retailUnitId > 0 && $currentUnitId === $retailUnitId) {
            return; // sudah benar, tidak ada yang perlu dibersihkan
        }

        $label = sprintf(
            'Varian #%d @ Gudang #%d (unit #%d, qty %s)',
            $variantId,
            $warehouseId,
            $currentUnitId,
            rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.')
        );

        if ($retailUnitId <= 0) {
            // Sengaja TIDAK di-nol-kan: kita tidak tahu satuan retail yang benar untuk varian
            // ini (retail_unit belum pernah diatur), jadi stok yang ada bisa saja valid (mis.
            // sudah di Piece). Query di spec ("unit_id != retail_unit AND retail_unit set")
            // memang membatasi cleanup hanya untuk varian yang retail_unit-nya sudah diketahui —
            // di luar itu cuma dilaporkan supaya staf atur retail_unit-nya dulu (menu Stock
            // Transfer → satuan eceran), baru dijalankan ulang cleanup ini.
            $summary['skipped_no_retail_unit']++;
            $summary['details'][] = [
                'action' => 'skip_no_retail_unit',
                'label' => $label,
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $variantId,
                'unit_id' => $currentUnitId,
                'qty' => $qty,
            ];

            return;
        }

        $canConvert = ProductUnitStock::canConvertUnits($currentUnitId, $retailUnitId, $variantId);
        if (! $canConvert) {
            $summary['zeroed_no_chain']++;
            $summary['details'][] = [
                'action' => 'zero_no_chain',
                'label' => $label,
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $variantId,
                'unit_id' => $currentUnitId,
                'qty' => $qty,
                'retail_unit_id' => $retailUnitId,
            ];
            if (! $dryRun) {
                self::zeroRow(
                    $row,
                    'Cleanup stok eceran → satuan retail (satuan #' . $currentUnitId
                        . ' tidak sechain dengan retail_unit #' . $retailUnitId . ', di-nol-kan)'
                );
            }

            return;
        }

        $convertedQty = ProductUnitStock::convertQty($qty, $currentUnitId, $retailUnitId, $variantId);
        $summary['converted']++;
        $summary['details'][] = [
            'action' => 'convert',
            'label' => $label,
            'warehouse_id' => $warehouseId,
            'product_variant_id' => $variantId,
            'unit_id' => $currentUnitId,
            'qty' => $qty,
            'retail_unit_id' => $retailUnitId,
            'converted_qty' => $convertedQty,
        ];

        if ($dryRun) {
            return;
        }

        $add = ProductUnitStock::addQty(
            $warehouseId,
            (int) $row->product_id,
            $variantId,
            $retailUnitId,
            $convertedQty,
            self::LOG_CODE,
            'Cleanup stok eceran → satuan retail (konversi dari unit #' . $currentUnitId . ')'
        );
        if (! ($add['ok'] ?? false)) {
            throw new \RuntimeException($add['message'] ?? 'Gagal konversi stok eceran');
        }

        self::zeroRow(
            $row,
            'Cleanup stok eceran → satuan retail (dikonversi ke retail_unit #' . $retailUnitId . ')'
        );
    }

    private static function zeroRow(ProductStock $row, string $note): void
    {
        $qty = (float) $row->ps_stock;
        if ($qty <= 0) {
            return;
        }

        $row->ps_stock = 0;
        $row->save();

        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode' => self::LOG_CODE,
            'log_type' => 1,
            'log_category' => 2,
            'log_item_id' => $row->product_variant_id,
            'log_notes' => $note,
            'log_jumlah' => $qty,
            'log_saldo' => 0,
            'unit_id' => $row->unit_id,
            'warehouse_id' => $row->warehouse_id,
        ]);
    }
}
