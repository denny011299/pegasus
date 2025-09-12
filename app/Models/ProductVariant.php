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
            $variant->product_name = $p ? $p->product_name : "-";
            $variant->product_unit = $u ? $u->unit_name : "-";
            $variant->product_category = Category::find($p->category_id)->category_name ?? "-";
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
        $t->product_variant_barcode = $data["variant_barcode"] ?? $t->generateBarcode();
        $t->product_variant_stock = 0;
        $t->save();

        return $t->product_variant_id;
    }

    public function updateProductVariant($data)
    {
        $t = self::find($data["product_variant_id"]);
        if (!$t) {
            throw new \Exception("Product variant with ID " . $data["product_variant_id"] . " not found.");
        }
        $t->product_id = $data["product_id"];
        $t->product_variant_name = $data["variant_name"];
        $t->product_variant_sku = $data["product_variant_sku"];
        $t->product_variant_price = $data["product_variant_price"];
        $t->product_variant_barcode = $data["product_variant_barcode"] ?? $t->generateBarcode();
        $t->product_variant_stock = $data["product_variant_stock"];
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