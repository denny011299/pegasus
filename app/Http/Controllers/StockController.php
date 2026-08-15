<?php

namespace App\Http\Controllers;

use App\Models\LogStock;
use App\Models\ManageStock;
use App\Models\Product;
use App\Models\ProductIssues;
use App\Models\ProductIssuesDetail;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderDetailInvoice;
use App\Models\ReturnSupplies;
use App\Models\ReturnSuppliesDetail;
use App\Models\Staff;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\StockAlertSupplies;
use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameDetailBahan;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class StockController extends Controller
{
    // Stock Opname
    public function StockOpname()
    {
        return view('Backoffice.Inventory.Stock_Opname');
    }

    function getStockOpname(Request $req)
    {
        $data = (new StockOpname())->getStockOpname();
        return response()->json($data);
    }

    function insertStockOpname(Request $req)
    {
        $data = $req->all();
        $id =  (new StockOpname())->insertStockOpname($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["sto_id"] = $id;
            (new StockOpnameDetail())->insertDetail($value);
        }
        return response()->json(['status' => 1, 'sto_id' => $id]);
    }

    // Ditambahkan (2026-08-05): method ini sebelumnya di-comment-out seluruhnya — route
    // POST /updateStockOpname sudah ada dan sudah dipanggil dari CreateStockOpname.js (baik mode
    // edit biasa maupun canEditDraft), tapi memanggilnya crash 500 ("Call to undefined method")
    // karena method-nya tidak ada. Diaktifkan kembali, mirror persis updateStockOpnameBahan() di
    // bawah yang selama ini sudah aktif.
    function updateStockOpname(Request $req)
    {
        $data = $req->all();
        $id = (new StockOpname())->updateStockOpname($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["sto_id"] = $id;
            if (isset($value["stod_id"])) (new StockOpnameDetail())->updateDetail($value);
            else (new StockOpnameDetail())->insertDetail($value);
        }
    }

    // Keluarkan dokumen dari mode draft — dipanggil dari tombol "Ajukan" (lihat
    // CreateStockOpname.js), setelah insert/update terakhir sebagai draft berhasil. Status dokumen
    // TIDAK berubah di sini (masih 1/pending) — accStockOpname() sendiri yang menolak selama
    // is_draft masih true, method ini murni membuka gerbang itu.
    function submitStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpname())->submitStockOpname($data);
    }

    function deleteStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpname())->deleteStockOpname($data);
    }

    // Stock Opname Detail
    public function DetailStockOpname($id)
    {
        if ($id == -1) {
            return view('Backoffice.Inventory.CreateStockOpname', [
                'data' => [],
                'mode' => 1
            ]);
        }

        $sto = (new StockOpname())->getStockOpname(['sto_id' => $id, 'with_items' => true])->first();
        if (!$sto) {
            abort(404);
        }

        $items = [];
        foreach ($sto->item ?? [] as $detail) {
            $units = [];

            foreach ($detail->stock as $s) {
                $units[] = [
                    'unit_id'          => $s->unit_id,
                    'unit_short_name'  => $s->unit_short_name,
                    'system_qty'       => $this->getQty($detail->stod_system, $s->unit_short_name),
                    'real_qty'         => $this->getQty($detail->stod_real, $s->unit_short_name),
                    'selisih_qty'      => $this->getQty($detail->stod_selisih, $s->unit_short_name),
                ];
            }

            $items[] = [
                'product_id'           => $detail->product_id,
                'product_variant_id'   => $detail->product_variant_id,
                'product_variant_sku'  => $detail->product_variant_sku,
                'pr_name'              => $detail->pr_name,
                'product_variant_name' => $detail->product_variant_name,
                'stod_notes'           => $detail->stod_notes,
                'units'                => $units,
                'stod_system'          => $detail->stod_system,
                'stod_real'            => $detail->stod_real,
                'stod_selisih'         => $detail->stod_selisih,
            ];
        }

        usort($items, function ($a, $b) {
            $cmp = strcasecmp($a['pr_name'] ?? '', $b['pr_name'] ?? '');
            if ($cmp !== 0) return $cmp;
            return strcasecmp($a['product_variant_name'] ?? '', $b['product_variant_name'] ?? '');
        });

        $data = [
            'sto_id'      => $sto->sto_id,
            'sto_date'    => $sto->sto_date,
            'staff_id'    => $sto->staff_id,
            'staff_name'  => $sto->staff_name,
            'category_id' => $sto->category_id,
            'sto_notes'   => $sto->sto_notes,
            'status'      => $sto->status,
            // Ditambahkan (2026-08-05): CreateStockOpname.js membaca data.is_draft/data.created_by
            // untuk canEditDraft — sebelumnya keduanya tidak ada di array ini sama sekali, jadi
            // selalu undefined di frontend (draft tidak pernah terdeteksi sebagai draft).
            'is_draft'    => (bool) $sto->is_draft,
            'created_by'  => $sto->created_by,
            'item'        => $items
        ];

        return view('Backoffice.Inventory.CreateStockOpname', [
            'data' => $data,
            'mode' => 2
        ]);
    }

    private function getQty($string, $unit)
    {
        // contoh: "12 jerigen, 0 DOS, 0 pcs"
        foreach (explode(',', $string) as $part) {
            [$qty, $u] = explode(' ', trim($part));
            if ($u === $unit) {
                return (int) $qty;
            }
        }
        return 0;
    }

    // Kebalikan dari getQty() -- rakit ['DOS' => 10, 'pcs' => 2] jadi "10 DOS, 2 pcs".
    private function buildQtyString(array $qtyByUnit): string
    {
        $parts = [];
        foreach ($qtyByUnit as $unit => $qty) {
            $parts[] = $qty . ' ' . $unit;
        }
        return implode(', ', $parts);
    }

    /**
     * stod_system/stod_selisih (stobd_* untuk Bahan) dibekukan sekali saat dokumen dibuat/diedit
     * dan tidak pernah di-refresh -- begitu ada peristiwa stok LAIN APAPUN sebelum dokumen ini
     * diputuskan (stock opname lain di-ACC, SO dikirim, dst.), PDF-nya diam-diam membandingkan
     * dengan angka sistem yang sudah basi. getDetail()/getDetailBulk() sudah menempelkan koleksi
     * stok LIVE per unit (`->stock`, lihat ProductVariant::getProductVariantBulk()/
     * Supplies::getSuppliesBulk()) -- pakai itu, bukan string yang tersimpan, TAPI HANYA untuk
     * dokumen yang belum diputuskan (status masih 1/menunggu). Snapshot dokumen yang sudah
     * disetujui/ditolak adalah catatan historis yang sengaja dibekukan (lihat accStockOpname() --
     * dibekukan ke nilai sebenarnya saat itu terjadi) dan TIDAK BOLEH dihitung ulang live, atau
     * dokumen yang sudah disetujui akan selalu terlihat "tidak ada selisih" selamanya setelahnya.
     */
    private function refreshLiveSystemQty($detail, string $realKey, string $systemKey, string $selisihKey, string $stockQtyKey)
    {
        foreach ($detail as $item) {
            $liveByUnit = [];
            foreach ($item->stock ?? [] as $s) {
                $liveByUnit[$s->unit_short_name] = (int) $s->{$stockQtyKey};
            }
            if (empty($liveByUnit)) {
                continue;
            }

            $selisihByUnit = [];
            foreach ($liveByUnit as $unitName => $systemQty) {
                $realQty = $this->getQty($item->{$realKey} ?? '', $unitName);
                $selisihByUnit[$unitName] = $realQty - $systemQty;
            }

            $item->{$systemKey} = $this->buildQtyString($liveByUnit);
            $item->{$selisihKey} = $this->buildQtyString($selisihByUnit);
        }

        return $detail;
    }

    function getDetailStockOpname(Request $req)
    {
        $data = StockOpnameDetail::getDetail($req->all());
        return response()->json($data);
    }

    function insertDetailStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetail())->insertDetailStockOpname($data);
    }

    function updateDetailStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetail())->updateDetailStockOpname($data);
    }

    function deleteDetailStockOpname(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetail())->deleteDetailStockOpname($data);
    }

    function accStockOpname(Request $req) {
        $data = $req->all();
        $stod = json_decode($data['item'], true);
        $sto = StockOpname::find($data['sto_id']);

        // Ditambahkan (2026-08-05): gerbang draft — kolom is_draft sudah ada di DB tapi baru
        // sekarang benar-benar ditulis (lihat StockOpname::insertStockOpname()/updateStockOpname())
        // dan baru sekarang ditegakkan di sini juga. Status berbeda dari guard status!=1 di bawah
        // (-1 bukan -2) supaya frontend (CreateStockOpname.js) bisa membedakan "belum diajukan"
        // dari "sudah diproses orang lain".
        if ($sto->is_draft) {
            return response()->json([
                "status" => -1,
                "header" => "Gagal ACC",
                "message" => "Dokumen masih berupa draft — ajukan (submit) dokumen ini dulu sebelum bisa di-ACC",
            ]);
        }

        // Ditambahkan: dulu tidak ada pengecekan status sama sekali (cuma is_draft di halaman
        // insert) — beda dengan PO/SO/Production yang semuanya menolak kalau status != 1. Tanpa
        // ini, approve ulang pada dokumen yang sudah disetujui diam-diam menimpa ps_stock lagi
        // dengan real_qty apa pun yang dibawa request kedua.
        if ($sto->status != 1) {
            $staff = Staff::find($sto->acc_by)->staff_name ?? '-';
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }

        // GitHub #53 follow-up: bekukan stod_system/stod_selisih ke nilai SEBENARNYA yang dipakai
        // approval ini (bukan lagi nilai basi dari saat dokumen dibuat/diedit) -- ini jadi catatan
        // historis permanen dokumen, mirror persis "before" yang sudah ditulis ke log_stocks di
        // bawah, cuma sekarang juga disimpan balik ke stock_opname_details.
        $detailRows = StockOpnameDetail::where('sto_id', $sto->sto_id)
            ->where('status', 1)
            ->get()
            ->keyBy('product_variant_id');
        $allUnitIds = collect($stod)
            ->flatMap(fn ($v) => collect($v['units'] ?? [])->pluck('unit_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $unitNames = $allUnitIds !== []
            ? Unit::whereIn('unit_id', $allUnitIds)->pluck('unit_short_name', 'unit_id')
            : collect();

        DB::beginTransaction();
        try {
        $produk_gagal = [];
        foreach ($stod as $key => $value) {
            $liveSystemByUnit = [];
            $realByUnit = [];
            foreach ($value['units'] as $u) {
                $s = ProductStock::where('product_variant_id', $value['product_variant_id'])
                    ->where('unit_id', $u['unit_id'])
                    ->first();

                // Ditambahkan: dulu di-assign langsung tanpa null-check — sebuah unit_id yang
                // salah/tidak match menyebabkan crash "Attempt to assign property on null" di
                // tengah loop, sementara item-item sebelumnya sudah kadung ditimpa permanen.
                if (!$s) {
                    $pv = ProductVariant::find($value['product_variant_id']);
                    $namaProduk = '-';
                    if ($pv) {
                        $pr = Product::find($pv->product_id);
                        $namaProduk = trim(($pr->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
                        if ($namaProduk === '') $namaProduk = $pv->product_variant_name ?? '-';
                    }
                    if (!in_array($namaProduk, $produk_gagal, true)) $produk_gagal[] = $namaProduk;
                    continue;
                }

                $beforeStock = $s->ps_stock;

                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $sto->sto_code,
                    'log_type'    => 1,
                    'log_category' => 2,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes'  => "Stock Opname Produk",
                    'log_jumlah' => $beforeStock,
                    'unit_id'    => $u['unit_id'],
                ]);

                $s->ps_stock = $u['real_qty'];
                $s->save();

                (new LogStock())->insertLog([
                    // Catat log
                    'log_date' => now(),
                    'log_kode'    => $sto->sto_code,
                    'log_type'    => 1,
                    'log_category' => 1,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes'  => "Stock Opname Produk",
                    'log_jumlah' => $s->ps_stock,
                    'unit_id'    => $u['unit_id'],
                ]);

                $unitName = $unitNames[$u['unit_id']] ?? ('unit#'.$u['unit_id']);
                $liveSystemByUnit[$unitName] = (int) $beforeStock;
                $realByUnit[$unitName] = (int) $u['real_qty'];
            }

            $detailRow = $detailRows->get($value['product_variant_id']);
            if ($detailRow && !empty($liveSystemByUnit)) {
                $selisihByUnit = [];
                foreach ($liveSystemByUnit as $unitName => $systemQty) {
                    $selisihByUnit[$unitName] = ($realByUnit[$unitName] ?? 0) - $systemQty;
                }
                $detailRow->stod_system = $this->buildQtyString($liveSystemByUnit);
                $detailRow->stod_selisih = $this->buildQtyString($selisihByUnit);
                $detailRow->save();
            }
        }

        if (count($produk_gagal) > 0) {
            DB::rollBack();
            return response()->json([
                "status" => 0,
                "header" => "Gagal ACC",
                "message" => "Baris stok tidak ditemukan untuk: " . implode(', ', $produk_gagal),
            ]);
        }

        $sto->status = 2;
        $sto->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $sto->save();
        DB::commit();
        return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function tolakStockOpname(Request $req) {
        $data = $req->all();
        $sto = StockOpname::find($data["sto_id"]);

        $sto->status = 3; // Tolak
        $sto->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $sto->save();
    }

    function generateStockOpname($id) {
        $param['stockOpname'] = StockOpname::find($id);
        $param['staff_name'] = Staff::find($param['stockOpname']['staff_id']);
        $param["detail"] = (new StockOpnameDetail())->getDetail(['sto_id' => $id]);

        if ((int) $param['stockOpname']['status'] === 1) {
            $this->refreshLiveSystemQty($param['detail'], 'stod_real', 'stod_system', 'stod_selisih', 'ps_stock');
        }

        if ($param['stockOpname']['status'] == 1) $param['status'] = "Menunggu";
        else if ($param['stockOpname']['status'] == 2) $param['status'] = "Disetujui";
        else if ($param['stockOpname']['status'] == 3) $param['status'] = "Ditolak";

        if(count($param["detail"])<=0){
            return -1;
        }

        $u = session()->get('user');
        $param['printed_by'] = $u ? ($u->staff_name ?? '-') : '-';
        $param['printed_at'] = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('Backoffice.PDF.Opname', $param);
        return $pdf->download('Stock Opname_'.$param["stockOpname"]["sto_code"].'.pdf');
    }

    // Stock Opname
    public function StockOpnameBahan()
    {
        return view('Backoffice.Inventory.Stock_Opname_Bahan');
    }

    function getStockOpnameBahan(Request $req)
    {
        $data = (new StockOpnameBahan())->getStockOpnameBahan();
        return response()->json($data);
    }

    function insertStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $id =  (new StockOpnameBahan())->insertStockOpnameBahan($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["stob_id"] = $id;
            (new StockOpnameDetailBahan())->insertDetail($value);
        }
        return response()->json(['status' => 1, 'stob_id' => $id]);
    }

    function updateStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $id = (new StockOpnameBahan())->updateStockOpnameBahan($data);
        foreach (json_decode($req->item, true) as $key => $value) {
            $value["stob_id"] = $id;
            if (isset($value["stod_id"])) (new StockOpnameDetailBahan())->updateDetail($value);
            else (new StockOpnameDetailBahan())->insertDetail($value);
        }
    }

    // Mirrors submitStockOpname() above — see KNOWN_ISSUES.md "Stock Opname's draft feature is
    // entirely non-functional".
    function submitStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameBahan())->submitStockOpnameBahan($data);
    }

    function deleteStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameBahan())->deleteStockOpnameBahan($data);
    }

    // Stock Opname Detail
    public function DetailStockOpnameBahan($id)
    {
        if ($id != -1) {
            $rows = (new StockOpnameBahan())->getStockOpnameBahan(['stob_id' => $id, 'with_items' => true]);
            if ($rows->isEmpty()) {
                abort(404);
            }
            $param['data'] = $rows[0];
            if (!empty($param['data']->item)) {
                $sorted = collect($param['data']->item)
                    ->sortBy(fn($i) => strtolower($i->supplies_name ?? ''), SORT_STRING)
                    ->values();
                $param['data']->item = $sorted;
            }
            $param['mode'] = 2;
        } else {
            $param["data"] = [];
            $param["mode"] = 1;
        }
        return view('Backoffice.Inventory.CreateStockOpnameSupplies')->with($param);
    }

    function getDetailStockOpnameBahan(Request $req)
    {
        $data = StockOpnameDetailBahan::getDetail($req->all());
        return response()->json($data);
    }

    function insertDetailStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetailBahan())->insertDetailStockOpname($data);
    }

    function updateDetailStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetailBahan())->updateDetailStockOpname($data);
    }

    function deleteDetailStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameDetailBahan())->deleteDetailStockOpname($data);
    }

    function accStockOpnameBahan(Request $req) {
        $data = $req->all();
        $stod = json_decode($data['item'], true);
        $stob = StockOpnameBahan::find($data['stob_id']);

        // Mirrors accStockOpname()'s draft gate above.
        if ($stob->is_draft) {
            return response()->json([
                "status" => -1,
                "header" => "Gagal ACC",
                "message" => "Dokumen masih berupa draft — ajukan (submit) dokumen ini dulu sebelum bisa di-ACC",
            ]);
        }

        // Mirrors accStockOpname()'s status guard above.
        if ($stob->status != 1) {
            $staff = Staff::find($stob->acc_by)->staff_name ?? '-';
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }

        // GitHub #53 follow-up: mirrors accStockOpname() above -- bekukan stobd_system/
        // stobd_selisih ke nilai sebenarnya yang dipakai approval ini.
        $detailRows = StockOpnameDetailBahan::where('stob_id', $stob->stob_id)
            ->where('status', 1)
            ->get()
            ->keyBy('supplies_id');
        $allUnitIds = collect($stod)
            ->flatMap(fn ($v) => collect($v['sp_units'] ?? [])->pluck('unit_id'))
            ->filter()
            ->unique()
            ->values()
            ->all();
        $unitNames = $allUnitIds !== []
            ? Unit::whereIn('unit_id', $allUnitIds)->pluck('unit_short_name', 'unit_id')
            : collect();

        DB::beginTransaction();
        try {
        $bahan_gagal = [];
        foreach ($stod as $key => $value) {
            $liveSystemByUnit = [];
            $realByUnit = [];
            foreach ($value['sp_units'] as $u) {
                $s = SuppliesStock::where('supplies_id', $value['supplies_id'])
                    ->where('unit_id', $u['unit_id'])
                    ->first();

                // Mirrors accStockOpname()'s null-guard above.
                if (!$s) {
                    $sup = Supplies::find($value['supplies_id']);
                    $namaBahan = $sup->supplies_name ?? "id {$value['supplies_id']}";
                    if (!in_array($namaBahan, $bahan_gagal, true)) $bahan_gagal[] = $namaBahan;
                    continue;
                }

                $beforeStock = $s->ss_stock;

                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $stob->stob_code,
                    'log_type'    => 2,
                    'log_category' => 2,
                    'log_item_id' => $value['supplies_id'],
                    'log_notes'  => "Stock Opname Bahan Mentah",
                    'log_jumlah' => $beforeStock,
                    'unit_id'    => $u['unit_id'],
                ]);

                $s->ss_stock = $u['real_qty'];
                $s->save();

                // Catat log
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $stob->stob_code,
                    'log_type'    => 2,
                    'log_category' => 1,
                    'log_item_id' => $value['supplies_id'],
                    'log_notes'  => "Stock Opname Bahan Mentah",
                    'log_jumlah' => $s->ss_stock,
                    'unit_id'    => $u['unit_id'],
                ]);

                $unitName = $unitNames[$u['unit_id']] ?? ('unit#'.$u['unit_id']);
                $liveSystemByUnit[$unitName] = (int) $beforeStock;
                $realByUnit[$unitName] = (int) $u['real_qty'];
            }

            $detailRow = $detailRows->get($value['supplies_id']);
            if ($detailRow && !empty($liveSystemByUnit)) {
                $selisihByUnit = [];
                foreach ($liveSystemByUnit as $unitName => $systemQty) {
                    $selisihByUnit[$unitName] = ($realByUnit[$unitName] ?? 0) - $systemQty;
                }
                $detailRow->stobd_system = $this->buildQtyString($liveSystemByUnit);
                $detailRow->stobd_selisih = $this->buildQtyString($selisihByUnit);
                $detailRow->save();
            }
        }

        if (count($bahan_gagal) > 0) {
            DB::rollBack();
            return response()->json([
                "status" => 0,
                "header" => "Gagal ACC",
                "message" => "Baris stok tidak ditemukan untuk: " . implode(', ', $bahan_gagal),
            ]);
        }

        $stob->status = 2;
        $stob->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $stob->save();
        DB::commit();
        return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function tolakStockOpnameBahan(Request $req) {
        $data = $req->all();
        $stob = StockOpnameBahan::find($data["stob_id"]);

        $stob->status = 3; // Tolak
        $stob->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $stob->save();
    }

    function generateStockOpnameBahan($id) {
        $param['stockOpname'] = StockOpnameBahan::find($id);
        $param['staff_name'] = Staff::find($param['stockOpname']['staff_id']);
        $param["detail"] = (new StockOpnameDetailBahan())->getDetail(['stob_id' => $id]);

        if ((int) $param['stockOpname']['status'] === 1) {
            $this->refreshLiveSystemQty($param['detail'], 'stobd_real', 'stobd_system', 'stobd_selisih', 'ss_stock');
        }

        if ($param['stockOpname']['status'] == 1) $param['status'] = "Menunggu";
        else if ($param['stockOpname']['status'] == 2) $param['status'] = "Disetujui";
        else if ($param['stockOpname']['status'] == 3) $param['status'] = "Ditolak";

        if(count($param["detail"])<=0){
            return -1;
        }

        $u = session()->get('user');
        $param['printed_by'] = $u ? ($u->staff_name ?? '-') : '-';
        $param['printed_at'] = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('Backoffice.PDF.OpnameBahan', $param);
        return $pdf->download('Stock Opname_'.$param["stockOpname"]["stob_code"].'.pdf');
    }

    // Stock Alert
    public function StockAlert()
    {
        return view('Backoffice.Inventory.Stock_Alert');
    }

    function getStockAlert(Request $req)
    {
        $data = (new StockAlert())->getStockAlert(["mode" => $req->mode]);
        return response()->json($data);
    }

    function insertStockAlert(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->insertStockAlert($data);
    }

    function updateStockAlert(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->updateStockAlert($data);
    }

    function deleteStockAlert(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->deleteStockAlert($data);
    }

    // Stock Alert Supplies
    public function StockAlertSupplies()
    {
        return view('Backoffice.Inventory.Stock_Alert_Supplies');
    }

    function getStockAlertSupplies(Request $req)
    {
        $data = (new StockAlertSupplies())->getStockAlertSupplies(["mode" => $req->mode]);
        return response()->json($data);
    }

    function insertStockAlertSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->insertStockAlertSupplies($data);
    }

    function updateStockAlertSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->updateStockAlertSupplies($data);
    }

    function deleteStockAlertSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->deleteStockAlertSupplies($data);
    }


    // Product Issues
    public function ProductIssue()
    {
        return view('Backoffice.Inventory.Product_Issues');
    }

    function getProductIssue(Request $req)
    {
        $data = (new ProductIssues())->getProductIssues([
            "pi_type" => $req->pi_type,
            "tipe_return" => $req->tipe_return,
            "date" => $req->date,
        ]);
        return response()->json($data);
    }

    function insertProductIssue(Request $req)
    {
        $data = $req->all();
        // Ambil base64
        if (isset($req->photo)){
            $image = $req->photo;
    
            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);
    
            // Decode
            $imageData = base64_decode($image);
    
            // Nama file
            $imageName = 'photo_' . time() . '.png';
    
            // Path tujuan di public/produksi
            $path = public_path('issue/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            $data["pi_img"] = $imageName;
        }

        if ($data['tipe_return'] == 1){
            $bermasalah = [];
            $kurang = [];
            foreach (json_decode($data['items'], true) as $key => $value) { 
                // Pengecekan invoice
                // if (isset($data['ref_num'])){
                //     $inv = PurchaseOrderDetailInvoice::find($data['ref_num']);
                //     $po = PurchaseOrder::find($inv->po_id);
                //     $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
                    
                //     $ada = -1;
                //     foreach ($pod as $key => $detail) {
                //         if ($detail['supplies_variant_id'] == $value['supplies_variant_id'] && $detail['unit_id'] == $value['unit_id']) {
                //             $ada = 1;
                //             if (($detail['pod_qty'] - $value['pid_qty']) < 0){
                //                 array_push($kurang, $value['supplies_name']);
                //             }
                //         }
                //     }
                //     if ($ada == -1) {
                //         array_push($bermasalah, $value['supplies_name']);
                //     }
                // }
            }
            if (count($bermasalah) > 0) {
                return [
                    "status"=>-1,
                    "message"=>"Bahan tidak ditemukan dalam invoice : ".implode(", ",$bermasalah)
                ];
            }
            if (count($kurang) > 0) {
                return [
                    "status"=>-1,
                    "message"=>"Stok dalam invoice tidak mencukupi : ".implode(", ",$kurang)
                ];
            }
    
            foreach (json_decode($data['items'], true) as $key => $value) {
                $value['tipe_return'] = $data['tipe_return'];
                // Pengecekan stock
                $c = (new ProductIssuesDetail())->stockCheck($value);
                if ($c == -1) return -1;
            }
        }

        // insert
        // if ($data['tipe_return'] == 1) $data['po_id'] = $po->po_id;
        $t = (new ProductIssues())->insertProductIssues($data);
        foreach (json_decode($data['items'], true) as $key => $value) {
            $value['pi_id'] = $t->pi_id;
            // if (isset($t->ref_num)) $value['ref_num'] = $t->ref_num;
            (new ProductIssuesDetail())->insertProductIssuesDetail($value);
        }
    }

    function updateProductIssue(Request $req)
    {
        $data = $req->all();
        $image = $req->photo;

        // Hilangkan prefix base64
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

        // Decode
        $imageData = base64_decode($image);

        // Nama file
        $imageName = 'photo_' . time() . '.png';

        // Path tujuan di public/produksi
        $path = public_path('issue/' . $imageName);
        // Simpan file
        file_put_contents($path, $imageData);
        $data["pi_img"] = $imageName;
        
        $id = [];

        // if ($data['tipe_return'] == 1) {
        //     $inv = PurchaseOrderDetailInvoice::find($data['ref_num']);
        //     $po = PurchaseOrder::find($inv->po_id);
        //     $data['po_id'] = $po->po_id;
        // }
        $pi = (new ProductIssues())->updateProductIssues($data);

        // Cek stock
        $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
        if (count($getPi) > 0) {
            foreach ($getPi as $key => $val) {
                foreach (json_decode($data['items'], true) as $key => $value) {
                    if ($data['tipe_return'] == 1) {
                        if ($value['supplies_variant_id'] == $val['item_id'] && $value['unit_id'] == $val['unit_id']){
                            $val['tipe_return'] = $data['tipe_return'];
                            $val['pid_qty'] = $value['pid_qty'];
                            $c = (new ProductIssuesDetail())->stockCheck($val);
                            if ($c == -1) return -1;
                        }
                    }
                    if ($data['tipe_return'] == 2) {
                        if ($value['product_variant_id'] == $val['item_id'] && $value['unit_id'] == $val['unit_id']){
                            $val['tipe_return'] = $data['tipe_return'];
                            $val['pid_qty'] = $value['pid_qty'];
                            $c = (new ProductIssuesDetail())->stockCheck($val);
                            if ($c == -1) return -1;
                        }
                    }
                }
            }
        }
        // Pengecekan invoice
        // if (isset($pi->ref_num) && $pi->ref_num > 0){
            // $bermasalah = [];
            // foreach (json_decode($data['items'], true) as $key => $value) {
            //     $inv = PurchaseOrderDetailInvoice::find($pi->ref_num);
            //     $po = PurchaseOrder::find($inv->po_id);
            //     $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
                
            //     $ada = -1;
            //     foreach ($pod as $key => $detail) {
            //         if ($detail['supplies_variant_id'] == $value['supplies_variant_id'] && $detail['unit_id'] == $value['unit_id']) {
            //             $ada = 1;
            //             break;
            //         }
            //     }
            //     if ($ada == -1) {
            //         array_push($bermasalah, $value['supplies_name']);
            //     }
            // }
            // if (count($bermasalah) > 0) {
            //     return [
            //         "status"=>-1,
            //         "message"=>"Bahan tidak ditemukan dalam invoice : ".implode(", ",$bermasalah)
            //     ];
            // }

            // Kembalikan stock semua
            $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
            if (count($getPi) > 0) {
                foreach ($getPi as $key => $val) {
                    // Kembalikan stock invoice
                    // $total = 0;
                    // foreach ($pod as $key => $detail) {
                    //     if ($val->item_id == $detail['supplies_variant_id'] && $val->unit_id == $detail['unit_id']){
                    //         $detail['pod_qty'] += $val['pid_qty'];
                    //         $detail['pod_subtotal'] = $detail['pod_harga'] * $detail['pod_qty'];
                    //         $detail->save();
                    //     }
                    //     $total += $detail['pod_subtotal'];
                    // }
                    // if ($po->jenis_discount == "persen"){
                    //     $total -= $total * $po->po_discount/100;
                    // } else {
                    //     $total -= $po->po_discount;
                    // }
                    // $total += $total * $po->po_ppn/100;
                    // $total += $po->po_cost;
                    
                    // $inv->poi_total = $total;
                    // $inv->save();
                    // $po->po_total = $total;
                    // $po->save();

                    // Kembalikan stock
                    $svr = SuppliesVariant::find($val->item_id);
                    $ss = SuppliesStock::where('supplies_id', $svr->supplies_id)->where('unit_id', $val->unit_id)->first();
                    $ss->ss_stock += $val['pid_qty'];
                    $ss->save();
                    
                    // Catat Log
                    $logNotes = "";
                    $spr = Supplier::find($svr->supplier_id);
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    (new LogStock())->insertLog([
                        'log_date' => now(),
                        'log_kode'    => $pi->pi_code,
                        'log_type'    => 2,
                        'log_category' => 1,
                        'log_item_id' => $svr->supplies_id,
                        'log_notes'  => $logNotes,
                        'log_jumlah' => $val['pid_qty'],
                        'unit_id'    => $val['unit_id'],
                    ]);
                }
            }
        // }
        
        foreach (json_decode($data['items'], true) as $key => $value) {
            $value['pi_id'] = $data["pi_id"];
            if (isset($pi->ref_num)) $value['ref_num'] = $pi->ref_num;

            if (!isset($value["pid_id"])) {
                if ($pi->tipe_return == 2){
                    $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
                    if (count($getPi) > 0) {
                        foreach ($getPi as $key => $val) {
                            $ps = ProductStock::where('product_variant_id', $value['product_variant_id'])->where('unit_id', $val->unit_id)->first();

                            $ps->ps_stock -= $val->pid_qty;
                            $ps->save();

                            // Catat Log
                            $logNotes = "";
                            $logNotes = 'Perubahan data produk bermasalah retur Armada';
                            (new LogStock())->insertLog([
                                'log_date' => now(),
                                'log_kode'    => $pi->pi_code,
                                'log_type'    => 1,
                                'log_category' => 2,
                                'log_item_id' => $value['product_variant_id'],
                                'log_notes'  => $logNotes,
                                'log_jumlah' => $val['pid_qty'],
                                'unit_id'    => $val['unit_id'],
                            ]);
                        }
                    }
                }
                
                // Pengurangan stock
                $t = (new ProductIssuesDetail())->insertProductIssuesDetail($value);
                
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 2;
                    $logType = 2;

                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Perubahan data produk bermasalah retur Armada';
                    $logCategory = 1;
                    $logType = 1;
                    $itemId = $value['product_variant_id'];
                }
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $value['pid_qty'],
                    'unit_id'    => $value['unit_id'],
                ]);
            }
            else {
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 1;
                    $logType = 2;
                    $itemId = $sup->supplies_id;

                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Perubahan data produk bermasalah retur Armada';
                    $logCategory = 2;
                    $logType = 1;
                    $itemId = $value['product_variant_id'];
                }

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $value['pid_qty'],
                    'unit_id'    => $value['unit_id'],
                ]);
                
                $t = (new ProductIssuesDetail())->updateProductIssuesDetail($value);

                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 2;
                    $logType = 2;
                    $itemId = $sup->supplies_id;

                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Perubahan data produk bermasalah retur Armada';
                    $logCategory = 1;
                    $logType = 1;
                    $itemId = $value['product_variant_id'];
                }

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $value['pid_qty'],
                    'unit_id'    => $value['unit_id'],
                ]);
            }
            array_push($id, $t);
            
        }
        ProductIssuesDetail::where('pi_id', '=', $data["pi_id"])->whereNotIn("pid_id", $id)->update(["status" => 0]);
    }

    // UI-unreachable (confirmed 2026-08-02) — this route's delete trigger icon is commented out in
    // Product_Issues.js, on top of the original "MASIH NGEBUG" ("still buggy") note below. This is
    // the one caller of ProductIssues::deleteProductIssues() that DOES check its -1 return value —
    // see that method's dead-code comment for why the other, reachable caller doesn't need to
    // (yet).
    function deleteProductIssue(Request $req) // MASIH NGEBUG
    {
        $data = $req->all();

        $pi = ProductIssues::find($data['pi_id']);
        $w = ProductIssuesDetail::where('pi_id','=',$data["pi_id"])->where('status', '>=', 1)->get();

        if ($pi->tipe_return == 2) {
            // ─── Fase 1: Agregasi ───────────────────────────────────────────
            $aggregatedProducts = [];
            foreach ($w as $value) {
                $uniqueKey = $value->item_id . '_' . $value->unit_id;
                if (!isset($aggregatedProducts[$uniqueKey])) {
                    $aggregatedProducts[$uniqueKey] = [
                        'variant_id'  => $value->item_id,
                        'unit_id'     => $value->unit_id,
                        'total_butuh' => 0,
                        'details'     => $value,
                    ];
                }
                $aggregatedProducts[$uniqueKey]['total_butuh'] += $value->pid_qty;
            }

            // ─── Fase 2: Simulasi virtualStock ──────────────────────────────
            $kurang        = [];
            $simulasiHasil = [];

            foreach ($aggregatedProducts as $key => $item) {
                $variantId     = $item['variant_id'];
                $unitTarget    = (int)$item['unit_id'];
                $butuhTersedia = $item['total_butuh'];

                $ss = ProductStock::where('product_variant_id', $variantId)
                    ->where('status', 1)
                    ->orderBy('ps_id', 'desc')
                    ->get()
                    ->values();

                if ($ss->isEmpty()) {
                    $pvr = ProductVariant::find($variantId);
                    $pr  = Product::find($pvr->product_id);
                    $kurang[] = $pr->product_name . ' ' . $pvr->product_variant_name;
                    continue;
                }

                // Bangun virtualStock
                $virtualStock = [];
                $logSummary   = [];
                $keyTarget    = null;

                foreach ($ss as $idx => $stok) {
                    $virtualStock[$stok->ps_id] = [
                        'model'   => $stok,
                        'current' => (float)$stok->ps_stock,
                        'unit_id' => $stok->unit_id,
                        'ps_id'   => $stok->ps_id,
                    ];
                    if ((int)$stok->unit_id === $unitTarget) {
                        $keyTarget = $idx;
                    }
                }

                if ($keyTarget === null) {
                    $pvr = ProductVariant::find($variantId);
                    $pr  = Product::find($pvr->product_id);
                    $kurang[] = $pr->product_name . ' ' . $pvr->product_variant_name;
                    continue;
                }

                // Fungsi rekursif — cari unit atas via relasi, tidak bergantung index
                $siapkanStok = function($targetKey, $units, $depth = 0) use (&$virtualStock, &$logSummary, &$siapkanStok, $variantId) {
                    // Ditambahkan (2026-08-06): depth guard — $keyAtas dicari lewat lookup relasi,
                    // jadi rantai ProductRelation yang sirkular bisa membuat rekursi ini jalan
                    // selamanya sebelum sempat kembali ke while loop di bawah. Belum pernah
                    // tereproduksi dengan data nyata, murni defensive guard. Lihat KNOWN_ISSUES.md.
                    if ($depth >= 20) return false;

                    $stokSekarang = $units[$targetKey];

                    $sr = ProductRelation::where('product_variant_id', $variantId)
                        ->where('pr_unit_id_2', $stokSekarang->unit_id)
                        ->where('status', 1)
                        ->first();

                    if (!$sr) return false;

                    // Cari index unit atas berdasarkan relasi
                    $keyAtas = null;
                    foreach ($units as $idx => $stok) {
                        if ((int)$stok->unit_id === (int)$sr->pr_unit_id_1) {
                            $keyAtas = $idx;
                            break;
                        }
                    }

                    if ($keyAtas === null) return false;

                    $stokAtas = $units[$keyAtas];

                    if ($virtualStock[$stokAtas->ps_id]['current'] <= 0) {
                        if (!$siapkanStok($keyAtas, $units, $depth + 1)) return false;
                    }

                    if ($virtualStock[$stokAtas->ps_id]['current'] > 0) {
                        $virtualStock[$stokAtas->ps_id]['current'] -= 1;
                        $hasilBongkar = (float)$sr->pr_unit_value_2;
                        $virtualStock[$stokSekarang->ps_id]['current'] += $hasilBongkar;

                        $baseOrder = $stokAtas->ps_id * 10;
                        $logSummary[$stokAtas->unit_id . '_cat2'] = [
                            'unit_id'    => $stokAtas->unit_id,
                            'jumlah'     => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + 1,
                            'cat'        => 2,
                            'note'       => 'Konversi unit dari retur armada (Bongkar)',
                            'sort_order' => $baseOrder,
                        ];
                        $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                            'unit_id'    => $stokSekarang->unit_id,
                            'jumlah'     => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                            'cat'        => 1,
                            'note'       => 'Konversi unit dari retur armada (Hasil)',
                            'sort_order' => $baseOrder + 1,
                        ];
                        return true;
                    }
                    return false;
                };

                $idPalingBawah = $ss[$keyTarget]->ps_id;
                $safety = 0;

                while ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                    $safety++;
                    if ($safety > 500) break;
                    if (!$siapkanStok($keyTarget, $ss)) break;
                }

                if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                    $simulasiHasil[$key] = [
                        'virtualStock'  => $virtualStock,
                        'logSummary'    => $logSummary,
                        'idPalingBawah' => $idPalingBawah,
                        'butuhTersedia' => $butuhTersedia,
                        'variantId'     => $variantId,
                    ];
                } else {
                    $pvr = ProductVariant::find($variantId);
                    $pr  = Product::find($pvr->product_id);
                    $kurang[] = $pr->product_name . ' ' . $pvr->product_variant_name;
                }
            }

            // ─── Fase 3: Validasi ────────────────────────────────────────────
            if (count($kurang) > 0) {
                return [
                    "status"  => -1,
                    "header"  => "Gagal menghapus",
                    "message" => "Stok produk tidak mencukupi: " . implode(", ", $kurang)
                ];
            }

            // ─── Fase 4: Eksekusi virtualStock ke DB ─────────────────────────
            foreach ($simulasiHasil as $hasil) {
                $virtualStock  = $hasil['virtualStock'];
                $logSummary    = $hasil['logSummary'];
                $idPalingBawah = $hasil['idPalingBawah'];
                $butuhTersedia = $hasil['butuhTersedia'];
                $variantId     = $hasil['variantId'];

                // Save konversi HANYA kalau ada konversi yang terjadi
                if (!empty($logSummary)) {
                    foreach ($virtualStock as $psId => $v) {
                        if ($psId == $idPalingBawah) continue; // skip unit target
                        $v['model']->ps_stock = (int)$v['current'];
                        $v['model']->save();
                    }

                    usort($logSummary, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
                    foreach ($logSummary as $l) {
                        (new LogStock())->insertLog([
                            'log_date'     => now(),
                            'log_kode'     => $pi->pi_code,
                            'log_type'     => 1,
                            'log_category' => $l['cat'],
                            'log_item_id'  => $variantId,
                            'log_notes'    => $l['note'],
                            'log_jumlah'   => $l['jumlah'],
                            'unit_id'      => $l['unit_id'],
                        ]);
                    }
                }

                // Kurangi stok unit target — selalu dijalankan
                $stokFinal = ProductStock::find($idPalingBawah);
                $stokFinal->ps_stock = (int)($virtualStock[$idPalingBawah]['current'] - $butuhTersedia);
                $stokFinal->save();
            }
        }

        $del = (new ProductIssues())->deleteProductIssues($data);
        if ($del == -1) {
            return response()->json([
                "status"  => 0,
                "header"  => "Gagal Delete",
                "message" => "Invoice tersebut sudah terbayar"
            ]);
        }

        $rs = ReturnSupplies::where('pi_id', $data['pi_id'])->where('status', 1)->first();
        if ($rs) (new ReturnSupplies())->deleteReturnSupplies($rs);

        foreach ($w as $key => $value) {
            $value['tipe_return'] = $pi->tipe_return;
            if (isset($pi->ref_num) && $pi->ref_num != 0) $value['ref_num'] = $pi->ref_num;

            if ($rs) {
                $rsd = ReturnSuppliesDetail::where('rs_id', $rs->rs_id)->where('status', 1)->get();
                $total = 0;
                foreach ($rsd as $key => $val) {
                    $val['po_id'] = $pi['po_id'];
                    $total += ($val['rsd_price'] * $val['rsd_qty']);
                    (new ReturnSuppliesDetail())->deleteReturnSuppliesDetail($val);
                }
                $po = PurchaseOrder::find($pi['po_id']);
                $po->po_total += $total;
                $po->save();
            }

            $value['po_id'] = $pi['po_id'];
            (new ProductIssuesDetail())->deleteProductIssuesDetail($value);

            // Catat Log
            $logNotes    = "";
            $logCategory = 0;
            $logType     = 0;
            $itemId      = 0;

            if ($pi->tipe_return == 1) {
                $sup = SuppliesVariant::find($value['item_id']);
                $spr = Supplier::find($sup->supplier_id);

                $logNotes    = 'Penghapusan data produk bermasalah retur supplier ' . $spr->supplier_name;
                $logCategory = 1;
                $logType     = 2;
                $itemId      = $sup->supplies_id;

            } elseif ($pi->tipe_return == 2) {
                $logNotes    = 'Penghapusan data produk bermasalah retur Armada';
                $logCategory = 2;
                $logType     = 1;
                $itemId      = $value['item_id'];
            }

            (new LogStock())->insertLog([
                'log_date'     => now(),
                'log_kode'     => $pi->pi_code,
                'log_type'     => $logType,
                'log_category' => $logCategory,
                'log_item_id'  => $itemId,
                'log_notes'    => $logNotes,
                'log_jumlah'   => $value['pid_qty'],
                'unit_id'      => $value['unit_id'],
            ]);
        }
    }

    function accProductIssues(Request $req)
    {
        $data = $req->all();
        $pi = ProductIssues::find($data['pi_id']);
        $item = ProductIssuesDetail::where('pi_id', $data['pi_id'])->where('status', 1)->get();

        if ($pi->status != 1) {
            $staff = Staff::find($pi->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }

        // 1. PRE-CHECK (tanpa mutasi apa pun): hanya relevan untuk retur ke supplier — retur
        // dari customer/armada cuma menambah stok balik, tidak ada risiko kekurangan maupun
        // lookup supplier. Memastikan stok cukup DAN data supplier bahan valid untuk SEMUA item
        // dulu, sebelum ada satu pun mutasi — supaya tidak ada potongan stok sebagian kalau item
        // belakangan ternyata kurang, dan supaya lookup supplier yang null/rusak ditolak dengan
        // pesan jelas alih-alih 500 di tengah proses (dulu: Supplier::find($sup->supplier_id)
        // ->supplier_name crash kalau supplier_id null atau suppliernya sudah dihapus).
        $bahan_kurang = [];
        $supplier_invalid = [];
        if ($pi->tipe_return == 1) {
            foreach ($item as $value) {
                $m = SuppliesVariant::find($value['item_id']);
                if (!$m) {
                    $supplier_invalid[] = "Item bahan mentah tidak ditemukan (id {$value['item_id']})";
                    continue;
                }

                $namaBahan = $m->supplies_variant_name;
                if (!$namaBahan) {
                    $namaBahan = Supplies::find($m->supplies_id)->supplies_name ?? "id {$m->supplies_variant_id}";
                }

                $s = SuppliesStock::where('supplies_id', '=', $m->supplies_id)
                    ->where('unit_id', '=', $value['unit_id'])
                    ->first();
                $stocks = $s->ss_stock ?? 0;
                if ($stocks - $value['pid_qty'] < 0) {
                    $bahan_kurang[] = $namaBahan;
                }

                $spr = Supplier::find($m->supplier_id);
                if (!$spr && !in_array($namaBahan, $supplier_invalid, true)) {
                    $supplier_invalid[] = $namaBahan;
                }
            }
        }

        if (count($bahan_kurang) > 0) {
            return response()->json([
                'status' => -1,
                'header' => 'Gagal ACC',
                'message' => 'Stok bahan tidak mencukupi untuk: ' . implode(', ', $bahan_kurang),
            ]);
        }

        if (count($supplier_invalid) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => 'Data supplier tidak ditemukan/tidak valid untuk: '
                    . implode(', ', $supplier_invalid)
                    . '. Mohon perbarui data supplier bahan terkait sebelum approve.',
            ]);
        }

        DB::beginTransaction();
        try {
            foreach ($item as $key => $value) {
                $itemId = 0;
                // Return to Supplier
                if ($pi->tipe_return == 1) {
                    $itemId = $value['item_id'];
                    $m = SuppliesVariant::find($itemId);
                    $s = SuppliesStock::where('supplies_id', '=', $m->supplies_id)->where('unit_id', '=', $value["unit_id"])->first();

                    // Cek dari retur pembelian, apakah ada barang yang dibeli dari invoice ini
                    if ($pi['po_id'] != 0) {
                        if ($pi['ref_num'] != 0) {
                            $inv = PurchaseOrderDetailInvoice::find($pi['ref_num']);
                        }
                        $po = PurchaseOrder::find(!isset($inv) ? $pi['po_id'] : $inv->po_id);
                        $pod = PurchaseOrderDetail::where('po_id', $po->po_id)->get();
                    }

                    // pengurangan qty stok (sudah dipastikan cukup di pre-check di atas)
                    $stocks = ($s->ss_stock ?? 0) - $value["pid_qty"];

                    // pengurangan qty invoice
                    if ($pi['po_id'] != 0) {
                        $total = 0;
                        foreach ($pod as $key => $val) {
                            // Kalau retur pembelian tidak perlu potong stok PO
                            if (!isset($data['retur_pembelian'])) {
                                if ($value['item_id'] == $val['supplies_variant_id'] && $value['unit_id'] == $val['unit_id']) {
                                    $val['pod_qty'] -= $value['pid_qty'];
                                    $val['pod_subtotal'] = $val['pod_harga'] * $val['pod_qty'];
                                    $val->save();
                                }
                            }
                            $total += $val['pod_subtotal'];
                        }
                        if ($po->jenis_discount == "persen") {
                            $total -= $total * $po->po_discount / 100;
                        } else {
                            $total -= $po->po_discount;
                        }
                        $total += $total * $po->po_ppn / 100;
                        $total += $po->po_cost;

                        $data_retur = ReturnSupplies::where('po_id', !isset($inv) ? $pi['po_id'] : $inv->po_id)->where('status', 1)->get();
                        $total_retur = 0;
                        if ($data_retur) {
                            foreach ($data_retur as $key => $dr) {
                                $total_retur += $dr->rs_total;
                            }
                            if (isset($pi['total_retur'])) $total -= $pi['total_retur'];
                            else $total -= $total_retur;
                        }

                        if (isset($inv)) {
                            $inv->poi_total = $total;
                            $inv->save();
                        }
                        $po->po_total = $total;
                        $po->save();
                    }

                    $s->ss_stock = $stocks;
                    $m->save();
                    $s->save();
                }

                // Return from customer
                else {
                    $itemId = $value["item_id"];
                    $m = ProductVariant::find($itemId);
                    $s = ProductStock::where('product_variant_id', '=', $m->product_variant_id)->where('unit_id', '=', $value["unit_id"])->first();
                    $stocks = $s->ps_stock ?? 0;
                    $stocks += $value["pid_qty"];

                    $s->ps_stock = $stocks;
                    $m->save();
                    $s->save();
                }
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1) {
                    $sup = SuppliesVariant::find($value['item_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    $logNotes = 'Produk bermasalah retur supplier ' . $spr->supplier_name . ' ' . LogStock::actorSuffix();
                    $logCategory = 2;
                    $logType = 2;

                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2) {
                    $logNotes = 'Produk bermasalah retur Armada ' . LogStock::actorSuffix();
                    $logCategory = 1;
                    $logType = 1;
                    $itemId = $value['item_id'];
                }
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $value['pid_qty'],
                    'unit_id'    => $value['unit_id'],
                ]);
            }

            // 2. Status di-flip ke Approved HANYA SEKALI, setelah SEMUA item berhasil dimutasi —
            // bukan di dalam loop (dulu: mid-loop, jadi item pertama sudah permanen ter-potong dan
            // status sudah Approved sebelum item berikutnya sempat gagal).
            (new ProductIssues())->accProductIssues($data);
            DB::commit();
            return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function declineProductIssues(Request $req){
        $data = $req->all();
        $q = ProductIssues::find($data['pi_id']);
        if ($q->status != 1) {
            $staff = Staff::find($q->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        (new ProductIssues())->declineProductIssues($data);
    }

    // Manage Stock
    public function ManageStock()
    {
        return view('Backoffice.Inventory.Manage_Stock');
    }

    function getManageStock(Request $req)
    {
        $data = (new ManageStock())->getManageStocks();
        return response()->json($data);
    }
    function insertManageStocks(Request $req)
    {
        $data = $req->all();
        return (new ManageStock())->insertManageStock($data);
    }
    // Stock
    public function Stock()
    {
        return view('Backoffice.Inventory.Stock_Product');
    }

    function getStock(Request $req)
    {
        $data = (new ProductVariant())->getProductVariant();
        foreach ($data as $key => $value) {
            $value->stock = (new ProductStock())->getProductStock([
                "product_variant_id" => $value->product_variant_id,
                "relations" => $value->relasi,
            ]);
        }
        return response()->json($data);
    }

    // Stock supplies
    public function StockSupplies()
    {
        return view('Backoffice.Inventory.Stock_Supplies');
    }

    function getStockSupplies(Request $req)
    {
        // $data = (new SuppliesVariant())->getSuppliesVariant();
        //$data = (new SuppliesStock())->getProductStock();
        $data = (new Supplies())->getSupplies();
        return response()->json($data);
    }
}
