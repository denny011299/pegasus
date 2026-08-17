<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Models\Staff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class WarehouseController extends Controller
{
    // =========================
    // Warehouse / Gudang
    // =========================

    public function Warehouse()
    {
        return view('Backoffice.Warehouse.Warehouse');
    }

    public function getWarehouse(Request $req)
    {
        $data = (new Warehouse())->getWarehouse([
            'warehouse_name' => $req->warehouse_name,
            'warehouse_type_id' => $req->warehouse_type_id,
        ]);

        return response()->json($data);
    }

    /** List gudang non-utama (eceran/toko), filter by is_main_warehouse=0 */
    public function getRetailWarehouse(Request $req)
    {
        $data = Warehouse::query()
            ->active()
            ->with(['type' => fn ($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 0))
            ->when(
                trim((string) ($req->warehouse_name ?? '')) !== '',
                fn ($q) => $q->where('warehouse_name', 'like', '%' . trim($req->warehouse_name) . '%')
            )
            ->orderBy('warehouse_name')
            ->get(['id', 'warehouse_name', 'warehouse_type_id', 'warehouse_address', 'status']);

        return response()->json($data);
    }

    public function insertWarehouse(Request $req)
    {
        $payload = $req->only([
            'warehouse_name',
            'warehouse_type_id',
            'warehouse_address',
            'sidebar_menus',
        ]);
        if ($req->exists('kepala_staff_id')) {
            $payload['kepala_staff_id'] = $req->kepala_staff_id;
        }

        $result = (new Warehouse())->insertWarehouse($payload);

        return response()->json($result);
    }

    public function updateWarehouse(Request $req)
    {
        $payload = $req->only([
            'id',
            'warehouse_name',
            'warehouse_type_id',
            'warehouse_address',
            'sidebar_menus',
        ]);
        if ($req->exists('kepala_staff_id')) {
            $payload['kepala_staff_id'] = $req->kepala_staff_id;
        }

        $result = (new Warehouse())->updateWarehouse($payload);

        return response()->json($result);
    }

    public function updateWarehouseStatus(Request $req)
    {
        $result = (new Warehouse())->updateWarehouseStatus([
            'id' => $req->id,
            'status' => $req->status,
        ]);

        return response()->json($result);
    }

    public function deleteWarehouse(Request $req)
    {
        $result = (new Warehouse())->deleteWarehouse($req->all());

        // -4 = masih ada stok (butuh force=1)
        if (is_array($result) && isset($result['status']) && (int) $result['status'] === -4) {
            return response()->json($result);
        }

        return response()->json($result);
    }

    // =========================
    // Warehouse Type / Tipe Gudang
    // =========================

    public function WarehouseType()
    {
        return view('Backoffice.Warehouse.WarehouseType');
    }

    public function getWarehouseType(Request $req)
    {
        $data = (new WarehouseType())->getWarehouseType([
            'warehouse_type_name' => $req->warehouse_type_name,
        ]);

        return response()->json($data);
    }

    public function insertWarehouseType(Request $req)
    {
        $result = (new WarehouseType())->insertWarehouseType($req->all());

        return response()->json($result);
    }

    public function updateWarehouseType(Request $req)
    {
        $result = (new WarehouseType())->updateWarehouseType($req->all());

        return response()->json($result);
    }

    public function deleteWarehouseType(Request $req)
    {
        $result = (new WarehouseType())->deleteWarehouseType($req->all());

        if (is_array($result) && (int) ($result['status'] ?? 0) === -3) {
            return response()->json($result);
        }

        return response()->json($result);
    }

    // =========================
    // Active Warehouse (Navbar)
    // =========================

    public function setActiveWarehouse(Request $request)
    {
        $user = Session::get('user');
        $id = $request->input('warehouse_id', $request->input('id'));

        if ($id === null || $id === '') {
            Session::forget('active_warehouse_id');
            $this->persistLastActiveWarehouse($user, null);

            return response()->json([
                'status' => 'success',
                'success' => true,
                'active_warehouse_id' => null,
            ]);
        }

        $warehouse = Warehouse::active()->find($id);
        if (!$warehouse) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Gudang tidak ditemukan atau tidak aktif',
            ], 404);
        }

        $allowed = Warehouse::availableForUser($user)->pluck('id')->map(fn ($v) => (int) $v)->all();
        if (!in_array((int) $warehouse->id, $allowed, true)) {
            return response()->json([
                'status' => 'error',
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke gudang ini',
            ], 403);
        }

        Session::put('active_warehouse_id', (int) $warehouse->id);
        $this->persistLastActiveWarehouse($user, (int) $warehouse->id);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'active_warehouse_id' => (int) $warehouse->id,
        ]);
    }

    private function persistLastActiveWarehouse($user, ?int $warehouseId): void
    {
        if (!$user || empty($user->staff_id)) {
            return;
        }

        Staff::where('staff_id', (int) $user->staff_id)
            ->update(['last_active_warehouse_id' => $warehouseId]);

        $user->last_active_warehouse_id = $warehouseId;
        Session::put('user', $user);
    }
}
