<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table = "roles";
    protected $primaryKey = "role_id";
    public $timestamps = true;
    public $incrementing = true;

    function getRole($data = []){
        $data = array_merge([
            "role_name"=>null
        ], $data);

        $result = self::where('status', '=', 1)
            ->where('role_id', '>', 0);

        if (!empty($data['role_name'])) {
            $result->where('role_name', 'like', '%' . $data['role_name'] . '%');
        }

        return $result->orderBy('role_name', 'asc')->get();
    }

    function insertRole($data)
    {
        $data = array_merge([
            "role_access"=>"[]"
        ], $data);

        $t = new self();
        $t->role_name = $data["role_name"];
        $t->role_access = $data["role_access"];
        $t->save();
        return $t->role_id;
    }

    function updateRole($data)
    {
        $data = array_merge([
            "role_access"=>"[]"
        ], $data);
        $t = self::find($data["role_id"]);
        $t->role_name = $data["role_name"];
        $t->role_access = $data["role_access"];
        $t->save();
        return $t->role_id;
    }
    function updateRoleName($data)
    {
        $data = array_merge([
            "role_access"=>"[]"
        ], $data);
        $t = self::find($data["role_id"]);
        $t->role_name = $data["role_name"];
        // $t->role_access = $data["role_access"];
        $t->save();
        return $t->role_id;
    }

    function deleteRole($data)
    {
        $t = self::find($data["role_id"]);
        $t->status = 0;
        $t->save();
    }
}
