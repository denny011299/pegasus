<?php

namespace App\Support\StockOpname;

/**
 * Centang "ikut stok lama": sebelum writeFromPayload + rollUp, isi real_qty dari system_qty.
 * Dipakai Produk (units) dan Bahan (sp_units).
 */
final class UseSystemStock
{
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
}
