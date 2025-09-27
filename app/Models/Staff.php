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

            $r = Role::find($value->role_id);
            $value->role_name = $r->role_name;
        }
        
        return $result;
    }

    function insertStaff($data)
    {
        $t = new self();
        $t->staff_name = $data["staff_first_name"] . " " . $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->role_id = $data["role_id"];
        $t->staff_address = $data["staff_address"];
        $t->staff_notes = $data["staff_notes"];
        $t->save();
        return $t->pu_id;
    }

    function updateStaff($data)
    {
        $t = self::find($data["staff_id"]);
        $t->staff_name = $data["staff_first_name"] . " " . $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->role_id = $data["role_id"];
        $t->staff_address = $data["staff_address"];
        $t->staff_notes = $data["staff_notes"];
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
