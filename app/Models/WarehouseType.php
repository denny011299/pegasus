<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Session;

class WarehouseType extends Model
{
    protected $table = 'warehouse_types';
    protected $primaryKey = 'id';
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'warehouse_type_name',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by', 'staff_id');
    }

    public function warehouses(): HasMany
    {
        return $this->hasMany(Warehouse::class, 'warehouse_type_id');
    }

    public function getWarehouseType(array $data = [])
    {
        $data = array_merge([
            'warehouse_type_name' => null,
        ], $data);

        return self::active()
            ->with('creator:staff_id,staff_name')
            ->when($data['warehouse_type_name'], function ($q, $name) {
                $q->where('warehouse_type_name', 'like', '%' . $name . '%');
            })
            ->orderBy('created_at', 'asc')
            ->get()
            ->each(function ($row) {
                $row->created_by_name = $row->creator->staff_name ?? '-';
                unset($row->creator);
            });
    }

    public function insertWarehouseType(array $data)
    {
        if ($this->isDuplicateName($data['warehouse_type_name'] ?? null)) {
            return -2;
        }

        $row = self::create([
            'warehouse_type_name' => trim((string) $data['warehouse_type_name']),
            'status' => 1,
            'created_by' => Session::get('user')->staff_id ?? null,
        ]);

        return $row->id;
    }

    public function updateWarehouseType(array $data)
    {
        if ($this->isDuplicateName($data['warehouse_type_name'] ?? null, $data['id'] ?? null)) {
            return -2;
        }

        $row = self::active()->findOrFail($data['id']);
        $row->warehouse_type_name = trim((string) $data['warehouse_type_name']);
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        return $row->id;
    }

    public function deleteWarehouseType(array $data)
    {
        $row = self::active()->findOrFail($data['id']);
        $row->status = 0;
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        return $row->id;
    }

    public function isDuplicateName(?string $name, $excludeId = null): bool
    {
        $name = trim((string) $name);
        if ($name === '') {
            return false;
        }

        return self::active()
            ->whereRaw('LOWER(warehouse_type_name) = ?', [mb_strtolower($name)])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }
}
