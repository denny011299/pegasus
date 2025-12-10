<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetailInvoice extends Model
{
    protected $table = "purchase_order_detail_invoices";
    protected $primaryKey = "poi_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'po_id',
        'poi_date',
        'poi_due',
        'poi_code',
        'poi_total',
    ];

    // === GET DATA ===
    function getPoInvoice($data = [])
    {
        $data = array_merge([
            "poi_id" => null,
            "po_id"  => null,
        ], $data);

        $result = PurchaseOrderDetailInvoice::where('status','>=',0);

        if ($data["poi_id"]) {
            $result->where("poi_id", "=", $data["poi_id"]);
        }

        if ($data["po_id"]) {
            $result->where("po_id", "=", $data["po_id"]);
        }

        $result->orderBy("created_at", "asc");
        $result = $result->get();
        foreach ($result as $key => $value) {
            $po = PurchaseOrder::find($value->po_id);
            $value->supplier_name = Supplier::find($po->po_supplier)->supplier_name;
            $value->po_code = $po->po_number;
        }
        return $result;
    }

    // === INSERT ===
    function insertInvoicePO($data)
    {
        $t = new PurchaseOrderDetailInvoice();
        $t->po_id      = $data["po_id"];
        $t->poi_date   = $data["poi_date"];
        $t->poi_due    = $data["poi_due"];
        $t->poi_code   = $this->generateInvoicePurchaseOrderID();
        $t->poi_total  = $data["poi_total"];
        $t->save();
        $this->cekInvoice($t->po_id);
        return $t->poi_id;
    }

    // === UPDATE ===
    function updateInvoicePO($data)
    {
        $t = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        if (!$t) return null;

        $t->po_id      = $data["po_id"];
        $t->poi_date   = $data["poi_date"];
        $t->poi_due    = $data["poi_due"];
        $t->poi_total  = $data["poi_total"];
        $t->save();

        $this->cekInvoice($t->po_id);
        return $t->poi_id;
    }

    // === DELETE ===
    function deleteInvoicePO($data)
    {
        $t = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        if ($t) {
            $t->status=-1;
            $t->save(); // hard delete
        }
        $this->cekInvoice($t->po_id);
    }
    // === DELETE ===
    function changeStatusInvoicePO($data)
    {
        $t = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        if ($t) {
            $t->status=$data["status"];
            $t->save(); // hard delete
        }
       $this->cekInvoice($t->po_id);
    }

    function cekInvoice($po_id) {
        
        $total = PurchaseOrderDetailInvoice::where("po_id","=",$po_id)->where("status","=",2)->sum("poi_total");
        $po = PurchaseOrder::find($po_id);
        if($total<$po->po_total){
            $po->status = 3;
            $po->save();
        }
        else{
            $po->status = 4;
            $po->save();
        }
    }

    function generateInvoicePurchaseOrderID()
    {
        $id = self::max('poi_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "INV" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
