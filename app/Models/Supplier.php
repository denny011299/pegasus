<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Supplier extends Model
{
    protected $table = "suppliers";
    protected $primaryKey = "supplier_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSupplier($data = []){
        $data = array_merge([
            "supplier_id"=>null,
            "supplier_name"=>null,
            "supplier_code"=>null,
            "state_id"=>null,
            "city_id"=>null,
        ], $data);

        $result = Supplier::where('status', '=', 1);
        if($data["supplier_id"]) $result->where('supplier_id', '=', $data["supplier_id"]);
        if($data["supplier_name"]) $result->where('supplier_name','like','%'.$data["supplier_name"].'%');
        if($data["supplier_code"]) $result->where('supplier_code','like','%'.$data["supplier_code"].'%');
        if($data["state_id"]) $result->where('state_id', '=', $data["state_id"]);
        if($data["city_id"]) $result->where('city_id', '=', $data["city_id"]);
        $result->orderBy('created_at', 'asc');
        
        $result = $result->get();

        if ($result->isEmpty()) {
            return $result;
        }

        $cityIds = $result->pluck('city_id')->filter()->unique()->values()->all();
        $stateIds = $result->pluck('state_id')->filter()->unique()->values()->all();
        $bankIds = $result->pluck('bank_id')->filter()->unique()->values()->all();
        $supplierIds = $result->pluck('supplier_id')->all();

        $cities = $cityIds !== []
            ? Cities::whereIn('city_id', $cityIds)->get()->keyBy('city_id')
            : collect();
        $provinces = $stateIds !== []
            ? Provinces::whereIn('prov_id', $stateIds)->get()->keyBy('prov_id')
            : collect();
        $banks = $bankIds !== []
            ? Bank::whereIn('bank_id', $bankIds)->get()->keyBy('bank_id')
            : collect();

        $payments = PurchaseOrder::whereIn('po_supplier', $supplierIds)
            ->where('pembayaran', 1)
            ->where('status', 2)
            ->selectRaw('po_supplier, SUM(po_total) as payment')
            ->groupBy('po_supplier')
            ->pluck('payment', 'po_supplier');

        $posBySupplier = PurchaseOrder::whereIn('po_supplier', $supplierIds)
            ->where('pembayaran', 1)
            ->where('status', 2)
            ->get()
            ->groupBy('po_supplier');

        $staffNames = BatchLookup::staffNames($result->pluck('created_by'));

        foreach ($result as $value) {
            $city = $cities->get($value->city_id);
            $value->city_name = $city ? $city->city_name : null;

            $province = $provinces->get($value->state_id);
            $value->state_name = $province ? $province->prov_name : null;

            $bank = $banks->get($value->bank_id);
            $value->bank_name = $bank ? $bank->bank_kode : '-';

            $value->payment = (float) ($payments->get($value->supplier_id) ?? 0);
            $value->po = $posBySupplier->get($value->supplier_id, collect())->values();
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
        }

        return $result;
    }

    function insertSupplier($data)
    {
        $t = new Supplier();
        $t->supplier_name = $data["supplier_name"];
        $t->supplier_code = $this->generateSupplierID();
        $t->supplier_email = null;
        $t->supplier_phone = $data["supplier_phone"];
        $t->supplier_pic = $data["supplier_pic"];
        $t->supplier_address = $data["supplier_address"];
        $t->supplier_notes = $data["supplier_notes"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->supplier_bank = $data["supplier_bank"];
        $t->supplier_account_name = $data["supplier_account_name"];
        $t->supplier_account_number = $data["supplier_account_number"];
        $t->supplier_top = $data["supplier_top"];
        $t->bank_id = $data["bank_id"];
        $t->supplier_payment = $data["supplier_payment"];
        if(isset($data["supplier_image"])) $t->supplier_image = $data["supplier_image"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->supplier_id;
    }

    function updateSupplier($data)
    {
        $t = Supplier::find($data["supplier_id"]);
        $t->supplier_name = $data["supplier_name"];
        $t->supplier_email = null;
        $t->supplier_phone = $data["supplier_phone"];
        $t->supplier_pic = $data["supplier_pic"];
        $t->supplier_address = $data["supplier_address"];
        $t->supplier_notes = $data["supplier_notes"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->supplier_bank = $data["supplier_bank"];
        $t->supplier_account_name = $data["supplier_account_name"];
        $t->supplier_account_number = $data["supplier_account_number"];
        $t->supplier_top = $data["supplier_top"];
        $t->bank_id = $data["bank_id"];
        $t->supplier_payment = $data["supplier_payment"];
        if(isset($data["supplier_image"])) $t->supplier_image = $data["supplier_image"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->supplier_id;
    }

    function deleteSupplier($data)
    {
        $t = Supplier::find($data["supplier_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }

    function generateSupplierID()
    {
        $id  = self::max('supplier_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SUP".str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
