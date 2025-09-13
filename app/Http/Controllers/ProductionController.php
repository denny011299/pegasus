<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\Production;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    // BOM
    public function bom(){
        return view('Backoffice.Production.Bom');
    }

    function getBom(Request $req){
        $bomList = (new Bom())->getBom([
            "bom_id" =>$req->bom_id
        ]);
        foreach ($bomList as $bom) {
            $details = (new BomDetail())->getBomDetail([
                "bom_id" => $bom->bom_id
            ]);
            $bom->details = $details;
        }
        return response()->json($bomList);
    }

    function insertBom(Request $req){
        $data = $req->all();
        $bom_id = (new Bom())->insertBom($data);
        foreach (json_decode($req->bahan,true) as $key => $value) {
            $value['bom_id'] = $bom_id;
            (new BomDetail())->insertBomDetail($value);
        }
    }

    function updateBom(Request $req){
        $data = $req->all();
        $list_id_detail = [];
        $bom_id = (new Bom())->updateBom($data);
        foreach (json_decode($req->bahan,true) as $key => $value) {
            $value['bom_id'] = $bom_id;
            $id = (new BomDetail())->updateBomDetail($value);
            array_push($list_id_detail, $id);
        }
        BomDetail::whereNotIn('bom_detail_id', $list_id_detail)->where('bom_id','=',$bom_id)->update(['status' => 0]);
    }

    function deleteBom(Request $req){
        $data = $req->all();
        return (new Bom())->deleteBom($data);
    }


    function updateBomDetail(Request $req){
        $data = $req->all();
        return (new BomDetail())->updateBomDetail($data);
    }

    function deleteBomDetail(Request $req){
        $data = $req->all();
        return (new BomDetail())->deleteBomDetail($data);
    }

    // Production
    public function production(){
        return view('Backoffice.Production.Production');
    }

    function getProduction(Request $req){
        $data = (new Production())->getProduction();
        return response()->json($data);
    }

    function insertProduction(Request $req){
        $data = $req->all();
        $bahan = json_decode($req->detail,true)[0];
        $cek = -1;
        $bahan_kurang = [];

        foreach ($bahan as $key => $value) {
            $stok = Supplies::find($value['supplies_id'])->supplies_stock;
            if($stok - ($value['bom_detail_qty'] * $data['production_qty'])<0){
                $cek = 1;
                array_push($bahan_kurang, $value['supplies_name']);
            }
        }

        if($cek==1){
            return response()->json([
                "status"=>-1,
                "message"=>"Stok bahan baku tidak mencukupi : ".implode(", ", $bahan_kurang)
            ]);
        }
        
        foreach ($bahan as $key => $value) {
            $stok = Supplies::find($value['supplies_id']);
            $stok->supplies_stock -=  ($value['bom_detail_qty'] * $data['production_qty']);
            $stok->save();
        }

        (new Production())->insertProduction($data);

        $b = bom::find($data["production_bom_id"]);
        $v = ProductStock::where("product_variant_id","=",$data["production_product_id"])
        ->where("unit_id","=",$data["unit_id"])->first();
        $v->ps_stock += intval($data['production_qty'])*$b->bom_qty;
        $v->save();
        return response()->json([
                "status"=>1,
                "message"=>"Berhasil"
        ]);
    }

    function updateProduction(Request $req){
        $data = $req->all();
        return (new Production())->updateProduction($data);
    }

    function deleteProduction(Request $req){
        $data = $req->all();
        return (new Production())->deleteProduction($data);
    }
}
