<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\Production;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    // BOM
    public function bom(){
        return view('Backoffice.Production.Bom');
    }

    function getBom(Request $req){
        $data = (new Bom())->getBom();
        return response()->json($data);
    }

    function insertBom(Request $req){
        $data = $req->all();
        return (new Bom())->insertBom($data);
    }

    function updateBom(Request $req){
        $data = $req->all();
        return (new Bom())->updateBom($data);
    }

    function deleteBom(Request $req){
        $data = $req->all();
        return (new Bom())->deleteBom($data);
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
        return (new Production())->insertProduction($data);
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
