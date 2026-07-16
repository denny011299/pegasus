<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Product extends Model
{
    protected $table = "products";
    protected $primaryKey = "product_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProduct($data = [])
    {
        $data = array_merge([
            "product_name" => null,
            "category_id"  => null,
            "product_id"  => null,
        ], $data);

        $result = Product::where("status", "=", 1);

        if ($data["product_name"]) {
            $result->where("product_name", "like", "%".$data["product_name"]."%");
        }

        if ($data["category_id"]) {
            $result->where("category_id", "=", $data["category_id"]);
        }
        if ($data["product_id"]) {
            $result->where("product_id", "=", $data["product_id"]);
        }

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        if ($result->isEmpty()) {
            return $result;
        }

        $categoryIds = $result->pluck('category_id')->filter()->unique()->values()->all();
        $categories = $categoryIds !== []
            ? Category::whereIn('category_id', $categoryIds)->pluck('category_name', 'category_id')
            : collect();

        $staffNames = BatchLookup::staffNames($result->pluck('created_by'));

        $productIds = $result->pluck('product_id')->all();
        $variantsByProduct = ProductVariant::where('status', '=', 1)
            ->whereIn('product_id', $productIds)
            ->orderBy('created_at', 'asc')
            ->get()
            ->groupBy('product_id');

        $variantIds = $variantsByProduct->flatten()->pluck('product_variant_id')->filter()->unique()->values()->all();
        $relationsByVariant = collect();
        if ($variantIds !== []) {
            $relations = ProductRelation::where('status', 1)
                ->whereIn('product_variant_id', $variantIds)
                ->orderBy('created_at', 'asc')
                ->get();

            $relUnitIds = $relations->flatMap(function ($rel) {
                return [$rel->pr_unit_id_1, $rel->pr_unit_id_2];
            })->unique()->filter()->values()->all();

            $relUnitsMap = $relUnitIds !== []
                ? Unit::whereIn('unit_id', $relUnitIds)->pluck('unit_short_name', 'unit_id')
                : collect();

            foreach ($relations as $rel) {
                $rel->pr_unit_name_1 = $relUnitsMap[$rel->pr_unit_id_1] ?? '';
                $rel->pr_unit_name_2 = $relUnitsMap[$rel->pr_unit_id_2] ?? '';
            }

            $relationsByVariant = $relations->groupBy('product_variant_id');
        }

        $unitIdSet = [];
        foreach ($result as $product) {
            foreach ((array) (json_decode($product->product_unit, true) ?: []) as $unitId) {
                $unitIdSet[(int) $unitId] = true;
            }
        }
        $unitsMap = $unitIdSet !== []
            ? Unit::whereIn('unit_id', array_keys($unitIdSet))->get()->keyBy('unit_id')
            : collect();

        foreach ($result as $value) {
            $value->product_unit = json_decode($value->product_unit);
            $unitIds = (array) ($value->product_unit ?: []);
            $value->pr_unit = collect($unitIds)
                ->map(fn ($id) => $unitsMap->get((int) $id))
                ->filter()
                ->values();
            $value->pr_variant = ($variantsByProduct->get($value->product_id) ?? collect())
                ->map(function ($variant) use ($relationsByVariant) {
                    $variant->relasi = $relationsByVariant->get($variant->product_variant_id, collect())->values();
                    return $variant;
                })
                ->values();
            $value->product_category = $categories->get($value->category_id) ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
        }

        return $result;
    }

    function insertProduct($data)
    {
        $t = new Product();
        $t->product_name = $data["product_name"];
        $t->category_id  = $data["category_id"];
        $t->product_unit = $data["product_unit"];
        $t->unit_id = $data["unit_id"];
        $t->status       = $data["status"] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
       
        return $t->product_id;
    }

    function updateProduct($data)
    {
        $t = Product::find($data["product_id"]);
        $t->product_name = $data["product_name"];
        $t->category_id  = $data["category_id"];
        $t->product_unit = $data["product_unit"];
        $t->unit_id = $data["unit_id"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        
        return $t->product_id;
    }

    function deleteProduct($data)
    {
        $t = Product::find($data["product_id"]);
        $t->status = 0; // soft delete
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        ProductVariant::where("product_id", "=", $data["product_id"])->update(["status" => 0]);
        ProductStock::where("product_id", "=", $data["product_id"])->update(["status" => 0]);
    }
   
}
