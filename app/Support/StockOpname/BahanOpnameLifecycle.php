<?php

namespace App\Support\StockOpname;

use App\Models\Staff;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\UnitRollUp;

/**
 * Kembaran persis App\Support\StockOpname\OpnameLifecycle, untuk Stock Opname BAHAN (Supplies).
 * Aturan siklus hidup dan alasan lengkapnya IDENTIK -- lihat OpnameLifecycle untuk penjelasan.
 * Kelas terpisah, bukan diparameterisasi jadi satu, mengikuti konvensi repo ini (bandingkan
 * StockOpname.php vs StockOpnameBahan.php: mirror hampir baris-per-baris, bukan digabung).
 *
 * Beda dari OpnameLifecycle: Supplies tidak punya konsep varian/SKU, jadi snapshot identitas
 * baris cuma nama bahan + satuan (tidak ada sol_variant_name/sol_variant_sku setara).
 *
 * >>> PORTING popup konfirmasi gulung (2026-09-06, mengikuti sisi Produk) <<< detectRollupOpportunities()/
 * detectRollupOpportunitiesFromPayload()/rollUpUnitsFull() di sini adalah kembaran persis
 * method Produk dengan nama yang sama -- lihat OpnameLifecycle untuk seluruh alasan/keputusan
 * (2026-09-04 s.d. 2026-09-06). Saklar tampilan popupnya TIDAK diduplikasi di sini -- Bahan
 * memakai const yang SAMA, OpnameLifecycle::ROLLUP_PROJECTION_ENABLED (satu saklar untuk
 * keduanya, dirujuk langsung dari StockController::previewStockOpnameRollupBahan()/
 * insertStockOpnameBahan()/submitStockOpnameBahan()), bukan const terpisah -- popup gulung
 * Produk dan Bahan dimatikan/dinyalakan bersamaan.
 */
class BahanOpnameLifecycle
{
    public function publish($stob): void
    {
        if (! $stob || $stob->is_draft || $stob->is_old_version) {
            return;
        }

        if (empty($stob->stob_staff_name)) {
            $stob->stob_staff_name = optional(Staff::find($stob->staff_id))->staff_name;
            $stob->save();
        }

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)
            ->filter(fn ($l) => $l->sobl_supplies_name === null);

        if ($lines->isEmpty()) {
            return;
        }

        $supplies = Supplies::whereIn('supplies_id', $lines->pluck('supplies_id')->filter()->unique()->all())
            ->get()->keyBy('supplies_id');
        $units = Unit::whereIn('unit_id', $lines->pluck('unit_id')->filter()->unique()->all())
            ->get()->keyBy('unit_id');

