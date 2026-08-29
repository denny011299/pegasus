<?php

namespace App\Models;

use App\Support\RoleAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class StockOpname extends Model
{
    protected $table = "stock_opnames";
    protected $primaryKey = "sto_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'sto_date',
        'staff_id',
        'category_id',
        'warehouse_id',
        'sto_notes',
        'status',
        'is_draft',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
    ];

    /**
     * Get list of stock opname
     */
    function getStockOpname($data = [])
    {
        $data = array_merge([
            'sto_date' => null,
            'staff_id' => null,
            'category_id' => null,
            'sto_id' => null,
            'warehouse_id' => null,
            // List tidak butuh detail item (JS hanya pakai kolom header).
            // Set true hanya jika pemanggil benar-benar butuh $value->item.
            'with_items' => false,
        ], $data);

        $hasWarehouse = Schema::hasColumn($this->getTable(), 'warehouse_id');

        $result = self::where('status', '>=', 1);

        // Draft cuma boleh terlihat oleh staff yang membuatnya (atau super
        // admin) — begitu diajukan (is_draft=false) baru masuk alur approval
        // biasa dan terlihat semua orang seperti sebelumnya.
        $user = Session::get('user');
        if (! RoleAccess::isSuperAdmin($user)) {
            $myStaffId = (int) ($user->staff_id ?? 0);
            $result->where(function ($q) use ($myStaffId) {
                $q->where('is_draft', false)->orWhere('created_by', $myStaffId);
            });
        }

        if ($data['sto_date']) {
            $result->whereDate('sto_date', $data['sto_date']);
        }

        if ($data['staff_id']) {
            $result->where('staff_id', $data['staff_id']);
        }

        if ($data['sto_id']) {
            $result->where('sto_id', '=', $data['sto_id']);
        }

        // List per gudang aktif. Detail by id tidak difilter (buka URL langsung tetap jalan).
        if ($hasWarehouse && empty($data['sto_id'])) {
            $warehouseId = (int) ($data['warehouse_id'] ?? 0);
            if ($warehouseId <= 0) {
                $warehouseId = (int) ProductStock::resolveWarehouseId();
            }
            if ($warehouseId <= 0) {
                return collect();
            }
            $result->where('warehouse_id', $warehouseId);
        }

        $result->orderBy('status', 'asc')->orderBy('sto_date', 'desc');

        $result = $result->get();

        $staffIds = [];
        $warehouseIds = [];
        foreach ($result as $value) {
            if ($value->staff_id) {
                $staffIds[(int) $value->staff_id] = true;
            }
            if ($value->created_by) {
                $staffIds[(int) $value->created_by] = true;
            }
            if ($value->acc_by) {
                $staffIds[(int) $value->acc_by] = true;
            }
            if ($hasWarehouse && $value->warehouse_id) {
                $warehouseIds[(int) $value->warehouse_id] = true;
            }
        }
        $staffMap = [];
        if (count($staffIds) > 0) {
            $staffMap = Staff::whereIn('staff_id', array_keys($staffIds))
                ->pluck('staff_name', 'staff_id')
                ->toArray();
        }
        $warehouseMap = [];
        if ($warehouseIds !== []) {
            $warehouseMap = Warehouse::whereIn('id', array_keys($warehouseIds))
                ->pluck('warehouse_name', 'id')
                ->toArray();
        }

        $detailsGrouped = collect();
        if ($data['with_items']) {
            $stoIds = $result->pluck('sto_id')->unique()->filter()->values()->all();
            $detailsGrouped = StockOpnameDetail::getDetailBulk($stoIds);
        }

        foreach ($result as $value) {
            $value->staff_name = $staffMap[$value->staff_id] ?? '-';
            if ($data['with_items']) {
                $value->item = $detailsGrouped->get($value->sto_id, collect())->values();
            } else {
                $value->item = [];
            }
            $staffMain = $value->staff_name ?? '-';
            $value->created_by_name = $value->created_by
                ? ($staffMap[$value->created_by] ?? $staffMain)
                : $staffMain;
            $value->acc_by_name = $value->acc_by ? ($staffMap[$value->acc_by] ?? '-') : '-';
            if ($hasWarehouse) {
                $wid = (int) ($value->warehouse_id ?? 0);
                $value->warehouse_name = $wid > 0 ? ($warehouseMap[$wid] ?? '-') : '-';
            }
        }

        return $result;
    }

    /**
     * Insert new stock opname
     */
    function insertStockOpname($data)
    {
        $t = new self();
        $t->sto_date = $data['sto_date'];
        $t->sto_code = $this->generateStockOpnameID();
        $t->staff_id = $data['staff_id'];
        $t->category_id = $data['category_id'];
        $t->sto_notes = $data['sto_notes'] ?? null;
        // Ditambahkan (2026-08-05): kolom is_draft sudah ada di DB sejak 2026-07-31 (lihat
        // KNOWN_ISSUES.md "Stock Opname's draft feature is entirely non-functional") tapi tidak
        // pernah diisi di sini — frontend (CreateStockOpname.js) sudah selalu mengirim ini,
        // backend-nya yang belum menyimpannya.
        $t->is_draft = ! empty($data['is_draft']);
        if (Schema::hasColumn($t->getTable(), 'warehouse_id')) {
            $warehouseId = (int) ProductStock::resolveWarehouseId($data['warehouse_id'] ?? null);
            $t->warehouse_id = $warehouseId > 0 ? $warehouseId : null;
        }
        // Rancang ulang 2026-08-27: dokumen BARU selalu versi baru (stock_opname_lines).
        // Ditulis EKSPLISIT, tidak boleh mengandalkan default kolom -- default-nya sengaja true
        // supaya migrasinya murni ADD COLUMN untuk dokumen lama, jadi diam di sini berarti
        // dokumen baru salah dilabeli sebagai dokumen lama dan tampil kosong.
        $t->is_old_version = false;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->sto_id;
    }

    /**
     * Update stock opname
     */
    function updateStockOpname($data)
    {
        $t = self::find($data['sto_id']);
        if (!$t) {
            return null;
        }

        $t->sto_date = $data['sto_date'];
        $t->staff_id = $data['staff_id'];
        $t->category_id = $data['category_id'];
        $t->sto_notes = $data['sto_notes'] ?? null;
        // Sama seperti insertStockOpname(): kalau request ini tidak membawa is_draft sama sekali,
        // pertahankan nilai yang sudah ada (jangan diam-diam keluar dari draft/masuk ke draft).
        if (array_key_exists('is_draft', $data)) {
            $t->is_draft = ! empty($data['is_draft']);
        }
        // warehouse_id sengaja tidak diubah di update — dokumen terikat gudang saat dibuat.
        $t->save();

        return $t->sto_id;
    }

    /**
     * Soft delete stock opname (set status = 0)
     */
    function deleteStockOpname($data)
    {
        $t = self::find($data['sto_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

    function generateStockOpnameID()
    {
        $id = self::max('sto_id');
        if (is_null($id)) {
            $id = 0;
        }
        $id++;

        return "SP" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
