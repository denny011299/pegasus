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
        'sidebar_menus',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'integer',
        'warehouse_type_id' => 'integer',
        'sidebar_menus' => 'array',
    ];

    /** Alias agar blade/header yang pakai $wh->name tetap jalan */
    public function getNameAttribute(): ?string
    {
        return $this->attributes['warehouse_name'] ?? null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where($this->getTable() . '.status', 1);
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
     * Hanya status aktif (1) + assign di pivot staff_warehouses (tanpa bypass role).
     * Eager-load type; urut: tipe utama → nama tipe → nama gudang (siap di-groupBy).
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

        return self::query()
            ->from('warehouses')
            ->active()
            ->whereIn('warehouses.id', $ids)
            ->whereHas('type', fn($q) => $q->where('warehouse_types.status', 1))
            ->with([
                'type' => fn($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse'),
            ])
            ->leftJoin('warehouse_types', 'warehouse_types.id', '=', 'warehouses.warehouse_type_id')
            ->orderByDesc('warehouse_types.is_main_warehouse')
            ->orderBy('warehouse_types.warehouse_type_name')
            ->orderBy('warehouses.warehouse_name')
            ->select([
                'warehouses.id',
                'warehouses.warehouse_name',
                'warehouses.warehouse_type_id',
                'warehouses.sidebar_menus',
            ])
            ->get();
    }

    /**
     * Group gudang by tipe (untuk navbar). Key = nama tipe uppercase.
     * Urutan grup mengikuti urutan query (tipe utama dulu).
     */
    public static function groupByType(Collection $warehouses): Collection
    {
        return $warehouses->groupBy(function ($wh) {
            return strtoupper(trim((string) ($wh->type->warehouse_type_name ?? 'DAFTAR GUDANG')));
        });
    }

    /**
     * Pilih default gudang: last_active → tipe utama → first.
     */
    public static function pickDefaultWarehouse(Collection $warehouses, $user = null)
    {
        if ($warehouses->isEmpty()) {
            return null;
        }

        $lastId = $user->last_active_warehouse_id ?? null;
        if ($lastId) {
            $found = $warehouses->first(fn($wh) => (int) $wh->id === (int) $lastId);
            if ($found) {
                return $found;
            }
        }

        $main = $warehouses->first(
            fn($wh) => (int) ($wh->type->is_main_warehouse ?? 0) === 1
        );

        return $main ?: $warehouses->first();
    }

    /** Semua gudang aktif (form assign staf). */
    public static function allActive(): Collection
    {
        return self::active()
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_name']);
    }

    /**
     * Daftar gudang untuk External API.
     *
     * Memakai ulang scopeActive() dan relasi type() yang sudah ada, jadi tipe
     * gudang diambil lewat satu kueri eager-load — bukan satu kueri per baris.
     *
     * Berbeda dengan getWarehouse() milik halaman admin yang menampilkan
     * status 1 dan 2 (Aktif + Non-Aktif), di sini hanya gudang Aktif yang
     * keluar: data master untuk pihak ketiga tidak sepatutnya memuat gudang
     * yang sedang dinonaktifkan.
     *
     * id ikut diurutkan terakhir supaya urutan keluaran tetap sama di setiap
     * permintaan meski ada created_at yang kembar.
     *
     * Mengembalikan query builder (bukan koleksi sudah dieksekusi) supaya
     * controller bisa memilih ->get() (daftar utuh) atau ->paginate() lewat
     * HandlesListQueryParams, tanpa menduplikasi penyaringan/urutan di
     * dua tempat.
     */
    public function getWarehouseForExternalApi()
    {
        return self::active()
            ->with('type:id,warehouse_type_name')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->select(['id', 'warehouse_name', 'warehouse_type_id', 'warehouse_address']);
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
                'type:id,warehouse_type_name,is_main_warehouse',
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
            'sidebar_menus' => $this->normalizeSidebarMenus($data['sidebar_menus'] ?? null),
            'status' => 1,
            'created_by' => Session::get('user')->staff_id ?? null,
        ]);

        // Auto-generate stok 0 untuk semua produk/varian aktif di gudang baru
        (new ProductStock())->generateStocksForWarehouse($row->id);
        // Auto-generate stok bahan mentah 0 di gudang baru
        (new SuppliesStock())->generateStocksForWarehouse($row->id);

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
        if (array_key_exists('sidebar_menus', $data)) {
            $row->sidebar_menus = $this->normalizeSidebarMenus($data['sidebar_menus']);
        }
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        return $row->id;
    }

    /**
     * null = allow all (backward compatible).
     * non-empty array = whitelist nama SubModules.
     *
     * @return array<int, string>|null
     */
    public function allowedSidebarMenus(): ?array
    {
        $menus = $this->sidebar_menus;
        if ($menus === null || $menus === []) {
            return null;
        }
        if (! is_array($menus)) {
            return null;
        }

        $clean = array_values(array_unique(array_filter(array_map(
            static fn($m) => is_string($m) ? trim($m) : '',
            $menus
        ))));

        return $clean === [] ? null : $clean;
    }

    public function allowsSidebarMenu(string $name): bool
    {
        $allowed = $this->allowedSidebarMenus();
        if ($allowed === null) {
            return true;
        }

        $needle = strtolower(trim($name));
        foreach ($allowed as $menu) {
            if (strtolower(trim((string) $menu)) === $needle) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  mixed  $raw
     * @return array<int, string>|null
     */
    protected function normalizeSidebarMenus($raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === []) {
            return null;
        }

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($raw)) {
            return null;
        }

        $clean = array_values(array_unique(array_filter(array_map(
            static fn($m) => is_string($m) ? trim($m) : '',
            $raw
        ), static fn($m) => $m !== '')));

        return $clean === [] ? null : $clean;
    }

    /**
     * Soft delete (status = 0).
     * - Assign user ke gudang ini dihapus otomatis (bukan hard block).
     * -4 = masih ada stok ps_stock > 0 → butuh force=1 (2-step konfirmasi).
     *
     * @return int|array
     */
    public function deleteWarehouse(array $data)
    {
        $row = self::query()->whereIn('status', [1, 2])->findOrFail($data['id']);
        $force = ! empty($data['force']) && in_array($data['force'], [1, '1', true, 'true'], true);

        $stockWithQty = ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $row->id)
            ->where('status', 1)
            ->where('ps_stock', '>', 0)
            ->count();

        $suppliesStockWithQty = SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $row->id)
            ->where('status', 1)
            ->where('ss_stock', '>', 0)
            ->count();

        $stockWithQty += $suppliesStockWithQty;

        if ($stockWithQty > 0 && ! $force) {
            return [
                'status' => -4,
                'count' => $stockWithQty,
                'message' => 'Apakah anda yakin ingin menghapus gudang ini? Masih terdapat stock yang terdaftar di gudang ini',
            ];
        }

        // Lepas akses user ke gudang ini
        StaffWarehouse::where('warehouse_id', $row->id)->delete();

        $row->status = 0;
        $row->created_by = Session::get('user')->staff_id ?? null;
        $row->save();

        // Nonaktifkan baris stok gudang ini (soft)
        ProductStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $row->id)
            ->where('status', 1)
            ->update([
                'status' => 0,
                'updated_at' => now(),
            ]);

        SuppliesStock::withoutGlobalScope('active_warehouse')
            ->where('warehouse_id', $row->id)
            ->where('status', 1)
            ->update([
                'status' => 0,
                'updated_at' => now(),
            ]);

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
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
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
