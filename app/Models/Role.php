<?php

namespace App\Models;

use App\Support\RoleAccess;
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

        $userCounts = Staff::query()
            ->where('status', 1)
            ->whereNotNull('role_id')
            ->selectRaw('role_id, count(*) as total')
            ->groupBy('role_id')
            ->pluck('total', 'role_id');

        foreach ($result as $role) {
            $role->user_count = (int) ($userCounts[$role->role_id] ?? 0);
        }

        return $result;
    }

    /**
     * Daftar pengguna aktif yang memakai suatu peran, dipakai untuk
     * mengisi tabel konfirmasi hapus peran (per-user pengalihan peran).
     */
    function getRoleUsers($roleId)
    {
        return Staff::query()
            ->where('status', 1)
            ->where('role_id', $roleId)
            ->orderBy('staff_name')
            ->get(['staff_id', 'staff_name']);
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

        $affectedStaff = Staff::query()
            ->where('status', 1)
            ->where('role_id', $roleId)
            ->get(['staff_id', 'role_id']);

        // GitHub #124: siapa yang boleh melihat/mengganti daftar pengguna terdampak
        // mengikuti akses modul "Pengguna" milik SI PENGHAPUS peran, bukan "Peran &
        // Perizinan" (yang dia jelas punya, karena sedang menghapus peran).
        $currentUser = RoleAccess::userFromSession();
        $canViewUsers = RoleAccess::can($currentUser, 'Pengguna', 'view');
        $canEditUsers = RoleAccess::can($currentUser, 'Pengguna', 'edit');

        if ($affectedStaff->count() > 0 && !array_key_exists('reassignments', $data)) {
            return [
                'status' => -1,
                'need_confirmation' => true,
                'staff_count' => $affectedStaff->count(),
                'users' => $canViewUsers ? (new self())->getRoleUsers($roleId) : [],
                'message' => "Masih ada {$affectedStaff->count()} pengguna yang memakai peran ini. Pilih peran pengganti untuk tiap pengguna (boleh dikosongkan), lalu hapus peran.",
            ];
        }

        $reassignments = $canEditUsers ? ($data['reassignments'] ?? []) : [];
        if (is_string($reassignments)) {
            $decoded = json_decode($reassignments, true);
            $reassignments = is_array($decoded) ? $decoded : [];
        }
        $validRoleIds = self::where('status', 1)->where('role_id', '!=', $roleId)->pluck('role_id')->all();

        // staff_id => new_role_id (null diperbolehkan)
        $reassignById = [];
        foreach ($reassignments as $row) {
            $staffId = (int) ($row['staff_id'] ?? 0);
            if ($staffId <= 0) continue;
            $newRoleId = $row['role_id'] ?? null;
            $newRoleId = ($newRoleId === '' || $newRoleId === null) ? null : (int) $newRoleId;
            if ($newRoleId !== null && !in_array($newRoleId, $validRoleIds, true)) {
                $newRoleId = null; // abaikan peran pengganti yang tidak valid
            }
            $reassignById[$staffId] = $newRoleId;
        }

        \DB::transaction(function () use ($t, $affectedStaff, $reassignById) {
            foreach ($affectedStaff as $staff) {
                $staff->role_id = array_key_exists($staff->staff_id, $reassignById)
                    ? $reassignById[$staff->staff_id]
                    : null;
                $staff->save();
            }

            $t->status = 0;
            $t->save();
        });

        return 1;
    }
}
