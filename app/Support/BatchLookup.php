<?php

namespace App\Support;

use App\Models\Staff;
use Illuminate\Support\Collection;

class BatchLookup
{
    public static function staffNames(iterable $ids): Collection
    {
        $ids = collect($ids)
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return collect();
        }

        return Staff::whereIn('staff_id', $ids)->pluck('staff_name', 'staff_id');
    }
}
