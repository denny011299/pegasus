<?php

namespace App\Support;

use App\Models\LogStock;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\Session;

/**
 * Bahan/Supplies counterpart of ProductUnitStock::addQty() — same contract, same roll-up
 * mechanics (UnitRollUp::planSuppliesFolded()), just against SuppliesStock instead of
 * ProductStock.
 *
 * Why this exists (2026-09-07, follow-up to GitHub #159): the Bahan/Supplies side of Pengembalian
 * (`CustomerReturnController::acceptSupply()` / `CustomerSupplyReturnController::accept()`) had
 * the exact same flat-credit-no-rollup gap as GitHub #132/#155, but there was no `ss_stock`
 * equivalent of `ProductUnitStock::addQty()` to reuse — see `cdocs/testing/KNOWN_ISSUES.md`'s
 * #132 entry ("Not fixed in this pass: ... there is no SuppliesUnitStock equivalent ... building
 * one was out of scope"). This class is that follow-up.
 */
class SuppliesUnitStock
{
    /**
     * Tambah stok bahan di gudang (satuan yang sama, kecuali $rollUp). Buat baris stok jika belum
     * ada. Lihat docblock `ProductUnitStock::addQty()` untuk desain lengkapnya — method ini adalah
     * kembarannya persis, hanya menyasar `SuppliesStock`/`UnitRollUp::planSuppliesFolded()`.
     *
     * @return array{ok: bool, message?: string}
     */
    public static function addQty(
        int $warehouseId,
        int $suppliesId,
        int $unitId,
        float $qty,
        string $logCode,
        string $logNotes = 'Stock masuk',
        bool $rollUp = false,
        ?array $rollUpAllowedUnitIds = null
    ): array {
        if ($qty <= 0) {
            return ['ok' => true];
        }

        $credits = $rollUp
            ? UnitRollUp::planSuppliesFolded($suppliesId, $unitId, (int) $qty, $warehouseId, $rollUpAllowedUnitIds)
            : [['unit_id' => $unitId, 'qty' => $qty]];

        // Sama seperti ProductUnitStock::addQty() (lihat docblock-nya): kalau stok existing di
        // $unitId ikut terlipat ke keputusan roll-up sampai negatif, tulis sebagai 3 leg terpisah
        // (masuk penuh, lalu konversi keluar/hasil) alih-alih satu delta bersih yang tidak
        // terjelaskan.
        $originIsFoldedDeduction = $rollUp && $credits !== [] && $credits[0]['unit_id'] === $unitId && $credits[0]['qty'] < 0;

        if ($originIsFoldedDeduction) {
            $naik = (int) $qty - $credits[0]['qty'];
            self::creditOneSuppliesUnit($warehouseId, $suppliesId, $unitId, (float) $qty, $logCode, $logNotes);
            self::creditOneSuppliesUnit($warehouseId, $suppliesId, $unitId, -(float) $naik, $logCode, $logNotes);
            foreach (array_slice($credits, 1) as $credit) {
                if ($credit['qty'] == 0) {
                    continue;
                }
                self::creditOneSuppliesUnit(
                    $warehouseId, $suppliesId, (int) $credit['unit_id'], (float) $credit['qty'],
                    $logCode, $logNotes, isRollUpResult: true
                );
            }
        } else {
            foreach ($credits as $credit) {
                if ($credit['qty'] == 0) {
                    continue;
                }
                self::creditOneSuppliesUnit(
                    $warehouseId,
                    $suppliesId,
                    (int) $credit['unit_id'],
                    (float) $credit['qty'],
                    $logCode,
                    $logNotes
                );
            }
        }

        return ['ok' => true];
    }

    private static function creditOneSuppliesUnit(
        int $warehouseId,
        int $suppliesId,
        int $unitId,
        float $qty,
        string $logCode,
        string $logNotes,
        bool $isRollUpResult = false
    ): void {
        $rows = SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('status', 1)
            ->where('warehouse_id', $warehouseId)
            ->where('supplies_id', $suppliesId)
            ->where('unit_id', $unitId)
            ->lockForUpdate()
            ->get();
        $row = $rows->first();

        if (! $row) {
            $row = new SuppliesStock();
            $row->warehouse_id = $warehouseId;
            $row->supplies_id = $suppliesId;
            $row->unit_id = $unitId;
            $row->ss_stock = 0;
            $row->status = 1;
            $row->created_by = Session::get('user')->staff_id ?? null;
            $row->save();
        } else {
            $row->ss_stock = (float) $rows->sum('ss_stock');
            $rows->skip(1)->each(function ($duplicate) {
                $duplicate->ss_stock = 0;
                $duplicate->save();
            });
        }

        $row->ss_stock = round((float) $row->ss_stock + $qty, 4);
        $row->save();

        // $qty bisa negatif di sini (fold roll-up, lihat caller): sejumlah itu berpindah KELUAR
        // dari satuan ini ke satuan yang lebih besar lewat panggilan roll-up yang SAMA. log_jumlah
        // tetap magnitude positif, arah dibawa log_category -- konvensi yang sama dipakai
        // ProductIssuesDetail::deleteProductIssuesDetail().
        $isOut = $qty < 0;
        $notes = $logNotes;
        if ($isOut) {
            $notes = 'Konversi unit (naik satuan otomatis) ' . $logNotes;
        } elseif ($isRollUpResult) {
            $notes = 'Hasil konversi naik satuan otomatis (' . $logNotes . ')';
        }
        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode' => $logCode,
            'log_type' => 2,
            'log_category' => $isOut ? 2 : 1,
            'log_item_id' => $suppliesId,
            'log_notes' => $notes,
            'log_jumlah' => abs($qty),
            'log_saldo' => (float) $row->ss_stock,
            'unit_id' => $unitId,
            'warehouse_id' => $warehouseId,
        ]);
    }
}
