<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Bom extends Model
{
    protected $table = "boms";
    protected $primaryKey = "bom_id";
    public $timestamps = true;
    public $incrementing = true;

    function getBom($data = [])
    {

        $data = array_merge([
            "bom_id" => null,
            "search" => null,
            "product_id" => null,
            "supplies_id" => null,
            "with_details" => false,
        ], $data);
        $data['with_details'] = filter_var($data['with_details'], FILTER_VALIDATE_BOOLEAN);
        if ($data['bom_id']) {
            $data['with_details'] = true;
        }

        $result = Bom::where('boms.status', '=', 1)
          ->join('product_variants', 'product_variants.product_variant_id', '=', 'boms.product_id')
            ->join('products', 'products.product_id', '=', 'product_variants.product_id')
            ->select('boms.*');

        if ($data["product_id"]) $result->where('boms.product_id', '=', $data["product_id"]);
        if ($data["bom_id"]) $result->where('boms.bom_id', '=', $data["bom_id"]);
        if ($data["supplies_id"]) {
            $result->whereIn('boms.bom_id', function ($query) use ($data) {
                $query->select('bom_id')
                    ->from('bom_details')
                    ->where('supplies_id', '=', $data["supplies_id"])
                    ->where('status', '=', 1);
            });
        }

        if ($data['search']) {
            $s = $data['search'];
             $result->where(function ($q) use ($s) {
                $q->whereRaw("CONCAT(products.product_name, ' ', product_variants.product_variant_name) LIKE ?", ["%{$s}%"])
                ->orWhere("product_variants.product_variant_sku", "LIKE", "%{$s}%");
            });
        }

        $result->orderBy('created_at', 'asc');

        $result = $result->get();

        if ($result->isEmpty()) {
            return $result;
        }

        $variantIds = $result->pluck('product_id')->filter()->unique()->values()->all();
        $variants = ProductVariant::whereIn('product_variant_id', $variantIds)->get()->keyBy('product_variant_id');
        $products = Product::whereIn(
            'product_id',
            $variants->pluck('product_id')->filter()->unique()->values()->all()
        )->get()->keyBy('product_id');

        $unitIdSet = [];
        foreach ($result as $row) {
            if ($row->unit_id) {
                $unitIdSet[(int) $row->unit_id] = true;
            }
        }
        foreach ($products as $product) {
            foreach ((array) (json_decode($product->product_unit, true) ?: []) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
        }
        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        $staffNames = BatchLookup::staffNames($result->pluck('created_by'));

        $detailsByBom = $data['with_details']
            ? (new BomDetail())->getDetailBulk($result->pluck('bom_id')->all(), true)
            : (new BomDetail())->getDetailBulk($result->pluck('bom_id')->all(), false);

        foreach ($result as $value) {
            $v = $variants->get($value->product_id);
            $u = $v ? $products->get($v->product_id) : null;
            $value->product_sku = $v ? $v->product_variant_sku : '-';
            $value->product_variant_id = $v ? $v->product_variant_id : null;
            $value->default_unit = $u ? $u->unit_id : null;
            $defaultUnit = $u ? $unitsMap->get($u->unit_id) : null;
            $value->default_unit_name = $defaultUnit ? $defaultUnit->unit_short_name : '-';
            $value->product_name = $v && $u
                ? trim($u->product_name . ' ' . $v->product_variant_name)
                : '-';
            $value->product_variant_sku = $v ? $v->product_variant_sku : '-';
            $bomUnit = $unitsMap->get($value->unit_id);
            $value->unit_name = $bomUnit ? $bomUnit->unit_short_name : '-';
            $unitIds = $u ? (json_decode($u->product_unit, true) ?: []) : [];
            $value->pr_unit = collect($unitIds)
                ->map(fn ($id) => $unitsMap->get((int) $id))
                ->filter()
                ->values();
            $value->relasi = (new ProductRelation())->getProductRelation(['product_variant_id' => $value->product_id]);
            $details = ($detailsByBom->get($value->bom_id) ?? collect())->values();
            $value->details = $details;
            $value->items = $details;
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
        }

        return $result;
    }

    function insertBom($data)
    {
        $t = new Bom();
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bom_id;
    }

    function updateBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->product_id = $data["product_id"];
        $t->bom_qty = $data["bom_qty"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->bom_id;
    }

    function deleteBom($data)
    {
        $t = Bom::find($data["bom_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }
}
