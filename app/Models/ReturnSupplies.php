<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReturnSupplies extends Model
{
    protected $table = "return_supplies";
    protected $primaryKey = "rs_id";
    public $timestamps = true;
    public $incrementing = true;

    function getReturnSupplies($data = [])
    {
        $data = array_merge([
            "po_id"=>null,
        ], $data);

        $result = ReturnSupplies::where('status', '=', 1);
        if($data["po_id"]) $result->where('po_id', '=', $data["po_id"]);
        $result->orderBy('created_at', 'asc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->detail = (new ReturnSuppliesDetail())->getReturnSuppliesDetail(['rs_id' => $value->rs_id]);
        }
        return $result;
    }

    function insertReturnSupplies($data)
    {
        $t = new ReturnSupplies();
        $t->po_id = $data["po_id"];
        $t->pi_id = $data["pi_id"];
        $t->rs_date = $data["rs_date"];
        $t->rs_notes = $data["rs_notes"];
        $t->rs_total = $data["rs_total"];
        $t->save();
        return $t->rs_id;
    }

    function updateReturnSupplies($data)
    {
        $t = ReturnSupplies::find($data["rs_id"]);
        $t->po_id = $data["po_id"];
        $t->pi_id = $data["pi_id"];
        $t->rs_date = $data["rs_date"];
        $t->rs_notes = $data["rs_notes"];
        $t->rs_total = $data["rs_total"];
        $t->save();
        return $t->rs_id;
    }

    function deleteReturnSupplies($data)
    {
        $t = ReturnSupplies::find($data["rs_id"]);
        $t->status = 0;
        $t->save();
    }

    function getReturnReport($data = [])
    {
        $data = array_merge([
            "supplier_id" => null,
            "date" => null,
            "supplies_id" => null,
        ], $data);

        $query = DB::table('return_supplies as rs')
            ->join('return_supplies_detail as rsd', 'rsd.rs_id', '=', 'rs.rs_id')
            ->join('purchase_orders as po', 'po.po_id', '=', 'rs.po_id')
            ->leftJoin('suppliers as s', 's.supplier_id', '=', 'po.po_supplier')
            ->leftJoin('supplies_variants as sv', 'sv.supplies_variant_id', '=', 'rsd.supplies_variant_id')
            ->leftJoin('supplies as sp', 'sp.supplies_id', '=', 'sv.supplies_id')
            ->leftJoin('units as u', 'u.unit_id', '=', 'rsd.unit_id')
            ->where('rs.status', 1)
            ->where('rsd.status', 1)
            ->select(
                'rs.rs_id',
                'rs.rs_date',
                'rs.rs_notes',
                'rs.po_id',
                'po.po_number',
                'po.po_supplier',
                's.supplier_name',
                'rsd.rsd_id',
                'rsd.rsd_qty',
                'rsd.rsd_price',
                'rsd.unit_id',
                'u.unit_name',
                'sp.supplies_id',
                'rsd.supplies_variant_id',
                'sv.supplies_variant_name',
                'sp.supplies_name'
            );

        if ($data["supplier_id"]) {
            $query->where('po.po_supplier', $data["supplier_id"]);
        }
        if ($data["supplies_id"]) {
            $query->where('sp.supplies_id', $data["supplies_id"]);
        }

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->format('Y-m-d');
                $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->format('Y-m-d');
                $query->whereBetween('rs.rs_date', [$startDate, $endDate]);
            } else {
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d')) {
                    $date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');
                }
                $query->where('rs.rs_date', $date);
            }
        }

        $rows = $query->orderBy('rs.rs_date', 'desc')->orderBy('rs.rs_id', 'desc')->get();

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->supplies_id;
            $itemName = trim($row->supplies_name ?? '');
            if ($itemName == "") $itemName = $row->supplies_variant_name ?? '-';

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "supplies_id" => $row->supplies_id,
                    "item_name" => $itemName,
                    "transaction_count" => 0,
                    "details" => []
                ];
            }

            $grouped[$key]["transaction_count"] += 1;
            $grouped[$key]["details"][] = [
                "rs_id" => $row->rs_id,
                "rs_date" => $row->rs_date,
                "po_number" => $row->po_number,
                "supplier_name" => $row->supplier_name,
                "qty" => (int)$row->rsd_qty,
                "unit_name" => $row->unit_name,
                "price" => (int)$row->rsd_price,
                "subtotal" => ((int)$row->rsd_qty * (int)$row->rsd_price),
                "notes" => $row->rs_notes
            ];
        }

        return array_values($grouped);
    }
}
