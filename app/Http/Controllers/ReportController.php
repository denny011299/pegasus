<?php

namespace App\Http\Controllers;

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
}
