<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Warehouse extends Model
{
    protected $table = 'warehouses';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'warehouse_name',
        'warehouse_type_id',
        'warehouse_address',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'warehouse_type_id' => 'integer',
    ];

    /** Alias agar blade/header yang pakai $wh->name tetap jalan */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['warehouse_name'] ?? null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(WarehouseType::class, 'warehouse_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    public function staffAssignments(): HasMany
    {
        return $this->hasMany(StaffWarehouse::class, 'warehouse_id', 'id');
    }

    public function staffs(): BelongsToMany
    {
        return $this->belongsToMany(
            Staff::class,
            'staff_warehouses',
            'warehouse_id',
            'staff_id'
        )->withTimestamps();
    }

    /**
     * Gudang untuk dropdown navbar / share view.
     * Hanya berdasarkan assign di pivot staff_warehouses (tanpa bypass role).
     */
    public static function availableForUser($user = null): Collection
    {
        if (!$user) {
            return collect();
        }

        $ids = Staff::assignedWarehouseIds($user);
        if ($ids === []) {
            return collect();
        }

        return self::active()
            ->with('type')
            ->whereIn('id', $ids)
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_name', 'warehouse_type_id']);
    }

    /** Semua gudang aktif (form assign staf). */
    public static function allActive(): Collection
    {
        return self::active()
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_name']);
    }

    public function getWarehouse(array $data = [])
    {
        $data = array_merge([
            'warehouse_name' => null,
            'warehouse_type_id' => null,
        ], $data);

        // Tampilkan Aktif (1) & Non-Aktif (2); status 0 = soft delete, tetap disembunyikan
        return self::query()
            ->whereIn('status', [1, 2])
            ->with([
                'type:id,warehouse_type_name',
                'creator:staff_id,staff_name',
            ])
            ->when($data['warehouse_name'], function ($q, $name) {
                $q->where('warehouse_name', 'like', '%' . $name . '%');
            })
            ->when($data['warehouse_type_id'], function ($q, $typeId) {
                $q->where('warehouse_type_id', $typeId);
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->each(function ($row) {
                $row->created_by_name = $row->creator->staff_name ?? '-';
                unset($row->creator);
            });
    }

    /**
     * Ubah status gudang: 1 = Aktif, 2 = Non-Aktif.
     */
    public function updateWarehouseStatus(array $data)
    {
        $id = (int) ($data['id'] ?? 0);
        $status = (int) ($data['status'] ?? 0);

        if ($id <= 0 || !in_array($status, [1, 2], true)) {
            return -1;
        }

        $row = self::query()
            ->whereIn('status', [1, 2])
            ->find($id);

        if (!$row) {
            return -1;
        }

        $row->status = $status;
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        // Jika dinonaktifkan, lepaskan dari session / preferensi aktif
        if ($status === 2) {
            Staff::where('last_active_warehouse_id', $row->id)
                ->update(['last_active_warehouse_id' => null]);

            if ((string) Session::get('active_warehouse_id') === (string) $row->id) {
                Session::forget('active_warehouse_id');
            }
        }

        return $row->id;
    }

    public function insertWarehouse(array $data)
    {
        if ($this->isDuplicateName($data['warehouse_name'] ?? null)) {
            return -2;
        }

        $row = self::create([
            'warehouse_name' => trim((string) $data['warehouse_name']),
            'warehouse_type_id' => $data['warehouse_type_id'],
            'warehouse_address' => $this->normalizeAddress($data['warehouse_address'] ?? null),
            'status' => 1,
            'created_by' => Session::get('user')->staff_id ?? null,
        ]);

        return $row->id;
    }

    public function updateWarehouse(array $data)
    {
        if ($this->isDuplicateName($data['warehouse_name'] ?? null, $data['id'] ?? null)) {
            return -2;
        }

        $row = self::query()->whereIn('status', [1, 2])->findOrFail($data['id']);
        $row->warehouse_name = trim((string) $data['warehouse_name']);
        $row->warehouse_type_id = $data['warehouse_type_id'];
        $row->warehouse_address = $this->normalizeAddress($data['warehouse_address'] ?? null);
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        return $row->id;
    }

    /**
     * Soft delete (status = 0). Tolak jika masih ada staf yang di-assign.
     * @return int|-3  id jika sukses, -3 jika masih ada assignment
     */
    public function deleteWarehouse(array $data)
    {
        $row = self::query()->whereIn('status', [1, 2])->findOrFail($data['id']);

        $assignedCount = StaffWarehouse::where('warehouse_id', $row->id)->count();
        if ($assignedCount > 0) {
            return -3;
        }

        $row->status = 0;
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        Staff::where('last_active_warehouse_id', $row->id)
            ->update(['last_active_warehouse_id' => null]);

        if ((string) Session::get('active_warehouse_id') === (string) $row->id) {
            Session::forget('active_warehouse_id');
        }

        return $row->id;
    }

    public function isDuplicateName(?string $name, $excludeId = null): bool
    {
        $name = trim((string) $name);
        if ($name === '') {
            return false;
        }

        return self::active()
            ->whereRaw('LOWER(warehouse_name) = ?', [mb_strtolower($name)])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    private function normalizeAddress($address): ?string
    {
        if ($address === null) {
            return null;
        }

        $address = trim((string) $address);

        return $address === '' ? null : $address;
    }
}
