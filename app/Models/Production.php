<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Production extends Model
{
    protected $table = "productions";
    protected $primaryKey = "production_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProduction($data = [])
    {
        $data = array_merge([
            "date" => null,
            "report" => null,
            "production_id" => null,
            "created_at" => null
        ], $data);  

        // status: 1 = menunggu approve, 2 = accept, 3 = tolak, 4 = menunggu cancel
        if ($data["report"] == null) $result = Production::where('status', '>=', 1);
        else if ($data["report"]) $result = Production::where('status', '>=', 0);
        if ($data["production_id"]) $result->where('production_id', '=', $data["production_id"]);
        if ($data['created_at']) $result->whereDate('created_at', $data['created_at']);
        
        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                // Jika date adalah array [start_date, end_date]]
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->format('Y-m-d');
                $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->format('Y-m-d');
                $result->whereBetween('production_date', [$startDate, $endDate]);
            } else {
                // Jika date hanya satu nilai
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d')) $date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');

                $result->where('production_date', '=', $date);
            }
        }

        $result->orderByRaw('FIELD(status, 1, 3, 2, 4)')->orderBy('created_at', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->items = (new ProductionDetails())->getProductionDetail(["production_id" => $value->production_id]);

            $dos = 0;
            foreach ($value->items as $key => $val) {
                if (str($val->unit_name)->upper()->contains('DOS')) {
                    $dos += $val->pd_qty;
                } else {
                    $pr = (new ProductRelation())->getProductRelation(['product_variant_id' => $val->product_variant_id]);

                    $relasiDos = $pr->first(function ($rel) {
                        return str($rel->pr_unit_name_1)->upper()->contains('DOS');
                    });

                    if ($relasiDos && $relasiDos->pr_unit_value_2 > 0) {
                        // Contoh: pd_qty = 24 piece, pr_unit_value_2 = 12 → floor(24/12) = 2 dos
                        $dos += floor($val->pd_qty / $relasiDos->pr_unit_value_2);
                    }
                }
            }
            $value->total_dos = $dos;
        }
        return $result;
    }

    function insertProduction($data)
    {
        $t = new Production();
        $t->production_date = $data["production_date"];
        $t->production_code = $this->generateProductionID();
        $t->production_created_by = 0;
        $t->save();
        return $t;
    }

    function updateProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->production_date = $data["production_date"];
        $t->production_code = $data["production_code"];
        $t->production_created_by = 0;
        $t->save();
        return $t->production_id;
    }

    function deleteProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->notes = $data["delete_reason"];
        $t->status = 3;
        $t->save();    
    }

    function declineProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->notes = $data["delete_reason"];
        $t->status = 4;
        $t->save();    
    }

    function accProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->status = 2;
        $t->save();
    }

    function tolakDeleteProduction($data)
    {
        $t = Production::find($data["production_id"]);
        $t->notes = null;
        $t->status = 2;
        $t->save();    
    }

    function cancelProduction($data) {
        $t = Production::find($data['production_id']);
        $t->status = 4;
        $t->save();
    }

    function generateProductionID()
    {
        $id = self::max('production_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "PR" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }

    function getProductionReport($data = [])
    {
        $data = array_merge([
            "date" => null,
            "supplier_id" => null,
            "product_variant_id" => null
        ], $data);

        $query = ProductionDetails::query()
            ->join('productions as p', 'p.production_id', '=', 'production_details.production_id')
            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'production_details.product_variant_id')
            ->join('products as pr', 'pr.product_id', '=', 'pv.product_id')
            ->leftJoin('units as u', 'u.unit_id', '=', 'production_details.unit_id')
            ->where('production_details.status', '>=', 1)
            ->where('p.status', '>=', 0)
            ->select(
                'production_details.pd_id',
                'production_details.product_variant_id',
                'production_details.pd_qty',
                'production_details.list_bahan',
                'production_details.bom_id',
                'p.production_id',
                'p.production_code',
                'p.production_date',
                'p.status as production_status',
                'pv.product_variant_name',
                'pr.product_name',
                'u.unit_name'
            );

        if ($data["product_variant_id"]) {
            $query->where('production_details.product_variant_id', $data["product_variant_id"]);
        }

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->format('Y-m-d');
                $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->format('Y-m-d');
                $query->whereBetween('p.production_date', [$startDate, $endDate]);
            } else {
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d')) {
                    $date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');
                }
                $query->where('p.production_date', '=', $date);
            }
        }

        $rows = $query->orderBy('p.production_date', 'desc')
            ->orderBy('p.production_id', 'desc')
            ->get();

        $supplierSupplies = [];
        if ($data["supplier_id"]) {
            $supplierSupplies = DB::table('supplies_variants')
                ->where('supplier_id', $data["supplier_id"])
                ->pluck('supplies_id')
                ->unique()
                ->toArray();
        }

        $grouped = [];
        $bomSuppliesCache = [];
        foreach ($rows as $row) {
            $listBahan = [];
            if (!empty($row->list_bahan)) {
                $decoded = json_decode($row->list_bahan, true);
                if (is_array($decoded)) $listBahan = $decoded;
            }

            // Fallback untuk data lama yang belum menyimpan list_bahan
            if (count($listBahan) <= 0 && !empty($row->bom_id)) {
                if (!isset($bomSuppliesCache[$row->bom_id])) {
                    $bomSuppliesCache[$row->bom_id] = DB::table('bom_details')
                        ->where('bom_id', $row->bom_id)
                        ->where('status', 1)
                        ->pluck('supplies_id')
                        ->toArray();
                }
                $listBahan = $bomSuppliesCache[$row->bom_id];
            }

            if ($data["supplier_id"]) {
                if (count($supplierSupplies) <= 0) continue;
                if (count(array_intersect($listBahan, $supplierSupplies)) <= 0) continue;
            }

            $key = $row->product_variant_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "product_variant_id" => $row->product_variant_id,
                    "product_name" => trim($row->product_name . " " . $row->product_variant_name),
                    "production_count" => 0,
                    "total_qty" => 0,
                    "total_reject_count" => 0,
                    "total_reject_qty" => 0,
                    "details" => []
                ];
            }

            $isRejected = ((int)$row->production_status === 3);
            $grouped[$key]["production_count"] += 1;
            $grouped[$key]["total_qty"] += (int)$row->pd_qty;
            if ($isRejected) {
                $grouped[$key]["total_reject_count"] += 1;
                $grouped[$key]["total_reject_qty"] += (int)$row->pd_qty;
            }

            $grouped[$key]["details"][] = [
                "production_id" => $row->production_id,
                "production_code" => $row->production_code,
                "production_date" => $row->production_date,
                "qty" => (int)$row->pd_qty,
                "unit_name" => $row->unit_name,
                "status" => (int)$row->production_status,
                "status_text" => $isRejected ? "Ditolak" : "Selesai"
            ];
        }

        return array_values($grouped);
    }
}
