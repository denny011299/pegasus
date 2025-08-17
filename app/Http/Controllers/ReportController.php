<?php

namespace App\Http\Controllers;

use App\Models\Cash;
use App\Models\InwardOutward;
use App\Models\PettyCash;
use App\Models\ReportLoss;
use App\Models\ReportProfit;
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

    // Report Petty Cash
    public function PettyCash(){
        return view('Backoffice.Reports.Petty_Cash');
    }

    function getPettyCash(Request $req){
        $data = (new PettyCash())->getPettyCash();
        return response()->json($data);
    }
}
