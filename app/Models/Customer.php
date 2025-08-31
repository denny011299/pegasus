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
        }
        
        return $result;
    }

    function insertCustomer($data)
    {
        $t = new self();
        $t->customer_name = $data["customer_name"];
        $t->customer_code = $this->generateCustomerID();
        $t->customer_email = $data["customer_email"];
        $t->customer_birthdate = $data["customer_birthdate"];
        $t->customer_phone = $data["customer_phone"];
        $t->customer_address = $data["customer_address"];
        $t->customer_notes = $data["customer_notes"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->customer_zipcode = $data["customer_zipcode"];
        $t->customer_bank = $data["customer_bank"];
        $t->customer_branch = $data["customer_branch"];
        $t->customer_account_name = $data["customer_account_name"];
        $t->customer_account_number = $data["customer_account_number"];
        $t->customer_ifsc = $data["customer_ifsc"];
        $t->customer_payment = $data["customer_payment"];
        if(isset($data["customer_image"])) $t->customer_image = $data["customer_image"];
        $t->save();
        return $t->pu_id;
    }

    function updateCustomer($data)
    {
        $t = self::find($data["customer_id"]);
        $t->customer_name = $data["customer_name"];
        $t->customer_email = $data["customer_email"];
        $t->customer_birthdate = $data["customer_birthdate"];
        $t->customer_phone = $data["customer_phone"];
        $t->customer_address = $data["customer_address"];
        $t->customer_notes = $data["customer_notes"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->customer_zipcode = $data["customer_zipcode"];
        $t->customer_bank = $data["customer_bank"];
        $t->customer_branch = $data["customer_branch"];
        $t->customer_account_name = $data["customer_account_name"];
        $t->customer_account_number = $data["customer_account_number"];
        $t->customer_ifsc = $data["customer_ifsc"];
        $t->customer_payment = $data["customer_payment"];
        if(isset($data["customer_image"])) $t->customer_image = $data["customer_image"];
        $t->save();
        return $t->pu_id;
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
