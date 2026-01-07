<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\LogStock;
use App\Models\Production;
use App\Models\ProductionPhoto;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile as HttpUploadedFile;

class ProductionController extends Controller
{
    // BOM
    public function bom()
    {
        return view('Backoffice.Production.Bom');
    }

    function getBom(Request $req)
    {
        $bomList = (new Bom())->getBom([
            "bom_id" => $req->bom_id
        ]);
        foreach ($bomList as $bom) {
            $details = (new BomDetail())->getBomDetail([
                "bom_id" => $bom->bom_id
            ]);
            $bom->details = $details;
        }
        return response()->json($bomList);
    }

    function insertBom(Request $req)
    {
        $data = $req->all();
        $bom_id = (new Bom())->insertBom($data);
        foreach (json_decode($req->bahan, true) as $key => $value) {
            $value['bom_id'] = $bom_id;
            (new BomDetail())->insertBomDetail($value);
        }
    }

    function updateBom(Request $req)
    {
        $data = $req->all();
        $list_id_detail = [];
        $bom_id = (new Bom())->updateBom($data);
        foreach (json_decode($req->bahan, true) as $key => $value) {
            $value['bom_id'] = $bom_id;
            if (isset($value["bom_detail_id"])) $id = (new BomDetail())->updateBomDetail($value);
            else $id = (new BomDetail())->insertBomDetail($value);
            array_push($list_id_detail, $id);
        }
        BomDetail::whereNotIn('bom_detail_id', $list_id_detail)->where('bom_id', '=', $bom_id)->update(['status' => 0]);
    }

    function deleteBom(Request $req)
    {
        $data = $req->all();
        return (new Bom())->deleteBom($data);
    }


    function updateBomDetail(Request $req)
    {
        $data = $req->all();
        return (new BomDetail())->updateBomDetail($data);
    }

    function deleteBomDetail(Request $req)
    {
        $data = $req->all();
        return (new BomDetail())->deleteBomDetail($data);
    }

    // Production
    public function production()
    {
        return view('Backoffice.Production.Production');
    }

    function getProduction(Request $req)
    {
        $data = (new Production())->getProduction([
            "date" => $req->date,
            "report" => $req->report ? $req->report : null
        ]);
        return response()->json($data);
    }

