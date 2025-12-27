<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    protected $table = "purchase_orders";
    protected $primaryKey = "po_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPurchaseOrder($data = [])
    {
        $data = array_merge([
            "po_number"   => null,
            "po_supplier"   => null,
            "po_id"   => null,
            "po_customer" => null,
            "hutang" => 0,
        ], $data);

        if ($data["hutang"] == 0) {
            $result = PurchaseOrder::where("status", "<", 2);   
        } else {
            $result = PurchaseOrder::where("status", ">=", 1)->where("pembayaran", "=", 0);
        }
        if ($data["po_supplier"]) $result->where("po_supplier", "=", $data["po_supplier"]);
        if ($data["po_number"]) $result->where("po_number", "like", "%" . $data["po_number"] . "%");
        if ($data["po_id"]) $result->where("po_id", "=", $data["po_id"]);

        $result->orderBy("po_date", "desc");
        $result = $result->get();

        foreach ($result as $key => $value) {
            $value->po_supplier_name = Supplier::find($value->po_supplier)->supplier_name;
            // kalau ada relasi ke tabel customer atau detail bisa ditambahkan disini
            // contoh:
            // $value->customer_name = Customer::find($value->po_customer)->customer_name ?? "-";
            $value->items = (new PurchaseOrderDetail())->getPurchaseOrderDetail(["po_id" => $value->po_id]);
        }

        return $result;
    }

    function insertPurchaseOrder($data)
    {
        $t = new PurchaseOrder();
        $t->po_number   = $this->generatePurchaseOrderID();
        $t->po_supplier = $data["po_supplier"];
        $t->po_date     = $data["po_date"];
        $t->po_total    = $data["po_total"];
        $t->po_ppn      = $data["po_ppn"] ?? 0;
        $t->po_discount = $data["po_discount"] ?? 0;
        $t->po_cost     = $data["po_cost"] ?? 0;
        $t->status      = 1;
        $t->save();

        return $t->po_id;
    }

    function updatePurchaseOrder($data)
    {
        $t = PurchaseOrder::find($data["po_id"]);
        $t->po_number   = $data["po_number"];
        $t->po_customer = $data["po_customer"];
        $t->po_date     = $data["po_date"];
        $t->po_total    = $data["po_total"];
        $t->po_ppn      = $data["po_ppn"] ?? 0;
        $t->po_discount = $data["po_discount"] ?? 0;
        $t->po_cost     = $data["po_cost"] ?? 0;
        $t->status      = $data["status"] ?? $t->status;
        $t->save();

        return $t->po_id;
    }

    function deletePurchaseOrder($data)
    {
        $t = PurchaseOrder::find($data["po_id"]);
        $t->status = 0; // soft delete
        $t->save();

    }
    function pelunasanPurchaseOrder($data)
    {
        $t = PurchaseOrder::find($data["po_id"]);
        $t->pembayaran = 1; // soft delete
        $t->save();

    }

    function generatePurchaseOrderID()
    {
        $id = self::max('po_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "PO" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }

    function generateTandaTerimaID($kode)
    {
        $kode = Bank::find($kode)->bank_kode;
        $id = purchase_order_tt::where('tt_kode','like','%'.$kode."%")->max('tt_kode');
        
        if (is_null($id)) $id = 0;
        $id = $id ? intval(explode('-', $id)[1]) : 0;
        $id++;
        return $kode. "-" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
