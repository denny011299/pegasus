<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuppliesVariant extends Model
{
    protected $table = "supplies_variants";
    protected $primaryKey = "supplies_variant_id";
    public $timestamps = true;
    public $incrementing = true;

    public function getSuppliesVariant($data = [])
    {
        $data = array_merge([
            "supplies_id" => null,
            "supplies_variant_sku" => null,
            "supplies_variant_barcode" => null,
            "supplies_variant_id" => null,
            "search" => null,
            "status" => 1,
        ], $data);

        $result = self::where("status", "=", 1);

        // Filter berdasarkan supplies_id
        if ($data["supplies_id"]) {
            $result->where("supplies_id", "=", $data["supplies_id"]);
        }

        // Filter berdasarkan SKU
        if ($data["supplies_variant_sku"]) {
            $result->where("supplies_variant_sku", "like", "%" . $data["supplies_variant_sku"] . "%");
        }

        // Filter berdasarkan supplies_variant_id
        if ($data["supplies_variant_id"]) {
            $result->where("supplies_variant_id", "=", $data["supplies_variant_id"]);
        }
        if ($data["search"]) {
            $result->where("supplies_variant_sku", "=", $data["search"])
                ->orWhere("supplies_variant_barcode", "=", $data["search"]);
        }

        $result->orderBy("created_at", "asc");
        $variants = $result->get();

        // Menambahkan nama produk dari relasi
        foreach ($variants as $key  =>  $variant) {
            $s = Supplies::find($variant->supplies_id);
            $variant->supplies_name = $s ? $s->supplies_name : "-";

            $ss = Supplier::find($variant->supplier_id);
            $variant->supplier_name = $ss ? $ss->supplier_name : null;

            $u =  Unit::whereIn('unit_id', json_decode($s->supplies_unit, true))->first();
            $variant->supplies_unit = $u ? $u->unit_name : "-";
            $variant->supplies_unit_id = $u ? $u->unit_id : "-";
        }

        return $variants;
    }

    public function insertSuppliesVariant($data)
    {
        $t = new self();
        $t->supplies_id = $data["supplies_id"];
        $t->supplier_id = $data["supplier_id"] ?? null;
        $t->supplies_variant_name = $data["supplies_variant_name"];
        $t->supplies_variant_sku = $data["supplies_variant_sku"];
        $t->supplies_variant_price = $data["supplies_variant_price"];
        $t->supplies_variant_barcode = $data["supplies_variant_barcode"] ?? $t->generateBarcode();
        $t->supplies_variant_stock = 0;
        $t->save();

        return $t->supplies_variant_id;
    }

    public function updateSuppliesVariant($data)
    {
        $t = self::find($data["supplies_variant_id"]);
        if (!$t) {
            return $this->insertSuppliesVariant([
                "supplies_id" => $data["supplies_id"],
                "supplies_variant_name" => $data["supplies_variant_name"],
                "supplies_variant_sku" => $data["supplies_variant_sku"],
                "supplies_variant_price" => $data["supplies_variant_price"],
            ]);
        }
        $t->supplies_id = $data["supplies_id"];
        $t->supplier_id = $data["supplier_id"] ?? null;
        $t->supplies_variant_name = $data["supplies_variant_name"];
        $t->supplies_variant_sku = $data["supplies_variant_sku"];
        $t->supplies_variant_price = $data["supplies_variant_price"];
        $t->supplies_variant_barcode = $data["supplies_variant_barcode"] ?? $t->generateBarcode();
        $t->save();

        return $t->supplies_variant_id;
    }

    public function deleteSuppliesVariant($data)
    {
        $t = self::find($data["supplies_variant_id"]);
        if (!$t) {
            throw new \Exception("Supplies variant with ID " . $data["supplies_variant_id"] . " not found.");
        }
        $t->status = 0; // soft delete
        $t->save();
    }

    function generateBarcode()
    {
        do {
            // Generate angka acak sebanyak 12 digit
            $barcode = (string) random_int(100000000000, 999999999999);
        } while (self::where('supplies_variant_barcode', $barcode)->exists());
        return $barcode;
    }
}
