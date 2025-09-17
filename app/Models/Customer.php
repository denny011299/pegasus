<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = "customers";
    protected $primaryKey = "customer_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCustomer($data = [])
    {
        $data = array_merge([
            "customer_name"=>null,
            "customer_id"=>null,
            "city_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["customer_name"]) $result->where('customer_name','like','%'.$data["customer_name"].'%');
        if($data["city_id"]) $result->where('city_id','=',$data["city_id"]);
        if($data["customer_id"]) $result->where('customer_id','=',$data["customer_id"]);
        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        
        foreach ($result as $key => $value) {
            $u = Cities::find($value->city_id);
            $value->city_name = $u->city_name;

            $v = Provinces::find($value->state_id);
            $value->state_name = $v->prov_name;

            $value->staff_name = Staff::find($value->sales_id)->staff_name ?? "-";
        }
        
        return $result;
    }

    function insertCustomer($data)
    {
        $t = new self();
        $t->area_id = $data["area_id"];
        $t->customer_name = $data["customer_name"];
        $t->customer_code = $this->generateCustomerID();
        $t->customer_email = $data["customer_email"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->subdistrict_id = $data["subdistrict_id"];
        $t->customer_address = $data["customer_address"];
        $t->customer_phone = $data["customer_phone"];
        $t->customer_pic = $data["customer_pic"];
        $t->customer_pic_phone = $data["customer_pic_phone"];
        $t->customer_notes = $data["customer_notes"];
        $t->sales_id = $data["sales_id"];
        $t->customer_payment = $data["customer_payment"];
        $t->save();
        return $t->customer_id;
    }

    function updateCustomer($data)
    {
        $t = self::find($data["customer_id"]);
        $t->area_id = $data["area_id"];
        $t->customer_name = $data["customer_name"];
        $t->customer_code = $data["customer_code"];
        $t->customer_email = $data["customer_email"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->subdistrict_id = $data["subdistrict_id"];
        $t->customer_address = $data["customer_address"];
        $t->customer_phone = $data["customer_phone"];
        $t->customer_pic = $data["customer_pic"];
        $t->customer_pic_phone = $data["customer_pic_phone"];
        $t->customer_notes = $data["customer_notes"];
        $t->sales_id = $data["sales_id"];
        $t->customer_payment = $data["customer_payment"];
        $t->save();
        return $t->customer_id;
    }

    function deleteCustomer($data)
    {
        $t = self::find($data["customer_id"]);
        $t->status = 0;
        $t->save();
    }

     function generateCustomerID()
    {
        $id  = self::max('customer_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "CUS".str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
