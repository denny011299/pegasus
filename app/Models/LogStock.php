<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class LogStock extends Model
{
    protected $table = "log_stocks";
    protected $primaryKey = "log_id";
    public $timestamps = true;
    public $incrementing = true;

    function getLog($data = [])
    {

        $data = array_merge([
            "log_notes"=>null,
            "log_type"=>null,
            "log_item_id"=>null,
            "date"=>null
        ], $data);

        $result = LogStock::where('status', '=', 1);
        if($data['log_notes'])$result->where('log_notes','like','%'.$data["log_notes"].'%');
        if($data["log_type"])$result->where('log_type','=',$data["log_type"]);
        if($data["log_item_id"])$result->where('log_item_id','=',$data["log_item_id"]);

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                $startDate = \Carbon\Carbon::parse($data["date"][0])->startOfDay();
                $endDate   = \Carbon\Carbon::parse($data["date"][1])->endOfDay();

                $result->whereBetween('log_date', [$startDate, $endDate]);
            } else {
                $date = \Carbon\Carbon::parse($data["date"])->toDateString();
                $result->whereDate('log_date', $date);
            }
        }

        $result->orderBy('created_at', 'desc')->orderBy('log_id', 'desc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            $u = Unit::find($value->unit_id);
            $value->unit_name = $u->unit_name;
            if($value->staff_id){
                try {
                    $value->staff_name =Staff::find($value->staff_id)->staff_name; 
                } catch (\Throwable $th) {
                     $value->staff_name ="-"; 
                }
            }
            else{
                $value->staff_name ="-"; 
            }
        }
        return $result;
    }

    function insertLog($data)
    {
        $t = new LogStock();
        $t->log_date = $data["log_date"];
        $t->log_kode = $data["log_kode"];
        $t->log_type = $data["log_type"];
        $t->log_category = $data["log_category"];
        $t->log_item_id = $data["log_item_id"];
        $t->log_notes = $data["log_notes"];
        $t->log_jumlah = $data["log_jumlah"];
        $t->unit_id = $data["unit_id"];
        $t->staff_id = Session::get('user')->staff_id;
        $t->save();
        return $t->log_id;
    }

    function getRawMaterialUsageReport($data = [])
    {
        $data = array_merge([
            "date" => null,
            "supplier_id" => null,
            "supplies_id" => null
        ], $data);

        $query = DB::table('log_stocks as l')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'l.log_item_id')
            ->leftJoin('units as u', 'u.unit_id', '=', 'l.unit_id')
            ->leftJoin('productions as p', 'p.production_code', '=', 'l.log_kode')
            ->where('l.status', 1)
            ->where('l.log_type', 2)
            ->where('l.log_category', 2)
            ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%')
            ->select(
                'l.log_id',
                'l.log_date',
                'l.log_kode',
                'l.log_item_id',
                'l.log_jumlah',
                'l.log_notes',
                'u.unit_name',
                's.supplies_name',
                'p.production_date'
            );

        if ($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->startOfDay();
                $endDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->endOfDay();
                $query->whereBetween('l.log_date', [$startDate, $endDate]);
            } else {
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d')) {
                    $date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');
                }
                $query->whereDate('l.log_date', $date);
            }
        }

        if ($data["supplies_id"]) {
            $query->where('l.log_item_id', $data["supplies_id"]);
        }

        if ($data["supplier_id"]) {
            $supplierSupplies = DB::table('supplies_variants')
                ->where('supplier_id', $data["supplier_id"])
                ->pluck('supplies_id')
                ->unique()
                ->toArray();

            if (count($supplierSupplies) <= 0) return [];
            $query->whereIn('l.log_item_id', $supplierSupplies);
        }

        $rows = $query->orderBy('l.log_date', 'desc')->orderBy('l.log_id', 'desc')->get();

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->log_item_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "supplies_id" => $row->log_item_id,
                    "item_name" => $row->supplies_name ?? '-',
                    "supplier_summary" => "-",
                    "transaction_count" => 0,
                    "details" => []
                ];
            }

            $grouped[$key]["transaction_count"] += 1;
            $grouped[$key]["details"][] = [
                "production_date" => $row->production_date ?: date('Y-m-d', strtotime($row->log_date)),
                "log_date" => $row->log_date,
                "production_code" => $row->log_kode,
                "qty" => (int)$row->log_jumlah,
                "unit_name" => $row->unit_name,
                "notes" => $row->log_notes
            ];
        }

        if (count($grouped) > 0) {
            $suppliesIds = array_keys($grouped);
            $supplierRows = DB::table('supplies_variants as sv')
                ->leftJoin('suppliers as sp', 'sp.supplier_id', '=', 'sv.supplier_id')
                ->whereIn('sv.supplies_id', $suppliesIds)
                ->where('sv.status', 1)
                ->select('sv.supplies_id', 'sp.supplier_name')
                ->distinct()
                ->get();

            $supplierMap = [];
            foreach ($supplierRows as $srow) {
                if (empty($srow->supplier_name)) continue;
                if (!isset($supplierMap[$srow->supplies_id])) $supplierMap[$srow->supplies_id] = [];
                $supplierMap[$srow->supplies_id][$srow->supplier_name] = true;
            }

            foreach ($grouped as $suppliesId => $item) {
                $names = isset($supplierMap[$suppliesId]) ? array_keys($supplierMap[$suppliesId]) : [];
                $grouped[$suppliesId]["supplier_summary"] = count($names) > 0 ? implode(', ', $names) : "-";
            }
        }

        return array_values($grouped);
    }

    /**
     * Laporan stock aging: FIFO per (log_type, item, unit) dari log_stocks.
     * log_category 1 = masuk, 2 = keluar (sesuai pemakaian di StockController / SupplierController / Produksi).
     * Baris Stock Opname diabaikan (bukan delta FIFO).
     *
     * @param  array{type?:string,item_id?:mixed,as_of?:string|null}  $data
     * @return array<int, array<string, mixed>>
     */
    function getStockAgingReport($data = [])
    {
        $data = array_merge([
            'type' => 'all',
            'item_id' => null,
            'as_of' => null,
        ], $data);

        $type = strtolower((string) $data['type']);
        if (!in_array($type, ['all', 'product', 'bahan'], true)) {
            $type = 'all';
        }

        $asOf = \Carbon\Carbon::now()->startOfDay();
        if (!empty($data['as_of'])) {
            $raw = trim((string) $data['as_of']);
            if (\Carbon\Carbon::hasFormat($raw, 'Y-m-d')) {
                $asOf = \Carbon\Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
            } elseif (\Carbon\Carbon::hasFormat($raw, 'd-m-Y')) {
                $asOf = \Carbon\Carbon::createFromFormat('d-m-Y', $raw)->startOfDay();
            } else {
                try {
                    $asOf = \Carbon\Carbon::parse($raw)->startOfDay();
                } catch (\Throwable $e) {
                    $asOf = \Carbon\Carbon::now()->startOfDay();
                }
            }
        }

        $lastHargaSub = DB::table('purchase_orders_details as pod')
            ->join('supplies_variants as sv', 'sv.supplies_variant_id', '=', 'pod.supplies_variant_id')
            ->where('pod.status', 1)
            ->select('sv.supplies_id', DB::raw('MAX(pod.pod_harga) as last_harga'))
            ->groupBy('sv.supplies_id');

        $out = [];

        if ($type === 'all' || $type === 'product') {
            $q = DB::table('product_stocks as ps')
                ->join('product_variants as pv', 'pv.product_variant_id', '=', 'ps.product_variant_id')
                ->join('products as p', 'p.product_id', '=', 'pv.product_id')
                ->join('units as u', 'u.unit_id', '=', 'ps.unit_id')
                ->where('ps.status', 1)
                ->where('pv.status', 1)
                ->where('ps.ps_stock', '>', 0)
                ->select(
                    'ps.product_variant_id',
                    'ps.unit_id',
                    'ps.ps_stock',
                    'ps.created_at as ps_created_at',
                    'p.product_name',
                    'pv.product_variant_name',
                    'pv.product_variant_price',
                    'u.unit_name'
                );

            if ($type === 'product' && !empty($data['item_id'])) {
                $q->where('ps.product_variant_id', (int) $data['item_id']);
            }

            foreach ($q->get() as $row) {
                $logs = DB::table('log_stocks')
                    ->where('status', 1)
                    ->where('log_type', 1)
                    ->where('log_item_id', $row->product_variant_id)
                    ->where('unit_id', $row->unit_id)
                    ->whereRaw("COALESCE(log_notes,'') NOT LIKE ?", ['%Stock Opname%'])
                    ->orderBy('log_date', 'asc')
                    ->orderBy('log_id', 'asc')
                    ->get(['log_date', 'log_category', 'log_jumlah']);

                $layers = self::simulateFifoLayers($logs, (float) $row->ps_stock, $row->ps_created_at);
                $m = self::metricsFromFifoLayers($layers, $asOf);

                $qty = (float) $row->ps_stock;
                $price = (float) ($row->product_variant_price ?? 0);
                $out[] = [
                    'sumber' => 'produk',
                    'product_variant_id' => (int) $row->product_variant_id,
                    'supplies_id' => null,
                    'item_label' => trim(($row->product_name ?? '') . ' ' . ($row->product_variant_name ?? '')),
                    'variant_name' => (string) ($row->product_variant_name ?? ''),
                    'unit_name' => (string) ($row->unit_name ?? ''),
                    'qty' => $qty,
                    'qty_display' => (string) (int) round($qty),
                    'weighted_age_days' => $m['weighted_age_days'],
                    'oldest_layer_date' => $m['oldest_layer_date'],
                    'bucket' => $m['bucket'],
                    'unit_price' => $price,
                    'stock_value' => round($qty * $price, 2),
                ];
            }
        }

        if ($type === 'all' || $type === 'bahan') {
            $q = DB::table('supplies_stocks as ss')
                ->join('supplies as s', 's.supplies_id', '=', 'ss.supplies_id')
                ->leftJoinSub($lastHargaSub, 'lh', function ($join) {
                    $join->on('lh.supplies_id', '=', 'ss.supplies_id');
                })
                ->join('units as u', 'u.unit_id', '=', 'ss.unit_id')
                ->where('ss.status', 1)
                ->where('s.status', 1)
                ->where('ss.ss_stock', '>', 0)
                ->select(
                    'ss.supplies_id',
                    'ss.unit_id',
                    'ss.ss_stock',
                    'ss.created_at as ss_created_at',
                    's.supplies_name',
                    DB::raw('COALESCE(lh.last_harga,0) as last_harga'),
                    'u.unit_name'
                );

            if ($type === 'bahan' && !empty($data['item_id'])) {
                $q->where('ss.supplies_id', (int) $data['item_id']);
            }

            foreach ($q->get() as $row) {
                $logs = DB::table('log_stocks')
                    ->where('status', 1)
                    ->where('log_type', 2)
                    ->where('log_item_id', $row->supplies_id)
                    ->where('unit_id', $row->unit_id)
                    ->whereRaw("COALESCE(log_notes,'') NOT LIKE ?", ['%Stock Opname%'])
                    ->orderBy('log_date', 'asc')
                    ->orderBy('log_id', 'asc')
                    ->get(['log_date', 'log_category', 'log_jumlah']);

                $layers = self::simulateFifoLayers($logs, (float) $row->ss_stock, $row->ss_created_at);
                $m = self::metricsFromFifoLayers($layers, $asOf);

                $qty = (float) $row->ss_stock;
                $price = (float) ($row->last_harga ?? 0);
                $out[] = [
                    'sumber' => 'bahan',
                    'product_variant_id' => null,
                    'supplies_id' => (int) $row->supplies_id,
                    'item_label' => (string) ($row->supplies_name ?? ''),
                    'variant_name' => '',
                    'unit_name' => (string) ($row->unit_name ?? ''),
                    'qty' => $qty,
                    'qty_display' => (string) (int) round($qty),
                    'weighted_age_days' => $m['weighted_age_days'],
                    'oldest_layer_date' => $m['oldest_layer_date'],
                    'bucket' => $m['bucket'],
                    'unit_price' => $price,
                    'stock_value' => round($qty * $price, 2),
                ];
            }
        }

        usort($out, function ($a, $b) {
            $ba = (float) ($b['weighted_age_days'] ?? 0);
            $aa = (float) ($a['weighted_age_days'] ?? 0);
            if ($ba !== $aa) {
                return $ba <=> $aa;
            }

            return strcmp((string) ($a['item_label'] ?? ''), (string) ($b['item_label'] ?? ''));
        });

        return $out;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $logsOrdered
     * @return array<int, array{qty: float, date: \Carbon\Carbon, synthetic?: bool}>
     */
    private static function simulateFifoLayers($logsOrdered, float $targetStock, $createdAtFallback): array
    {
        $layers = [];
        foreach ($logsOrdered as $row) {
            $qty = abs((float) ($row->log_jumlah ?? 0));
            if ($qty < 0.0000001) {
                continue;
            }
            $d = \Carbon\Carbon::parse($row->log_date);
            $cat = (int) ($row->log_category ?? 0);
            if ($cat === 1) {
                self::fifoPush($layers, $qty, $d);
            } elseif ($cat === 2) {
                self::fifoConsume($layers, $qty);
            }
        }

        $sum = 0.0;
        foreach ($layers as $l) {
            $sum += (float) $l['qty'];
        }

        if ($sum < $targetStock - 0.0001) {
            $gap = $targetStock - $sum;
            $fb = null;
            if ($createdAtFallback) {
                try {
                    $fb = \Carbon\Carbon::parse($createdAtFallback)->startOfDay();
                } catch (\Throwable $e) {
                    $fb = null;
                }
            }
            if ($fb === null) {
                $fb = \Carbon\Carbon::now()->startOfDay();
            }
            array_unshift($layers, ['qty' => $gap, 'date' => $fb, 'synthetic' => true]);
        } elseif ($sum > $targetStock + 0.0001) {
            $over = $sum - $targetStock;
            for ($i = count($layers) - 1; $i >= 0 && $over > 0.0001; $i--) {
                $take = min((float) $layers[$i]['qty'], $over);
                $layers[$i]['qty'] = (float) $layers[$i]['qty'] - $take;
                $over -= $take;
                if ($layers[$i]['qty'] < 0.0001) {
                    array_splice($layers, $i, 1);
                }
            }
        }

        return array_values(array_filter($layers, function ($l) {
            return ((float) $l['qty']) > 0.0001;
        }));
    }

    /**
     * @param  array<int, array{qty: float, date: \Carbon\Carbon, synthetic?: bool}>  $layers
     * @return array{weighted_age_days: float, oldest_layer_date: ?string, bucket: string}
     */
    private static function metricsFromFifoLayers(array $layers, \Carbon\Carbon $asOf): array
    {
        if (count($layers) === 0) {
            return [
                'weighted_age_days' => 0.0,
                'oldest_layer_date' => null,
                'bucket' => '-',
            ];
        }

        $as = $asOf->copy()->startOfDay();
        $totalQty = 0.0;
        $weighted = 0.0;
        $oldest = null;

        foreach ($layers as $l) {
            $ld = $l['date']->copy()->startOfDay();
            if ($oldest === null || $ld->lt($oldest)) {
                $oldest = $ld->copy();
            }
            $days = $ld->diffInDays($as);
            if ($ld->gt($as)) {
                $days = 0;
            }
            $q = (float) $l['qty'];
            $totalQty += $q;
            $weighted += $q * (float) $days;
        }

        $wAge = $totalQty > 0.0000001 ? $weighted / $totalQty : 0.0;

        return [
            'weighted_age_days' => round($wAge, 1),
            'oldest_layer_date' => $oldest ? $oldest->toDateString() : null,
            'bucket' => self::agingBucketFromDays((int) round($wAge)),
        ];
    }

    private static function agingBucketFromDays(int $d): string
    {
        if ($d <= 30) {
            return '0-30 hari';
        }
        if ($d <= 60) {
            return '31-60 hari';
        }
        if ($d <= 90) {
            return '61-90 hari';
        }
        if ($d <= 180) {
            return '91-180 hari';
        }

        return '>180 hari';
    }

    /**
     * @param  array<int, array{qty: float, date: \Carbon\Carbon, synthetic?: bool}>  $layers
     */
    private static function fifoPush(array &$layers, float $qty, \Carbon\Carbon $date): void
    {
        if ($qty < 0.0000001) {
            return;
        }
        $layers[] = ['qty' => $qty, 'date' => $date->copy()];
    }

    /**
     * @param  array<int, array{qty: float, date: \Carbon\Carbon, synthetic?: bool}>  $layers
     */
    private static function fifoConsume(array &$layers, float $qtyOut): void
    {
        $remain = $qtyOut;
        while ($remain > 0.0000001 && count($layers) > 0) {
            $head = &$layers[0];
            $take = min((float) $head['qty'], $remain);
            $head['qty'] = (float) $head['qty'] - $take;
            $remain -= $take;
            if ($head['qty'] < 0.0000001) {
                array_shift($layers);
            }
            unset($head);
        }
    }
}
