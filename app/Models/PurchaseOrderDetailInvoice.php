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
            "bank_id"  => null,
            "status"  => null,
            "po_supplier"  => null,
            "dates" => null,
        ], $data);

        $result = PurchaseOrderDetailInvoice::where('purchase_order_detail_invoices.status','>=',0);
        $result->join('purchase_orders','purchase_orders.po_id','=','purchase_order_detail_invoices.po_id');
        $result->where('purchase_orders.status','>=',-1)->where('purchase_orders.status', '!=', 0);
        
        if ($data["poi_id"]) {
            $result->where("purchase_order_detail_invoices.poi_id", "=", $data["poi_id"]);
        }

        if ($data["po_id"]) {
            $result->where("purchase_order_detail_invoices.po_id", "=", $data["po_id"]);
        }
        
        if ($data["bank_id"]) {
            $result->where("purchase_order_detail_invoices.bank_id", "=", $data["bank_id"]);
        }
        
        if ($data["status"]) {
            if ($data["status"] == 5){
                $result->where('purchase_orders.status', -1);
            } else{
                $result->where("purchase_orders.pembayaran", "=", $data["status"])->where("purchase_orders.status", '>', 0);
            }
        }
        
        if ($data["po_supplier"]) {
            $result->where("purchase_orders.po_supplier", "=", $data["po_supplier"]);
        }

        if ($data["dates"]) {
            if (is_array($data["dates"]) && count($data["dates"]) === 2) {
                $startDate = \Carbon\Carbon::parse($data["dates"][0])->startOfDay();
                $endDate   = \Carbon\Carbon::parse($data["dates"][1])->endOfDay();

                $result->whereDate('purchase_order_detail_invoices.poi_date', '>=', $startDate->toDateString())
                        ->whereDate('purchase_order_detail_invoices.poi_date', '<=', $endDate->toDateString());
            } else {
                $date = \Carbon\Carbon::parse($data["dates"])->toDateString();
                $result->whereDate('purchase_order_detail_invoices.poi_date', $date);
            }
        }

        $result->select('purchase_order_detail_invoices.*');
        $result->orderByRaw('FIELD(purchase_orders.status, 1, 2, 3, -1)')->orderByRaw('FIELD(purchase_orders.pembayaran, 1, 3, 2)')->orderBy("purchase_order_detail_invoices.poi_due", "asc");
        $result = $result->get();
        
        foreach ($result as $key => $value) {
            $po = PurchaseOrder::find($value->po_id);
            $value->supplier_name = Supplier::find($po->po_supplier)->supplier_name;
            $value->po_code = $po->po_number;
            $value->pembayaran = $po->pembayaran;

            $b = Bank::find($value->bank_id);
            $value->bank_kode = $b->bank_kode??"-";
        }
        return $result;
    }

    // === INSERT ===
    function insertInvoicePO($data)
    {
        $t = new PurchaseOrderDetailInvoice();
        $t->po_id      = $data["po_id"];
        $t->poi_date   = $data["poi_date"]??date("Y-m-d");
        $t->poi_due    = $data["poi_due"];
        $t->poi_code   = $this->generateInvoicePurchaseOrderID();
        $t->poi_total  = $data["poi_total"];
        $t->status  = $data["status"]??1;
        $t->bank_id  = $data["bank_id"]??null;
        $t->save();
        //$this->cekInvoice($t->po_id);
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
