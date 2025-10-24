<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        foreach ($result as $key => $value) {
            $u = Cities::find($value->city_id);
            $value->city_name = $u->city_name;

            $v = Provinces::find($value->state_id);
            $value->state_name = $v->prov_name;
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
        $t->supplier_payment = $data["supplier_payment"];
        if(isset($data["supplier_image"])) $t->supplier_image = $data["supplier_image"];
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
        $t->supplier_payment = $data["supplier_payment"];
        if(isset($data["supplier_image"])) $t->supplier_image = $data["supplier_image"];
        $t->save();
        return $t->supplier_id;
    }

    function deleteSupplier($data)
    {
        $t = Supplier::find($data["supplier_id"]);
        $t->status = 0;
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
