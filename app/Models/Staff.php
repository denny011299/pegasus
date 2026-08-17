<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Support\RoleIds;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
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

    /**
     * Daftar sales untuk External API.
     *
     * Sales bukan tabel tersendiri, melainkan staf yang perannya bernama
     * "sales" — karena itu penyaringan dilakukan atas NAMA peran, bukan atas
     * role_id yang ditulis mati di kode.
     *
     * getStaff() sebenarnya punya penyaring role_name, tetapi tidak dipakai di
     * sini karena dua hal:
     *
     * 1. Penyaring itu memanggil Role::...->first(), jadi hanya SATU peran yang
     *    cocok yang terpakai. Selama peran yang mengandung kata "sales" hanya
     *    satu, hasilnya kebetulan benar; begitu ada peran kedua (misalnya
     *    "Supervisor Sales"), stafnya akan hilang diam-diam dari hasil. JOIN di
     *    bawah memperlakukan SEMUA peran yang cocok.
     * 2. getStaff() ikut menempelkan role_access — yaitu JSON perizinan — ke
     *    setiap baris. Itu data internal yang tidak boleh mendekati respons API.
     *
     * Satu kueri JOIN, tanpa N+1, dan hanya kolom yang aman yang diambil:
     * kata sandi, nama pengguna, dan saldo staf tidak pernah ikut terbaca.
     *
     * Mengembalikan query builder (bukan koleksi sudah dieksekusi) supaya
     * controller bisa memilih ->get() (daftar utuh) atau ->paginate() lewat
     * HandlesListQueryParams, tanpa menduplikasi penyaringan/urutan di
     * dua tempat. JOIN-nya aman dipaginasi: role_id adalah relasi banyak-ke-
     * satu ke roles, jadi tidak ada baris staf yang terlipat ganda.
     */
    function getSalesForExternalApi(string $roleNameLike = 'sales')
    {
        return self::query()
            ->join('roles', 'roles.role_id', '=', 'staffs.role_id')
            ->where('roles.role_name', 'like', '%'.$roleNameLike.'%')
            ->where('staffs.status', '=', 1)
            ->orderBy('staffs.created_at', 'asc')
            ->orderBy('staffs.staff_id', 'asc')
            ->select([
                'staffs.staff_id',
                'staffs.staff_name',
                'staffs.staff_code',
                'staffs.external_ref_id',
                'staffs.staff_email',
                'staffs.staff_phone',
                'staffs.staff_address',
                'roles.role_name',
            ]);
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
            'kepala_warehouse_ids' => $this->kepalaWarehouseIdsForStaff((int) $staff->staff_id),
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

        $this->applyStaffWarehouses($t->staff_id, $data['staff_warehouses'] ?? null);

        return $t->staff_id;
    }

    function updateStaff($data)
    {
        $staffId = (int) $data["staff_id"];
        $blocked = $this->kepalaUnassignError($staffId, $data['staff_warehouses'] ?? null);
        if ($blocked !== null) {
            return $blocked;
        }

        $t = self::find($staffId);
        $t->staff_name = $data["staff_first_name"] . " " . $data["staff_last_name"];
        $t->staff_email = $data["staff_email"];
        $t->staff_phone = $data["staff_phone"];
        $t->role_id = $data["staff_position"];
        $t->staff_address = $data["staff_address"];
        $t->staff_username = $data["staff_username"];

        // Dulu membandingkan staff_password yang dikirim (password BARU dari form) terhadap hash
        // LAMA via Hash::check(), lalu abort seluruh update (bukan cuma password) kalau tidak
        // sama — jadi mengetik password baru yang sungguhan justru selalu gagal. Password hash-nya
        // sendiri juga tidak pernah di-reassign di jalur manapun. Sekarang: kosong = password tidak
        // diubah, terisi = di-hash dan disimpan sebagai password baru (sama seperti insertStaff()).
        if (!empty($data["staff_password"])) {
            $t->staff_password = Hash::make($data["staff_password"]);
        }

        // $t->staff_notes = $data["staff_notes"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        $this->applyStaffWarehouses($t->staff_id, $data['staff_warehouses'] ?? null);

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
     * Tambah gudang yang belum ada. Baris lama tidak diubah.
     * Unassign hanya menghapus baris yang memang dilepas, dan hanya jika bukan kepala cabang.
     *
     * @param  mixed  $raw
     */
    public function applyStaffWarehouses(int $staffId, $raw): void
    {
        $wanted = $this->parseWarehouseIds($raw);
        $existing = StaffWarehouse::query()
            ->where('staff_id', $staffId)
            ->pluck('warehouse_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $toRemove = array_values(array_diff($existing, $wanted));
        if ($toRemove !== []) {
            StaffWarehouse::query()
                ->where('staff_id', $staffId)
                ->whereIn('warehouse_id', $toRemove)
                ->delete();
        }

        $this->insertMissingStaffWarehouses($staffId, array_values(array_diff($wanted, $existing)));
    }

    /**
     * @param  mixed  $raw
     * @return array{status:int,message:string}|null
     */
    public function kepalaUnassignError(int $staffId, $raw): ?array
    {
        $wanted = $this->parseWarehouseIds($raw);
        $existing = StaffWarehouse::query()
            ->where('staff_id', $staffId)
            ->pluck('warehouse_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $toRemove = array_values(array_diff($existing, $wanted));
        $blocked = $this->kepalaWarehouseIdsAmong($staffId, $toRemove);
        if ($blocked === []) {
            return null;
        }

        $names = Warehouse::query()
            ->whereIn('id', $blocked)
            ->pluck('warehouse_name')
            ->filter()
            ->implode(', ');

        return [
            'status' => -1,
            'message' => 'Tidak bisa menonaktifkan gudang'
                . ($names !== '' ? ' '.$names : '')
                . ' karena staf ini Kepala Operasional gudang tersebut.',
        ];
    }

    /**
     * @return array<int, int>
     */
    public function kepalaWarehouseIdsForStaff(int $staffId): array
    {
        return $this->kepalaWarehouseIdsAmong($staffId, null);
    }

    /**
     * @param  array<int, int>|null  $warehouseIds
     * @return array<int, int>
     */
    private function kepalaWarehouseIdsAmong(int $staffId, ?array $warehouseIds): array
    {
        if ($warehouseIds !== null && $warehouseIds === []) {
            return [];
        }
        if (! Schema::hasColumn('staff_warehouses', 'is_kepala_cabang')) {
            return [];
        }

        $query = StaffWarehouse::query()
            ->where('staff_id', $staffId)
            ->where('is_kepala_cabang', 1);
        if ($warehouseIds !== null) {
            $query->whereIn('warehouse_id', $warehouseIds);
        }

        return $query->pluck('warehouse_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @param  array<int, int>  $warehouseIds
     */
    private function insertMissingStaffWarehouses(int $staffId, array $warehouseIds): void
    {
        if ($warehouseIds === []) {
            return;
        }

        $hasKepala = Schema::hasColumn('staff_warehouses', 'is_kepala_cabang');
        $now = now();
        $rows = array_map(static function ($warehouseId) use ($staffId, $hasKepala, $now) {
            $row = [
                'staff_id' => $staffId,
                'warehouse_id' => $warehouseId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($hasKepala) {
                $row['is_kepala_cabang'] = 0;
            }

            return $row;
        }, $warehouseIds);

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

    /**
     * Staff QC gudang (role_id tetap) yang sudah di-assign ke gudang.
     *
     * @return array<int, array{id:int,text:string}>
     */
    public static function qcGudangForWarehouse(int $warehouseId): array
    {
        if ($warehouseId <= 0) {
            return [];
        }

        $assignedIds = StaffWarehouse::query()
            ->where('warehouse_id', $warehouseId)
            ->pluck('staff_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($assignedIds === []) {
            return [];
        }

        return self::query()
            ->where('status', 1)
            ->where('role_id', RoleIds::QC_GUDANG)
            ->whereIn('staff_id', $assignedIds)
            ->orderBy('staff_name')
            ->get(['staff_id', 'staff_name'])
            ->map(static fn ($row) => [
                'id' => (int) $row->staff_id,
                'text' => (string) $row->staff_name,
            ])
            ->values()
            ->all();
    }
}
