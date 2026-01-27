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
        $bom = Bom::where('product_id', $data['product_id'])->where('status', 1)->get();
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
        // 1. AGGREGASI: Hitung total kebutuhan bahan mentah dari SEMUA item produksi di awal
        $aggregatedRequirements = [];
        foreach ($item as $key => $value) {
            $qty = 1; // Reset pengali konversi produk untuk setiap item produksi
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (count($bom) == 0) {
                return response()->json([
                    "status" => -1,
                    "message" => "Mohon cek kembali resep bahan mentah"
                ]);
            }

            // Logika pencarian pengali konversi (BOM vs Input User)
            if ($bom['unit_id'] != $value['unit_id']){
                $pr = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('pr_id', 'desc')
                    ->get();
                if (count($pr) == 0) {
                    return response()->json([
                        "status" => -1,
                        "message" => "Mohon masukkan relasi produk terlebih dahulu"
                    ]);
                }
                foreach ($pr as $p) {
                    if ($p['pr_unit_id_2'] != $value['unit_id']) {
                        $qty *= $p['pr_unit_value_2'];
                    }
                }
            }

            // Masukkan ke dalam array agregat berdasarkan supplies_id
            foreach ($bom['items'] as $bd) {
                $id = $bd['supplies_id'];
                // Rumus: Kebutuhan BoM * Qty Produksi * Pengali Konversi Satuan
                $kebutuhanBaris = $bd['bom_detail_qty'] * $value['pd_qty'] * $qty;

                if (!isset($aggregatedRequirements[$id])) {
                    $aggregatedRequirements[$id] = [
                        'total_butuh' => 0,
                        'details' => $bd // Simpan satu contoh detail untuk referensi
                    ];
                }
                $aggregatedRequirements[$id]['total_butuh'] += $kebutuhanBaris;
            }
        }

        // 2. PROCESSING: Eksekusi Konversi Stok (Bongkar Satuan Besar) berdasarkan total agregat
        foreach ($aggregatedRequirements as $suppliesId => $req) {
            $butuhTersedia = $req['total_butuh'];
            $bd = $req['details'];

            $ss = SuppliesStock::where('supplies_id', $suppliesId)
                ->where('status', 1)
                ->orderBy('ss_id', 'desc') 
                ->get();

            if (count($ss) > 0) {
                $virtualStock = [];
                $logSummary = []; 

                foreach ($ss as $stok) {
                    $virtualStock[$stok->ss_id] = [
                        'model' => $stok,
                        'current' => (float)$stok->ss_stock,
                        'unit_id' => $stok->unit_id,
                        'ss_id' => $stok->ss_id
                    ];
                }

                // FUNGSI REKURSIF UNTUK BONGKAR STOK
                $siapkanStok = function($targetKey, $units) use (&$virtualStock, &$logSummary, &$siapkanStok, $bd) {
                    if (!isset($units[$targetKey + 1])) return false; 

                    $stokSekarang = $units[$targetKey];
                    $stokAtas = $units[$targetKey + 1];

                    if ($virtualStock[$stokAtas->ss_id]['current'] <= 0) {
                        if (!$siapkanStok($targetKey + 1, $units)) return false;
                    }

                    $sr = SuppliesRelation::where('supplies_id', $bd['supplies_id'])
                        ->where('su_id_2', $stokSekarang->unit_id)
                        ->where('status', 1)
                        ->first();
                    if (count($sr) == 0) {
                        return response()->json([
                            "status" => -1,
                            "message" => "Mohon masukkan relasi bahan mentah terlebih dahulu"
                        ]);
                    }

                    if ($sr && $virtualStock[$stokAtas->ss_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ss_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr['sr_value_2'];
                        $virtualStock[$stokSekarang->ss_id]['current'] += $hasilBongkar;

                        $baseOrder = $stokAtas->ss_id * 10; 

                        $logSummary[$stokAtas->unit_id . '_cat2'] = [
                            'unit_id' => $stokAtas->unit_id,
                            'jumlah' => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + 1,
                            'cat' => 2,
                            'note' => "Konversi unit dari produksi (Bongkar)",
                            'sort_order' => $baseOrder
                        ];

                        $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                            'unit_id' => $stokSekarang->unit_id,
                            'jumlah' => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                            'cat' => 1,
                            'note' => "Konversi unit dari produksi (Hasil)",
                            'sort_order' => $baseOrder + 1
                        ];
                        return true;
                    }
                    return false;
                };

                $keyPalingBawah = 0;
                $idPalingBawah = $ss[$keyPalingBawah]->ss_id;
                $safety = 0; 

                // Bongkar stok sampai level terbawah mencukupi total agregat
                while ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                    $safety++;
                    if ($safety > 500) break; 
                    if (!$siapkanStok($keyPalingBawah, $ss)) break; 
                }

                // --- FINAL EKSEKUSI PENYIAPAN ---
                if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                    foreach ($virtualStock as $v) {
                        $v['model']->ss_stock = (int)$v['current']; // Simpan sebagai INT sesuai DB
                        $v['model']->save();
                    }
                    
                    usort($logSummary, function($a, $b) {
                        return $a['sort_order'] <=> $b['sort_order'];
                    });

                    foreach ($logSummary as $l) {
                        (new LogStock())->insertLog([
                            'log_date' => now(), 
                            'log_kode' => "-", 
                            'log_type' => 2,
                            'log_category' => $l['cat'], 
                            'log_item_id' => $bd['supplies_id'],
                            'log_notes' => $l['note'], 
                            'log_jumlah' => $l['jumlah'], 
                            'unit_id' => $l['unit_id'],
                        ]);
                    }
                } else {
                    $cek = 1;
                    $s = Supplies::find($suppliesId);
                    if (!in_array($s['supplies_name'], $bahan_kurang, true)) {
                        array_push($bahan_kurang, $s['supplies_name']);
                    }
                }
            }
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
        // --- TAHAP 2: EKSEKUSI REAL (PENGURANGAN & PENAMBAHAN) ---
        foreach ($item as $key => $value) {
            // 1. Reset total konversi setiap baris item
            $qty_konversi_produk = 1; 
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();

            // 2. Ambil ID Unit Produksi dari input user secara tegas
            $unitIdInputUser = $value['unit_id']; 

            if ($bom['unit_id'] != $unitIdInputUser){
                $pr = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('pr_id', 'desc')
                    ->get();
                
                foreach ($pr as $r) {
                    // Pastikan pengali hanya dihitung jika unit tersebut bukan unit target user
                    if ($r['pr_unit_id_2'] != $unitIdInputUser) {
                        $qty_konversi_produk *= $r['pr_unit_value_2'];
                    }
                }
            }

            // 3. PENGURANGAN BAHAN (SUPPLIES)
            foreach ($bom['items'] as $bd) {
                $stokSupplies = SuppliesStock::where("supplies_id", $bd['supplies_id'])
                    ->where("unit_id", $bd['unit_id'])->first();
                
                $jumlahKurang = (int)($bd['bom_detail_qty'] * $value['pd_qty'] * $qty_konversi_produk);
                
                $stokSupplies->ss_stock -= $jumlahKurang;
                $stokSupplies->save();

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $p->production_code,
                    'log_type' => 2, 'log_category' => 2,
                    'log_item_id' => $bd['supplies_id'],
                    'log_notes' => "Pengurangan bahan untuk produksi",
                    'log_jumlah' => $jumlahKurang,
                    'unit_id' => $bd['unit_id'],
                ]);
            }

            // 4. PENAMBAHAN PRODUK (PRODUCT STOCK) - BAGIAN KRUSIAL
            // Kita pastikan mencari berdasarkan variant DAN unit_id yang diketik user
            $v = ProductStock::where("product_variant_id", $value["product_variant_id"])
                ->where("unit_id", $unitIdInputUser) // Pakai variabel tegas
                ->first();
            
            // Jika wadah unit (misal: Kilogram) belum ada, buatkan dulu
            if(!$v){
                $pv = ProductVariant::find($value["product_variant_id"]);
                dd($pv);
                (new ProductStock())->syncStock($pv->product_id);
                
                // Cari ulang setelah sync
                $v = ProductStock::where("product_variant_id", $value["product_variant_id"])
                    ->where("unit_id", $unitIdInputUser)
                    ->first();
            }

            // HITUNG TAMBAH: Cukup Qty Produksi * Hasil Per BOM
            $jumlahTambah = (int)($value['pd_qty'] * $bom->bom_qty);
            
            // VALIDASI TERAKHIR: Cek apakah $v benar-benar mengarah ke unit yang benar
            if ($v && $v->unit_id == $unitIdInputUser) {
                $v->ps_stock += $jumlahTambah;
                $v->save();
            } else {
                // Jika tetap nyasar ke Liter, kita paksa bikin baru atau lempar error
                // Ini adalah "Safety Net" agar tidak terjadi salah alamat stok
                throw new \Exception("Gagal menemukan wadah stok untuk Unit ID: " . $unitIdInputUser);
            }

            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode' => $p->production_code,
                'log_type' => 1, 'log_category' => 1,
                'log_item_id' => $value["product_variant_id"],
                'log_notes' => "Produksi produk",
                'log_jumlah' => $jumlahTambah,
                'unit_id' => $unitIdInputUser,
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
        (new ProductionDetails())->cancelProductionDetail($data);

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
