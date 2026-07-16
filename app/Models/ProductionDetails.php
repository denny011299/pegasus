<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class ProductionDetails extends Model
{
    protected $table = "production_details";
    protected $primaryKey = "pd_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductionDetail($data = [])
    {
        $data = array_merge([
            "production_id" => null,
            "production_ids" => null,
            "report" => null
        ], $data);

        $result = ProductionDetails::query();

        if ($data["report"] == null) {
            $result->where('status', '>=', 1);
        } else if ($data["report"]) {
            $result->where('status', '>=', 0);
        }

        if ($data["production_ids"]) {
            $result->whereIn('production_id', $data["production_ids"]);
        } else if ($data["production_id"]) {
            $result->where('production_id', '=', $data["production_id"]);
        }

        $result = $result->orderBy('pd_id', 'asc')->get();

        return $this->enrichDetailsCollection($result);
    }

    public function enrichDetailsCollection($details)
    {
        $details = collect($details);
        if ($details->isEmpty()) {
            return $details;
        }

        $variantIds = $details->pluck('product_variant_id')->filter()->unique()->values()->all();
        $variants = $variantIds !== []
            ? ProductVariant::whereIn('product_variant_id', $variantIds)->get()->keyBy('product_variant_id')
            : collect();

        $productIds = $variants->pluck('product_id')->filter()->unique()->values()->all();
        $products = $productIds !== []
            ? Product::whereIn('product_id', $productIds)->get()->keyBy('product_id')
            : collect();

        $unitIds = $details->pluck('unit_id')->filter()->unique()->values()->all();
        $units = $unitIds !== []
            ? Unit::whereIn('unit_id', $unitIds)->get()->keyBy('unit_id')
            : collect();

        foreach ($details as $value) {
            $v = $variants->get($value->product_variant_id);
            $p = $v ? $products->get($v->product_id) : null;
            $unit = $units->get($value->unit_id);

            $value->product_variant_id = $v ? $v->product_variant_id : $value->product_variant_id;
            $value->product_sku = $v ? $v->product_variant_sku : '-';
            $value->product_name = $v && $p
                ? $p->product_name . ' ' . $v->product_variant_name
                : '-';
            $value->unit_name = $unit ? $unit->unit_name : '-';
        }

        return $details;
    }

    function insertProductionDetail($data)
    {
        $t = new ProductionDetails();
        $t->production_id = $data["production_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->pd_qty = $data["pd_qty"];
        $t->bom_id = $data["bom_id"];
        $t->unit_id = $data["unit_id"];
        $t->list_bahan = $data["list_bahan"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t;
    }

    function updateProductionDetail($data)
    {
        $t = ProductionDetails::find($data["pd_id"]);
        $t->production_id = $data["production_id"];
        $t->product_variant_id = $data["product_variant_id"];
        $t->pd_qty = $data["pd_qty"];
        $t->bom_id = $data["bom_id"];
        $t->unit_id = $data["unit_id"];
        $t->list_bahan = $data["list_bahan"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();
        return $t->pd_id;
    }

    function deleteProductionDetail($data)
    {
        $t = ProductionDetails::find($data["pd_id"]);
        $t->notes = $data["delete_reason"];
        $t->status = 2;
        $t->save();    
    }

    function cancelProductionDetail($data){
        $pd = ProductionDetails::where('production_id', $data['production_id'])->where('status', 1)->get();
        foreach ($pd as $key => $value) {
            $value->status = 2;
            $value->save();
        }
        // $t = (new Production())->getProduction(["production_id" => $data['production_id']])->first();
        // foreach ($t['items'] as $key => $detail) {
        //     $qty = 1;
        //     $b = (new Bom())->getBom([
        //         "bom_id" => $detail->bom_id
        //     ])->first();
        //     // Cek relasi produk (untuk dikali produksi bahan mentah)
        //     if ($b['unit_id'] != $detail['unit_id']){
        //         $pr = ProductRelation::where('product_variant_id', $detail['product_variant_id'])
        //             ->where('status', 1)
        //             ->orderBy('pr_id', 'desc')
        //             ->get();
        //         foreach ($pr as $p) {
        //             if ($p['pr_unit_id_2'] != $detail['unit_id']) {
        //                 $qty *= $p['pr_unit_value_2'];
        //             }
        //         }
        //     }
        //     foreach ($b['items'] as $value) {
        //         $infoBahan = Supplies::find($value->supplies_id);
        //         $namaBahan = $infoBahan->supplies_name;

        //         // 2. LOGIKA DOS / PACK (Case Insensitive)
        //         if (preg_match('/dos|pack/i', $namaBahan)) {
        //             // Cari isi per Dos (Pcs ke Dos)
        //             $relasiKonversi = ProductRelation::where('product_variant_id', $detail['product_variant_id'])
        //                             ->where('pr_unit_id_2', $b['unit_id'])
        //                             ->where('status', 1)
        //                             ->first();

        //             $nilaiIsiDos = ($relasiKonversi) ? (float)$relasiKonversi->pr_unit_value_2 : 1;
                    
        //             // Total Pcs yang dibatalkan
        //             $totalPcsBatal = $detail->pd_qty * $qty;

        //             // Hitung jumlah yang balik (Gunakan FLOOR agar sinkron dengan saat potong)
        //             $jumlahKembali = floor($totalPcsBatal / $nilaiIsiDos) * $value->bom_detail_qty;
        //         } else {
        //             // 3. LOGIKA STANDAR (Label, Plastik, dll)
        //             $jumlahKembali = $value->bom_detail_qty * $detail->pd_qty * $qty;
        //         }

        //         // 4. EKSEKUSI PENGEMBALIAN STOK
        //         if ($jumlahKembali > 0) {
        //             $s = SuppliesStock::where("supplies_id", $value->supplies_id)
        //                 ->where("unit_id", $value->unit_id)
        //                 ->first();

        //             if ($s) {
        //                 $s->ss_stock += $jumlahKembali; // Tambahkan kembali ke stok
        //                 $s->save();

        //                 // Log Stock
        //                 (new LogStock())->insertLog([
        //                     'log_date'    => now(),
        //                     'log_kode'    => $t->production_code,
        //                     'log_type'    => 2,
        //                     'log_category' => 1,
        //                     'log_item_id' => $value->supplies_id,
        //                     'log_notes'   => "Pengembalian stok bahan akibat pembatalan produksi",
        //                     'log_jumlah'  => $jumlahKembali,
        //                     'unit_id'     => $value->unit_id,
        //                 ]);
        //             }
        //         }
        //     }
        // }
    }
}
