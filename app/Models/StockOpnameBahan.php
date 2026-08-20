<?php

namespace App\Models;

use App\Support\RoleAccess;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class StockOpnameBahan extends Model
{
    protected $table = "stock_opname_bahans";
    protected $primaryKey = "stob_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'stob_date',
        'staff_id',
        'warehouse_id',
        'stob_notes',
        'status',
        'is_draft',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
    ];

    function getStockOpnameBahan($data = [])
    {
        $data = array_merge([
            'stob_date' => null,
            'staff_id'  => null,
            'stob_id'   => null,
            'warehouse_id' => null,
            // List tidak butuh detail item (JS hanya pakai kolom header).
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

        if ($data['stob_date']) $result->whereDate('stob_date', $data['stob_date']);
        if ($data['staff_id'])  $result->where('staff_id', $data['staff_id']);
        if ($data['stob_id'])   $result->where('stob_id', $data['stob_id']);

        // List per gudang aktif. Detail by id tidak difilter.
        if ($hasWarehouse && empty($data['stob_id'])) {
            $warehouseId = (int) ($data['warehouse_id'] ?? 0);
            if ($warehouseId <= 0) {
                $warehouseId = (int) SuppliesStock::resolveWarehouseId();
            }
            if ($warehouseId <= 0) {
                return collect();
            }
            $result->where('warehouse_id', $warehouseId);
        }

        $result->orderBy('status', 'asc')->orderBy('stob_date', 'desc');

        $result = $result->get();

        $staffIdSet = [];
        $warehouseIds = [];
        foreach ($result as $value) {
            if ($value->staff_id) {
                $staffIdSet[(int) $value->staff_id] = true;
            }
            if ($value->created_by) {
                $staffIdSet[(int) $value->created_by] = true;
            }
            if ($value->acc_by) {
                $staffIdSet[(int) $value->acc_by] = true;
            }
            if ($hasWarehouse && $value->warehouse_id) {
                $warehouseIds[(int) $value->warehouse_id] = true;
            }
        }
        $staffMap = $staffIdSet !== []
            ? Staff::whereIn('staff_id', array_keys($staffIdSet))->pluck('staff_name', 'staff_id')
            : collect();
        $warehouseMap = $warehouseIds !== []
            ? Warehouse::whereIn('id', array_keys($warehouseIds))->pluck('warehouse_name', 'id')
            : collect();

        $detailsGrouped = collect();
        if ($data['with_items']) {
            $stobIds = $result->pluck('stob_id')->unique()->filter();
            $detailsGrouped = StockOpnameDetailBahan::getDetailBulk($stobIds->toArray());
        }

        foreach ($result as $value) {
            $value->staff_name = $staffMap[$value->staff_id] ?? '-';
            if ($data['with_items']) {
                $value->item = $detailsGrouped->get($value->stob_id, collect());
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
    function insertStockOpnameBahan($data)
    {
        $t = new self();
        $t->stob_date = $data['stob_date'];
        $t->stob_code   = $this->generateStockOpnameBahanID();
        $t->staff_id = $data['staff_id'];
        $t->stob_notes = $data['stob_notes'] ?? null;
        // Mirrors StockOpname::insertStockOpname() — see KNOWN_ISSUES.md "Stock Opname's draft
        // feature is entirely non-functional".
        $t->is_draft = ! empty($data['is_draft']);
        if (Schema::hasColumn($t->getTable(), 'warehouse_id')) {
            $warehouseId = (int) SuppliesStock::resolveWarehouseId($data['warehouse_id'] ?? null);
            $t->warehouse_id = $warehouseId > 0 ? $warehouseId : null;
        }
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        return $t->stob_id;
    }

    /**
     * Update stock opname
     */
    function updateStockOpnameBahan($data)
    {
        $t = self::find($data['stob_id']);
        if (!$t) return null;

        $t->stob_date = $data['stob_date'];
        $t->staff_id = $data['staff_id'];
        $t->stob_notes = $data['stob_notes'] ?? null;
        if (array_key_exists('is_draft', $data)) {
            $t->is_draft = ! empty($data['is_draft']);
        }
        // warehouse_id sengaja tidak diubah di update — dokumen terikat gudang saat dibuat.
        $t->save();

        return $t->stob_id;
    }

    /**
     * Soft delete stock opname (set status = 0)
     */
    function deleteStockOpnameBahan($data)
    {
        $t = self::find($data['stob_id']);
        if ($t) {
            $t->status = 0;
            $t->save();
        }
    }

    function generateStockOpnameBahanID()
    {
        $id = self::max('stob_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SB" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
