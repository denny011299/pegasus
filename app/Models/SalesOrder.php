<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    protected $table = "sales_orders";
    protected $primaryKey = "so_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSalesOrder($data = []){
        $data = array_merge([
            "so_number" => null,
            "so_customer" => null,
            "so_id" => null,
        ], $data);

        $result = SalesOrder::where("status", ">=", 1);

        if ($data["so_id"]) $result->where("so_id", "=", $data["so_id"]);

        if ($data["so_number"]) $result->where("so_number", "like", "%".$data["so_number"]."%");
        
        if ($data["so_customer"]) {
            $result->where("so_customer", "like", "%".$data["so_customer"]."%");
        }

        $result->orderBy("created_at", "desc");
        $result= $result->get();
        foreach ($result as $key => $value) {
            $value->customer_name = Customer::find($value->so_customer)->customer_name ?? "-";
            $value->items = (new SalesOrderDetail())->getSalesOrderDetail(["so_id"=>$value->so_id]);
            $value->staff_name = Staff::find($value->so_cashier)->staff_name ?? "-";
        }
        return $result;
    }

    function insertSalesOrder($data){
        // foreach (json_decode($data["products"], true) as $key => $value) {
        //     $m = ProductVariant::find($value["product_variant_id"]);
        //     $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$value["unit_id"])->first();
        //     if ($s->ps_stock - $value["so_qty"] > 0) {
        //         $s->ps_stock -= $value["so_qty"];
        //     } else {
        //         return -1;
        //     }
        //     $m->save();
        //     $s->save();
        // }
        $t = new SalesOrder();
        $t->so_number = $this->generateSalesOrderID();
        $t->so_customer  = $data["so_customer"];
        // $t->sales_id  = $data["sales_id"];
        $t->so_date  = $data["so_date"];
        $t->so_total  = $data["so_total"];
        $t->so_ppn  = $data["so_ppn"];
        $t->so_discount  = $data["so_discount"];
        $t->so_cost  = $data["so_cost"];
        $t->so_img  = $data["so_img"];
        $t->so_invoice_no  = $data["so_invoice_no"];
        // $t->so_payment  = $data["so_payment"];
        $t->so_cashier  = $data['sales_id'];
        $t->save();

        return $t;
    }

    function updateSalesOrder($data){
        // foreach (json_decode($data["products"], true) as $key => $value){
        //     $m = ProductVariant::find($value["product_variant_id"]);
        //     $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$value["unit_id"])->first();

        //     // ambil detail lama kalau ada
        //     $d = isset($value["sod_id"]) ? SalesOrderDetail::find($value["sod_id"]) : null;

        //     // kembalikan stok lama dulu kalau update
        //     if ($d) $s->ps_stock += $d->sod_qty;

        //     // cek stok baru
        //     if ($s->ps_stock - $value["so_qty"] < 0) return -1;
        //     $s->ps_stock -= $value["so_qty"];
        //     $s->save();

        //     // simpan detail baru / update
        //     if ($d == null){
        //         $detail = new SalesOrderDetail();
        //         $detail->so_id = $data["so_id"];
        //         $detail->product_variant_id = $value["product_variant_id"];
        //         $detail->sod_nama = $value["product_name"];
        //         $detail->sod_variant = $value["product_variant_name"];
        //         $detail->sod_sku = $value["product_variant_sku"];
        //         $detail->sod_unit = $value["unit_id"];
        //         $detail->sod_qty = $value["so_qty"];
        //         $detail->sod_harga = $value["sod_price"] ?? 0;
        //         $detail->sod_subtotal = $value["sod_subtotal"] ?? ($value["so_qty"] * $detail->sod_price);
        //         $detail->save();
        //     }
        //     $m->save();
        //     $s->save();
        // }
        $t = SalesOrder::find($data["so_id"]);
        $t->so_number = $data["so_number"];
        $t->so_customer  = $data["so_customer"];
        // $t->sales_id  = $data["sales_id"];
        $t->so_date  = $data["so_date"];
        $t->so_total  = $data["so_total"];
        $t->so_ppn  = $data["so_ppn"];
        $t->so_discount  = $data["so_discount"];
        $t->so_cost  = $data["so_cost"];
        $t->so_invoice_no  = $data["so_invoice_no"];
        // $t->so_payment  = $data["so_payment"];
        $t->so_cashier  = $data['sales_id'];
        $t->save();

        return $t;
    }

    function deleteSalesOrder($data){
        $t = SalesOrder::find($data["so_id"]);
        $t->status = 0; // soft delete
        $t->save();
    }

    function generateSalesOrderID()
    {
        $id  = self::max('so_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SO".str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
