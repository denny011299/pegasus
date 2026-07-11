<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

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
            "customer_notes"=>null,
            "customer_id"=>null,
            "city_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        // if($data["customer_name"]) $result->where('customer_name','like','%'.$data["customer_name"].'%');
        // if($data["city_id"]) $result->where('city_id','=',$data["city_id"]);
        if($data["customer_notes"]) $result->where('customer_notes','like','%'.$data["customer_notes"].'%');
        if($data["customer_id"]) $result->where('customer_id','=',$data["customer_id"]);
        $result->orderBy('created_at', 'asc');
        $result = $result->get();

        $staffNames = BatchLookup::staffNames($result->pluck('created_by'));
        foreach ($result as $value) {
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
        }
        
        return $result;
    }

    function insertCustomer($data)
    {
        $t = new self();
        $t->customer_code = $this->generateCustomerID();
        $t->customer_pic = $data["customer_pic"];
        $t->customer_pic_phone = $data["customer_pic_phone"];
        $t->customer_notes = $data["customer_notes"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->customer_id;
    }

    function updateCustomer($data)
    {
        $t = self::find($data["customer_id"]);
        $t->customer_code = $data["customer_code"];
        $t->customer_pic = $data["customer_pic"];
        $t->customer_pic_phone = $data["customer_pic_phone"];
        $t->customer_notes = $data["customer_notes"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->customer_id;
    }

    function deleteCustomer($data)
    {
        $t = self::find($data["customer_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
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
