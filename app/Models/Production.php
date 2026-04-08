<?php

namespace App\Models;

use App\Http\Controllers\ProductionController;
use Carbon\Carbon;
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
            "created_at" => null,
            "status" => null
        ], $data);  

        // status header produksi: 1 = pending, 2 = berhasil, 3 = tolak (4 = menunggu batal — jarang, bukan salah satu tiga utama)
        if ($data["report"] == null) $result = Production::where('status', '>=', 1);
        else if ($data["report"]) $result = Production::where('status', '>=', 0);
        if ($data["production_id"]) $result->where('production_id', '=', $data["production_id"]);
        if ($data['created_at']) $result->whereDate('created_at', $data['created_at']);
        if ($data['status']) $result->where('status', $data['status']);
        
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

        $result->orderBy('production_date', 'desc')->orderByRaw('FIELD(status, 1, 3, 2, 4)')->orderBy('created_at', 'desc');

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

            // Kalau misal ada yang sudah 3 hari lebih dan statusnya masih menunggu approve, maka auto ACC
            $productionDate = Carbon::parse($value->production_date);
            $diffDays = Carbon::now()->diffInDays($productionDate, false); 

            if ($diffDays < -4 && $value->status == 1) {
                $requestAcc = new \Illuminate\Http\Request();
                $requestAcc->merge(['production_id' => $value->production_id]);
                
                $resultAcc = (new ProductionController())->accProduction($requestAcc);
                
                // Cek apakah return 1 (sukses) atau bukan
                $isSuccess = $resultAcc === 1;
                if (!$isSuccess) {
                    // Gagal, jalankan decline
                    $newRequest = new \Illuminate\Http\Request();
                    $newRequest->merge(['production_id' => $value->production_id]);
                    (new ProductionController())->declineProduction($newRequest);
                }
                return $this->getProduction($data);
            }
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
                "status_text" => $this->labelProduksiHeaderStatus((int)$row->production_status),
            ];
        }

        return array_values($grouped);
    }

    function getProductionEfficiencyReport($data = [])
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
            // Hanya transaksi final: berhasil (2) dan tolak (3). Pending (1) tidak ikut.
            ->whereIn('p.status', [2, 3])
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

        $detailRows = [];
        $bomSuppliesCache = [];
        foreach ($rows as $row) {
            $listBahan = [];
            if (!empty($row->list_bahan)) {
                $decoded = json_decode($row->list_bahan, true);
                if (is_array($decoded)) $listBahan = $decoded;
            }

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

            $detailRows[] = $row;
        }

        if (count($detailRows) <= 0) return [];

        $totalQtyByCode = [];
        foreach ($detailRows as $row) {
            if (!isset($totalQtyByCode[$row->production_code])) $totalQtyByCode[$row->production_code] = 0;
            $totalQtyByCode[$row->production_code] += (float)$row->pd_qty;
        }

        $allCodes = array_values(array_unique(array_map(function ($r) {
            return $r->production_code;
        }, $detailRows)));

        $logRows = [];
        if (count($allCodes) > 0) {
            $logRows = DB::table('log_stocks as l')
                ->leftJoin('units as u', 'u.unit_id', '=', 'l.unit_id')
                ->where('l.status', 1)
                ->where('l.log_type', 2)
                ->where('l.log_category', 2)
                ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%')
                ->whereIn('l.log_kode', $allCodes)
                ->select('l.log_kode', 'l.log_jumlah', 'u.unit_name')
                ->get();
        }

        $materialByCode = [];
        foreach ($logRows as $lr) {
            $code = $lr->log_kode;
            $unit = trim((string)($lr->unit_name ?? ""));
            if ($unit === "") $unit = "unit";
            $qty = (float)($lr->log_jumlah ?? 0);
            if (!isset($materialByCode[$code])) $materialByCode[$code] = [];
            if (!isset($materialByCode[$code][$unit])) $materialByCode[$code][$unit] = 0;
            $materialByCode[$code][$unit] += $qty;
        }

        $fmtUnitMap = function ($map) {
            if (!is_array($map) || count($map) <= 0) return "-";
            $parts = [];
            foreach ($map as $unit => $qty) {
                $v = (float)$qty;
                if ($v <= 0) continue;
                $textQty = (abs($v - floor($v)) < 0.0001) ? (string)((int)$v) : rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
                $parts[] = $textQty . " " . $unit;
            }
            return count($parts) > 0 ? implode(" ", $parts) : "-";
        };

        $grouped = [];
        foreach ($detailRows as $row) {
            $variantId = $row->product_variant_id;
            $key = (string)$variantId;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "product_variant_id" => $variantId,
                    "product_name" => trim($row->product_name . " " . $row->product_variant_name),
                    "production_count" => 0,
                    "total_qty" => 0,
                    "total_reject_count" => 0,
                    "total_reject_qty" => 0,
                    "material_total_map" => [],
                    "material_waste_map" => [],
                    "production_code_set" => [],
                    "reject_code_set" => [],
                    "details" => []
                ];
            }

            $code = $row->production_code;
            $qty = (float)$row->pd_qty;
            $isRejected = ((int)$row->production_status === 3);
            $grouped[$key]["total_qty"] += $qty;
            if ($isRejected) $grouped[$key]["total_reject_qty"] += $qty;

            $grouped[$key]["production_code_set"][$code] = true;
            if ($isRejected) $grouped[$key]["reject_code_set"][$code] = true;

            $codeTotalQty = (float)($totalQtyByCode[$code] ?? 0);
            $ratio = $codeTotalQty > 0 ? ($qty / $codeTotalQty) : 0;
            $allocatedUsageMap = [];
            $allocatedWasteMap = [];
            $matCode = $materialByCode[$code] ?? [];
            foreach ($matCode as $u => $matQty) {
                $alloc = (float)$matQty * $ratio;
                if ($alloc <= 0) continue;
                $allocatedUsageMap[$u] = $alloc;
                if (!isset($grouped[$key]["material_total_map"][$u])) $grouped[$key]["material_total_map"][$u] = 0;
                $grouped[$key]["material_total_map"][$u] += $alloc;

                if ($isRejected) {
                    $allocatedWasteMap[$u] = $alloc;
                    if (!isset($grouped[$key]["material_waste_map"][$u])) $grouped[$key]["material_waste_map"][$u] = 0;
                    $grouped[$key]["material_waste_map"][$u] += $alloc;
                }
            }

            $hasMatLog = false;
            foreach ($matCode as $mq) {
                if ((float)$mq > 0) {
                    $hasMatLog = true;
                    break;
                }
            }

            $grouped[$key]["details"][] = [
                "production_id" => $row->production_id,
                "production_code" => $code,
                "production_date" => $row->production_date,
                "qty" => (int)$qty,
                "unit_name" => $row->unit_name,
                "status" => (int)$row->production_status,
                "status_text" => $this->labelProduksiHeaderStatus((int)$row->production_status),
                "material_usage_text" => $fmtUnitMap($allocatedUsageMap),
                "material_waste_text" => $isRejected ? $fmtUnitMap($allocatedWasteMap) : "-",
                "material_tracked" => $hasMatLog,
            ];
        }

        $result = array_values($grouped);
        foreach ($result as $idx => $row) {
            $result[$idx]["production_count"] = count($row["production_code_set"]);
            $result[$idx]["total_reject_count"] = count($row["reject_code_set"]);
            $rejectRatio = $row["total_qty"] > 0 ? ($row["total_reject_qty"] / $row["total_qty"]) * 100 : 0;
            $sumMatTotal = array_sum($row["material_total_map"]);
            $sumMatWaste = array_sum($row["material_waste_map"]);
            $materialWasteRatio = $sumMatTotal > 0 ? ($sumMatWaste / $sumMatTotal) * 100 : 0;
            $yieldPct = max(0, 100 - $rejectRatio);
            $rCap = min(100, max(0, $rejectRatio));
            $wCap = min(100, max(0, $materialWasteRatio));
            // Skor gabungan: yield output × retensi bahan (keduanya 0–100); turun jika reject ATAU waste tinggi
            $operationalScore = round(((100 - $rCap) * (100 - $wCap)) / 100, 2);

            $untrackedBatches = 0;
            foreach (array_keys($row["production_code_set"]) as $pcode) {
                $m = $materialByCode[$pcode] ?? [];
                $has = false;
                foreach ($m as $mq) {
                    if ((float)$mq > 0) {
                        $has = true;
                        break;
                    }
                }
                if (!$has) {
                    $untrackedBatches++;
                }
            }

            $result[$idx]["good_qty"] = (int)max(0, round($row["total_qty"] - $row["total_reject_qty"]));
            $result[$idx]["yield_pct"] = round($yieldPct, 2);
            $result[$idx]["reject_ratio"] = round($rejectRatio, 2);
            $result[$idx]["efficiency_ratio"] = round($yieldPct, 2);
            $result[$idx]["operational_score"] = $operationalScore;
            $result[$idx]["material_waste_ratio"] = round($materialWasteRatio, 2);
            $result[$idx]["untracked_batch_count"] = $untrackedBatches;
            $result[$idx]["material_total_text"] = $fmtUnitMap($row["material_total_map"]);
            $result[$idx]["material_waste_text"] = $fmtUnitMap($row["material_waste_map"]);

            if ($operationalScore >= 90) {
                $result[$idx]["risk_level"] = "rendah";
            } elseif ($operationalScore >= 75) {
                $result[$idx]["risk_level"] = "sedang";
            } else {
                $result[$idx]["risk_level"] = "tinggi";
            }

            unset($result[$idx]["material_total_map"], $result[$idx]["material_waste_map"], $result[$idx]["production_code_set"], $result[$idx]["reject_code_set"]);
        }

        usort($result, function ($a, $b) {
            $ua = (int)($a["untracked_batch_count"] ?? 0);
            $ub = (int)($b["untracked_batch_count"] ?? 0);
            if ($ua !== $ub) {
                return $ub <=> $ua;
            }
            $sa = (float)($a["operational_score"] ?? 0);
            $sb = (float)($b["operational_score"] ?? 0);
            if (abs($sa - $sb) > 0.0001) {
                return $sa <=> $sb;
            }
            $ra = (float)($a["reject_ratio"] ?? 0);
            $rb = (float)($b["reject_ratio"] ?? 0);
            return $rb <=> $ra;
        });

        return $result;
    }

    /**
     * Status header tabel `productions` sesuai operasional: pending, berhasil, tolak.
     */
    private function labelProduksiHeaderStatus(int $status): string
    {
        switch ($status) {
            case 1:
                return 'Pending';
            case 2:
                return 'Berhasil';
            case 3:
                return 'Tolak';
            case 4:
                return 'Menunggu batal';
            default:
                return '-';
        }
    }
}
