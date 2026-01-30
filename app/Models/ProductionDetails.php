<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionDetails extends Model
{
    protected $table = "production_details";
    protected $primaryKey = "pd_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductionDetail($data = [])
    {
        $data = array_merge([
            "production_id" => null,
            "report" => null
        ], $data);  

        if ($data["report"] == null) $result = ProductionDetails::where('status', '>=', 1);
        else if ($data["report"]) $result = ProductionDetails::where('status', '>=', 0);
        if ($data["production_id"]) $result->where('production_id', '=', $data["production_id"]);

        $result->orderBy('created_at', 'asc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = ProductVariant::find($value->product_variant_id);
            $value->product_variant_id = $u->product_variant_id;
            $value->product_sku = $u->product_variant_sku;
            $v = Product::find($u->product_id);
            $value->product_name = $v->product_name." ".$u->product_variant_name;
            $x = Unit::find($value->unit_id);
            $value->unit_name = $x->unit_name;
        }
        return $result;
    }

    function insertProductionDetail($data)
    {
        $t = new ProductionDetails();
        $t->production_id = $data["production_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->pd_qty = $data["pd_qty"];
        $t->bom_id = $data["bom_id"];
        $t->unit_id = $data["unit_id"];
        $t->list_bahan = $data["list_bahan"];
        $t->save();
        return $t;
    }

    function updateProductionDetail($data)
    {
        $t = ProductionDetails::find($data["pd_id"]);
        $t->production_id = $data["production_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->pd_qty = $data["pd_qty"];
        $t->bom_id = $data["bom_id"];
        $t->unit_id = $data["unit_id"];
        $t->list_bahan = $data["list_bahan"];
        $t->save();
        return $t->pd_id;
    }

    function deleteProductionDetail($data)
    {
        $t = ProductionDetails::find($data["pd_id"]);
        $t->notes = $data["delete_reason"];
        $t->status = 2;
        $t->save();    
    }

    function cancelProductionDetail($data){
        $t = (new Production())->getProduction(["production_id" => $data['production_id']])->first();
        foreach ($t['items'] as $key => $detail) {
            $qty = 1;
            $b = (new Bom())->getBom([
                "bom_id" => $detail->bom_id
            ])->first();
            // Cek relasi produk (untuk dikali produksi bahan mentah)
            if ($b['unit_id'] != $detail['unit_id']){
                $pr = ProductRelation::where('product_variant_id', $detail['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('pr_id', 'desc')
                    ->get();
                foreach ($pr as $p) {
                    if ($p['pr_unit_id_2'] != $detail['unit_id']) {
                        $qty *= $p['pr_unit_value_2'];
                    }
                }
            }
            $bahan = json_decode($detail->list_bahan, true) ?? [];
            foreach ($b['items'] as $value) {
                foreach ($bahan as $bhn) {
                    if ($value->supplies_id == $bhn){
                        $s = SuppliesStock::where("supplies_id", "=", $value->supplies_id)
                            ->where("unit_id", "=", $value->unit_id)
                            ->first();
                        $s->ss_stock +=  ($value->bom_detail_qty * $detail->pd_qty * $qty);
                        $s->save();
            
                        // Catat log
                        (new LogStock())->insertLog([
                            'log_date' => now(),
                            'log_kode'    => $t->production_code,
                            'log_type'    => 2,
                            'log_category' => 1,
                            'log_item_id' => $value->supplies_id,
                            'log_notes'  => "Pengembalian stok bahan akibat pembatalan produksi",
                            'log_jumlah' => ($value->bom_detail_qty * $detail->pd_qty * $qty),
                            'unit_id'    => $value->unit_id,
                        ]);
                    }
                }
            }
        }
    }
}
