<?php

namespace App\Models;

use App\Support\BatchLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class Staff extends Model
{
    protected $table = "staffs";
    protected $primaryKey = "staff_id";
    public $timestamps = true;
    public $incrementing = true;

    public function warehouses(): BelongsToMany
    {
        return $this->belongsToMany(
            Warehouse::class,
            'staff_warehouses',
            'staff_id',
            'warehouse_id'
        )->withTimestamps();
    }

    function getStaff($data = [])
    {
        $data = array_merge([
            "staff_name"=>null,
            "staff_id"=>null,
            "staff_username"=>null,
            "staff_password"=>null,
            // "city_id"=>null,
            "role_id"=>null,
            "role_name" => null,
            "include_inactive" => false,
        ], $data);

        $result = self::query();
        // Detail/edit by id: boleh ambil meskipun filter lain kosong
        if (empty($data['include_inactive']) && empty($data['staff_id'])) {
            $result->where('status', '=', 1);
        } elseif (empty($data['include_inactive']) && !empty($data['staff_id'])) {
            // Form update: tetap prefer aktif, tapi jangan gagal diam-diam
            $result->where('status', '=', 1);
        }

        if($data["staff_name"]) $result->where('staff_name','like','%'.$data["staff_name"].'%');
        if($data["staff_id"]) $result->where('staff_id','=',$data["staff_id"]);
        if ($data["role_name"]){
            $roles = Role::where('role_name','like','%'.$data["role_name"].'%')->first();
            if ($roles) {
                $data['role_id'] = $roles->role_id;
            }
        }
        if($data["role_id"]) $result->where('role_id','=',$data["role_id"]);
        if($data["staff_username"] && array_key_exists('staff_password', $data) && $data['staff_password'] !== null && $data['staff_password'] !== '') {
            $staff = Staff::where('staff_username', $data["staff_username"])->first();
            if ($staff && $data["staff_password"] && Hash::check($data["staff_password"], $staff->staff_password)) {
                $result->where('staff_username','=',$data["staff_username"]);
            } else{
                return -1;
            }
        }
        $result->orderBy('created_at', 'asc');
        $result = $result->get();

        $roleIds = $result->pluck('role_id')->filter()->unique()->values()->all();
        $roles = $roleIds !== []
            ? Role::whereIn('role_id', $roleIds)->get()->keyBy(fn ($r) => (int) $r->role_id)
            : collect();

        $staffIds = $result->pluck('staff_id')->filter()->values()->all();
        $warehouseMap = [];
        if ($staffIds !== [] && \Illuminate\Support\Facades\Schema::hasTable('staff_warehouses')) {
            $warehouseMap = StaffWarehouse::query()
                ->whereIn('staff_id', $staffIds)
                ->get(['staff_id', 'warehouse_id'])
                ->groupBy('staff_id')
                ->map(fn ($rows) => $rows->pluck('warehouse_id')->map(fn ($id) => (string) $id)->values()->all())
                ->all();
        }

        $staffNames = BatchLookup::staffNames($result->pluck('created_by'));
        foreach ($result as $value) {
            $role = $roles->get((int) $value->role_id);
            if ($role) {
                $value->role_name = $role->role_name;
                $value->role_access = $role->role_access;
            } else {
                $value->role_name = $value->role_name ?? '-';
            }
            $value->created_by_name = $value->created_by
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->staff_warehouses = $warehouseMap[$value->staff_id] ?? [];
        }
        
        return $result;
    }

    /**
     * Payload aman untuk form insert/update staf (tanpa password & role_access besar).
     */
    public function getStaffFormData($staffId): ?array
    {
        $rows = $this->getStaff([
            'staff_id' => $staffId,
        ]);

        if ($rows === -1 || !$rows || $rows->isEmpty()) {
            return null;
        }

        $staff = $rows->first();

        return [
            'staff_id' => (int) $staff->staff_id,
            'staff_name' => (string) ($staff->staff_name ?? ''),
            'staff_email' => (string) ($staff->staff_email ?? ''),
            'staff_phone' => (string) ($staff->staff_phone ?? ''),
            'staff_address' => (string) ($staff->staff_address ?? ''),
            'staff_username' => (string) ($staff->staff_username ?? ''),
            'role_id' => $staff->role_id !== null ? (int) $staff->role_id : null,
            'role_name' => (string) ($staff->role_name ?? ''),
            'staff_warehouses' => $staff->staff_warehouses ?? [],
            'status' => (int) ($staff->status ?? 0),
        ];
    }

    function insertStaff($data)
    {
        $t = new self();
        $t->staff_name = $data["staff_first_name"] . " " . $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->role_id = $data["staff_position"];
        $t->staff_address = $data["staff_address"];
        $t->staff_username = $data["staff_username"];
        $t->staff_password = Hash::make($data["staff_password"]);
        // $t->staff_notes = $data["staff_notes"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        $this->syncStaffWarehouses($t->staff_id, $data['staff_warehouses'] ?? null);

        return $t->staff_id;
    }

    function updateStaff($data)
    {
        $t = self::find($data["staff_id"]);
        $t->staff_name = $data["staff_first_name"] . " " . $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->role_id = $data["staff_position"];
        $t->staff_address = $data["staff_address"];
        $t->staff_username = $data["staff_username"];

        // Password opsional: kosong = biarkan password lama
        if (!empty($data["staff_password"])) {
            $t->staff_password = Hash::make($data["staff_password"]);
        }

        // $t->staff_notes = $data["staff_notes"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        $this->syncStaffWarehouses($t->staff_id, $data['staff_warehouses'] ?? null);

        return $t->staff_id;
    }

    function deletestaff($data)
    {
        $t = self::find($data["staff_id"]);
        $t->status = 0;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
    }

    /**
     * @param  mixed  $raw  JSON string "[1,2]", array, atau null
     */
    public function syncStaffWarehouses(int $staffId, $raw): void
    {
        $ids = $this->parseWarehouseIds($raw);

        StaffWarehouse::where('staff_id', $staffId)->delete();

        if ($ids === []) {
            return;
        }

        $now = now();
        $rows = array_map(static fn ($warehouseId) => [
            'staff_id' => $staffId,
            'warehouse_id' => $warehouseId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $ids);

        StaffWarehouse::insert($rows);
    }

    public function parseWarehouseIds($raw): array
    {
        if ($raw === null || $raw === '' || $raw === 'null') {
            return [];
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $raw = $decoded;
            } else {
                $raw = array_filter(array_map('trim', explode(',', $raw)));
            }
        }

        if (!is_array($raw)) {
            $raw = [$raw];
        }

        return collect($raw)
            ->filter(fn ($id) => $id !== null && $id !== '' && $id !== 'null')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /** ID gudang yang di-assign ke staff (untuk navbar). */
    public static function assignedWarehouseIds($user): array
    {
        if (!$user || empty($user->staff_id)) {
            return [];
        }

        return StaffWarehouse::query()
            ->where('staff_id', (int) $user->staff_id)
            ->pluck('warehouse_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
