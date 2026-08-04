<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * DEPRECATED (2026-08-04): Sales Order Delivery is no longer used. insertSoDeliveryDetail()/
 * updateSoDeliveryDetail() deduct ps_stock a second time on top of what CustomerController::accSO()
 * already deducted at SO-approval time, and match ProductStock by product_id instead of
 * product_variant_id — confirmed by product owner as unused, not fixed, not tested. See
 * KNOWN_ISSUES.md.
 */
class SalesOrderDeliveryDetail extends Model
{
    protected $table = "sales_delivery_orders_details";
    protected $primaryKey = "sdod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSoDeliveryDetail($data = [])
    {
        $data = array_merge([
            "sdo_id"   => null,
            "sdod_id"   => null,
        ], $data);

        $result = SalesOrderDeliveryDetail::where("status", ">=", 1);

        if ($data["sdo_id"]) $result->where("sdo_id", "=", $data["sdo_id"]);
        if ($data["sdod_id"]) $result->where("sdod_id", "=", $data["sdod_id"]);

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        $hasUnitCol = Schema::hasColumn($this->getTable(), 'unit_id');
        foreach ($result as $value) {
            $sv = ProductVariant::find($value->product_variant_id);
            $s = $sv ? Product::find($sv->product_id) : null;
            $value->product_name = $s->product_name ?? '-';
            $value->product_variant_name = $sv->product_variant_name ?? '-';
            $value->product_variant_sku = $sv->product_variant_sku ?? ($value->sdod_sku ?? '-');
            if ($hasUnitCol && $value->unit_id) {
                $unit = Unit::find($value->unit_id);
                $value->unit_name = $unit
                    ? ($unit->unit_name ?? $unit->unit_short_name ?? '-')
                    : '-';
            } else {
                $value->unit_name = '-';
            }
        }

        return $result;
    }

    /**
     * Catatan pengiriman = rencana. Tidak memotong stok.
     * Potong stok tetap di ACC Sales Order (SalesOrderStock).
     */
    function insertSoDeliveryDetail($data)
    {
        $t = new SalesOrderDeliveryDetail();
        $t->sdo_id = $data["sdo_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->sdod_sku = $data["sdod_sku"] ?? ($data["sku"] ?? '');
        $t->sdod_qty = (int) ($data["sdod_qty"] ?? 0);
        if (Schema::hasColumn($t->getTable(), 'unit_id')) {
            $t->unit_id = ! empty($data["unit_id"]) ? (int) $data["unit_id"] : null;
        }
        $t->status = 1;
        $t->save();

        return $t->sdod_id;
    }

    function updateSoDeliveryDetail($data)
    {
        $t = SalesOrderDeliveryDetail::find($data["sdod_id"]);
        if (! $t) {
            return null;
        }

        $t->product_variant_id = $data["product_variant_id"];
        $t->sdod_sku = $data["sdod_sku"] ?? ($data["sku"] ?? $t->sdod_sku);
        $t->sdod_qty = (int) ($data["sdod_qty"] ?? $t->sdod_qty);
        if (Schema::hasColumn($t->getTable(), 'unit_id') && array_key_exists('unit_id', $data)) {
            $t->unit_id = ! empty($data["unit_id"]) ? (int) $data["unit_id"] : null;
        }
        $t->save();

        return $t->sdod_id;
    }

    function deleteSoDeliveryDetail($data)
    {
        $t = SalesOrderDeliveryDetail::find($data["sdod_id"]);
        if (! $t) {
            return;
        }
        $t->status = 0;
        $t->save();
    }
}
