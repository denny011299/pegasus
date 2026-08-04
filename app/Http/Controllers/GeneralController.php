<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class GeneralController extends Controller
{
    function testing() {
        $p = Product::where('status','=',1)->get();
        foreach ($p as $key => $value) {
            (new ProductStock())->syncStock($value->product_id);
        }

    }
     function login() {
          return view('Login');
     }

     /**
      * Dulu Logout cuma link ke /login tanpa pernah menghapus session — session yang "sudah
      * logout" tetap valid (termasuk role_access lama kalau rolenya berubah) sampai expired
      * alami. invalidate() membuang seluruh data session + regenerate session id, regenerateToken()
      * menyegarkan CSRF token supaya form di halaman login berikutnya tidak memakai token basi.
      */
     function logout() {
          Session::invalidate();
          Session::regenerateToken();
          return redirect('/login');
     }

    // Wilayah
    public function Area(){
        return view('Backoffice.Area.Area');
    }

    function getArea(Request $req){
        $data = (new Area())->getArea();
        return response()->json($data);
    }

    function insertArea(Request $req){
        $data = $req->all();
        return (new Area())->insertArea($data);
    }

    function updateArea(Request $req){
        $data = $req->all();
        return (new Area())->updateArea($data);
    }

    function deleteArea(Request $req){
        $data = $req->all();
        return (new Area())->deleteArea($data);
    }

    function getLog(Request $req){
        $data = (new LogStock())->getLog($req->all());
        return response()->json($data);
    }
}
