<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = "customers";
    protected $primaryKey = "cus_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCustomer($data = [])
    {
        $data = array_merge([
            "cus_name"=>null,
            "cus_id"=>null,
            "city_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["cus_name"]) $result->where('cus_name','like','%'.$data["cus_name"].'%');
        if($data["city_id"]) $result->where('city_id','=',$data["city_id"]);
        if($data["cus_id"]) $result->where('cus_id','=',$data["cus_id"]);
        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        
        foreach ($result as $key => $value) {
            $value->city_name = Cities::find($value->city_id)->name;
        }
        
        return $result;
    }

    function insertCustomer($data)
    {
        $t = new self();
        $t->cus_name = $data["cus_name"];
        $t->cus_code = $this->generateCustomerID();
        $t->cus_nomer = $data["cus_nomer"];
        $t->cus_gender = $data["cus_gender"];
        $t->cus_dob = $data["cus_dob"];
        $t->cus_address = $data["cus_address"];
        $t->city_id = $data["city_id"];
        $t->cus_notes = $data["cus_notes"];
        if(isset($data["cus_img"])) $t->cus_img = $data["cus_img"];
        $t->save();
        return $t->pu_id;
    }

    function updateCustomer($data)
    {
        $t = self::find($data["cus_id"]);
        $t->cus_name = $data["cus_name"];
        $t->cus_nomer = $data["cus_nomer"];
        $t->cus_gender = $data["cus_gender"];
        $t->cus_dob = $data["cus_dob"];
        $t->cus_address = $data["cus_address"];
        $t->city_id = $data["city_id"];
        $t->cus_notes = $data["cus_notes"];
        if(isset($data["cus_img"])) $t->cus_img = $data["cus_img"];
        $t->save();
        return $t->pu_id;
    }

    function deleteCustomer($data)
    {
        $t = self::find($data["cus_id"]);
        $t->status = 0;
        $t->save();
    }

     function generateCustomerID()
    {
        $id  = self::max('cus_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "CSO".str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
