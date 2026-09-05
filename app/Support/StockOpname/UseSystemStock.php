<?php

namespace App\Support\StockOpname;

use App\Models\ProductStock;
use App\Models\StockOpnameBahanLine;
use App\Models\StockOpnameLine;
use App\Models\SuppliesStock;

/**
 * Centang "ikut stock sistem".
 *
 * - Non-draft simpan: autofillPayload isi real_qty dari system_qty payload, lalu hangus/roll-up.
 * - Draft: JANGAN autofill — hanya flag + ketikan user. Saat ajukan, materialize*FromLive isi
 *   counted dari stok live gudang dokumen, baru hangus/roll-up.
 */
final class UseSystemStock
{
    /**
     * Satu baris tidak boleh semua satuan tercentang (sama artinya tidak di-opname).
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    public static function rejectIfAllUnitsUseSystem(array $items, string $unitsKey = 'units'): ?string
    {
        foreach ($items as $item) {
            $units = $item[$unitsKey] ?? null;
            if (! is_array($units) || $units === []) {
                continue;
            }

            $all = true;
            foreach ($units as $unit) {
                if (empty($unit['use_system_stock'])) {
                    $all = false;
                    break;
                }
            }

            if ($all) {
                return 'Satu baris tidak boleh semua satuan tercentang. Kosongkan minimal satu atau isi hitungan.';
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public static function autofillPayload(array $items, string $unitsKey = 'units'): array
    {
        foreach ($items as &$item) {
            if (empty($item[$unitsKey]) || ! is_array($item[$unitsKey])) {
                continue;
            }

            foreach ($item[$unitsKey] as &$unit) {
                if (empty($unit['use_system_stock'])) {
                    continue;
                }

                $system = $unit['system_qty'] ?? null;
                $unit['real_qty'] = ($system === null || $system === '') ? 0 : (int) $system;
            }
            unset($unit);
        }
        unset($item);

        return $items;
    }

    /** Ajukan draft produk: isi counted dari stok live untuk baris yang tercengang. */
    public static function materializeProductFlagsFromLive($sto): void
    {
        if (! $sto || $sto->is_old_version) {
            return;
        }

        $lines = StockOpnameLine::getLines($sto->sto_id);
        $flagged = $lines->filter(fn ($l) => (bool) $l->sol_use_system_stock);
        if ($flagged->isEmpty()) {
            return;
        }

        $warehouseId = $sto->warehouse_id ?: null;
        $live = ($warehouseId !== null
                ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : ProductStock::query())
            ->where('status', 1)
            ->whereIn('product_variant_id', $flagged->pluck('product_variant_id')->filter()->unique()->all())
            ->get()
            ->mapWithKeys(fn ($s) => [$s->product_variant_id.'-'.$s->unit_id => (int) $s->ps_stock]);

        foreach ($flagged as $line) {
            $qty = (int) ($live->get($line->product_variant_id.'-'.$line->unit_id) ?? 0);
            StockOpnameLine::upsertLine([
                'sto_id' => $line->sto_id,
                'product_id' => $line->product_id,
                'product_variant_id' => $line->product_variant_id,
                'unit_id' => $line->unit_id,
                'sol_counted_qty' => $qty,
                'sol_use_system_stock' => true,
                'sol_notes' => $line->sol_notes,
            ]);
        }
    }

    /** Ajukan draft bahan: kembaran materializeProductFlagsFromLive. */
    public static function materializeBahanFlagsFromLive($stob): void
    {
        if (! $stob || $stob->is_old_version) {
            return;
        }

        $lines = StockOpnameBahanLine::getLines($stob->stob_id);
        $flagged = $lines->filter(fn ($l) => (bool) $l->sobl_use_system_stock);
        if ($flagged->isEmpty()) {
            return;
        }

        $warehouseId = $stob->warehouse_id ?: null;
        $live = ($warehouseId !== null
                ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : SuppliesStock::query())
            ->where('status', 1)
            ->whereIn('supplies_id', $flagged->pluck('supplies_id')->filter()->unique()->all())
            ->get()
            ->mapWithKeys(fn ($s) => [$s->supplies_id.'-'.$s->unit_id => (int) $s->ss_stock]);

        foreach ($flagged as $line) {
            $qty = (int) ($live->get($line->supplies_id.'-'.$line->unit_id) ?? 0);
            StockOpnameBahanLine::upsertLine([
                'stob_id' => $line->stob_id,
                'supplies_id' => $line->supplies_id,
                'unit_id' => $line->unit_id,
                'sobl_counted_qty' => $qty,
                'sobl_use_system_stock' => true,
                'sobl_notes' => $line->sobl_notes,
            ]);
        }
    }
}
