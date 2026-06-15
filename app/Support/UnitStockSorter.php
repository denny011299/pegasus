<?php

namespace App\Support;

class UnitStockSorter
{
    /**
     * Urutkan stok dari satuan terbesar ke terkecil berdasarkan relasi unit.
     * Contoh relasi: 1 Pack = 10 Piece → tampil Pack dulu, baru Piece.
     */
    public static function sort($stock, $relations, string $parentKey = 'pr_unit_id_1', string $childKey = 'pr_unit_id_2')
    {
        $stock = collect($stock)->values();
        if ($stock->isEmpty()) {
            return $stock;
        }

        $relations = collect($relations);
        if ($relations->isEmpty()) {
            return $stock;
        }

        $children = [];
        $allParents = [];
        $allChildren = [];

        foreach ($relations as $rel) {
            $parent = (int) self::read($rel, $parentKey);
            $child = (int) self::read($rel, $childKey);
            if (! $parent || ! $child) {
                continue;
            }
            $children[$parent][] = $child;
            $allParents[$parent] = true;
            $allChildren[$child] = true;
        }

        if ($allParents === []) {
            return $stock;
        }

        $roots = array_keys(array_diff_key($allParents, $allChildren));
        $stockUnitIds = $stock
            ->map(fn ($item) => (int) self::read($item, 'unit_id'))
            ->unique()
            ->values()
            ->all();

        if ($roots === []) {
            foreach ($stockUnitIds as $uid) {
                if (! isset($allChildren[$uid])) {
                    $roots[] = $uid;
                }
            }
        }

        $orderedUnitIds = [];
        $visited = [];
        $queue = $roots;

        while (! empty($queue)) {
            $uid = array_shift($queue);
            if (isset($visited[$uid])) {
                continue;
            }
            $visited[$uid] = true;
            $orderedUnitIds[] = $uid;
            foreach ($children[$uid] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }

        foreach ($stockUnitIds as $uid) {
            if (! isset($visited[$uid])) {
                $orderedUnitIds[] = $uid;
            }
        }

        $rank = array_flip($orderedUnitIds);

        return $stock
            ->sortBy(fn ($item) => $rank[(int) self::read($item, 'unit_id')] ?? 999)
            ->values();
    }

    private static function read($item, string $key): mixed
    {
        if (is_array($item)) {
            return $item[$key] ?? null;
        }

        return $item->{$key} ?? null;
    }
}
