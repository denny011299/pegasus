<?php

namespace App\Support\StockOpname;

use App\Models\Staff;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Support\UnitRollUp;

/**
 * Kembaran persis App\Support\StockOpname\OpnameLifecycle, untuk Stock Opname BAHAN (Supplies).
 * Aturan siklus hidup dan alasan lengkapnya IDENTIK -- lihat OpnameLifecycle untuk penjelasan.
 * Kelas terpisah, bukan diparameterisasi jadi satu, mengikuti konvensi repo ini (bandingkan
 * StockOpname.php vs StockOpnameBahan.php: mirror hampir baris-per-baris, bukan digabung).
 *
 * Beda dari OpnameLifecycle: Supplies tidak punya konsep varian/SKU, jadi snapshot identitas
 * baris cuma nama bahan + satuan (tidak ada sol_variant_name/sol_variant_sku setara).
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
     * Kembaran persis OpnameLifecycle::rollUpUnits() (Produk), untuk Supplies. Lihat kelas itu
     * untuk alasan lengkap.
     */
    public function rollUpUnits($stob): void
    {
        if (! $stob || $stob->is_old_version) {
            return;
        }

        $lines = StockOpnameBahanLine::getLines($stob->stob_id)->groupBy('supplies_id');

        foreach ($lines as $suppliesId => $group) {
            if (! $suppliesId) {
                continue;
            }

            $qtyByUnit = $group->mapWithKeys(fn ($l) => [(int) $l->unit_id => $l->sobl_counted_qty])->all();
            $collapsed = UnitRollUp::collapseSupplies((int) $suppliesId, $qtyByUnit);
            if ($collapsed === []) {
                continue;
            }

            $first = $group->first();

            foreach ($collapsed as $credit) {
                StockOpnameBahanLine::upsertLine([
                    'stob_id' => $stob->stob_id,
                    'supplies_id' => $suppliesId,
                    'unit_id' => $credit['unit_id'],
                    'sobl_counted_qty' => $credit['qty'],
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
}
