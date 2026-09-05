<?php

namespace App\Models;

use App\Support\StockOpname\LegacyQtyString;
use Illuminate\Database\Eloquent\Model;

/**
 * Kembaran persis App\Models\StockOpnameLine, untuk Stock Opname BAHAN (Supplies). Satu baris per
 * satuan, angka betulan, sobl_counted_qty NULL = tidak dihitung. Lihat StockOpnameLine untuk
 * penjelasan lengkap tiap keputusan desain -- di sini cuma disebut bedanya:
 *
 *  - Identitasnya `supplies_id`, bukan product_id + product_variant_id (Supplies tidak punya
 *    konsep varian/SKU seperti Product/ProductVariant).
 *  - Payload JS mengirim satuannya di bawah key `sp_units`, bukan `units`
 *    (lihat CreateStockOpnameSupplies.js).
 *
 * Dokumen lama tetap di stock_opname_detail_bahans dan tidak pernah disentuh; pemisahnya
 * stock_opname_bahans.is_old_version.
 */
class StockOpnameBahanLine extends Model
{
    protected $table = "stock_opname_bahan_lines";
    protected $primaryKey = "sobl_id";
    public $timestamps = true;
    public $incrementing = true;

    public static function getLines($stobId)
    {
        return self::where('status', 1)
            ->where('stob_id', $stobId)
            ->orderBy('sobl_id', 'asc')
            ->get();
    }

    public static function upsertLine(array $data)
    {
        $t = self::where('stob_id', $data['stob_id'])
            ->where('supplies_id', $data['supplies_id'])
            ->where('unit_id', $data['unit_id'])
            ->first() ?? new self();

        $t->stob_id = $data['stob_id'];
        $t->supplies_id = $data['supplies_id'];
        $t->unit_id = $data['unit_id'];
        $t->sobl_counted_qty = array_key_exists('sobl_counted_qty', $data)
            ? ($data['sobl_counted_qty'] === null ? null : (int) $data['sobl_counted_qty'])
            : $t->sobl_counted_qty;
        if (array_key_exists('sobl_use_system_stock', $data)) {
            $t->sobl_use_system_stock = (bool) $data['sobl_use_system_stock'];
        }
        $t->sobl_notes = $data['sobl_notes'] ?? null;
        $t->status = 1;
        $t->save();

        return $t->sobl_id;
    }

    public static function deleteLines($stobId)
    {
        self::where('stob_id', $stobId)->update(['status' => 0]);
    }

    /**
     * Tulis baris dari payload frontend (CreateStockOpnameSupplies.js). Bentuk item-nya:
     *   ['supplies_id', 'stobd_notes', 'sp_units' => [
     *       ['unit_id' => .., 'system_qty' => .., 'real_qty' => null|int], ...
     *   ]]
     * (key-nya `sp_units`, bukan `units` -- beda dari payload Produk.)
     */
    public static function writeFromPayload($stobId, array $items): void
    {
        foreach ($items as $item) {
            if (empty($item['supplies_id'])) {
                continue;
            }

            $units = ! empty($item['sp_units']) ? $item['sp_units'] : self::unitsFromLegacyPayload($item);
            if ($units === []) {
                continue;
            }

            foreach ($units as $unit) {
                if (empty($unit['unit_id'])) {
                    continue;
                }

                $useSystem = ! empty($unit['use_system_stock']);
                self::upsertLine([
                    'stob_id' => $stobId,
                    'supplies_id' => $item['supplies_id'],
                    'unit_id' => $unit['unit_id'],
                    'sobl_counted_qty' => array_key_exists('real_qty', $unit) ? $unit['real_qty'] : null,
                    'sobl_use_system_stock' => $useSystem,
                    'sobl_notes' => $item['stobd_notes'] ?? null,
                ]);
            }
        }
    }

    /**
     * Cadangan kalau payload tidak membawa sp_units[] sama sekali -- pulihkan dari string gaya
     * lama (stobd_system/stobd_real). Lihat StockOpnameLine::unitsFromLegacyPayload() untuk
     * alasan lengkap: ada supaya kegagalannya tidak pernah senyap.
     */
    private static function unitsFromLegacyPayload(array $item): array
    {
        $system = LegacyQtyString::parse($item['stobd_system'] ?? '');
        $real = LegacyQtyString::parse($item['stobd_real'] ?? '');
        if ($system === [] && $real === []) {
            return [];
        }

        $names = array_keys($system + $real);
        $unitIds = Unit::whereIn('unit_short_name', $names)->pluck('unit_id', 'unit_short_name');

        $units = [];
        foreach ($names as $name) {
            if (! isset($unitIds[$name])) {
                continue;
            }

            $units[] = [
                'unit_id' => $unitIds[$name],
                'system_qty' => $system[$name] ?? 0,
                'real_qty' => array_key_exists($name, $real) ? $real[$name] : null,
            ];
        }

        return $units;
    }

    public function dihitung(): bool
    {
        return $this->sobl_counted_qty !== null;
    }

    public function selisih(?int $systemQty): ?int
    {
        if ($this->sobl_counted_qty === null) {
            return null;
        }

        return (int) $this->sobl_counted_qty - (int) $systemQty;
    }
}
