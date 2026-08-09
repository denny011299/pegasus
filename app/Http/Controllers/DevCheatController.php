<?php

namespace App\Http\Controllers;

use App\Models\ProductStock;
use App\Models\SuppliesStock;
use App\Support\RoleAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class DevCheatController extends Controller
{
    /**
     * DEV CHEAT — isi stok gudang aktif untuk testing
     *
     * GET /dev/cheat-stock?qty=9999&type=all&mode=set
     * type: product|supplies|all  |  mode: set|add
     * Allowed: local env OR super admin (role_id = -1).
     */
    public function cheatFillStock(Request $req)
    {
        $user = Session::get('user');
        $allowed = app()->environment('local') || RoleAccess::isSuperAdmin($user);
        if (! $allowed) {
            abort(403, 'Cheat stock hanya untuk local atau super admin.');
        }

        $warehouseId = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($warehouseId <= 0) {
            return response()->json([
                'ok' => false,
                'message' => 'active_warehouse_id kosong di session',
            ], 422);
        }

        $qty = (float) ($req->query('qty', 9999));
        if ($qty < 0) {
            $qty = 0;
        }

        $type = strtolower((string) $req->query('type', 'all'));
        if (! in_array($type, ['product', 'supplies', 'all'], true)) {
            $type = 'all';
        }

        $mode = strtolower((string) $req->query('mode', 'set'));
        if (! in_array($mode, ['set', 'add'], true)) {
            $mode = 'set';
        }

        $updated = 0;

        if ($type === 'product' || $type === 'all') {
            $q = ProductStock::withoutGlobalScope('active_warehouse')
                ->where('status', 1)
                ->where('warehouse_id', $warehouseId);
            if ($mode === 'add') {
                $updated += $q->update(['ps_stock' => DB::raw('ps_stock + ' . $qty)]);
            } else {
                $updated += $q->update(['ps_stock' => $qty]);
            }
        }

        if ($type === 'supplies' || $type === 'all') {
            $q = SuppliesStock::withoutGlobalScope('active_warehouse')
                ->where('status', 1)
                ->where('warehouse_id', $warehouseId);
            if ($mode === 'add') {
                $updated += $q->update(['ss_stock' => DB::raw('ss_stock + ' . $qty)]);
            } else {
                $updated += $q->update(['ss_stock' => $qty]);
            }
        }

        return response()->json([
            'ok' => true,
            'warehouse_id' => $warehouseId,
            'updated' => (int) $updated,
            'qty' => $qty,
            'type' => $type,
            'mode' => $mode,
        ]);
    }
}
