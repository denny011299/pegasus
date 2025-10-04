<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = "product_variants";
    protected $primaryKey = "product_variant_id";
    public $timestamps = true;
    public $incrementing = true;

    public function getProductVariant($data = [])
    {
        $data = array_merge([
            "product_id" => null,
            "product_variant_sku" => null,
            "product_variant_id" => null,
            "status" => 1,
            "search_product" => null,
        ], $data);

        $result = self::where("status", "=", 1);


        // Filter berdasarkan product_id
        if ($data["product_id"]) {
            $result->where("product_id", "=", $data["product_id"]);
        }

        // Filter berdasarkan SKU
        if ($data["product_variant_sku"]) {
            $result->where("product_variant_sku", "like", "%" . $data["product_variant_sku"] . "%");
        }

        if ($data["search_product"]){
            $result->where("product_variant_sku", "like", "%" . $data["search_product"])
            ->orwhere("product_variant_barcode", "=", $data["search_product"]);
        }

        // Filter berdasarkan product_variant_id
        if ($data["product_variant_id"]) {
            $result->where("product_variant_id", "=", $data["product_variant_id"]);
        }

        $result->orderBy("created_at", "asc");
        $variants = $result->get();
        
        // Menambahkan nama produk dari relasi
        foreach ($variants as $variant) {
            $p = Product::find($variant->product_id);
            $u =  Unit::whereIn('unit_id', json_decode($p->product_unit,true))->first();

            $variant->pr_name = $p ? $p->product_name : "-";
            $variant->product_unit = $u ? $u->unit_name : "-";
            $variant->product_category = Category::find($p->category_id)->category_name ?? "-";
            $variant->pr_unit = Unit::whereIn('unit_id', json_decode($p->product_unit,true))->get();
            $variant->relasi = ProductRelation::where('product_variant_id', '=', $variant->product_variant_id)->get();

            // Get nama unit default
            $v = Unit::find($p->unit_id);
            $variant->unit_id = $v->unit_id;
            $variant->unit_name = $v->unit_name;

            // Get stock
            $s = ProductStock::where('product_variant_id', $variant->product_variant_id)
                ->where('unit_id', $variant->unit_id)
                ->first();
            $variant->ps_stock = $s ? $s->ps_stock : 0;
        }

        return $variants;
    }

    public function insertProductVariant($data)
    {
        $t = new self();
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["variant_sku"];
        $t->product_variant_price = $data["variant_price"];
        $t->product_variant_barcode = $data["variant_barcode"]!="" ? $data["variant_barcode"] : $t->generateBarcode();
        $t->product_variant_alert = $data["variant_alert"];
        $t->product_variant_stock = 0;
        $t->save();
        return $t->product_variant_id;
    }

    public function updateProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            return $this->insertProductVariant([
                "product_id"      => $data["product_id"],
                "variant_name"    => $data["variant_name"],
                "variant_sku"     => $data["variant_sku"],
                "variant_price"   => $data["variant_price"],
                "variant_alert"   => $data["variant_alert"],
                "variant_barcode" => $data["variant_barcode"] ?? "",
            ]);
        }
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["variant_sku"];
        $t->product_variant_price = $data["variant_price"];
        $t->product_variant_barcode =  $data["variant_barcode"]!="" ? $data["variant_barcode"] : $t->generateBarcode();
        $t->product_variant_alert = $data["variant_alert"];
        $t->save();

        return $t->product_variant_id;
    }

    public function deleteProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            throw new \Exception("Product variant with ID " . $data["product_variant_id"] . " not found.");
        }
        $t->status = 0; // soft delete
        $t->save();
    }
     function generateBarcode() {
       do {
        // Generate angka acak sebanyak 12 digit
            $barcode = (string) random_int(100000000000, 999999999999);
        } while (self::where('product_variant_barcode', $barcode)->exists());
        return $barcode;
    }
}