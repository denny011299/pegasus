<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Cash;
use App\Models\CashAdmin;
use App\Models\CashAdminDetail;
use App\Models\CashArmada;
use App\Models\CashArmadaDetail;
use App\Models\CashCategory;
use App\Models\CashGudang;
use App\Models\CashGudangDetail;
use App\Models\CashSales;
use App\Models\CashSalesDetail;
use App\Models\Customer;
use App\Models\InwardOutward;
use App\Models\LogStock;
use App\Models\PettyCash;
use App\Models\Product;
use App\Models\ProductIssues;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\ReportLoss;
use App\Models\ReportProfit;
use App\Models\Role;
use App\Models\Production;
use App\Models\ProductVariant;
use App\Models\ReturnSupplies;
use App\Models\Supplies;
use App\Models\Staff;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    private function parseSelisihNumeric($selisihText): float
    {
        if ($selisihText === null) return 0;
        preg_match('/-?\d+(?:\.\d+)?/', (string) $selisihText, $m);
        if (!isset($m[0])) return 0;
        return (float) $m[0];
    }

    private function mergePdfPrintMeta(array $param): array
    {
        $u = session()->get('user');
        $param['printed_by'] = $u ? ($u->staff_name ?? '-') : '-';
        $param['printed_at'] = now()->format('d/m/Y H:i');
        return $param;
    }

    /**
     * Widget ringkas: penjualan (SO), produksi, pembelian (PO) — bulan berjalan vs bulan lalu.
     *
     * @return array<string, array<string, mixed>>
     */
    private function buildDashboardCrossWidgets(): array
    {
        $fmtRp = static function (float $n): string {
            return 'Rp ' . number_format($n, 0, ',', '.');
        };

        $fallback = static function (string $title, string $subtitle, string $href): array {
            return [
                'title' => $title,
                'subtitle' => $subtitle,
                'primary' => 0,
                'primary_label' => '—',
                'secondary' => '—',
                'mom_pct' => null,
                'href' => $href,
                'meta' => ['icon' => 'fe-activity'],
            ];
        };

        try {
            $start = \Carbon\Carbon::now()->startOfMonth();
            $end = \Carbon\Carbon::now()->endOfMonth();
            $pStart = $start->copy()->subMonth()->startOfMonth();
            $pEnd = $pStart->copy()->endOfMonth();

            $startD = $start->toDateString();
            $endD = $end->toDateString();
            $pStartD = $pStart->toDateString();
            $pEndD = $pEnd->toDateString();

            // Hindari translatedFormat('MMM…') yang bisa berakhir ganda di beberapa lingkungan PHP/intl
            try {
                $monthLabel = $start->copy()->locale('id')->monthName . ' ' . $start->year;
            } catch (\Throwable $e) {
                $monthLabel = $start->format('Y-m');
            }

            $salesThis = DB::table('sales_orders')->where('status', '>=', 1)
                ->whereRaw('DATE(COALESCE(so_date, created_at)) BETWEEN ? AND ?', [$startD, $endD]);
            $salesCnt = (clone $salesThis)->count();
            $salesSum = (float) ((clone $salesThis)->sum('so_total') ?? 0);

            $salesPrev = DB::table('sales_orders')->where('status', '>=', 1)
                ->whereRaw('DATE(COALESCE(so_date, created_at)) BETWEEN ? AND ?', [$pStartD, $pEndD]);
            $salesCntPrev = (clone $salesPrev)->count();
            $momSales = $salesCntPrev > 0 ? round((($salesCnt - $salesCntPrev) / $salesCntPrev) * 100, 1) : null;

            $prodThis = DB::table('productions')->where('status', '>=', 1)
                ->whereBetween('production_date', [$startD, $endD]);
            $prodCnt = (clone $prodThis)->count();
            $mixRows = (clone $prodThis)->select('status', DB::raw('COUNT(*) as c'))->groupBy('status')->get();
            $mix = [];
            foreach ($mixRows as $row) {
                $mix[(string) $row->status] = (int) $row->c;
            }
            $acc = (int) ($mix['2'] ?? 0);
            $wait = (int) ($mix['1'] ?? 0) + (int) ($mix['4'] ?? 0);
            $rej = (int) ($mix['3'] ?? 0);

            $prodPrev = DB::table('productions')->where('status', '>=', 1)
                ->whereBetween('production_date', [$pStartD, $pEndD]);
            $prodCntPrev = (clone $prodPrev)->count();
            $momProd = $prodCntPrev > 0 ? round((($prodCnt - $prodCntPrev) / $prodCntPrev) * 100, 1) : null;

            $poThis = DB::table('purchase_orders')
                ->where('status', '!=', 0)
                ->where('status', '>=', -1)
                ->whereBetween('po_date', [$startD, $endD]);
            $poCnt = (clone $poThis)->count();
            $poSum = (float) ((clone $poThis)->sum('po_total') ?? 0);

            $poPrev = DB::table('purchase_orders')
                ->where('status', '!=', 0)
                ->where('status', '>=', -1)
                ->whereBetween('po_date', [$pStartD, $pEndD]);
            $poCntPrev = (clone $poPrev)->count();
            $momPo = $poCntPrev > 0 ? round((($poCnt - $poCntPrev) / $poCntPrev) * 100, 1) : null;

            return [
                'sales' => [
                    'title' => 'Pengiriman',
                    'subtitle' => 'Pengiriman · ' . $monthLabel,
                    'primary' => $salesCnt,
                    'primary_label' => 'pengiriman',
                    'secondary' => $fmtRp($salesSum),
                    'mom_pct' => $momSales,
                    'href' => url('/salesOrder'),
                    'meta' => ['icon' => 'fe-truck'],
                ],
                'production' => [
                    'title' => 'Produksi',
                    'subtitle' => 'Batch · ' . $monthLabel,
                    'primary' => $prodCnt,
                    'primary_label' => 'Batch',
                    'secondary' => 'ACC ' . $acc . ' · Tunggu ' . $wait . ($rej > 0 ? ' · Tolak ' . $rej : ''),
                    'mom_pct' => $momProd,
                    'href' => url('/production'),
                    'meta' => ['icon' => 'fe-layers'],
                ],
                'purchase' => [
                    'title' => 'Pembelian',
                    'subtitle' => 'Purchase order · ' . $monthLabel,
                    'primary' => $poCnt,
                    'primary_label' => 'PO',
                    'secondary' => $fmtRp($poSum),
                    'mom_pct' => $momPo,
                    'href' => url('/purchaseOrder'),
                    'meta' => ['icon' => 'fe-shopping-cart'],
                ],
            ];
        } catch (\Throwable $e) {
            return [
                'sales' => $fallback('Penjualan', 'Sales order', url('/salesOrder')),
                'production' => $fallback('Produksi', 'Produksi', url('/production')),
                'purchase' => $fallback('Pembelian', 'Purchase order', url('/purchaseOrder')),
            ];
        }
    }

    /**
     * Seri bulanan untuk grafik dashboard (SO, produksi, PO).
     *
     * @return array{labels: array<int, string>, sales_count: array<int, int>, production_count: array<int, int>, purchase_count: array<int, int>, sales_total: array<int, float>, purchase_total: array<int, float>}
     */
    private function buildDashboardExecutiveChartSeries(int $months): array
    {
        $months = max(3, min(18, $months));
        $empty = static function (int $n): array {
            return [
                'labels' => array_fill(0, $n, '-'),
                'sales_count' => array_fill(0, $n, 0),
                'production_count' => array_fill(0, $n, 0),
                'purchase_count' => array_fill(0, $n, 0),
                'sales_total' => array_fill(0, $n, 0.0),
                'purchase_total' => array_fill(0, $n, 0.0),
            ];
        };

        try {
            $rangeEnd = \Carbon\Carbon::now()->endOfMonth();
            $rangeStart = \Carbon\Carbon::now()->copy()->subMonths($months - 1)->startOfMonth();

            $ymKeys = [];
            $labels = [];
            $cursor = $rangeStart->copy();
            while ($cursor->lte($rangeEnd)) {
                $ymKeys[] = $cursor->format('Y-m');
                // Label pendek numerik (hindari artefak render Apex + locale di sumbu X)
                $labels[] = $cursor->format('m/y');
                $cursor->addMonth();
            }

            $n = count($ymKeys);
            if ($n === 0) {
                return $empty(1);
            }

            $startD = $rangeStart->toDateString();
            $endD = $rangeEnd->toDateString();

            $salesRows = DB::table('sales_orders')
                ->where('status', '>=', 1)
                ->whereRaw('DATE(COALESCE(so_date, created_at)) BETWEEN ? AND ?', [$startD, $endD])
                ->selectRaw("DATE_FORMAT(DATE(COALESCE(so_date, created_at)), '%Y-%m') as ym")
                ->selectRaw('COUNT(*) as cnt')
                ->selectRaw('SUM(COALESCE(so_total, 0)) as total')
                ->groupBy('ym')
                ->get()
                ->keyBy('ym');

            $prodRows = DB::table('productions')
                ->where('status', '>=', 1)
                ->whereBetween('production_date', [$startD, $endD])
                ->selectRaw("DATE_FORMAT(production_date, '%Y-%m') as ym")
                ->selectRaw('COUNT(*) as cnt')
                ->groupBy('ym')
                ->get()
                ->keyBy('ym');

            $poRows = DB::table('purchase_orders')
                ->where('status', '!=', 0)
                ->where('status', '>=', -1)
                ->whereBetween('po_date', [$startD, $endD])
                ->selectRaw("DATE_FORMAT(po_date, '%Y-%m') as ym")
                ->selectRaw('COUNT(*) as cnt')
                ->selectRaw('SUM(COALESCE(po_total, 0)) as total')
                ->groupBy('ym')
                ->get()
                ->keyBy('ym');

            $salesCount = [];
            $salesTotal = [];
            $prodCount = [];
            $poCount = [];
            $poTotal = [];
            foreach ($ymKeys as $ym) {
                $sRow = $salesRows->get($ym);
                $pRow = $prodRows->get($ym);
                $poRow = $poRows->get($ym);
                $salesCount[] = $sRow ? (int) $sRow->cnt : 0;
                $salesTotal[] = $sRow ? (float) $sRow->total : 0.0;
                $prodCount[] = $pRow ? (int) $pRow->cnt : 0;
                $poCount[] = $poRow ? (int) $poRow->cnt : 0;
                $poTotal[] = $poRow ? (float) $poRow->total : 0.0;
            }

            return [
                'labels' => $labels,
                'sales_count' => $salesCount,
                'sales_total' => $salesTotal,
                'production_count' => $prodCount,
                'purchase_count' => $poCount,
                'purchase_total' => $poTotal,
            ];
        } catch (\Throwable $e) {
            return $empty(6);
        }
    }

    private function hasNonZeroSelisih($selisihText): bool
    {
        if ($selisihText === null) return false;
        preg_match_all('/-?\d+(?:\.\d+)?/', (string) $selisihText, $matches);
        if (!isset($matches[0]) || count($matches[0]) === 0) return false;
        foreach ($matches[0] as $num) {
            if ((float) $num != 0.0) return true;
        }
        return false;
    }

    /**
     * PDF: hanya baris detail yang benar-benar ber-selisih; hitung ulang ringkasan per kode opname.
     *
     * @param  mixed  $data  Array hasil getReportSelisihOpname (bisa nested stdClass dari JSON).
     * @return array<int, array<string, mixed>>
     */
    private function selisihOpnamePdfRowsOnlySelisih($data): array
    {
        if (!is_array($data)) {
            return [];
        }
        $out = [];
        foreach ($data as $row) {
            $row = json_decode(json_encode($row), true);
            if (!is_array($row)) {
                continue;
            }
            $detailsIn = $row['details'] ?? [];
            if (!is_array($detailsIn)) {
                continue;
            }
            $details = [];
            foreach ($detailsIn as $d) {
                $d = json_decode(json_encode($d), true);
                if (!is_array($d)) {
                    continue;
                }
                $text = $d['selisih_text'] ?? '';
                if (!$this->hasNonZeroSelisih($text)) {
                    continue;
                }
                $qty = isset($d['selisih_qty']) ? (float) $d['selisih_qty'] : $this->parseSelisihNumeric($text);
                if (abs($qty) < 0.0000001) {
                    continue;
                }
                $details[] = $d;
            }
            if (count($details) === 0) {
                continue;
            }
            $nominalTotal = 0.0;
            foreach ($details as $d) {
                $nominalTotal += (float) ($d['nominal'] ?? 0);
            }
            $row['details'] = $details;
            $row['total_item_selisih'] = count($details);
            $row['total_nominal'] = $nominalTotal;
            $out[] = $row;
        }

        return $out;
    }

    // Report Profit Loss
    public function ProfitLoss(){
        return view('Backoffice.Reports.Profit_Loss');
    }

    function getProfit(Request $req){
        $data = (new ReportProfit())->getProfit();
        return response()->json($data);
    }

    function getLoss(Request $req){
        $data = (new ReportLoss())->getLoss();
        return response()->json($data);
    }

    // Report Payables Receiveables
    public function PayReceive(){
        return view('Backoffice.Reports.Pay_Receive');
    }

    public function checkHutang(Request $req)
    {
        $data = $req->all();
        $detail = (new PurchaseOrderDetailInvoice())->getPoInvoice($data);

        if (count($detail) <= 0) {
            return response()->json([
                'status' => -1,
                'message' => 'Minimal ada 1 data hutang'
            ]);
        }

        return response()->json([
            'status' => 1
        ]);
    }

    function generateHutang(Request $req) {
        $data = $req->all();
        $param["detail"] = (new PurchaseOrderDetailInvoice())->getPoInvoice($data);

        $total = 0;
        foreach ($param['detail'] as $key => $value) {
            $total += $value->poi_total;

            if ($value['pembayaran'] == 1 && $value['status'] == 1) $value['status_hutang'] = "Belum Terbayar";
            else if ($value['pembayaran'] == 2) $value['status_hutang'] = "Terbayar";
            else if ($value['pembayaran'] == 3) $value['status_hutang'] = "Menunggu Tanda Terima";
            else $value['status_hutang'] = "Ditolak";
        }
        $param['total'] = $total;

        $param['dates'] = $data['dates'] ?? "-";

        $bank_kode = $data['bank_id'] ? Bank::find($data['bank_id'])->bank_kode : "-";
        $param['bank_kode'] = $bank_kode;

        $supplier = $data['po_supplier'] ? Supplier::find($data['po_supplier'])->supplier_name : "-";
        $param['supplier_name'] = $supplier;

        $user = session('user');
        $param['user_name'] = $user->staff_name;

        $role = Role::find($user->role_id)->role_name ?? "-";
        $param['role_name'] = $role;

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.Hutang', $param);
        return $pdf->download('Hutang_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    // Report Inward Outward Goods
    public function InwardOutward(){
        return view('Backoffice.Reports.Inward_Outward');
    }

    function getInwardOutward(Request $req){
        $data = (new InwardOutward())->getInwardOutward();
        return response()->json($data);
    }

    // Report Cash
    public function Cash(){
        return view('Backoffice.Reports.Cash');
    }

    function getCash(Request $req){
        $data = (new Cash())->getCash($req->all());
        return response()->json($data);
    }

    function insertCash(Request $req){
        $data = $req->all();
        $cash_id = (new Cash())->insertCash($data);
        if (isset($data['cash_tujuan']) && $data['cash_tujuan'] != null) {
            if ($data['cash_tujuan'] == "admin"){
                (new CashAdmin())->insertCashAdmin([
                    "staff_id" => session('user')->staff_id,
                    "cash_id" => $cash_id,
                    "ca_nominal" => $data['cash_nominal'],
                    "ca_notes" => $data['cash_description'],
                    "ca_type" => 2, // kredit
                    "oc_transaksi" => 1, // Supaya jadi debit
                    "status" => 2
                ]);
            }
            else if ($data['cash_tujuan'] == "gudang"){
                (new CashGudang())->insertCashGudang([
                    "staff_id" => session('user')->staff_id,
                    "cash_id" => $cash_id,
                    "cg_nominal" => $data['cash_nominal'],
                    "cg_notes" => $data['cash_description'],
                    "cg_type" => 2, // kredit
                    "oc_transaksi" => 1, // Supaya jadi debit
                    "status" => 2
                ]);
            }
        }
    }

    // Report Petty Cash
    public function PettyCash(){
        return view('Backoffice.Reports.Petty_Cash');
    }

    function getPettyCash(Request $req){
        $data = (new PettyCash())->getPettyCash($req->all());
        return response()->json($data);
    }

    function insertPettyCash(Request $req){
        $data = $req->all();
        return (new PettyCash())->insertPettyCash($data);
    }

    // Report Kas Operasional
    public function OperationalCash(){
        return view('Backoffice.Reports.Cash_Operational');
    }

    function getCashAdmin(Request $req)
    {
        $data = (new CashAdmin())->getCashAdmin($req->all());
        return response()->json($data);
    }

    function insertCashAdmin(Request $req)
    {
        $data = $req->all();
        if ($req->photo){
            // Ambil base64
            $image = $req->photo;
    
            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
    
            // Decode
            $imageData = base64_decode($image);
    
            // Nama file
            $imageName = 'photo_' . time() . '.png';
    
            // Path tujuan di public/produksi
            $path = public_path('kas_admin/admin/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            $data["ca_img"] = $imageName;
        }


        if ($data['jenis_input'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);

            foreach ($item as $key => $value) {
                $total += $value['cad_nominal'];
            }

            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $data['ca_notes'] = "Pengeluaran admin " . $staff_name;
            $data['ca_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $notes = $data['ca_notes'] . " oleh admin " . $staff_name;
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $type = 3;
                $nominal = $data['ca_nominal'];
                if ($data['ca_nominal'] < 0) {
                    $type = 1;
                    $nominal = $data['ca_nominal'] - ($data['ca_nominal'] * 2);
                }
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // keluar 1
                    "cash_tujuan" => 1, // admin
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $type = 1;
                $nominal = $data['ca_nominal'];
                if ($data['ca_nominal'] < 0) {
                    $type = 2;
                    $nominal = $data['ca_nominal'] - ($data['ca_nominal'] * 2);
                }
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // debit
                    "cash_tujuan" => 1, // admin
                    "status" => 1
                ]);
            }
            $data['cash_id'] = $cash_id;
        }

        if ($data['jenis_input'] == "operasional"){
            $data['oc_transaksi'] = 0;
            $ca_id = (new CashAdmin())->insertCashAdmin($data);

            foreach ($item as $key => $value) {
                $value['ca_id'] = $ca_id;
                (new CashAdminDetail())->insertCashAdminDetail($value);
            }
        }
        
        else if ($data['jenis_input'] == "saldo") {
            $data['ca_type'] = 1;
            (new CashAdmin())->insertCashAdmin($data);
        }
    }

    function updateCashAdmin(Request $req)
    {
        $data = $req->all();

        if ($req->photo){
            // Ambil base64
            $image = $req->photo;
    
            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
    
            // Decode
            $imageData = base64_decode($image);
    
            // Nama file
            $imageName = 'photo_' . time() . '.png';
    
            // Path tujuan di public/produksi
            $path = public_path('kas_admin/admin/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            $data["ca_img"] = $imageName;
        }

        $id = [];
        $cash = CashAdmin::find($data['ca_id']);

        if ($data['jenis_input'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);

            foreach ($item as $key => $value) {
                $total += $value['cad_nominal'];
            }

            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $data['ca_notes'] = "Pengeluaran admin " . $staff_name;
            $data['ca_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $notes = $data['ca_notes'] . " oleh admin " . $staff_name;
            $data['ca_type'] = 1;
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $type = 3;
                $nominal = $data['ca_nominal'];
                if ($data['ca_nominal'] < 0) {
                    $type = 1;
                    $nominal = $data['ca_nominal'] - ($data['ca_nominal'] * 2);
                }
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // keluar 1
                    "cash_tujuan" => 1, // admin
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $type = 1;
                $nominal = $data['ca_nominal'];
                if ($data['ca_nominal'] < 0) {
                    $type = 2;
                    $nominal = $data['ca_nominal'] - ($data['ca_nominal'] * 2);
                }
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // debit
                    "cash_tujuan" => 1, // admin
                    "status" => 1
                ]);
            }
            $data['cash_id'] = $cash_id;
        }

        (new CashAdmin())->updateCashAdmin($data);

        if ($data['jenis_input'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);

            foreach ($item as $key => $value) {
                $value['ca_id'] = $data['ca_id'];

                if (!isset($value['cad_id']) || !$value['cad_id']){
                    $t = (new CashAdminDetail())->insertCashAdminDetail($value);
                }
                else {
                    $t = (new CashAdminDetail())->updateCashAdminDetail($value);
                }
                array_push($id, $t);
            }
            CashAdminDetail::where('ca_id', '=', $data["ca_id"])->whereNotIn("cad_id", $id)->update(["status" => 0]);
        }
    }

    function deleteCashAdmin(Request $req)
    {
        $data = $req->all();
        $ca = CashAdmin::find($data['ca_id']);
        (new CashAdmin())->deleteCashAdmin($data);
        // Kalau manajemen saldo, maka hapus dari kas juga
        if ($ca->ca_type == 1) (new Cash())->deleteCash($ca);
    }

    function acceptCashAdmin(Request $req)
    {
        $data = $req->all();
        return (new CashAdmin())->acceptCashAdmin($data);
    }

    function declineCashAdmin(Request $req)
    {
        $data = $req->all();
        return (new CashAdmin())->declineCashAdmin($data);
    }

    function getCashGudang(Request $req)
    {
        $data = (new CashGudang())->getCashGudang($req->all());
        return response()->json($data);
    }

    function insertCashGudang(Request $req)
    {
        $data = $req->all();
        if ($req->photo){
            // Ambil base64
            $image = $req->photo;
    
            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
    
            // Decode
            $imageData = base64_decode($image);
    
            // Nama file
            $imageName = 'photo_' . time() . '.png';
    
            // Path tujuan di public/produksi
            $path = public_path('kas_admin/gudang/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            $data["cg_img"] = $imageName;
        }

        if ($data['jenis_input'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);

            foreach ($item as $key => $value) {
                $total += $value['cgd_nominal'];
            }
            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $data['cg_notes'] = "Penyerahan kas armada dari gudang - " . $staff_name;
            $data['cg_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $notes = $data['cg_notes'] . " dari gudang - " . $staff_name;
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $type = 3;
                $nominal = $data['cg_nominal'];
                if ($data['cg_nominal'] < 0) {
                    $type = 1;
                    $nominal = $data['cg_nominal'] - ($data['cg_nominal'] * 2);
                }
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // keluar 1
                    "cash_tujuan" => 2, // gudang
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $type = 1;
                $nominal = $data['cg_nominal'];
                if ($data['cg_nominal'] < 0) {
                    $type = 2;
                    $nominal = $data['cg_nominal'] - ($data['cg_nominal'] * 2);
                }
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // debit
                    "cash_tujuan" => 2, // gudang
                    "status" => 1
                ]);
            }
            $data['cash_id'] = $cash_id;
        }

        if ($data['jenis_input'] == "operasional"){
            $data['oc_transaksi'] = 0;
            $cg_id = (new CashGudang())->insertCashGudang($data);

            foreach ($item as $key => $value) {
                $value['cg_id'] = $cg_id;
                (new CashGudangDetail())->insertCashGudangDetail($value);
            }
        }
        
        else if ($data['jenis_input'] == "saldo") {
            (new CashGudang())->insertCashGudang($data);
        }
    }

    function updateCashGudang(Request $req)
    {
        $data = $req->all();

        if ($req->photo){
            // Ambil base64
            $image = $req->photo;
    
            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
    
            // Decode
            $imageData = base64_decode($image);
    
            // Nama file
            $imageName = 'photo_' . time() . '.png';
    
            // Path tujuan di public/produksi
            $path = public_path('kas_admin/gudang/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            $data["cg_img"] = $imageName;
        }

        $id = [];
        $cash = CashGudang::find($data['cg_id']);

        if ($data['jenis_input'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);

            foreach ($item as $key => $value) {
                $total += $value['cgd_nominal'];
            }

            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $data['cg_notes'] = "Penyerahan kas armada dari gudang - " . $staff_name;
            $data['cg_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            $staff_name = Staff::find($data['staff_id'])->staff_name;
            $notes = $data['cg_notes'] . " dari gudang - " . $staff_name;
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $type = 3;
                $nominal = $data['cg_nominal'];
                if ($data['cg_nominal'] < 0) {
                    $type = 1;
                    $nominal = $data['cg_nominal'] - ($data['cg_nominal'] * 2);
                }
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // keluar 1
                    "cash_tujuan" => 2, // gudang
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $type = 1;
                $nominal = $data['cg_nominal'];
                if ($data['cg_nominal'] < 0) {
                    $type = 2;
                    $nominal = $data['cg_nominal'] - ($data['cg_nominal'] * 2);
                }
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // debit
                    "cash_tujuan" => 2, // gudang
                    "status" => 1
                ]);
            }
            $data['cash_id'] = $cash_id;
        }

        (new CashGudang())->updateCashGudang($data);

        if ($data['jenis_input'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);

            foreach ($item as $key => $value) {
                $value['cg_id'] = $data['cg_id'];

                if (!isset($value['cgd_id']) || !$value['cgd_id']){
                    $t = (new CashGudangDetail())->insertCashGudangDetail($value);
                }
                else {
                    $t = (new CashGudangDetail())->updateCashGudangDetail($value);
                }
                array_push($id, $t);
            }
            CashGudangDetail::where('cg_id', '=', $data["cg_id"])->whereNotIn("cgd_id", $id)->update(["status" => 0]);
        }
    }

    function deleteCashGudang(Request $req)
    {
        $data = $req->all();
        $ca = CashGudang::find($data['cg_id']);
        (new CashGudang())->deleteCashGudang($data);
        // Kalau manajemen saldo, maka hapus dari kas juga
        if ($ca->cg_type == 1) (new Cash())->deleteCash($ca);
    }

    function acceptCashGudang(Request $req)
    {
        $data = $req->all();

        if (isset($data['cg_id'])){
            $cgd = CashGudangDetail::where('cg_id', $data['cg_id'])->where('status', 1)->get();

            foreach ($cgd as $key => $value) {
                $customer = Customer::find($value['customer_id']);
                $customer->customer_saldo += $value['cgd_nominal'];
                $customer->save();
            }
        }
        return (new CashGudang())->acceptCashGudang($data);
    }

    function declineCashGudang(Request $req)
    {
        $data = $req->all();
        return (new CashGudang())->declineCashGudang($data);
    }

    function getCashArmada(Request $req)
    {
        $data = (new CashArmada())->getCashArmada($req->all());
        return response()->json($data);
    }

    function insertCashArmada(Request $req)
    {
        $data = $req->all();

        $total = 0;
        $customer = Customer::find($data['customer_id']);
        if ($data['oc_transaksi'] == "operasional"){
            $item = json_decode($data['items'], true);
    
            foreach ($item as $key => $value) {
                $total += $value['crd_nominal'];
            }

            if ($item[0]['crd_type'] != 1) {
                // if ($total > $customer->customer_saldo){
                //     return response()->json([
                //         "status" => 0,
                //         "header" => "Gagal Insert",
                //         "message" => "Saldo armada " . $customer->customer_notes . " tidak mencukupi"
                //     ]);
                // }
            }
        } else {
            // if ($data['cr_nominal'] > $customer->customer_saldo){
            //     return response()->json([
            //         "status" => 0,
            //         "header" => "Gagal Insert",
            //         "message" => "Saldo armada " . $customer->customer_notes . " tidak mencukupi"
            //     ]);
            // }
        }

        if ($data['photo']){
            $img = [];
            foreach (json_decode($data["photo"]) as $key => $value) {
                $image = $value;

                // Hilangkan prefix base64
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

                // Decode
                $imageData = base64_decode($image);

                // Nama file
                $imageName = 'photo_' . uniqid() . '.png';

                // Path tujuan di public/produksi
                $path = public_path('kas_admin/armada/' . $imageName);
                // Simpan file
                file_put_contents($path, $imageData);
                array_push($img, $imageName);
            }
            $data["cr_img"] = json_encode($img);
        }

        if ($data['oc_transaksi'] == "operasional"){
            $data['cr_notes'] = "Pengeluaran armada " . $customer->customer_notes;
            if ($item[0]['crd_type'] == 1) {
                $data['cr_notes'] = "Setoran armada " . $customer->customer_notes;
                $data['cr_type'] = 1;
            }
            $data['cr_nominal'] = $total;
            $data['cash_id'] = 0;
            $data['cr_aksi'] = 2;
        } else {
            $notes = $data['cr_notes'] . " dari armada " . $customer->customer_notes;
            $type = 1;
            $nominal = $data['cr_nominal'];
            if ($data['cr_nominal'] < 0) {
                $type = 2;
                $nominal = $data['cr_nominal'] - ($data['cr_nominal'] * 2);
            }
            
            $cash_id = (new Cash())->insertCash([
                "person_id" => $data['customer_id'],
                "cash_date" => now(),
                "cash_description" => $notes,
                "cash_nominal" => $nominal,
                "cash_type" => $type,
                "cash_tujuan" => 3, // Armada
                "status" => 1
            ]);
    
            $data['cash_id'] = $cash_id;
            $data['cr_aksi'] = 1;
        }

        $cr_id = (new CashArmada())->insertCashArmada($data);

        if ($data['oc_transaksi'] == "operasional"){
            foreach ($item as $key => $value) {
                $value['cr_id'] = $cr_id;
                (new CashArmadaDetail())->insertCashArmadaDetail($value);
            }
        }
    }

    function updateCashArmada(Request $req)
    {
        $data = $req->all();

        $id = [];
        $customer = Customer::find($data['customer_id'])->customer_notes;

        if ($data['oc_transaksi'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);
    
            foreach ($item as $key => $value) {
                $total += $value['crd_nominal'];
            }
    
            $data['cr_notes'] = "Pengeluaran armada " . $customer;
            $data['cr_nominal'] = $total;
        }

        if ($data['oc_transaksi'] == "saldo") $data['cr_aksi'] = 1;
        else if ($data['oc_transaksi'] == "operasional") $data['cr_aksi'] = 2;
        $cr_id = (new CashArmada())->updateCashArmada($data);

        if ($data['oc_transaksi'] == "saldo"){
            $notes = $data['cr_notes'] . " dari armada " . $customer;
            $type = 1;
            $nominal = $data['cr_nominal'];
            if ($data['cr_nominal'] < 0) {
                $type = 2;
                $nominal = $data['cr_nominal'] - ($data['cr_nominal'] * 2);
            }
            $cash_id = (new Cash())->updateCash([
                "cash_id" => $data['cash_id'],
                "person_id" => $data['customer_id'],
                "cash_date" => now(),
                "cash_description" => $notes,
                "cash_nominal" => $nominal,
                "cash_type" => $type, // debit (pengembalian kas)
                "cash_tujuan" => 3, // Armada
                "status" => 1
            ]);
        }
        // else if ($data['oc_transaksi'] == "operasional"){
        //     foreach ($item as $key => $value) {
        //         $cash_id = (new Cash())->updateCash([
        //             "cash_date" => now(),
        //             "cash_description" => $value['crd_notes'],
        //             "cash_nominal" => $value['crd_nominal'],
        //             "cash_type" => $value['crd_type'], // kredit 1
        //             "cash_tujuan" => 3, // Armada
        //             "status" => 1
        //         ]);
    
        //         $value['cr_id'] = $cr_id;
        //         $value['cash_id'] = $cash_id;
    
        //         if (!isset($value['crd_id']) || !$value['crd_id']){
        //             $t = (new CashArmadaDetail())->insertCashArmadaDetail($value);
        //         }
        //         else {
        //             $t = (new CashArmadaDetail())->updateCashArmadaDetail($value);
        //         }
        //         array_push($id, $t);
        //     }
        //     CashArmadaDetail::where('cr_id', '=', $data["cr_id"])->whereNotIn("crd_id", $id)->update(["status" => 0]);
        // }
    }

    function deleteCashArmada(Request $req)
    {
        $data = $req->all();
        $ca = CashArmada::find($data['cr_id']);
        (new CashArmada())->deleteCashArmada($data);
        $detail = CashArmadaDetail::where('cr_id', $data['cr_id'])->where('status', 1)->get();
        foreach ($detail as $key => $value) {
            (new CashArmadaDetail())->deleteCashArmadaDetail($value);
        }
        // Kalau manajemen saldo, maka hapus dari kas juga
        if ($ca->cr_aksi == 1) (new Cash())->deleteCash($ca);
    }

    function acceptCashArmada(Request $req)
    {
        $data = $req->all();

        if (isset($data['cr_id'])){
            $cr = CashArmada::find($data['cr_id']);
        } else {
            $cr = CashArmada::where('cash_id', $data["cash_id"])->first();
        }
        $customer = Customer::find($cr['customer_id']);
        if ($cr->cr_type == 1){
            $customer->customer_saldo += $cr['cr_nominal'];
        } else if ($cr->cr_type >= 2) {
            $customer->customer_saldo -= $cr['cr_nominal'];
        }
        // if ($customer->customer_saldo < 0){
        //     return response()->json([
        //         "status" => 0,
        //         "header" => "Gagal Konfirmasi",
        //         "message" => "Saldo armada " . $customer->customer_notes . " tidak mencukupi"
        //     ]);
        // }
        $customer->save();
        return (new CashArmada())->acceptCashArmada($data);
    }

    function declineCashArmada(Request $req)
    {
        $data = $req->all();
        return (new CashArmada())->declineCashArmada($data);
    }

    function getCashSales(Request $req)
    {
        $data = (new CashSales())->getCashSales($req->all());
        return response()->json($data);
    }

    function insertCashSales(Request $req)
    {
        $data = $req->all();

        $total = 0;
        $sales = Staff::find($data['staff_id']);
        if ($data['oc_transaksi'] == "operasional"){
            $item = json_decode($data['items'], true);
    
            foreach ($item as $key => $value) {
                $total += $value['csd_nominal'];
            }

            // if ($item[0]['csd_type'] != 1){
            //     if ($total > $sales->staff_saldo){
            //         return response()->json([
            //             "status" => 0,
            //             "header" => "Gagal Insert",
            //             "message" => "Saldo sales " . $sales->staff_name . " tidak mencukupi"
            //         ]);
            //     }
            // }
        }

        if ($data['photo']){
            $img = [];
            foreach (json_decode($data["photo"]) as $key => $value) {
                $image = $value;

                // Hilangkan prefix base64
                $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

                // Decode
                $imageData = base64_decode($image);

                // Nama file
                $imageName = 'photo_' . uniqid() . '.png';

                // Path tujuan di public/produksi
                $path = public_path('kas_admin/sales/' . $imageName);
                // Simpan file
                file_put_contents($path, $imageData);
                array_push($img, $imageName);
            }
            $data["cs_img"] = json_encode($img);
        }

        if ($data['oc_transaksi'] == "operasional"){
            $data['cs_notes'] = "Pengeluaran sales " . $sales->staff_name;
            $data['cs_transaction'] = $item[0]['csd_type'];
            if ($item[0]['csd_type'] == 1) {
                $data['cs_notes'] = "Setoran sales " . $sales->staff_name;
            }
            $data['cs_type'] = 2;
            $data['cs_nominal'] = $total;
            $data['cash_id'] = 0;
        } else {
            $notes = $data['cs_notes'] . " dari sales " . $sales->staff_name;
            if ($data['cs_aksi'] == "1"){
                $data['cs_transaction'] = 1;
            }
            else if ($data['cs_aksi'] == "2") {
                $type = 3;
                $nominal = $data['cs_nominal'];
                if ($data['cs_nominal'] < 0) {
                    $type = 1;
                    $nominal = $data['cs_nominal'] - ($data['cs_nominal'] * 2);
                }

                $cash_id = (new Cash())->insertCash([
                    "person_id" => $data['staff_id'],
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // Keluar 1
                    "cash_tujuan" => 4, // Sales
                    "status" => 1
                ]);
        
                $data['cash_id'] = $cash_id;
                $data['cs_transaction'] = 3;
            }
            else if ($data['cs_aksi'] == "3") {
                $type = 1;
                $nominal = $data['cs_nominal'];
                if ($data['cs_nominal'] < 0) {
                    $type = 2;
                    $nominal = $data['cs_nominal'] - ($data['cs_nominal'] * 2);
                }

                $cash_id = (new Cash())->insertCash([
                    "person_id" => $data['staff_id'],
                    "cash_date" => now(),
                    "cash_description" => $notes,
                    "cash_nominal" => $nominal,
                    "cash_type" => $type, // Debit
                    "cash_tujuan" => 4, // Sales
                    "status" => 1
                ]);
        
                $data['cash_id'] = $cash_id;
                $data['cs_transaction'] = 2;
            }
            $data['cs_type'] = 1;
        }

        $cs_id = (new CashSales())->insertCashSales($data);

        if ($data['oc_transaksi'] == "operasional"){
            foreach ($item as $key => $value) {
                $value['cs_id'] = $cs_id;
                (new CashSalesDetail())->insertCashSalesDetail($value);
            }
        }
    }

    function updateCashSales(Request $req)
    {
        $data = $req->all();

        $id = [];
        $sales = Staff::find($data['staff_id']);

        if ($data['oc_transaksi'] == "operasional"){
            $total = 0;
            $item = json_decode($data['items'], true);
    
            foreach ($item as $key => $value) {
                $total += $value['csd_nominal'];
            }
    
            $data['cs_notes'] = "Pengeluaran sales " . $sales->staff_name;
            $data['cs_nominal'] = $total;
        }

        if ($data['oc_transaksi'] == "saldo") $data['cs_aksi'] = 1;
        else if ($data['oc_transaksi'] == "operasional") $data['cs_aksi'] = 2;
        $cs_id = (new CashSales())->updateCashSales($data);

        if ($data['oc_transaksi'] == "saldo"){
            $notes = $data['cs_notes'] . " dari sales " . $sales->staff_name;
            $type = 3;
            $nominal = $data['cs_nominal'];
            if ($data['cs_nominal'] < 0) {
                $type = 1;
                $nominal = $data['cs_nominal'] - ($data['cs_nominal'] * 2);
            }
            $cash_id = (new Cash())->updateCash([
                "cash_id" => $data['cash_id'],
                "person_id" => $data['staff_id'],
                "cash_date" => now(),
                "cash_description" => $notes,
                "cash_nominal" => $nominal,
                "cash_type" => $type, // keluar 1 (setor kas ke bank)
                "cash_tujuan" => 3, // Armada
                "status" => 1
            ]);
        }
    }

    function deleteCashSales(Request $req)
    {
        $data = $req->all();
        $ca = CashSales::find($data['cs_id']);
        (new CashSales())->deleteCashSales($data);
        $detail = CashSalesDetail::where('cs_id', $data['cs_id'])->where('status', 1)->get();
        foreach ($detail as $key => $value) {
            (new CashSalesDetail())->deleteCashSalesDetail($value);
        }
        // Kalau manajemen saldo, maka hapus dari kas juga
        if ($ca->cs_aksi == 2 || $ca->cs_aksi == 3) (new Cash())->deleteCash($ca);
    }

    function acceptCashSales(Request $req)
    {
        $data = $req->all();

        if (isset($data['cs_id'])){
            $cs = CashSales::find($data['cs_id']);
        } else {
            $cs = CashSales::where('cash_id', $data["cash_id"])->first();
        }
        $sales = Staff::find($cs['staff_id']);
        if ($cs->cs_type == 1 && $cs->cs_aksi == 1){
            $sales->staff_saldo += $cs['cs_nominal'];
        } else if ($cs->cs_type == 2 && $cs->cs_aksi == 1) {
            $sales->staff_saldo += $cs['cs_nominal'];
        }else {
            $sales->staff_saldo -= $cs['cs_nominal'];
        }
        // if ($sales->staff_saldo < 0){
        //     return response()->json([
        //         "status" => 0,
        //         "header" => "Gagal Konfirmasi",
        //         "message" => "Saldo sales " . $sales->staff_name . " tidak mencukupi"
        //     ]);
        // }
        $sales->save();
        return (new CashSales())->acceptCashSales($data);
    }

    function declineCashSales(Request $req)
    {
        $data = $req->all();
        return (new CashSales())->declineCashSales($data);
    }
    
    function reportBahanBaku(){
        return view('Backoffice.Reports.Bahan_Baku');
    }

    function getDashboardExecutiveWidgets(Request $req)
    {
        $chartMonths = (int) $req->get('chart_months', 6);
        if (! in_array($chartMonths, [3, 6, 12], true)) {
            $chartMonths = 6;
        }

        return response()->json([
            'cross_widgets' => $this->buildDashboardCrossWidgets(),
            'exec_charts' => $this->buildDashboardExecutiveChartSeries($chartMonths),
            'top_sales' => $this->buildDashboardTopSalesByLine($chartMonths),
        ]);
    }

    /**
     * Top baris penjualan (agregasi detail SO) menurut qty terjual — rentang sama grafik "Penjualan" di atas.
     *
     * @return array{range: array{start: string, end: string}, rows: array<int, array{name: string, qty: float, unit: string}>}
     */
    private function buildDashboardTopSalesByLine(int $months): array
    {
        $months = max(3, min(18, $months));
        $emptyRange = static function (): array {
            $t = \Carbon\Carbon::now();

            return [
                'start' => $t->copy()->startOfMonth()->toDateString(),
                'end' => $t->copy()->endOfMonth()->toDateString(),
            ];
        };

        try {
            $rangeEnd = \Carbon\Carbon::now()->endOfMonth();
            $rangeStart = \Carbon\Carbon::now()->copy()->subMonths($months - 1)->startOfMonth();
            $startD = $rangeStart->toDateString();
            $endD = $rangeEnd->toDateString();

            $rows = DB::table('sales_order_details as sod')
                ->join('sales_orders as so', 'so.so_id', '=', 'sod.so_id')
                ->leftJoin('units as u', 'u.unit_id', '=', 'sod.unit_id')
                ->where('so.status', '>=', 1)
                ->where('sod.status', '>=', 1)
                ->whereRaw('DATE(COALESCE(so.so_date, so.created_at)) BETWEEN ? AND ?', [$startD, $endD])
                ->select('sod.sod_nama', 'sod.sod_variant', 'sod.sod_sku', 'sod.unit_id', 'u.unit_short_name', 'u.unit_name')
                ->selectRaw('SUM(sod.sod_qty) as qty_sum')
                ->groupBy('sod.sod_nama', 'sod.sod_variant', 'sod.sod_sku', 'sod.unit_id', 'u.unit_short_name', 'u.unit_name')
                ->orderByDesc('qty_sum')
                ->limit(10)
                ->get();

            $out = [];
            foreach ($rows as $r) {
                $base = trim((string) ($r->sod_nama ?? ''));
                $var = trim((string) ($r->sod_variant ?? ''));
                $name = $base;
                if ($var !== '') {
                    $name = trim($base.' '.$var);
                }
                if ($name === '') {
                    $name = trim((string) ($r->sod_sku ?? '')) ?: '-';
                }
                $short = trim((string) ($r->unit_short_name ?? ''));
                $long = trim((string) ($r->unit_name ?? ''));
                $unit = $short !== '' ? $short : ($long !== '' ? $long : '-');
                $out[] = [
                    'name' => $name,
                    'qty' => (float) ($r->qty_sum ?? 0),
                    'unit' => $unit,
                ];
            }

            return [
                'range' => ['start' => $startD, 'end' => $endD],
                'rows' => $out,
            ];
        } catch (\Throwable $e) {
            return [
                'range' => $emptyRange(),
                'rows' => [],
            ];
        }
    }

    function getDashboardPemakaianBahan(Request $req)
    {
        $months = (int) $req->get('months', 12);
        if (! in_array($months, [6, 12, 18, 24], true)) {
            $months = 12;
        }

        $data = (new LogStock())->getRawMaterialUsageMonthlyDashboard([
            'months' => $months,
            'supplies_id' => $req->filled('supplies_id') ? (int) $req->supplies_id : null,
            'supplier_id' => $req->filled('supplier_id') ? (int) $req->supplier_id : null,
        ]);

        return response()->json($data);
    }

    function getDashboardProcurementEstimate(Request $req)
    {
        $months = (int) $req->get('months', 6);
        if (! in_array($months, [3, 6, 12, 18, 24], true)) {
            $months = 6;
        }
        $top = (int) $req->get('top', 12);
        $top = max(5, min(40, $top));

        return response()->json((new LogStock())->getProcurementEstimateProductionMaterials([
            'months' => $months,
            'top' => $top,
        ]));
    }

    function getReportPemakaianBahan(Request $req){
        $data = (new LogStock())->getRawMaterialUsageReport([
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "supplies_id" => $req->supplies_id
        ]);
        return response()->json($data);
    }

    function reportSelisihOpname(){
        return view('Backoffice.Reports.StockOpnameDifference');
    }

    function getReportSelisihOpname(Request $req){
        $date = $req->date;
        $type = strtolower((string)($req->type ?? 'all'));
        if (!in_array($type, ['all', 'bahan', 'product'])) $type = 'all';
        $itemId = $req->item_id;
        $startDate = null;
        $endDate = null;
        if (is_array($date) && count($date) === 2) {
            $startRaw = trim((string)($date[0] ?? ""));
            $endRaw = trim((string)($date[1] ?? ""));
            if ($startRaw !== "") {
                $startDate = \Carbon\Carbon::hasFormat($startRaw, 'Y-m-d')
                    ? $startRaw
                    : \Carbon\Carbon::createFromFormat('d-m-Y', $startRaw)->format('Y-m-d');
            }
            if ($endRaw !== "") {
                $endDate = \Carbon\Carbon::hasFormat($endRaw, 'Y-m-d')
                    ? $endRaw
                    : \Carbon\Carbon::createFromFormat('d-m-Y', $endRaw)->format('Y-m-d');
            }
        }

        $rows = [];

        if ($type === 'all' || $type === 'product') {
            $qProduct = DB::table('stock_opname_details as d')
                ->join('stock_opnames as h', 'h.sto_id', '=', 'd.sto_id')
                ->leftJoin('product_variants as pv', 'pv.product_variant_id', '=', 'd.product_variant_id')
                ->leftJoin('products as p', 'p.product_id', '=', 'd.product_id')
                ->where('d.status', 1)
                ->where('h.status', '>=', 1)
                ->select('h.sto_code as kode', 'h.sto_date as tanggal', DB::raw("'produk' as sumber"), 'p.product_name as item_name', 'pv.product_variant_name as variant_name', 'd.stod_system as stock_system', 'd.stod_real as stock_fisik', 'd.stod_selisih as selisih_text', 'pv.product_variant_price as harga_satuan');
            if ($startDate && $endDate) $qProduct->whereBetween('h.sto_date', [$startDate, $endDate]);
            else if ($startDate) $qProduct->where('h.sto_date', '>=', $startDate);
            else if ($endDate) $qProduct->where('h.sto_date', '<=', $endDate);
            if ($type === 'product' && !empty($itemId)) $qProduct->where('d.product_variant_id', $itemId);
            $rows = array_merge($rows, $qProduct->get()->toArray());
        }

        $lastHargaSub = DB::table('purchase_orders_details as pod')
            ->join('supplies_variants as sv', 'sv.supplies_variant_id', '=', 'pod.supplies_variant_id')
            ->where('pod.status', 1)
            ->select('sv.supplies_id', DB::raw('MAX(pod.pod_harga) as last_harga'))
            ->groupBy('sv.supplies_id');

        if ($type === 'all' || $type === 'bahan') {
            $qBahan = DB::table('stock_opname_detail_bahans as d')
                ->join('stock_opname_bahans as h', 'h.stob_id', '=', 'd.stob_id')
                ->leftJoin('supplies as s', 's.supplies_id', '=', 'd.supplies_id')
                ->leftJoinSub($lastHargaSub, 'lh', function ($join) {
                    $join->on('lh.supplies_id', '=', 'd.supplies_id');
                })
                ->where('d.status', 1)
                ->where('h.status', '>=', 1)
                ->select('h.stob_code as kode', 'h.stob_date as tanggal', DB::raw("'bahan' as sumber"), 's.supplies_name as item_name', DB::raw("'' as variant_name"), 'd.stobd_system as stock_system', 'd.stobd_real as stock_fisik', 'd.stobd_selisih as selisih_text', DB::raw('COALESCE(lh.last_harga,0) as harga_satuan'));
            if ($startDate && $endDate) $qBahan->whereBetween('h.stob_date', [$startDate, $endDate]);
            else if ($startDate) $qBahan->where('h.stob_date', '>=', $startDate);
            else if ($endDate) $qBahan->where('h.stob_date', '<=', $endDate);
            if ($type === 'bahan' && !empty($itemId)) $qBahan->where('d.supplies_id', $itemId);
            $rows = array_merge($rows, $qBahan->get()->toArray());
        }

        $grouped = [];
        foreach ($rows as $row) {
            if (!$this->hasNonZeroSelisih($row->selisih_text)) continue;
            $selisihQty = $this->parseSelisihNumeric($row->selisih_text);
            if ((float)$selisihQty == 0.0) continue;

            $nominal = $selisihQty * ((float)($row->harga_satuan ?? 0));
            $kode = $row->kode ?? '-';
            if (!isset($grouped[$kode])) {
                $grouped[$kode] = [
                    "kode" => $kode,
                    "tanggal" => $row->tanggal,
                    "total_item_selisih" => 0,
                    "total_nominal" => 0,
                    "details" => []
                ];
            }
            $grouped[$kode]["total_item_selisih"] += 1;
            $grouped[$kode]["total_nominal"] += $nominal;
            $grouped[$kode]["details"][] = [
                "sumber" => $row->sumber,
                "item_name" => $row->item_name ?? '-',
                "variant_name" => $row->variant_name ?? '-',
                "stock_system" => $row->stock_system ?? '-',
                "stock_fisik" => $row->stock_fisik ?? '-',
                "selisih_text" => $row->selisih_text ?? '-',
                "selisih_qty" => $selisihQty,
                "harga_satuan" => (float)($row->harga_satuan ?? 0),
                "nominal" => $nominal
            ];
        }

        $result = array_values($grouped);
        usort($result, function($a, $b) {
            return strcmp((string)$b["tanggal"], (string)$a["tanggal"]);
        });
        return response()->json($result);
    }

    function generateReportSelisihOpnamePdf(Request $req){
        $json = $this->getReportSelisihOpname($req);
        $data = $json->getData(true);
        $raw = is_array($data) ? $data : [];
        $param["data"] = $this->selisihOpnamePdfRowsOnlySelisih($raw);
        $param["start_date"] = is_array($req->date) && !empty($req->date[0]) ? $req->date[0] : "-";
        $param["end_date"] = is_array($req->date) && !empty($req->date[1]) ? $req->date[1] : "-";
        $type = strtolower((string)($req->type ?? 'all'));
        if (!in_array($type, ['all', 'bahan', 'product'])) $type = 'all';
        $param["type_label"] = $type === 'bahan' ? 'Bahan' : ($type === 'product' ? 'Product' : 'All');
        $itemName = "Semua Item";
        if (!empty($req->item_id)) {
            if ($type === 'bahan') {
                $itemName = Supplies::find($req->item_id)->supplies_name ?? "Semua Item";
            } elseif ($type === 'product') {
                $pv = ProductVariant::find($req->item_id);
                if ($pv) {
                    $p = Product::find($pv->product_id);
                    $itemName = trim(
                        (($p->product_name ?? '') !== '' ? $p->product_name . ' ' : '') .
                        ($pv->product_variant_name ?? '')
                    );
                    $itemName = $itemName !== '' ? $itemName : "Semua Item";
                } else {
                    $itemName = "Semua Item";
                }
            }
        }
        $param["item_name"] = $itemName;
        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportSelisihOpname', $param)->setPaper('a4', 'portrait');
        return $pdf->stream('Laporan_Selisih_Stok_Opname_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    function generateReportPemakaianBahanPdf(Request $req){
        $filter = [
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "supplies_id" => $req->supplies_id
        ];

        $param["data"] = (new LogStock())->getRawMaterialUsageReport($filter);
        $param["start_date"] = is_array($req->date) && !empty($req->date[0]) ? $req->date[0] : "-";
        $param["end_date"] = is_array($req->date) && !empty($req->date[1]) ? $req->date[1] : "-";
        $param["supplier_name"] = $req->supplier_id ? (Supplier::find($req->supplier_id)->supplier_name ?? "-") : "Semua Supplier";
        $item = null;
        if ($req->supplies_id) {
            $item = Supplies::find($req->supplies_id);
        }
        $param["supplies_name"] = $item->supplies_name ?? "Semua Bahan";

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportPemakaianBahan', $param)->setPaper('a4', 'portrait');
        return $pdf->stream('Laporan_Pemakaian_Bahan_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
    
    function ProductReturn(){
        return view('Backoffice.Reports.ProductReturn');
    }

    function getReportReturn(Request $req){
        $data = (new ReturnSupplies())->getReturnReport([
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "supplies_id" => $req->supplies_id
        ]);
        return response()->json($data);
    }

    function generateReportReturnPdf(Request $req){
        $filter = [
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "supplies_id" => $req->supplies_id
        ];

        $param["data"] = (new ReturnSupplies())->getReturnReport($filter);
        $param["start_date"] = is_array($req->date) && !empty($req->date[0]) ? $req->date[0] : "-";
        $param["end_date"] = is_array($req->date) && !empty($req->date[1]) ? $req->date[1] : "-";
        $param["supplier_name"] = $req->supplier_id ? (Supplier::find($req->supplier_id)->supplier_name ?? "-") : "Semua Supplier";
        $item = null;
        if ($req->supplies_id) {
            $item = Supplies::find($req->supplies_id);
        }
        $param["item_name"] = $item->supplies_name ?? "Semua Barang";

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportReturn', $param)->setPaper('a4', 'portrait');
        return $pdf->stream('Laporan_Retur_Product_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    function reportReturProdukArmada()
    {
        return view('Backoffice.Reports.ReportReturProdukArmada');
    }

    function getReportReturProdukArmada(Request $req)
    {
        $data = (new ProductIssues())->getArmadaReturnReport([
            'date' => $req->date,
            'product_variant_id' => $req->product_variant_id,
        ]);

        return response()->json($data);
    }

    function generateReportReturProdukArmadaPdf(Request $req)
    {
        $filter = [
            'date' => $req->date,
            'product_variant_id' => $req->product_variant_id,
        ];

        $param['data'] = (new ProductIssues())->getArmadaReturnReport($filter);
        $param['start_date'] = is_array($req->date) && !empty($req->date[0]) ? $req->date[0] : '-';
        $param['end_date'] = is_array($req->date) && !empty($req->date[1]) ? $req->date[1] : '-';
        $param['product_label'] = 'Semua Produk';
        if ($req->product_variant_id) {
            $pv = ProductVariant::find($req->product_variant_id);
            if ($pv) {
                $pr = Product::find($pv->product_id);
                $param['product_label'] = trim(($pr->product_name ?? '') . ' ' . ($pv->product_variant_name ?? '')) ?: 'Produk #' . $pv->product_variant_id;
            }
        }

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportReturProdukArmada', $param)->setPaper('a4', 'portrait');

        return $pdf->stream('Laporan_Retur_Produk_Armada_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }
    
    function reportProduksi(){
        return view('Backoffice.Reports.ReportProduksi');
    }

    function reportEfisiensiProduksi(){
        return view('Backoffice.Reports.ReportEfisiensiProduksi');
    }

    function getReportProduksi(Request $req){
        $data = (new Production())->getProductionReport([
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "product_variant_id" => $req->product_variant_id
        ]);
        return response()->json($data);
    }

    function getReportEfisiensiProduksi(Request $req){
        $data = (new Production())->getProductionEfficiencyReport([
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "product_variant_id" => $req->product_variant_id
        ]);
        return response()->json($data);
    }

    function generateReportProduksiPdf(Request $req){
        $filter = [
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "product_variant_id" => $req->product_variant_id
        ];

        $param["data"] = (new Production())->getProductionReport($filter);
        $param["start_date"] = is_array($req->date) && isset($req->date[0]) ? $req->date[0] : "-";
        $param["end_date"] = is_array($req->date) && isset($req->date[1]) ? $req->date[1] : "-";
        $param["supplier_name"] = $req->supplier_id ? (Supplier::find($req->supplier_id)->supplier_name ?? "-") : "Semua Supplier";
        $param["product_name"] = $req->product_variant_id ? (ProductVariant::find($req->product_variant_id)->product_variant_name ?? "-") : "Semua Produk";

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportProduksi', $param)->setPaper('a4', 'portrait');
        return $pdf->stream('Laporan_Produksi_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    function generateReportEfisiensiProduksiPdf(Request $req){
        $filter = [
            "date" => $req->date,
            "supplier_id" => $req->supplier_id,
            "product_variant_id" => $req->product_variant_id
        ];

        $param["data"] = (new Production())->getProductionEfficiencyReport($filter);
        $param["start_date"] = is_array($req->date) && isset($req->date[0]) ? $req->date[0] : "-";
        $param["end_date"] = is_array($req->date) && isset($req->date[1]) ? $req->date[1] : "-";
        $param["supplier_name"] = $req->supplier_id ? (Supplier::find($req->supplier_id)->supplier_name ?? "-") : "Semua Supplier";
        $param["product_name"] = $req->product_variant_id ? (ProductVariant::find($req->product_variant_id)->product_variant_name ?? "-") : "Semua Produk";

        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportEfisiensiProduksi', $param)->setPaper('a4', 'portrait');
        return $pdf->stream('Laporan_Efisiensi_Produksi_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    function reportStockAging()
    {
        return view('Backoffice.Reports.StockAging');
    }

    function getReportStockAging(Request $req)
    {
        $type = strtolower((string) ($req->type ?? 'all'));
        if (!in_array($type, ['all', 'bahan', 'product'], true)) {
            $type = 'all';
        }
        $data = (new LogStock())->getStockAgingReport([
            'type' => $type,
            'item_id' => $req->item_id,
            'as_of' => $req->as_of,
        ]);

        return response()->json($data);
    }

    function generateReportStockAgingPdf(Request $req)
    {
        $type = strtolower((string) ($req->type ?? 'all'));
        if (!in_array($type, ['all', 'bahan', 'product'], true)) {
            $type = 'all';
        }
        $param['data'] = (new LogStock())->getStockAgingReport([
            'type' => $type,
            'item_id' => $req->item_id,
            'as_of' => $req->as_of,
        ]);
        $param['type_label'] = $type === 'bahan' ? 'Bahan mentah' : ($type === 'product' ? 'Produk jadi' : 'Semua');
        $rawAsOf = trim((string) ($req->as_of ?? ''));
        if ($rawAsOf === '') {
            $param['as_of_label'] = now()->format('d-m-Y');
        } else {
            $param['as_of_label'] = $rawAsOf;
        }
        $itemLabel = 'Semua item';
        if (!empty($req->item_id)) {
            if ($type === 'bahan') {
                $itemLabel = Supplies::find($req->item_id)->supplies_name ?? 'Semua item';
            } elseif ($type === 'product') {
                $pv = ProductVariant::find($req->item_id);
                if ($pv) {
                    $p = Product::find($pv->product_id);
                    $itemLabel = trim((($p->product_name ?? '') !== '' ? $p->product_name . ' ' : '') . ($pv->product_variant_name ?? ''));
                    $itemLabel = $itemLabel !== '' ? $itemLabel : 'Semua item';
                }
            }
        }
        $param['item_label'] = $itemLabel;
        $param = $this->mergePdfPrintMeta($param);
        $pdf = Pdf::loadView('Backoffice.PDF.ReportStockAging', $param)->setPaper('a4', 'landscape');

        return $pdf->stream('Laporan_Stock_Aging_' . now()->format('Y-m-d_H-i-s') . '.pdf');
    }

    // Cash Category
    public function CashCategory(){
        return view('Backoffice.Reports.Cash_Category');
    }

    function getCashCategory(Request $req){
        $data = (new CashCategory())->getCashCategory();
        return response()->json($data);
    }

    function insertCashCategory(Request $req){
        $data = $req->all();
        return (new CashCategory())->insertCashCategory($data);
    }

    function updateCashCategory(Request $req){
        $data = $req->all();
        return (new CashCategory())->updateCashCategory($data);
    }

    function deleteCashCategory(Request $req){
        $data = $req->all();
        return (new CashCategory())->deleteCashCategory($data);
    }
}
