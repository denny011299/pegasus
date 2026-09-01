<?php

namespace Tests\Support;

use App\Models\Warehouse;

trait ResolvesTestWarehouses
{
    protected function resolveActiveRetailWarehouseId(?string $sidebarMenu = null): int
    {
        $query = Warehouse::query()
            ->where('status', 1)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0));

        $warehouses = $query->get();

        if ($sidebarMenu !== null && $sidebarMenu !== '') {
            $match = $warehouses->first(fn ($w) => $w->allowsSidebarMenu($sidebarMenu));
            if ($match) {
                return (int) $match->id;
            }
        }

        $fallback = $warehouses->first();
        if (! $fallback) {
            $this->fail('No active retail warehouse exists in the loaded test data.');
        }

        return (int) $fallback->id;
    }
}
