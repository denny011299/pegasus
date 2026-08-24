<?php

namespace App\Models;

use App\Support\UnitRollUp;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDeliveryDetail extends Model
{
    protected $table = "purchase_delivery_orders_details";
    protected $primaryKey = "pdod_id";
    public $timestamps = true;
    public $incrementing = true;

    function getPoDeliveryDetail($data = [])
    {
        $data = array_merge([
            "pdo_id"   => null,
            "pdod_id"   => null,
        ], $data);

        $result = PurchaseOrderDeliveryDetail::where("status", ">=", 1);

        if ($data["pdo_id"]) $result->where("pdo_id", "=", $data["pdo_id"]);
        if ($data["pdod_id"]) $result->where("pdod_id", "=", $data["pdod_id"]);

        $result->orderBy("created_at", "asc");
        $result = $result->get();

        foreach ($result as $key => $value) {

            $sv = SuppliesVariant::find($value->supplies_variant_id);
            $s = Supplies::find($sv->supplies_id);
            $value->supplies_name = $s->supplies_name;
            $value->supplies_variant_name = $sv->supplies_variant_name;
        }

        return $result;
    }

    function insertPoDeliveryDetail($data)
    {
        $t = new PurchaseOrderDeliveryDetail();
        $t->pdo_id = $data["pdo_id"];
        $t->supplies_variant_id = $data["supplies_variant_id"];
        $t->pdod_sku = $data["pdod_sku"];
        $t->pdod_qty = $data["pdod_qty"];
        $t->status = $data["status"]??1;
        $t->save();
        if(isset($data["statusPO"])&&$data["statusPO"]==2){
            $sv = SuppliesVariant::find($data["supplies_variant_id"]);

            // Ditambahkan (2026-08-25): penerimaan barang PO dulu flat (`ss_stock += pdod_qty`)
            // tanpa konversi satuan -- 24 Piece yang dibeli tetap 24 Piece walaupun 1 DOS = 12 Piece,
            // padahal 24 Piece hasil PRODUKSI sudah naik jadi 2 DOS sejak GitHub #19. Barang fisik
            // yang sama jadi terepresentasi beda tergantung cara masuknya. Sekarang dinaikkan
            // berjenjang lewat UnitRollUp, yang hanya menaikkan ke satuan yang SUDAH punya baris
            // stok aktif (tidak membuat baris baru diam-diam).
            //
            // PENTING: tolakPO() -- pembatalan PO yang sudah di-ACC -- ikut disesuaikan di
            // SupplierController supaya bisa membongkar satuan besar saat mengembalikan stok.
            // Tanpa itu, roll-up di sini membuat pembatalan PO selalu gagal "Stok bahan tidak
            // mencukupi", karena stoknya sudah tidak lagi berada di satuan yang dipesan.
            $rollUp = UnitRollUp::planSupplies(
                (int) $sv->supplies_id,
                (int) $data["unit_id"],
                (int) $data["pdod_qty"]
            );

            // Entry ke-0 selalu satuan asal. Baris stoknya SENGAJA tidak di-null-guard: kalau
            // kombinasi supplies_id + unit_id tidak ketemu, itu unit_id yang salah dan harus
            // meledak seperti perilaku sebelumnya supaya transaksi accPO() rollback -- bukan
            // di-skip diam-diam, yang berarti "barang diterima tapi stok tidak pernah bertambah"
            // tanpa ada yang tahu (antipattern yang sudah pernah diperbaiki di accProduction()).
            $base = array_shift($rollUp);
            $s = SuppliesStock::where("supplies_id", "=", $sv->supplies_id)
                ->where("unit_id", "=", $base['unit_id'])
                ->where("status", "=", 1)
                ->first();
            $s->ss_stock += $base['qty'];
            $s->save();

            // Level di atasnya dijamin punya baris stok aktif — UnitRollUp hanya menaikkan ke
            // satuan yang sudah punya baris (lihat allowedSuppliesUnitIds()).
            foreach ($rollUp as $credit) {
                if ($credit['qty'] <= 0) continue;

                $row = SuppliesStock::where("supplies_id", "=", $sv->supplies_id)
                    ->where("unit_id", "=", $credit['unit_id'])
                    ->where("status", "=", 1)
                    ->first();
                if (!$row) continue;

                $row->ss_stock += $credit['qty'];
                $row->save();
            }
        }
        return $t->pdod_id;
    }

    // DEPRECATED (2026-08-04): only reachable via the deprecated manual PO Delivery workflow
    // (SupplierController::updatePoDelivery()/accPoDelivery()/declinePoDelivery()) — accPO() never
    // calls this (it only ever inserts, never updates, its auto-generated delivery). See
    // KNOWN_ISSUES.md.
    function updatePoDeliveryDetail($data)
    {
        $s = SuppliesVariant::find($data["supplies_variant_id"]);
        $st = SuppliesStock::where("supplies_id", "=", $s->supplies_id)
            ->where("unit_id", "=", $data["unit_id"])
            ->where("status", "=", 1)
            ->first();
        $t = PurchaseOrderDeliveryDetail::find($data["pdod_id"]);
        if(isset($data["statusPO"])&&$data["statusPO"]==2){
            
            $t->supplies_variant_id = $data["supplies_variant_id"];
            $t->pdod_sku = $data["sku"];
            $t->pdod_qty = $data["pdod_qty"];
            $t->save();

            //ditambah lagi
            $st->ss_stock += $data["pdod_qty"];
            $st->save();
        }
        else if(isset($data["statusPO"])&&$data["statusPO"]==0){
            $st->ss_stock -= $t->pdod_qty;
            $st->save();

            $t->supplies_variant_id = $data["supplies_variant_id"];
            $t->pdod_sku = $data["sku"];
            $t->pdod_qty = $data["pdod_qty"];
            $t->save();

            //ditambah lagi
            $st->ss_stock -= $data["pdod_qty"];
            $st->save();
        }
        return $t->pdod_id;
    }

    function deletePoDeliveryDetail($data)
    {
        $t = PurchaseOrderDeliveryDetail::find($data["pdod_id"]);
        $t->status = 0; // soft delete
        $t->save();

        $s = SuppliesVariant::find($t->supplies_variant_id);
        $s->supplies_variant_stock -= $t->pdod_qty;
        $s->save();
    }
}
