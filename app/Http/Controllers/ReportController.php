<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Cash;
use App\Models\CashAdmin;
use App\Models\CashAdminDetail;
use App\Models\CashCategory;
use App\Models\CashGudang;
use App\Models\CashGudangDetail;
use App\Models\Customer;
use App\Models\InwardOutward;
use App\Models\PettyCash;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\ReportLoss;
use App\Models\ReportProfit;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
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
        $data = (new Cash())->getCash();
        return response()->json($data);
    }

    function insertCash(Request $req){
        $data = $req->all();
        $cash_id = (new Cash())->insertCash($data);
        if ($data['cash_tujuan'] != null) {
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
            $path = public_path('kas_admin/' . $imageName);
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
            $data['ca_notes'] = "Pengeluaran " . $staff_name . " " . now()->format("Y-m-d");
            $data['ca_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $data['ca_notes'],
                    "cash_nominal" => $data['ca_nominal'],
                    "cash_type" => 2, // kredit 1
                    "cash_tujuan" => 1, // admin
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $data['ca_notes'],
                    "cash_nominal" => $data['ca_nominal'],
                    "cash_type" => 1, // debit
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
            $path = public_path('kas_admin/' . $imageName);
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
            $data['ca_notes'] = "Pengeluaran " . $staff_name . " " . now()->format("Y-m-d");
            $data['ca_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $data['ca_notes'],
                    "cash_nominal" => $data['ca_nominal'],
                    "cash_type" => 2, // kredit 1
                    "cash_tujuan" => 1, // admin
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $data['ca_notes'],
                    "cash_nominal" => $data['ca_nominal'],
                    "cash_type" => 1, // debit
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
            $path = public_path('kas_admin/' . $imageName);
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

            $data['cg_notes'] = "Penyerahan kas armada " . now()->format("Y-m-d");
            $data['cg_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $data['cg_notes'],
                    "cash_nominal" => $data['cg_nominal'],
                    "cash_type" => 2, // kredit 1
                    "cash_tujuan" => 2, // gudang
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $cash_id = (new Cash())->insertCash([
                    "cash_date" => now(),
                    "cash_description" => $data['cg_notes'],
                    "cash_nominal" => $data['cg_nominal'],
                    "cash_type" => 1, // debit
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
            $path = public_path('kas_admin/' . $imageName);
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

            $data['cg_notes'] = "Penyerahan kas armada " . now()->format("Y-m-d");
            $data['cg_nominal'] = $total;
        }

        else if ($data['jenis_input'] == "saldo"){
            // Pengajuan dana
            if ($data['oc_transaksi'] == 1){
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $data['cg_notes'],
                    "cash_nominal" => $data['cg_nominal'],
                    "cash_type" => 2, // kredit 1
                    "cash_tujuan" => 2, // gudang
                    "status" => 1
                ]);
            }
            // Pengembalian dana
            else if ($data['oc_transaksi'] == 2) {
                $cash_id = (new Cash())->updateCash([
                    "cash_id" => $cash->cash_id,
                    "cash_date" => now(),
                    "cash_description" => $data['cg_notes'],
                    "cash_nominal" => $data['cg_nominal'],
                    "cash_type" => 1, // debit
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
    
    function reportBahanBaku(){
        return view('Backoffice.Reports.Bahan_Baku');
    }
    
    function ProductReturn(){
        return view('Backoffice.Reports.ProductReturn');
    }
    
    function reportProduksi(){
        return view('Backoffice.Reports.ReportProduksi');
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
