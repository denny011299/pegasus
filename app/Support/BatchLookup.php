<?php

namespace App\Support;

use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Support\Collection;

class BatchLookup
{
    public static function staffNames(iterable $ids): Collection
    {
        $ids = self::normalizeIds($ids);

        if ($ids === []) {
            return collect();
        }

        return Staff::whereIn('staff_id', $ids)->pluck('staff_name', 'staff_id');
    }

    /**
     * @return Collection<int, Staff>
     */
    public static function staffRows(iterable $ids, array $columns = ['staff_id', 'staff_name', 'staff_saldo']): Collection
    {
        $ids = self::normalizeIds($ids);

        if ($ids === []) {
            return collect();
        }

        return Staff::whereIn('staff_id', $ids)->get($columns)->keyBy('staff_id');
    }

    /**
     * @return Collection<int, Customer>
     */
    public static function customers(iterable $ids, array $columns = ['customer_id', 'customer_notes', 'customer_saldo']): Collection
    {
        $ids = self::normalizeIds($ids);

        if ($ids === []) {
            return collect();
        }

        return Customer::whereIn('customer_id', $ids)->get($columns)->keyBy('customer_id');
    }

    /**
     * @return array<int, int>
     */
    private static function normalizeIds(iterable $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}
