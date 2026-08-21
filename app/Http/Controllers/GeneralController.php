<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Support\RoleAccess;
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

    /**
     * Riwayat Stok Produk / Bahan Mentah modal (Stock_Product.js & Stock_Supplies.js).
     * `log_type` (1=produk, 2=bahan mentah -- see LogStock::getLog()) decides which module's
     * `view` access is required: staf yang punya akses 'Daftar Produk' boleh lihat histori
     * produk, staf yang punya akses 'Daftar Bahan Mentah' boleh lihat histori bahan mentah.
     * check.access middleware tidak bisa mengekspresikan ini karena modulnya tergantung
     * parameter request, bukan route yang tetap (GitHub #68).
     */
    function getLog(Request $req){
        $module = match ((int) $req->input('log_type')) {
            1 => 'Daftar Produk',
            2 => 'Daftar Bahan Mentah',
            default => null,
        };
        if ($module === null || !RoleAccess::can(Session::get('user'), $module, 'view')) {
            abort(403, 'Unauthorized');
        }

        $data = (new LogStock())->getLog($req->all());
        return response()->json($data);
    }
}
