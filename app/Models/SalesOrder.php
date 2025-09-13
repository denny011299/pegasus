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
        ], $data);

        $result = SalesOrder::where("status", ">=", 1);

        if ($data["so_customer"]) {
            $result->where("so_customer", "like", "%".$data["so_customer"]."%");
        }

        $result->orderBy("created_at", "asc");
        $result= $result->get();
        foreach ($result as $key => $value) {
            $value->customer_name = Customer::find($value->so_customer)->customer_name ?? "-";
            $value->items = (new SalesOrderDetail())->getSalesOrderDetail(["so_id"=>$value->so_id]);
        }
        return $result;
    }

    function insertSalesOrder($data){
        $t = new SalesOrder();
        $t->so_number = $this->generateSalesOrderID();
        $t->so_customer  = $data["so_customer"];
        $t->so_date  = $data["so_date"];
        $t->so_total  = $data["so_total"];
        $t->so_ppn  = $data["so_ppn"];
        $t->so_discount  = $data["so_discount"];
        $t->so_cost  = $data["so_cost"];
        $t->so_cashier  = 1;
        $t->save();

        return $t->so_id;
    }

    function updateSalesOrder($data){
        $t = SalesOrder::find($data["so_id"]);
        $t->so_number = $data["so_number"];
        $t->so_customer  = $data["so_customer"];
        $t->so_date  = $data["so_date"];
        $t->so_total  = $data["so_total"];
        $t->so_ppn  = $data["so_ppn"];
        $t->so_discount  = $data["so_discount"];
        $t->so_cost  = $data["so_cost"];
        $t->so_cashier  = 1;
        $t->save();

        return $t->product_id;
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
