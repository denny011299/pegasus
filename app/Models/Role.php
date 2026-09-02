<?php

namespace App\Models;

use App\Support\RoleIds;
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

        // $result = self::where('status', '=', 1)->where('role_id', '>=', 1);
        $result = self::where('status', '=', 1);
        if($data["role_name"]) $result->where('role_name','like','%'.$data["role_name"].'%');
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        return $result;
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
        $roleId = (int) ($data['role_id'] ?? 0);
        if ($roleId <= 0) {
            return ['status' => -1, 'message' => 'Peran tidak valid'];
        }

        $protected = [RoleIds::DIREKSI, RoleIds::DEVELOPER, RoleIds::QC_GUDANG];
        if (in_array($roleId, $protected, true)) {
            return ['status' => -1, 'message' => 'Peran sistem tidak boleh dihapus'];
        }

        $t = self::find($roleId);
        if (!$t || (int) $t->status !== 1) {
            return ['status' => -1, 'message' => 'Peran tidak ditemukan'];
        }

        $force = filter_var($data['force'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $staffCount = Staff::query()
            ->where('status', 1)
            ->where('role_id', $roleId)
            ->count();

        if ($staffCount > 0 && !$force) {
            return [
                'status' => -1,
                'need_confirmation' => true,
                'staff_count' => $staffCount,
                'message' => "Masih ada {$staffCount} pengguna yang memakai peran ini. Lepas peran dari pengguna tersebut dan hapus peran?",
            ];
        }

        \DB::transaction(function () use ($t, $roleId, $staffCount) {
            if ($staffCount > 0) {
                Staff::query()
                    ->where('status', 1)
                    ->where('role_id', $roleId)
                    ->update(['role_id' => null]);
            }

            $t->status = 0;
            $t->save();
        });

        return 1;
    }
}
