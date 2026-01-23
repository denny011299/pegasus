<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\LogStock;
use App\Models\Production;
use App\Models\ProductionDetails;
use App\Models\ProductionPhoto;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
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
        
        // Pengecekan unique resep
        $bom = Bom::where('product_id', $data['product_id'])->get();
        if (count($bom) > 0){
            return [
                "status"=>-1,
                "message"=>"Resep produk ini sudah ada. Mohon pilih produk lainnya"
            ];
        }
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
        $item = json_decode($req->detail, true);
        $cek = -1;
        $bahan_kurang = [];
        $qty = 1;
        foreach ($item as $key => $value) {
            $qty = 1;
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if ($bom['unit_id'] != $value['unit_id']){
                $pr = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('pr_id', 'desc')
                    ->get();
                foreach ($pr as $key => $p) {
                    if ($p['pr_unit_id_2'] != $value['unit_id']) {
                        $qty *= $p['pr_unit_value_2'];
                    }
                }
            }
            // Pengecekan stock
            foreach ($bom['items'] as $key => $bd) {
                $stok = SuppliesStock::where("supplies_id", "=", $bd['supplies_id'])
                    ->where("unit_id", "=", $bd['unit_id'])->first()->ss_stock;
                if ($stok - ($bd['bom_detail_qty'] * $value['pd_qty'] * $qty) <= 0) {
                    $cek = 1;
                    $s = Supplies::find($bd['supplies_id']);
                    if (!in_array($s['supplies_name'], $bahan_kurang, true)){
                        array_push($bahan_kurang, $s['supplies_name']);
                    }
                }
            }
            // foreach ($bom as $key => $b) {
            //     // Cek relasi produk (untuk dikali produksi bahan mentah)
            // }
        }

        if ($cek == 1) {
            return response()->json([
                "status" => -1,
                "message" => "Bahan baku tidak mencukupi untuk : " . implode(", ", $bahan_kurang)
            ]);
        }

        $p = (new Production())->insertProduction($data);
        foreach ($item as $key => $value) {
            $value['production_id'] = $p->production_id;
            (new ProductionDetails())->insertProductionDetail($value);
        }
        
        foreach ($item as $key => $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            foreach ($bom['items'] as $key => $bd) {
                $stok = SuppliesStock::where("supplies_id", "=", $bd['supplies_id'])
                    ->where("unit_id", "=", $bd['unit_id'])->first();
                $stok->ss_stock -=  ($bd['bom_detail_qty'] * $value['pd_qty'] * $qty);
                $stok->save();
    
                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $p->production_code,
                    'log_type'    => 2,
                    'log_category' => 2,
                    'log_item_id' => $bd['supplies_id'],
                    'log_notes'  => "Pengurangan bahan untuk produksi",
                    'log_jumlah' => ($bd['bom_detail_qty'] * $value['pd_qty'] * $qty),
                    'unit_id'    => $bd['unit_id'],
                ]);
            }

            $v = ProductStock::where("product_variant_id", "=", $value["product_variant_id"])
                ->where("unit_id", "=", $value["unit_id"])->first();
            if(!$v){
                $pv  = ProductVariant::find($value["product_variant_id"]);
                $v = (new ProductStock())->syncStock($pv->product_id);
                $v = ProductStock::where("product_variant_id", "=", $value["product_variant_id"])
                ->where("unit_id", "=", $value["unit_id"])->first();
            }
            $v->ps_stock += intval($value['pd_qty']) * $bom->bom_qty;
            $v->save();
    
            (new ProductStock())->cekStockBerlebih($v);
    
            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $p->production_code,
                'log_type'    => 1,
                'log_category' => 1,
                'log_item_id' => $value["product_variant_id"],
                'log_notes'  => "Produksi produk",
                'log_jumlah' => intval($value['pd_qty']) * $bom->bom_qty,
                'unit_id'    => $value['unit_id'],
            ]);
        }
        

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
        $p = (new Production())->getProduction(["production_id" => $data['production_id']])->first();
        foreach ($p['items'] as $key => $value) {
            $b = Bom::find($value['bom_id']);
            $cek = -1;
            $stok = ProductStock::where("product_variant_id", "=", $value['product_variant_id'])
                    ->where("unit_id", "=", $value['unit_id'])->first()->ps_stock;
            if ($stok - (intval($value['pd_qty']) * $b->bom_qty) < 0) {
                $cek = 1;
            }
        }

        if ($cek == 1) {
            return response()->json([
                "status" => -1,
                "message" => "Stok produk tidak mencukupi"
            ]);
        }
        (new Production())->cancelProduction($data);
        foreach ($p['items'] as $key => $value) {
            (new ProductionDetails())->cancelProductionDetail($value);
        }

        foreach ($p['items'] as $key => $value) {
            $b = bom::find($value->bom_id);
            $v = ProductStock::where("product_variant_id", "=", $value["product_variant_id"])
                ->where("unit_id", "=", $value["unit_id"])->first();
            $v->ps_stock -= intval($value['pd_qty']) * $b->bom_qty;
            $v->save();

            // Catat log
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $p->production_code,
                'log_type'    => 1,
                'log_category' => 2,
                'log_item_id' => $value["product_variant_id"],
                'log_notes'  => "Pembatalan produksi produk",
                'log_jumlah' => intval($value['pd_qty']) * $b->bom_qty,
                'unit_id'    => $value['unit_id'],
            ]);
        }
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
