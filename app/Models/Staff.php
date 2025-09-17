<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    protected $table = "staffs";
    protected $primaryKey = "staff_id";
    public $timestamps = true;
    public $incrementing = true;

    function getStaff($data = [])
    {
        $data = array_merge([
            "staff_name"=>null,
            "staff_id"=>null,
            "city_id"=>null,
            "role_id"=>null
        ], $data);

        $result = self::where('status', '=', 1);
        if($data["staff_name"]) $result->where('staff_name','like','%'.$data["staff_name"].'%');
        if($data["city_id"]) $result->where('city_id','=',$data["city_id"]);
        if($data["staff_id"]) $result->where('staff_id','=',$data["staff_id"]);
        if($data["role_id"]) $result->where('role_id','=',$data["role_id"]);
        $result->orderBy('created_at', 'asc');
        $result = $result->get();
        
        foreach ($result as $key => $value) {
            // $u = Cities::find($value->city_id);
            // $value->city_name = $u->city_name;

            // $v = Provinces::find($value->state_id);
            // $value->state_name = $v->prov_name;
        }
        
        return $result;
    }

    function insertStaff($data)
    {
        $t = new self();
        $t->staff_first_name = $data["staff_first_name"];
        $t->staff_last_name = $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->staff_birthdate = $data["staff_birthdate"];
        $t->staff_gender = $data["staff_gender"];
        $t->staff_join_date = $data["staff_join_date"];
        $t->staff_shift = $data["staff_shift"];
        $t->staff_departement = $data["staff_departement"];
        $t->staff_position = $data["staff_position"];
        $t->staff_emergency1 = $data["staff_emergency1"];
        $t->staff_address = $data["staff_address"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->staff_zipcode = $data["staff_zipcode"];
        $t->staff_password = bcrypt($data["staff_password"]);
        if(isset($data["staff_image"])) $t->staff_image = $data["staff_image"];
        $t->save();
        return $t->pu_id;
    }

    function updateStaff($data)
    {
        $t = self::find($data["staff_id"]);
        $t->staff_first_name = $data["staff_first_name"];
        $t->staff_last_name = $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->staff_birthdate = $data["staff_birthdate"];
        $t->staff_gender = $data["staff_gender"];
        $t->staff_join_date = $data["staff_join_date"];
        $t->staff_shift = $data["staff_shift"];
        $t->staff_departement = $data["staff_departement"];
        $t->staff_position = $data["staff_position"];
        $t->staff_emergency1 = $data["staff_emergency1"];
        $t->staff_address = $data["staff_address"];
        $t->state_id = $data["state_id"];
        $t->city_id = $data["city_id"];
        $t->staff_zipcode = $data["staff_zipcode"];
        $t->staff_password = bcrypt($data["staff_password"]);
        if(isset($data["staff_image"])) $t->staff_image = $data["staff_image"];
        $t->save();
        return $t->pu_id;
    }

    function deletestaff($data)
    {
        $t = self::find($data["staff_id"]);
        $t->status = 0;
        $t->save();
    }
}
