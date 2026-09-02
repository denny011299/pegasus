<?php

namespace App\Models;

use App\Support\BatchLookup;
use App\Support\SalesOrderStock;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class SalesOrder extends Model
{
    protected $table = "sales_orders";
    protected $primaryKey = "so_id";
    public $timestamps = true;
    public $incrementing = true;

    function getSalesOrder($data = []){
        $data = array_merge([
            "so_number" => null,
            "so_customer" => null,
            "so_id" => null,
            "so_ref_number" => null,
            "with_items" => false,
        ], $data);
        $data['with_items'] = filter_var($data['with_items'], FILTER_VALIDATE_BOOLEAN);
        if ($data['so_id']) {
            $data['with_items'] = true;
        }

        $result = SalesOrder::where("status", ">=", 1);

        if ($data["so_id"]) $result->where("so_id", "=", $data["so_id"]);

        if ($data["so_number"]) $result->where("so_number", "like", "%".$data["so_number"]."%");

        if ($data["so_ref_number"]) $result->where("so_ref_number", "like", "%".$data["so_ref_number"]."%");
        
        if ($data["so_customer"]) {
            $result->where("so_customer", "like", "%".$data["so_customer"]."%");
        }

        $result->orderBy('status', 'asc')->orderBy("created_at", "desc");
        $result = $result->get();

        if ($result->isEmpty()) {
            return $result;
        }

        $hasCreatedBy = Schema::hasColumn($this->getTable(), 'created_by');
        $hasAccBy = Schema::hasColumn($this->getTable(), 'acc_by');

        $customerIds = $result->pluck('so_customer')->filter()->unique()->values()->all();
        $customers = $customerIds !== []
            ? Customer::whereIn('customer_id', $customerIds)->get()->keyBy('customer_id')
            : collect();

        $staffIdSet = [];
        foreach ($result as $row) {
            if ($row->so_cashier) {
                $staffIdSet[(int) $row->so_cashier] = true;
            }
            if ($hasCreatedBy && ($row->created_by ?? null)) {
                $staffIdSet[(int) $row->created_by] = true;
            }
            if ($hasAccBy && ($row->acc_by ?? null)) {
                $staffIdSet[(int) $row->acc_by] = true;
            }
        }
        $staffNames = BatchLookup::staffNames(array_keys($staffIdSet));

        $detailsBySoId = collect();
        if ($data['with_items']) {
            $detailQuery = SalesOrderDetail::where('status', '=', 1);
            if ($data['so_id']) {
                $detailQuery->where('so_id', '=', $data['so_id']);
            } else {
                $detailQuery->whereIn('so_id', $result->pluck('so_id')->all());
            }
            $detailsBySoId = (new SalesOrderDetail())
                ->enrichDetailsCollection($detailQuery->orderBy('sod_id', 'asc')->get())
                ->groupBy('so_id');
        }

        $hasRetailWh = Schema::hasColumn($this->getTable(), 'retail_warehouse_id');
        $retailWhNames = collect();
        if ($hasRetailWh) {
            $whIds = $result->pluck('retail_warehouse_id')->filter()->unique()->values()->all();
            if ($whIds !== []) {
                $retailWhNames = Warehouse::whereIn('id', $whIds)->pluck('warehouse_name', 'id');
            }
        }

        foreach ($result as $value) {
            $value->customer_name = $customers->get($value->so_customer)?->customer_notes ?? '-';
            $value->items = $data['with_items']
                ? ($detailsBySoId->get($value->so_id) ?? collect())->values()->all()
                : [];
            $value->staff_name = $value->so_cashier
                ? ($staffNames->get((int) $value->so_cashier) ?? '-')
                : '-';
            $value->created_by_name = $hasCreatedBy && ($value->created_by ?? null)
                ? ($staffNames->get((int) $value->created_by) ?? '-')
                : '-';
            $value->acc_by_name = $hasAccBy && ($value->acc_by ?? null)
                ? ($staffNames->get((int) $value->acc_by) ?? '-')
                : '-';
            if ($hasRetailWh) {
                $rid = (int) ($value->retail_warehouse_id ?? 0);
                $value->retail_warehouse_name = $rid > 0
                    ? ($retailWhNames->get($rid) ?? '-')
                    : null;
            }
        }

        return $result;
    }

    /**
     * Server-side DataTables untuk list Pengiriman.
     */
    function getSalesOrderDataTable(array $data = [])
    {
        $draw = (int) ($data['draw'] ?? 1);
        $start = max(0, (int) ($data['start'] ?? 0));
        $length = (int) ($data['length'] ?? 10);
        if ($length < 1) {
            $length = 10;
        }
        if ($length > 100) {
            $length = 100;
        }

        $search = trim((string) data_get($data, 'search.value', ''));
        $orderColIdx = (int) data_get($data, 'order.0.column', 4);
        $orderDir = strtolower((string) data_get($data, 'order.0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $activeWh = (int) ($data['active_warehouse_id'] ?? $data['warehouse_id'] ?? Session::get('active_warehouse_id') ?? 0);
        if ($activeWh <= 0) {
            return [
                'draw' => $draw,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ];
        }

        $hasCreatedBy = Schema::hasColumn($this->getTable(), 'created_by');
        $hasAccBy = Schema::hasColumn($this->getTable(), 'acc_by');
        $hasRetailWh = Schema::hasColumn($this->getTable(), 'retail_warehouse_id');

        $columns = [
            0 => 'c.customer_notes',
            1 => 'sales_orders.so_date',
            2 => 'sales_orders.so_invoice_no',
            3 => 'sales_orders.so_ref_number',
            4 => 'sales_orders.status',
            5 => $hasCreatedBy ? 'creator.staff_name' : 'sales_orders.so_id',
            6 => $hasAccBy ? 'approver.staff_name' : 'sales_orders.so_id',
            7 => 'sales_orders.so_id',
        ];
        $orderCol = $columns[$orderColIdx] ?? 'sales_orders.status';

        $base = SalesOrder::query()
            ->from('sales_orders')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'sales_orders.so_customer')
            ->where('sales_orders.status', '>=', 1);

        if ($hasCreatedBy) {
            $base->leftJoin('staffs as creator', 'creator.staff_id', '=', 'sales_orders.created_by');
        }
        if ($hasAccBy) {
            $base->leftJoin('staffs as approver', 'approver.staff_id', '=', 'sales_orders.acc_by');
        }

        $this->applyActiveWarehouseFilter($base, $activeWh, $hasRetailWh);

        $countBase = SalesOrder::query()->where('status', '>=', 1);
        $this->applyActiveWarehouseFilter($countBase, $activeWh, $hasRetailWh);

        $recordsTotal = $countBase->count('so_id');

        $dateFrom = trim((string) ($data['date_from'] ?? ''));
        $dateTo = trim((string) ($data['date_to'] ?? ''));
        $statusFilter = trim((string) ($data['status'] ?? ''));
        if ($dateFrom !== '') {
            $base->whereDate('sales_orders.so_date', '>=', $dateFrom);
        }
        if ($dateTo !== '') {
            $base->whereDate('sales_orders.so_date', '<=', $dateTo);
        }
        if ($statusFilter !== '' && ctype_digit($statusFilter)) {
            $base->where('sales_orders.status', (int) $statusFilter);
        }

        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($q) use ($like, $hasCreatedBy, $hasAccBy) {
                $q->where('c.customer_notes', 'like', $like)
                    ->orWhere('sales_orders.so_invoice_no', 'like', $like)
                    ->orWhere('sales_orders.so_ref_number', 'like', $like)
                    ->orWhere('sales_orders.so_number', 'like', $like);
                if ($hasCreatedBy) {
                    $q->orWhere('creator.staff_name', 'like', $like);
                }
                if ($hasAccBy) {
                    $q->orWhere('approver.staff_name', 'like', $like);
                }
            });
        }

        $hasListFilter = $search !== '' || $dateFrom !== '' || $dateTo !== '' || $statusFilter !== '';
        $recordsFiltered = $hasListFilter
            ? (clone $base)->distinct()->count('sales_orders.so_id')
            : $recordsTotal;

        $select = [
            'sales_orders.so_id',
            'sales_orders.so_number',
            'sales_orders.so_customer',
            'sales_orders.so_date',
            'sales_orders.so_invoice_no',
            'sales_orders.so_ref_number',
            'sales_orders.so_cashier',
            'sales_orders.status',
            'sales_orders.created_at',
            'c.customer_notes as customer_name',
        ];
        if ($hasCreatedBy) {
            $select[] = 'sales_orders.created_by';
            $select[] = 'creator.staff_name as created_by_name';
        }
        if ($hasAccBy) {
            $select[] = 'sales_orders.acc_by';
            $select[] = 'approver.staff_name as acc_by_name';
        }
        if ($hasRetailWh) {
            $select[] = 'sales_orders.retail_warehouse_id';
        }

        $rows = (clone $base)
            ->select($select)
            ->orderBy($orderCol, $orderDir)
            ->orderBy('sales_orders.created_at', 'desc')
            ->orderBy('sales_orders.so_id', 'desc')
            ->skip($start)
            ->take($length)
            ->get();

        $retailWhNames = collect();
        if ($hasRetailWh) {
            $whIds = $rows->pluck('retail_warehouse_id')->filter()->unique()->values()->all();
            if ($whIds !== []) {
                $retailWhNames = Warehouse::whereIn('id', $whIds)->pluck('warehouse_name', 'id');
            }
        }

        $dataOut = [];
        foreach ($rows as $row) {
            $item = [
                'so_id' => (int) $row->so_id,
                'so_number' => $row->so_number,
                'so_customer' => $row->so_customer,
                'customer_name' => $row->customer_name ?: '-',
                'so_date' => $row->so_date,
                'so_invoice_no' => $row->so_invoice_no,
                'so_ref_number' => $row->so_ref_number,
                'status' => (int) $row->status,
                'created_by_name' => $hasCreatedBy ? ($row->created_by_name ?: '-') : '-',
                'acc_by_name' => $hasAccBy ? ($row->acc_by_name ?: '-') : '-',
            ];
            if ($hasRetailWh) {
                $rid = (int) ($row->retail_warehouse_id ?? 0);
                $item['retail_warehouse_id'] = $rid > 0 ? $rid : null;
                $item['retail_warehouse_name'] = $rid > 0
                    ? ($retailWhNames->get($rid) ?? '-')
                    : null;
            }
            $dataOut[] = $item;
        }

        return [
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $dataOut,
        ];
    }

    /**
     * List pengiriman hanya yang relevan dengan gudang aktif:
     * - gudang utama → SO dengan item non-eceran
     * - gudang eceran → SO dengan item eceran dari gudang tersebut
     */
    private function applyActiveWarehouseFilter($query, int $activeWh, bool $hasRetailWh): void
    {
        $isMain = Warehouse::query()
            ->active()
            ->whereKey($activeWh)
            ->whereHas('type', fn ($q) => $q->where('is_main_warehouse', 1))
            ->exists();

        $hasDetailWh = Schema::hasColumn('sales_order_details', 'warehouse_id');
        $hasRetailUnit = Schema::hasColumn('product_variants', 'retail_unit');

        if ($isMain || ! $hasRetailUnit) {
            if (! $hasRetailUnit) {
                return;
            }

            $query->where(function ($q) use ($activeWh, $hasRetailWh, $hasDetailWh) {
                // Item non-eceran → stok dari gudang utama
                $q->whereExists(function ($sub) {
                    $sub->from('sales_order_details as sod')
                        ->join('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
                        ->whereColumn('sod.so_id', 'sales_orders.so_id')
                        ->where('sod.status', 1)
                        ->where(function ($w) {
                            $w->whereNull('pv.retail_unit')
                                ->orWhere('pv.retail_unit', 0)
                                ->orWhereColumn('sod.unit_id', '!=', 'pv.retail_unit');
                        });
                });

                // Item eceran yang eksplisit dari gudang utama (data legacy / fallback header)
                if ($hasDetailWh) {
                    $q->orWhereExists(function ($sub) use ($activeWh, $hasRetailWh) {
                        $sub->from('sales_order_details as sod')
                            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
                            ->whereColumn('sod.so_id', 'sales_orders.so_id')
                            ->where('sod.status', 1)
                            ->where('pv.retail_unit', '>', 0)
                            ->whereColumn('sod.unit_id', '=', 'pv.retail_unit')
                            ->where(function ($w) use ($activeWh, $hasRetailWh) {
                                $w->where('sod.warehouse_id', $activeWh);
                                if ($hasRetailWh) {
                                    $w->orWhere(function ($w2) use ($activeWh) {
                                        $w2->whereNull('sod.warehouse_id')
                                            ->where('sales_orders.retail_warehouse_id', $activeWh);
                                    });
                                }
                            });
                    });
                } elseif ($hasRetailWh) {
                    $q->orWhere('sales_orders.retail_warehouse_id', $activeWh);
                }
            });

            return;
        }

        $query->whereExists(function ($sub) use ($activeWh, $hasRetailWh, $hasDetailWh) {
            $sub->from('sales_order_details as sod')
                ->join('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
                ->whereColumn('sod.so_id', 'sales_orders.so_id')
                ->where('sod.status', 1)
                ->where('pv.retail_unit', '>', 0)
                ->whereColumn('sod.unit_id', '=', 'pv.retail_unit');

            if ($hasDetailWh) {
                $sub->where(function ($w) use ($activeWh, $hasRetailWh) {
                    $w->where('sod.warehouse_id', $activeWh);
                    if ($hasRetailWh) {
                        $w->orWhere(function ($w2) use ($activeWh) {
                            $w2->whereNull('sod.warehouse_id')
                                ->where('sales_orders.retail_warehouse_id', $activeWh);
                        });
                    }
                });
            } elseif ($hasRetailWh) {
                $sub->where('sales_orders.retail_warehouse_id', $activeWh);
            } else {
                $sub->whereRaw('1 = 0');
            }
        });
    }

    function insertSalesOrder($data){
        // foreach (json_decode($data["products"], true) as $key => $value) {
        //     $m = ProductVariant::find($value["product_variant_id"]);
        //     $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$value["unit_id"])->first();
        //     if ($s->ps_stock - $value["so_qty"] > 0) {
        //         $s->ps_stock -= $value["so_qty"];
        //     } else {
        //         return -1;
        //     }
        //     $m->save();
        //     $s->save();
        // }
        $t = new SalesOrder();
        $t->so_number = $this->generateSalesOrderID();
        $t->so_customer  = $data["so_customer"];
        // $t->sales_id  = $data["sales_id"];
        $t->so_date  = $data["so_date"];
        $t->so_total  = $data["so_total"];
        $t->so_ppn  = 0;
        $t->so_discount  = 0;
        $t->so_cost  = 0;
        $t->so_img  = $data["so_img"];
        $t->so_invoice_no  = $this->generateInvoiceSalesOrderID();
        $t->so_ref_number = trim((string) ($data["so_ref_number"] ?? '')) ?: null;
        // $t->so_payment  = $data["so_payment"];
        $t->so_cashier  = null;
        if (Schema::hasColumn($t->getTable(), 'retail_warehouse_id')) {
            $wid = (int) ($data['retail_warehouse_id'] ?? 0);
            $t->retail_warehouse_id = $wid > 0 ? $wid : null;
        }
        if (Schema::hasColumn($t->getTable(), 'created_by')) {
            $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        }
        $t->save();

        return $t;
    }

    function updateSalesOrder($data){
        // foreach (json_decode($data["products"], true) as $key => $value){
        //     $m = ProductVariant::find($value["product_variant_id"]);
        //     $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$value["unit_id"])->first();

        //     // ambil detail lama kalau ada
        //     $d = isset($value["sod_id"]) ? SalesOrderDetail::find($value["sod_id"]) : null;

        //     // kembalikan stok lama dulu kalau update
        //     if ($d) $s->ps_stock += $d->sod_qty;

        //     // cek stok baru
        //     if ($s->ps_stock - $value["so_qty"] < 0) return -1;
        //     $s->ps_stock -= $value["so_qty"];
        //     $s->save();

        //     // simpan detail baru / update
        //     if ($d == null){
        //         $detail = new SalesOrderDetail();
        //         $detail->so_id = $data["so_id"];
        //         $detail->product_variant_id = $value["product_variant_id"];
        //         $detail->sod_nama = $value["product_name"];
        //         $detail->sod_variant = $value["product_variant_name"];
        //         $detail->sod_sku = $value["product_variant_sku"];
        //         $detail->sod_unit = $value["unit_id"];
        //         $detail->sod_qty = $value["so_qty"];
        //         $detail->sod_harga = $value["sod_price"] ?? 0;
        //         $detail->sod_subtotal = $value["sod_subtotal"] ?? ($value["so_qty"] * $detail->sod_price);
        //         $detail->save();
        //     }
        //     $m->save();
        //     $s->save();
        // }
        $t = SalesOrder::find($data["so_id"]);
        $t->so_number = $data["so_number"];
        $t->so_customer  = $data["so_customer"];
        // $t->sales_id  = $data["sales_id"];
        $t->so_date  = $data["so_date"];
        $t->so_total  = $data["so_total"];
        $t->so_ppn  = 0;
        $t->so_discount  = 0;
        $t->so_cost  = 0;
        $t->so_invoice_no  = $data["so_invoice_no"];
        $t->so_ref_number = trim((string) ($data["so_ref_number"] ?? '')) ?: null;
        // $t->so_payment  = $data["so_payment"];
        $t->so_cashier  = null;
        if (Schema::hasColumn($t->getTable(), 'retail_warehouse_id') && array_key_exists('retail_warehouse_id', $data)) {
            $wid = (int) ($data['retail_warehouse_id'] ?? 0);
            $t->retail_warehouse_id = $wid > 0 ? $wid : null;
        }
        // Jika sebelumnya ditolak, update ini dianggap input ulang dan kembali ke antrean ACC.
        if ((int) ($t->status ?? 0) === 3) {
            $t->status = 1;
            if (Schema::hasColumn($t->getTable(), 'acc_by')) {
                $t->acc_by = null;
            }
        }
        $t->save();

        return $t;
    }

    function deleteSalesOrder($data){
        $t = SalesOrder::find($data["so_id"]);
        $t->status = 3; // soft delete
        $t->save();
    }

    function accSO($data){
        $t = SalesOrder::find($data["so_id"]);
        $t->status = 2; // accept
        if (Schema::hasColumn($t->getTable(), 'acc_by')) {
            $t->acc_by = Session::get('user') ? Session::get('user')->staff_id : null;
        }
        $t->save();
    }
    
    function declineSO($data){
        $t = SalesOrder::find($data["so_id"]);
        $t->status = 3; // decline
        if (Schema::hasColumn($t->getTable(), 'acc_by')) {
            $t->acc_by = Session::get('user') ? Session::get('user')->staff_id : null;
        }
        $t->save();
    }

    function generateSalesOrderID()
    {
        $id  = self::max('so_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "SO".str_pad($id, 4, "0", STR_PAD_LEFT);
    }

    function generateInvoiceSalesOrderID()
    {
        $id = self::max('so_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "INV" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
