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
        'is_main_warehouse',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'is_main_warehouse' => 'integer',
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

    /**
     * Daftar tipe gudang untuk External API.
     *
     * Aturan bisnisnya sama dengan getWarehouseType(): hanya tipe aktif
     * (scopeActive). Bedanya, data pembuat (creator) tidak ikut diambil —
     * nama staf internal tidak boleh keluar ke pihak ketiga.
     *
     * Kolom status tetap ikut karena kontrak API memintanya secara eksplisit,
     * walaupun nilainya akan selalu 1 selama hanya baris aktif yang keluar.
     *
     * Mengembalikan query builder (bukan koleksi sudah dieksekusi) supaya
     * controller bisa memilih ->get() (daftar utuh) atau ->paginate() lewat
     * HandlesOptionalPagination, tanpa menduplikasi penyaringan/urutan di
     * dua tempat.
     */
    public function getWarehouseTypeForExternalApi()
    {
        return self::active()
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->select(['id', 'warehouse_type_name', 'status']);
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

        $isMain = $this->parseIsMain($data);
        if ($isMain === 1 && $this->hasOtherMain()) {
            return -4;
        }

        $row = self::create([
            'warehouse_type_name' => trim((string) $data['warehouse_type_name']),
            'is_main_warehouse' => $isMain,
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
        $isMain = $this->parseIsMain($data);

        if ($isMain === 1 && $this->hasOtherMain((int) $row->id)) {
            return -4;
        }

        $row->warehouse_type_name = trim((string) $data['warehouse_type_name']);
        $row->is_main_warehouse = $isMain;
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        return $row->id;
    }

    public function deleteWarehouseType(array $data)
    {
        $row = self::active()->findOrFail($data['id']);
        $force = !empty($data['force']) && in_array($data['force'], [1, '1', true, 'true'], true);

        $usedCount = Warehouse::query()
            ->whereIn('status', [1, 2])
            ->where('warehouse_type_id', $row->id)
            ->count();

        if ($usedCount > 0 && !$force) {
            return [
                'status' => -3,
                'count' => $usedCount,
                'message' => "Masih ada {$usedCount} gudang yang memakai tipe ini",
            ];
        }

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
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    private function parseIsMain(array $data): int
    {
        $v = $data['is_main_warehouse'] ?? 0;
        if (is_bool($v)) {
            return $v ? 1 : 0;
        }

        return in_array($v, [1, '1', true, 'true', 'on', 'yes'], true) ? 1 : 0;
    }

    private function hasOtherMain(?int $exceptId = null): bool
    {
        return self::active()
            ->where('is_main_warehouse', 1)
            ->when($exceptId, fn($q) => $q->where('id', '!=', $exceptId))
            ->exists();
    }
}
