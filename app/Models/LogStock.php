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
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('l.log_category', 2)
                        ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%');
                })->orWhere(function ($q3) {
                    $q3->where('l.log_category', 1)
                        ->where('l.log_notes', 'like', '%Pengembalian stok bahan akibat pembatalan produksi%');
                });
            })
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
            $qty = (int) $row->log_jumlah;
            if (stripos((string) ($row->log_notes ?? ''), 'Pengembalian stok bahan akibat pembatalan produksi') !== false) {
                $qty = -abs($qty);
            }
            $grouped[$key]["details"][] = [
                "production_date" => $row->production_date ?: date('Y-m-d', strtotime($row->log_date)),
                "log_date" => $row->log_date,
                "production_code" => $row->log_kode,
                "qty" => $qty,
                "unit_name" => $row->unit_name,
                "notes" => $row->log_notes,
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
     * Dashboard pemakaian bahan: agregat per bulan (grafik & KPI, tanpa prediksi).
     * Net qty mengikuti logika getRawMaterialUsageReport (keluar produksi positif, pembatalan negatif).
     *
     * @param  array{months?:int, supplies_id?:int|null, supplier_id?:int|null}  $data
     * @return array<string, mixed>
     */
    function getRawMaterialUsageMonthlyDashboard($data = [])
    {
        $data = array_merge([
            'months' => 12,
            'supplies_id' => null,
            'supplier_id' => null,
        ], $data);

        $months = max(3, min(24, (int) $data['months']));
        $end = \Carbon\Carbon::now()->endOfMonth();
        $start = \Carbon\Carbon::now()->copy()->subMonths($months - 1)->startOfMonth();

        $supplierSupplies = null;
        if ($data['supplier_id']) {
            $supplierSupplies = DB::table('supplies_variants')
                ->where('supplier_id', $data['supplier_id'])
                ->pluck('supplies_id')
                ->unique()
                ->filter()
                ->values()
                ->all();
            if (count($supplierSupplies) === 0) {
                return self::emptyRawMaterialUsageDashboard($start, $end, $months);
            }
        }

        $base = DB::table('log_stocks as l')
            ->where('l.status', 1)
            ->where('l.log_type', 2)
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('l.log_category', 2)
                        ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%');
                })->orWhere(function ($q3) {
                    $q3->where('l.log_category', 1)
                        ->where('l.log_notes', 'like', '%Pengembalian stok bahan akibat pembatalan produksi%');
                });
            })
            ->whereBetween('l.log_date', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')]);

        if ($data['supplies_id']) {
            $base->where('l.log_item_id', (int) $data['supplies_id']);
        }
        if ($supplierSupplies !== null) {
            $base->whereIn('l.log_item_id', $supplierSupplies);
        }

        $monthly = (clone $base)
            ->selectRaw("DATE_FORMAT(l.log_date, '%Y-%m') as ym")
            ->selectRaw('SUM(CASE WHEN l.log_notes LIKE ? THEN -ABS(l.log_jumlah) ELSE l.log_jumlah END) as net_qty', ['%Pengembalian stok bahan akibat pembatalan produksi%'])
            ->selectRaw('COUNT(*) as txn_count')
            ->groupBy(DB::raw("DATE_FORMAT(l.log_date, '%Y-%m')"))
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $ymKeys = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $ymKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $netSeries = [];
        $txnSeries = [];
        $chartLabels = [];
        foreach ($ymKeys as $ym) {
            $row = $monthly->get($ym);
            $netSeries[] = $row ? (float) $row->net_qty : 0.0;
            $txnSeries[] = $row ? (int) $row->txn_count : 0;
            try {
                $chartLabels[] = \Carbon\Carbon::createFromFormat('Y-m', $ym)->locale('id')->translatedFormat('M Y');
            } catch (\Throwable $e) {
                $chartLabels[] = $ym;
            }
        }

        $n = count($netSeries);

        $thisMonth = \Carbon\Carbon::now()->format('Y-m');
        $prevMonth = \Carbon\Carbon::now()->copy()->subMonth()->format('Y-m');
        $thisRow = $monthly->get($thisMonth);
        $prevRow = $monthly->get($prevMonth);
        $thisNet = $thisRow ? (float) $thisRow->net_qty : ($n >= 1 ? $netSeries[$n - 1] : 0.0);
        $prevNet = $prevRow ? (float) $prevRow->net_qty : 0.0;
        $momPct = $prevNet != 0.0 ? round((($thisNet - $prevNet) / $prevNet) * 100, 1) : null;

        $thisTxn = $thisRow ? (int) $thisRow->txn_count : ($n >= 1 ? $txnSeries[$n - 1] : 0);
        $prevTxn = $prevRow ? (int) $prevRow->txn_count : 0;
        $momTxnPct = $prevTxn > 0 ? round((($thisTxn - $prevTxn) / $prevTxn) * 100, 1) : null;

        $topCandidates = (clone $base)
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'l.log_item_id')
            ->leftJoin('units as du', 'du.unit_id', '=', 's.supplies_default_unit')
            ->select('l.log_item_id as supplies_id', 's.supplies_name as name')
            ->selectRaw(
                "MAX(COALESCE(NULLIF(TRIM(du.unit_short_name), ''), NULLIF(TRIM(du.unit_name), ''), '-')) as unit_label"
            )
            ->selectRaw('SUM(CASE WHEN l.log_notes LIKE ? THEN -ABS(l.log_jumlah) ELSE l.log_jumlah END) as net_qty', ['%Pengembalian stok bahan akibat pembatalan produksi%'])
            ->groupBy('l.log_item_id', 's.supplies_name')
            ->orderByDesc('net_qty')
            ->limit(120)
            ->get();

        [$topKemasan, $topBahan] = self::splitTopMaterialsByPackagingHeuristic($topCandidates->all(), 8);

        $lowStockCount = (int) DB::table('supplies')
            ->where('status', 1)
            ->where('supplies_alert', '>', 0)
            ->whereRaw(
                '(SELECT COALESCE(SUM(ss_stock), 0) FROM supplies_stocks ss WHERE ss.supplies_id = supplies.supplies_id AND ss.status = 1) < supplies.supplies_alert'
            )
            ->count();

        return [
            'range' => [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
            ],
            'months' => $months,
            'disclaimer' => 'Grafik: total qty net log per bulan. Tabel top & estimasi: angka + satuan default master (supplies). Pisah kemasan vs lainnya dari pola nama. Rincian di laporan bahan baku.',
            'series' => [
                'labels' => $chartLabels,
                'ym_keys' => $ymKeys,
                'net_qty' => $netSeries,
                'txn_count' => $txnSeries,
            ],
            'kpis' => [
                'this_month_net' => $thisNet,
                'prev_month_net' => $prevNet,
                'mom_net_pct' => $momPct,
                'this_month_txn' => $thisTxn,
                'prev_month_txn' => $prevTxn,
                'mom_txn_pct' => $momTxnPct,
                'window_net_total' => round(array_sum($netSeries), 2),
                'window_txn_total' => array_sum($txnSeries),
            ],
            'top_materials_kemasan' => $topKemasan,
            'top_materials_bahan' => $topBahan,
            'low_stock_count' => $lowStockCount,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyRawMaterialUsageDashboard(\Carbon\Carbon $start, \Carbon\Carbon $end, int $months)
    {
        return [
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'months' => $months,
            'disclaimer' => 'Tidak ada bahan untuk supplier yang dipilih.',
            'series' => ['labels' => [], 'ym_keys' => [], 'net_qty' => [], 'txn_count' => []],
            'kpis' => [
                'this_month_net' => 0,
                'prev_month_net' => 0,
                'mom_net_pct' => null,
                'this_month_txn' => 0,
                'prev_month_txn' => 0,
                'mom_txn_pct' => null,
                'window_net_total' => 0,
                'window_txn_total' => 0,
            ],
            'top_materials_kemasan' => [],
            'top_materials_bahan' => [],
            'low_stock_count' => 0,
        ];
    }

    /**
     * Heuristik: kemasan / wadah / penunjang (botol, jerigen, tutup, sticker, …) vs sisa nama.
     * Mengandalkan pola pada nama master (supplies_name); tidak memakai kolom kategori DB.
     */
    private static function suppliesNameLooksLikePackaging(?string $name): bool
    {
        if ($name === null || $name === '') {
            return false;
        }
        $n = mb_strtolower($name, 'UTF-8');

        $pattern = '/(' .
            'botol|tutup|penutup|tutup\s*tumpul|closure|cap\\b|lid\\b|' .
            'jerigen|jurigen|jerrycan|\\bgalon\\b|\\bibc\\b|sumpel|penyumbat|' .
            'sticker|stiker|stickers|label|etiket|hang[\\s\\-]*tag|' .
            'kardus|karton|\\bdus\\b|\\bdos\\b|inner\\s*master|master\\s*box|' .
            'kemasan|packing|pembungkus|wrapper|wrapping|' .
            'pouch|sachet|tube|kaleng|vial|ampul|' .
            'shrink|sleeve|segel|lakban|isolasi|isolatif|' .
            'foam|gabus|bubble|pipet|dropper|spray|pump|nozzle|' .
            'paper\\s*cup|\\bcup\\b|gelas\\s*plastik|kantong\\s*plastik|plastik\\s*roll|' .
            '\\bpet\\b|\\bhdpe\\b|\\bpp\\b' .
            ')/iu';

        return (bool) preg_match($pattern, $n);
    }

    /**
     * @param  array<int, object>  $orderedCandidates  baris supplies_id, name, net_qty — sudah urut net_qty desc
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private static function splitTopMaterialsByPackagingHeuristic(array $orderedCandidates, int $limitEach): array
    {
        $kemasan = [];
        $bahan = [];
        foreach ($orderedCandidates as $r) {
            $name = is_object($r) ? ($r->name ?? '') : ($r['name'] ?? '');
            $unitRaw = trim((string) (is_object($r) ? ($r->unit_label ?? '') : ($r['unit_label'] ?? '')));
            $unit = ($unitRaw !== '' && $unitRaw !== '-') ? $unitRaw : '-';
            $entry = [
                'supplies_id' => (int) (is_object($r) ? $r->supplies_id : $r['supplies_id']),
                'name' => is_object($r) ? ($r->name ?? '-') : ($r['name'] ?? '-'),
                'net_qty' => round((float) (is_object($r) ? $r->net_qty : $r['net_qty']), 2),
                'unit' => $unit,
            ];
            if (self::suppliesNameLooksLikePackaging($name)) {
                if (count($kemasan) < $limitEach) {
                    $kemasan[] = $entry;
                }
            } elseif (count($bahan) < $limitEach) {
                $bahan[] = $entry;
            }
        }

        return [$kemasan, $bahan];
    }

    /**
     * @param  \stdClass  $t  supplies_id, name, net_qty
     * @param  array<int, string>  $ymKeys
     * @param  array<int, array<string, float>>  $pivot
     * @param  \Illuminate\Support\Collection<int, mixed>  $stockById
     * @return array<string, mixed>
     */
    private static function buildProcurementEstimateRow(object $t, array $ymKeys, array $pivot, $stockById, int $months): array
    {
        $sid = (int) $t->supplies_id;
        $series = [];
        foreach ($ymKeys as $ym) {
            $series[] = isset($pivot[$sid][$ym]) ? (float) $pivot[$sid][$ym] : 0.0;
        }
        $forecast = self::forecastNetQtyFromMonthlySeries($series);
        $windowTotal = (float) $t->net_qty;
        $avgPerMonth = round($windowTotal / $months, 2);
        $stok = isset($stockById[$sid]) ? (float) $stockById[$sid] : 0.0;
        $gap = max(0.0, round($forecast - $stok, 2));

        $unitRaw = trim((string) ($t->unit_label ?? ''));
        $unit = ($unitRaw !== '' && $unitRaw !== '-') ? $unitRaw : '-';

        return [
            'supplies_id' => $sid,
            'name' => $t->name ?? '-',
            'unit' => $unit,
            'window_total' => round($windowTotal, 2),
            'avg_per_month' => $avgPerMonth,
            'estimate_next_month' => $forecast,
            'stock_agg' => round($stok, 2),
            'gap_to_buy' => $gap,
        ];
    }

    /**
     * Perkiraan qty net bulan berikutnya dari deret bulanan (sama seperti rumus dashboard lama).
     *
     * @param  array<int, float>  $series
     */
    private static function forecastNetQtyFromMonthlySeries(array $series): float
    {
        $n = count($series);
        if ($n === 0) {
            return 0.0;
        }
        $last3 = array_slice($series, -min(3, $n));
        $avg3 = array_sum($last3) / count($last3);
        $trend = $n >= 2 ? ($series[$n - 1] - $series[$n - 2]) : 0.0;

        return max(0.0, round($avg3 + 0.35 * $trend, 2));
    }

    /**
     * Estimasi pembelian bahan mentah bulan depan: bahan dengan pemakaian produksi tertinggi (dari log),
     * perkiraan per bahan = rata-rata 3 bulan terakhir + 0,35 × tren bulan terakhir.
     *
     * @param  array{months?:int, top?:int}  $data
     * @return array<string, mixed>
     */
    function getProcurementEstimateProductionMaterials(array $data = []): array
    {
        $data = array_merge([
            'months' => 6,
            'top' => 12,
        ], $data);

        $months = max(3, min(24, (int) $data['months']));
        $top = max(5, min(40, (int) $data['top']));
        $end = \Carbon\Carbon::now()->endOfMonth();
        $start = \Carbon\Carbon::now()->copy()->subMonths($months - 1)->startOfMonth();

        $ymKeys = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $ymKeys[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        $cancelLike = '%Pengembalian stok bahan akibat pembatalan produksi%';

        $allTotals = DB::table('log_stocks as l')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'l.log_item_id')
            ->leftJoin('units as du', 'du.unit_id', '=', 's.supplies_default_unit')
            ->where('l.status', 1)
            ->where('l.log_type', 2)
            ->whereNotNull('l.log_item_id')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('l.log_category', 2)
                        ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%');
                })->orWhere(function ($q3) {
                    $q3->where('l.log_category', 1)
                        ->where('l.log_notes', 'like', '%Pengembalian stok bahan akibat pembatalan produksi%');
                });
            })
            ->whereBetween('l.log_date', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->select('l.log_item_id as supplies_id', 's.supplies_name as name')
            ->selectRaw(
                "MAX(COALESCE(NULLIF(TRIM(du.unit_short_name), ''), NULLIF(TRIM(du.unit_name), ''), '-')) as unit_label"
            )
            ->selectRaw('SUM(CASE WHEN l.log_notes LIKE ? THEN -ABS(l.log_jumlah) ELSE l.log_jumlah END) as net_qty', [$cancelLike])
            ->groupBy('l.log_item_id', 's.supplies_name')
            ->orderByDesc('net_qty')
            ->limit(200)
            ->get();

        if ($allTotals->isEmpty()) {
            return [
                'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'months' => $months,
                'next_month_label' => '-',
                'rows_kemasan' => [],
                'rows_bahan' => [],
                'disclaimer' => 'Belum ada log pemakaian bahan untuk produksi di rentang ini.',
            ];
        }

        $totalsKemasan = [];
        $totalsBahan = [];
        foreach ($allTotals as $row) {
            if (self::suppliesNameLooksLikePackaging($row->name ?? '')) {
                if (count($totalsKemasan) < $top) {
                    $totalsKemasan[] = $row;
                }
            } elseif (count($totalsBahan) < $top) {
                $totalsBahan[] = $row;
            }
        }

        $totalsMerged = array_merge($totalsKemasan, $totalsBahan);
        if ($totalsMerged === []) {
            return [
                'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
                'months' => $months,
                'next_month_label' => '-',
                'rows_kemasan' => [],
                'rows_bahan' => [],
                'disclaimer' => 'Belum ada log pemakaian bahan untuk produksi di rentang ini.',
            ];
        }

        $ids = collect($totalsMerged)->pluck('supplies_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $pivotRows = DB::table('log_stocks as l')
            ->where('l.status', 1)
            ->where('l.log_type', 2)
            ->whereNotNull('l.log_item_id')
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('l.log_category', 2)
                        ->where('l.log_notes', 'like', '%Pengurangan bahan untuk produksi%');
                })->orWhere(function ($q3) {
                    $q3->where('l.log_category', 1)
                        ->where('l.log_notes', 'like', '%Pengembalian stok bahan akibat pembatalan produksi%');
                });
            })
            ->whereBetween('l.log_date', [$start->format('Y-m-d 00:00:00'), $end->format('Y-m-d 23:59:59')])
            ->whereIn('l.log_item_id', $ids)
            ->select('l.log_item_id as supplies_id')
            ->selectRaw("DATE_FORMAT(l.log_date, '%Y-%m') as ym")
            ->selectRaw('SUM(CASE WHEN l.log_notes LIKE ? THEN -ABS(l.log_jumlah) ELSE l.log_jumlah END) as net_qty', [$cancelLike])
            ->groupBy('l.log_item_id', DB::raw("DATE_FORMAT(l.log_date, '%Y-%m')"))
            ->orderBy('supplies_id')
            ->orderBy('ym')
            ->get();

        $pivot = [];
        foreach ($pivotRows as $pr) {
            $sid = (int) $pr->supplies_id;
            if (! isset($pivot[$sid])) {
                $pivot[$sid] = [];
            }
            $pivot[$sid][$pr->ym] = (float) $pr->net_qty;
        }

        $stockById = DB::table('supplies_stocks')
            ->where('status', 1)
            ->whereIn('supplies_id', $ids)
            ->selectRaw('supplies_id, SUM(ss_stock) as qty')
            ->groupBy('supplies_id')
            ->pluck('qty', 'supplies_id');

        $nextMonth = \Carbon\Carbon::now()->copy()->addMonth()->startOfMonth();
        try {
            $nextLabel = $nextMonth->locale('id')->monthName . ' ' . $nextMonth->year;
        } catch (\Throwable $e) {
            $nextLabel = $nextMonth->format('Y-m');
        }

        $rowsKemasan = [];
        foreach ($totalsKemasan as $t) {
            $rowsKemasan[] = self::buildProcurementEstimateRow($t, $ymKeys, $pivot, $stockById, $months);
        }
        $rowsBahan = [];
        foreach ($totalsBahan as $t) {
            $rowsBahan[] = self::buildProcurementEstimateRow($t, $ymKeys, $pivot, $stockById, $months);
        }

        return [
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'months' => $months,
            'next_month_label' => $nextLabel,
            'rows_kemasan' => $rowsKemasan,
            'rows_bahan' => $rowsBahan,
            'disclaimer' => 'Estimasi dari log produksi; satuan = default master per baris. Bukan perintah pembelian formal.',
        ];
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