    function insertProduction(Request $req)
    {
        $data = $req->all();
        $bahan = json_decode($req->detail, true)[0];
        $cek = -1;
        $bahan_kurang = [];
        foreach ($bahan as $key => $value) {
            $stok = SuppliesStock::where("supplies_id", "=", $value['supplies_id'])
                ->where("unit_id", "=", $value['unit_id'])->first()->ss_stock;
            if ($stok - ($value['bom_detail_qty'] * $data['production_qty']) <= 0) {
                $cek = 1;
                array_push($bahan_kurang, $value['supplies_name']);
            }
        }

        if ($cek == 1) {
            return response()->json([
                "status" => -1,
                "message" => "Stok bahan baku tidak mencukupi : " . implode(", ", $bahan_kurang)
            ]);
        }

        $p = (new Production())->insertProduction($data);
        $b = bom::find($data["production_bom_id"]);

        foreach ($bahan as $key => $value) {
            $stok = SuppliesStock::where("supplies_id", "=", $value['supplies_id'])
                ->where("unit_id", "=", $value['unit_id'])->first();
            $stok->ss_stock -=  ($value['bom_detail_qty'] * $data['production_qty']);
            $stok->save();

            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $p->production_code,
                'log_category' => 2,
                'log_item_id' => $value['supplies_id'],
                'log_notes'  => "Pengurangan bahan mentah untuk produksi",
                'log_jumlah' => ($value['bom_detail_qty'] * $data['production_qty']),
                'unit_id'    => $value['unit_id'],
            ]);
        }
        
        $v = ProductStock::where("product_variant_id", "=", $data["production_product_id"])
            ->where("unit_id", "=", $b["unit_id"])->first();
        if(!$v){
            $pv  = ProductVariant::find($data["production_product_id"]);
            $v = (new ProductStock())->syncStock($pv->product_id);
            $v = ProductStock::where("product_variant_id", "=", $data["production_product_id"])
            ->where("unit_id", "=", $b["unit_id"])->first();
        }
        $v->ps_stock += intval($data['production_qty']) * $b->bom_qty;
        $v->save();

        (new ProductStock())->cekStockBerlebih($v);

        // Catat log
        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode'    => $p->production_code,
            'log_category' => 1,
            'log_item_id' => $data["production_product_id"],
            'log_notes'  => "Produksi produk",
            'log_jumlah' => intval($data['production_qty']) * $b->bom_qty,
            'unit_id'    => $b['unit_id'],
        ]);

        return response()->json([
            "status" => 1,
            "message" => "Berhasil"
        ]);
    }

    function updateProduction(Request $req) {}

    function deleteProduction(Request $req)
    {
        $data = $req->all();
        (new Production())->deleteProduction($data);
       
    }
    function tolakDeleteProduction(Request $req)
    {
        $data = $req->all();
        (new Production())->tolakDeleteProduction($data);
       
    }
    function accDeleteProduction(Request $req)
    {
        $data = $req->all();

        $p = production::find($data["production_id"]);
        $b = bom::find($p["production_bom_id"]);
        $cek = -1;
        $stok = ProductStock::where("product_variant_id", "=", $p['production_product_id'])
                ->where("unit_id", "=", $b['unit_id'])->first()->ps_stock;
        if ($stok - (intval($p['production_qty']) * $b->bom_qty) < 0) {
            $cek = 1;
        }

        if ($cek == 1) {
            return response()->json([
                "status" => -1,
                "message" => "Stok produk tidak mencukupi"
            ]);
        }

        (new Production())->cancelProduction($data);

        $b = bom::find($p->production_bom_id);
        $v = ProductStock::where("product_variant_id", "=", $b["product_id"])
            ->where("unit_id", "=", $b["unit_id"])->first();
        $v->ps_stock -= intval($p['production_qty']) * $b->bom_qty;
        $v->save();

        // Catat log
        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode'    => $p->production_code,
            'log_category' => 2,
            'log_item_id' => $p["production_product_id"],
            'log_notes'  => "Pembatalan produksi produk",
            'log_jumlah' => intval($p['production_qty']) * $b->bom_qty,
            'unit_id'    => $b['unit_id'],
        ]);
    }

    function getPemakaian(Request $req)
    {
        $bahan = [];
        $data = (new production)->getProduction(["date" => $req->date]);
        foreach ($data as $key => $value) {
            $bhan = BomDetail::where("bom_id", '=', $value["production_bom_id"])->get();
            foreach ($bhan as $key => $valueBahan) {
                $supVar = SuppliesVariant::find($valueBahan->supplies_id);
                $sup = Supplies::find($supVar->supplies_id);
                $unit_name = Unit::find($sup->supplies_unit)->unit_name;
                $supVar->production_date = $value->production_date;
                $supVar->kode_produksi = "PR" . str_pad($value["production_id"], 4, "0", STR_PAD_LEFT);
                $supVar->qtyPemakaian = ($value["production_qty"] * $valueBahan["bom_detail_qty"]) . " " . $unit_name;
                $supVar->supplies_name = $sup->supplies_name . " " . $supVar->supplies_variant_name;
                array_push($bahan, $supVar);
            }
        }
        return response()->json($bahan);
    }

    public function uploadPhotoProduksi(Request $req)
    {
        // Ambil base64
        $image = $req->photo;

        // Hilangkan prefix base64
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

        // Decode
        $imageData = base64_decode($image);

        // Nama file
        $imageName = 'photo_' . time() . '.png';

        // Path tujuan di public/produksi
        $path = public_path('produksi/' . $imageName);

        // Simpan file
        file_put_contents($path, $imageData);

        (new ProductionPhoto())->insertPhoto([
            "pp_date" => date("Y-m-d"),
            "pp_photo" => 'produksi/' . $imageName
        ]);

        return response()->json([
            'url' => url('photos/' . $imageName)
        ]);
    }

    function getFotoProduksi(Request $req)
    {
        $photos = (new ProductionPhoto())->getPhotos($req->all());
        return $photos;
    }

    
}
