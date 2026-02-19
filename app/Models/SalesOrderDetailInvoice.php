<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesOrderDetailInvoice extends Model
{
    protected $table = "sales_order_detail_invoices";
    protected $primaryKey = "soi_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'so_id',
        'soi_date',
        'soi_due',
        'soi_code',
        'soi_total',
    ];

    // === GET DATA ===
    function getSoInvoice($data = [])
    {
        $data = array_merge([
            "soi_id" => null,
            "so_id"  => null,
        ], $data);

        $result = SalesOrderDetailInvoice::where('status','>=',0);

        if ($data["soi_id"]) {
            $result->where("soi_id", "=", $data["soi_id"]);
        }

        if ($data["so_id"]) {
            $result->where("so_id", "=", $data["so_id"]);
        }

        $result->orderBy("created_at", "asc");
        $result = $result->get();
        foreach ($result as $key => $value) {
            $so = SalesOrder::find($value->so_id);
            $value->customer_name = Customer::find($so->so_customer)->customer_notes;
            $value->so_code = $so->so_number;
        }
        return $result;
    }

    // === INSERT ===
    function insertInvoiceSO($data)
    {
        $t = new SalesOrderDetailInvoice();
        $t->so_id      = $data["so_id"];
        $t->soi_date   = $data["soi_date"];
        $t->soi_due    = $data["soi_due"];
        $t->soi_code   = $this->generateInvoiceSalesOrderID();
        $t->soi_total  = $data["soi_total"];
        $t->save();

        return $t->soi_id;
    }

    // === UPDATE ===
    function updateInvoiceSO($data)
    {
        $t = SalesOrderDetailInvoice::find($data["soi_id"]);
        if (!$t) return null;

        $t->so_id      = $data["so_id"];
        $t->soi_date   = $data["soi_date"];
        $t->soi_due    = $data["soi_due"];
        $t->soi_total  = $data["soi_total"];
        $t->save();

        return $t->soi_id;
    }

    // === DELETE ===
    function deleteInvoiceSO($data)
    {
        $t = SalesOrderDetailInvoice::find($data["soi_id"]);
        if ($t) {
            $t->status=-1;
            $t->save(); // hard delete
        }
    }
    // === DELETE ===
    function changeStatusInvoiceSO($data)
    {
        $t = SalesOrderDetailInvoice::find($data["soi_id"]);
        if ($t) {
            $t->status=$data["status"];
            $t->save(); // hard delete
        }
    }
    function generateInvoiceSalesOrderID()
    {
        $id = self::max('soi_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "INV" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