        foreach ($lines as $line) {
            $supply = $supplies->get($line->supplies_id);
            $unit = $units->get($line->unit_id);

            $line->sobl_supplies_name = $supply->supplies_name ?? ('bahan#'.$line->supplies_id);
            $line->sobl_unit_short_name = $unit->unit_short_name ?? ('unit#'.$line->unit_id);
            $line->sobl_unit_name = $unit->unit_name ?? null;
            $line->save();
        }
    }

    /**
     * WAJIB dipanggil SEBELUM stok live ditimpa hasil hitung -- lihat OpnameLifecycle::
     * freezeSystemQty() untuk alasan lengkap (urutan salah = selisih dokumen jadi 0 selamanya).
     */
    public function freezeSystemQty($stob): void
    {
        if (! $stob || $stob->is_old_version) {
            return;
        }

        $lines = StockOpnameBahanLine::getLines($stob->stob_id);
        if ($lines->isEmpty()) {
            return;
        }

        // Dipin ke gudang dokumen ini -- lihat OpnameLifecycle::freezeSystemQty()'s doc.
        $warehouseId = $stob->warehouse_id ?: null;
        $stocks = ($warehouseId !== null
                ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                : SuppliesStock::query())
            ->where('status', 1)
            ->whereIn('supplies_id', $lines->pluck('supplies_id')->filter()->unique()->all())
            ->get()
            ->keyBy(fn ($s) => $s->supplies_id.'-'.$s->unit_id);

        foreach ($lines as $line) {
            $stock = $stocks->get($line->supplies_id.'-'.$line->unit_id);
            $line->sobl_system_qty_final = $stock ? (int) $stock->ss_stock : null;
            $line->save();
        }
    }

    /**
     * Kembaran OpnameLifecycle::rollUpUnits() — hangus + roll tiap simpan, tanpa lipat stok live.
     */
    public function rollUpUnits($stob): void
    {
        if (! $stob || $stob->is_old_version) {
            return;
        }

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->groupBy('supplies_id');
        $warehouseId = $stob->warehouse_id ?: null;

        if (self::isRetailWarehouse($warehouseId)) {
            return;
        }

        foreach ($lines as $suppliesId => $group) {
            if (! $suppliesId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sobl_counted_qty])->all();
            $touched = collect($qtyByUnit)->contains(fn ($q) => $q !== null);
            if (! $touched) {
                continue;
            }

            $collapsed = UnitRollUp::collapseSupplies((int) $suppliesId, $qtyByUnit, $warehouseId, false);
            $resultByUnit = [];
            foreach ($collapsed as $credit) {
                $resultByUnit[(int) $credit['unit_id']] = (int) $credit['qty'];
            }

            $first = $group->first();
            foreach ($group as $line) {
                $uid = (int) $line->unit_id;
                if (array_key_exists($uid, $resultByUnit)) {
                    $qty = $resultByUnit[$uid];
                } elseif ($qtyByUnit[$uid] !== null) {
                    $qty = (int) $qtyByUnit[$uid];
                } else {
                    $qty = 0;
                }

                StockOpnameBahanLine::upsertLine([
                    'stob_id' => $stob->stob_id,
                    'supplies_id' => $suppliesId,
                    'unit_id' => $uid,
                    'sobl_counted_qty' => $qty,
                    'sobl_notes' => $first->sobl_notes,
                ]);
            }
        }
    }

    /**
     * Kembaran persis OpnameLifecycle::detectRollupOpportunities() (Produk), untuk Supplies --
     * lihat kelas itu untuk seluruh alasan/keputusan (2026-09-05/06). Sengaja tidak diparameterisasi
     * jadi satu dengan Produk, mengikuti konvensi repo ini.
     *
     * @return array<int, array{supplies_id: int, supplies_name: string, changes: array<int, array{unit_id: int, unit_short_name: string, before: int, after: int}>}>
     */
    public function detectRollupOpportunities($stob): array
    {
        if (! $stob || $stob->is_old_version) {
            return [];
        }

        $warehouseId = $stob->warehouse_id ?: null;
        if (self::isRetailWarehouse($warehouseId)) {
            return [];
        }

        $flatLines = StockOpnameBahanLine::getLines($stob->stob_id);
        if ($flatLines->isEmpty()) {
            return [];
        }
        $lines = $flatLines->groupBy('supplies_id');

        $supplies = Supplies::whereIn('supplies_id', $lines->keys()->filter()->unique()->all())
            ->get()->keyBy('supplies_id');
        // $flatLines (BUKAN $lines yang sudah di-groupBy) -- groupBy() membungkus tiap grup jadi
        // Collection tersendiri, jadi pluck('unit_id') di atasnya cuma menghasilkan array kosong.
        $unitNames = Unit::whereIn('unit_id', $flatLines->pluck('unit_id')->filter()->unique()->all())
            ->pluck('unit_short_name', 'unit_id');

        $opportunities = [];
        foreach ($lines as $suppliesId => $group) {
            if (! $suppliesId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sobl_counted_qty])->all();
            $opportunity = $this->buildRollupOpportunity((int) $suppliesId, $qtyByUnit, $warehouseId, $supplies, $unitNames);
            if ($opportunity !== null) {
                $opportunities[] = $opportunity;
            }
        }

        return $opportunities;
    }

    /**
     * Kembaran persis OpnameLifecycle::detectRollupOpportunitiesFromPayload() (Produk), untuk
     * Supplies -- bekerja LANGSUNG dari payload frontend (CreateStockOpnameSupplies.js), tanpa
     * dokumen apa pun perlu ada di database. Key satuannya `sp_units`, bukan `units` -- lihat
     * StockOpnameBahanLine::writeFromPayload()'s docblock untuk alasannya.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array{supplies_id: int, supplies_name: string, changes: array<int, array{unit_id: int, unit_short_name: string, before: int, after: int}>}>
     */
    public function detectRollupOpportunitiesFromPayload(array $items, ?int $warehouseId): array
    {
        if (self::isRetailWarehouse($warehouseId)) {
            return [];
        }

        $bySupplies = [];
        foreach ($items as $item) {
            $suppliesId = (int) ($item['supplies_id'] ?? 0);
            if (! $suppliesId) {
                continue;
            }
            foreach (($item['sp_units'] ?? []) as $unit) {
                $unitId = (int) ($unit['unit_id'] ?? 0);
                if (! $unitId) {
                    continue;
                }
                // array_key_exists, bukan ?? -- null di sini BERMAKNA "tidak dihitung", sama
                // seperti konvensi StockOpnameBahanLine::upsertLine().
                $bySupplies[$suppliesId][$unitId] = array_key_exists('real_qty', $unit) && $unit['real_qty'] !== null
                    ? (int) $unit['real_qty']
                    : null;
            }
        }

        if ($bySupplies === []) {
            return [];
        }

        $suppliesIds = array_keys($bySupplies);
        $supplies = Supplies::whereIn('supplies_id', $suppliesIds)->get()->keyBy('supplies_id');
        $allUnitIds = collect($bySupplies)->flatMap(fn ($units) => array_keys($units))->unique()->all();
        $unitNames = Unit::whereIn('unit_id', $allUnitIds)->pluck('unit_short_name', 'unit_id');

        $opportunities = [];
        foreach ($bySupplies as $suppliesId => $qtyByUnit) {
            $opportunity = $this->buildRollupOpportunity($suppliesId, $qtyByUnit, $warehouseId, $supplies, $unitNames);
            if ($opportunity !== null) {
                $opportunities[] = $opportunity;
            }
        }

        return $opportunities;
    }

    /**
     * Kembaran persis OpnameLifecycle::buildRollupOpportunity() (Produk) -- lihat itu untuk
     * seluruh alasan (termasuk keputusan 2026-09-05 bahwa bahan yang tidak disentuh staf TETAP
     * dievaluasi kalau stok sistemnya sendiri tidak kanonik).
     *
     * @param  array<int, int|null>  $qtyByUnit
     * @param  \Illuminate\Support\Collection  $supplies  keyBy('supplies_id')
     * @param  \Illuminate\Support\Collection  $unitNames  unit_id => unit_short_name
     * @return array{supplies_id: int, supplies_name: string, changes: array<int, array{unit_id: int, unit_short_name: string, before: int, after: int}>}|null
     */
    private function buildRollupOpportunity(int $suppliesId, array $qtyByUnit, ?int $warehouseId, $supplies, $unitNames): ?array
    {
        $changes = self::computeFullProjectionChanges($suppliesId, $qtyByUnit, $warehouseId);
        if ($changes === []) {
            return null;
        }

        foreach ($changes as &$change) {
            $change['unit_short_name'] = $unitNames->get($change['unit_id']) ?? ('unit#'.$change['unit_id']);
        }
        unset($change);

        $multipliers = UnitRollUp::multipliersFromBottom(UnitRollUp::suppliesChain($suppliesId));
        usort($changes, fn ($a, $b) => ($multipliers[$b['unit_id']] ?? 0) <=> ($multipliers[$a['unit_id']] ?? 0));

        $supply = $supplies->get($suppliesId);
        $name = $supply->supplies_name ?? ('bahan#'.$suppliesId);

        return [
            'supplies_id' => $suppliesId,
            'supplies_name' => $name,
            'changes' => $changes,
        ];
    }

    /**
     * Kembaran persis OpnameLifecycle::computeFullProjectionChanges() (Produk) -- "before" adalah
     * nilai APA ADANYA (diketik staf, atau stok sistem kalau tidak diketik), BUKAN hasil gulung
     * parsial. Lihat kelas itu untuk alasan lengkap (bug GH mengisi satuan terkecil sendirian
     * tidak pernah lewat popup).
     *
     * @param  array<int, int|null>  $qtyByUnit
     * @return array<int, array{unit_id: int, before: int, after: int}>
     */
    private static function computeFullProjectionChanges(int $suppliesId, array $qtyByUnit, ?int $warehouseId): array
    {
        $existing = UnitRollUp::existingSuppliesStockByUnit($suppliesId, $warehouseId);
        $fullByUnit = collect(UnitRollUp::collapseSuppliesFull($suppliesId, $qtyByUnit, $warehouseId))
            ->pluck('qty', 'unit_id')->all();

        $unitIds = array_unique(array_merge(array_keys($qtyByUnit), array_keys($fullByUnit)));
        $changes = [];
        foreach ($unitIds as $unitId) {
            $before = $qtyByUnit[$unitId] ?? $existing[$unitId] ?? 0;
            $after = $fullByUnit[$unitId] ?? $before;
            if ((int) $before !== (int) $after) {
                $changes[] = [
                    'unit_id' => (int) $unitId,
                    'before' => (int) $before,
                    'after' => (int) $after,
                ];
            }
        }

        return $changes;
    }

    /**
     * Kembaran persis OpnameLifecycle::rollUpUnitsFull() (Produk) -- gulung PENUH lewat
     * UnitRollUp::collapseSuppliesFull(), digerbangi computeFullProjectionChanges() supaya
     * bahan yang tidak punya perubahan nyata (before === after di semua satuan) dilewati apa
     * adanya, tidak menimpa baris NULL ("tidak dihitung") dengan 0.
     *
     * >>> HANYA dipanggil setelah staf mengonfirmasi lewat popup ("Lanjut") -- lihat
     * StockController::insertStockOpnameBahan()/submitStockOpnameBahan() untuk gerbangnya.
     * TIDAK PERNAH otomatis, TIDAK PERNAH dari draft.
     */
    public function rollUpUnitsFull($stob): void
    {
        if (! $stob || $stob->is_old_version || $stob->is_draft) {
            return;
        }

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->groupBy('supplies_id');
        $warehouseId = $stob->warehouse_id ?: null;

        if (self::isRetailWarehouse($warehouseId)) {
            return;
        }

        foreach ($lines as $suppliesId => $group) {
            if (! $suppliesId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sobl_counted_qty])->all();
            $changes = self::computeFullProjectionChanges((int) $suppliesId, $qtyByUnit, $warehouseId);
            if ($changes === []) {
                continue;
            }

            $first = $group->first();

            foreach ($changes as $change) {
                StockOpnameBahanLine::upsertLine([
                    'stob_id' => $stob->stob_id,
                    'supplies_id' => $suppliesId,
                    'unit_id' => $change['unit_id'],
                    'sobl_counted_qty' => $change['after'],
                    'sobl_notes' => $first->sobl_notes,
                ]);
            }
        }
    }

    public function stampDecision($stob, ?int $accBy): void
    {
        if (! $stob || $stob->is_old_version) {
            return;
        }

        $stob->stob_acc_name = $accBy ? optional(Staff::find($accBy))->staff_name : null;
        $stob->stob_decided_at = now();
        $stob->save();
    }

    /** Kembaran persis OpnameLifecycle::isRetailWarehouse(). */
    private static function isRetailWarehouse(?int $warehouseId): bool
    {
        if (! $warehouseId) {
            return false;
        }

        $warehouse = Warehouse::query()
            ->with(['type' => fn ($q) => $q->select('id', 'is_main_warehouse')])
            ->find($warehouseId);

        return (bool) ($warehouse && $warehouse->type && (int) $warehouse->type->is_main_warehouse !== 1);
    }
}
