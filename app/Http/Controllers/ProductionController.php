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
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use App\Support\ProductionPendingStockRestorer;
use App\Support\UnitRollUp;
use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    // BOM
    public function bom()
    {
        return view('Backoffice.Production.Bom');
    }

    function getBom(Request $req)
    {
        $withDetails = filter_var($req->with_details ?? false, FILTER_VALIDATE_BOOLEAN);

        return response()->json((new Bom())->getBom([
            "bom_id" => $req->bom_id,
            "product_id" => $req->product_id,
            "supplies_id" => $req->supplies_id,
            "with_details" => $withDetails,
        ]));
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
            "created_at" => $req->created_at,
            "date" => $req->date,
            "status" => $req->status,
            "report" => $req->report ? $req->report : null
        ]);
        return response()->json($data);
    }

    function insertProduction(Request $req)
    {
        $data = $req->all();
        $isRevisionResubmit = (int) ($req->input('revision_source_production_id') ?? 0) > 0;
        if ($isRevisionResubmit) {
            // Revisi wajib dianggap pengajuan baru di tanggal hari ini.
            $data['production_date'] = now()->toDateString();
        }
        $item = json_decode($req->detail, true);
        $bahan = json_decode($req->list_bahan, true);
        $cek = -1;
        $bahan_kurang = [];
        $produk_tanpa_relasi = [];
        $produk_qty_tidak_kelipatan = [];
        $simulasiHasil = [];

        $bahan_satuan_tidak_aktif = $this->validateProductionBomActiveUnits($item);
        if (count($bahan_satuan_tidak_aktif) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bahan_satuan_tidak_aktif),
            ]);
        }

        $bahan_bukan_satuan_terkecil = $this->validateBomSuppliesSmallestUnit($item);
        if (count($bahan_bukan_satuan_terkecil) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Satuan bahan mentah pada resep bukan satuan terkecil sesuai relasi. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bahan_bukan_satuan_terkecil),
            ]);
        }

        $bom_satuan_produk_bukan_terkecil = $this->validateBomProductSmallestUnit($item);
        if (count($bom_satuan_produk_bukan_terkecil) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Satuan produk pada resep bukan satuan terkecil sesuai relasi produk. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bom_satuan_produk_bukan_terkecil),
            ]);
        }

        foreach ($item as $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!$bom) {
                continue;
            }
            $pdSmallest = $this->convertQtyToSmallestUnit(
                (int) $value['pd_qty'],
                (int) $value['unit_id'],
                (int) $value['product_variant_id']
            );
            $bomSmallest = $this->convertQtyToSmallestUnit(
                (int) $bom->bom_qty,
                (int) $bom->unit_id,
                (int) $value['product_variant_id']
            );
            if ($bomSmallest <= 0) {
                $bomSmallest = (int) $bom->bom_qty;
            }
            if ($pdSmallest % $bomSmallest !== 0) {
                $pv = ProductVariant::find($value['product_variant_id']);
                $namaProduk = '-';
                if ($pv) {
                    $prName = Product::find($pv->product_id);
                    $namaProduk = trim(($prName->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
                    if ($namaProduk === '') {
                        $namaProduk = $pv->product_variant_name ?? '-';
                    }
                }
                if (!in_array($namaProduk, $produk_qty_tidak_kelipatan, true)) {
                    $produk_qty_tidak_kelipatan[] = $namaProduk;
                }
            }
        }

        if (count($produk_qty_tidak_kelipatan) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Qty produksi harus kelipatan resep bahan mentah untuk produk: ' . implode(', ', $produk_qty_tidak_kelipatan),
            ]);
        }

        // 1. AGGREGASI: Hitung total kebutuhan bahan mentah dari SEMUA item produksi di awal
        $aggregatedRequirements = [];
        foreach ($item as $key => $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!isset($bom)) {
                return response()->json([
                    "status" => 0,
                    "header" => "Gagal Insert",
                    "message" => "Mohon cek kembali resep bahan mentah"
                ]);
            }

            // Pengecekan unit produksi punya relasi yang tersambung ke satuan resep atau tidak
            // (dos/pack di bawah butuh ini; getBatchCount() sendiri sudah aman lewat
            // convertQtyToSmallestUnit()'s fail-safe kalau tidak tersambung).
            if ($bom['unit_id'] != $value['unit_id']){
                $pr = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('pr_id', 'desc')
                    ->get();
                if (!$pr || $pr->isEmpty()) {
                    $pv = ProductVariant::find($value['product_variant_id']);
                    $namaProduk = "-";
                    if ($pv) {
                        $prName = Product::find($pv['product_id']);
                        $namaProduk = trim(($prName->product_name ?? "") . " " . ($pv['product_variant_name'] ?? ""));
                        if ($namaProduk === "") $namaProduk = $pv['product_variant_name'] ?? "-";
                    }
                    if (!in_array($namaProduk, $produk_tanpa_relasi, true)) $produk_tanpa_relasi[] = $namaProduk;
                    continue;
                }

                // Pengecekan apakah unit ini ada dalam relasi atau tidak
                $ada = false;
                foreach ($pr as $p) {
                    if ($p['pr_unit_id_1'] == $value['unit_id'] || $p['pr_unit_id_2'] == $value['unit_id']) {
                        $ada = true;
                        break;
                    }
                }
                if (!$ada){
                    $pv = ProductVariant::find($value['product_variant_id']);
                    $namaProduk = "-";
                    if ($pv) {
                        $prName = Product::find($pv['product_id']);
                        $namaProduk = trim(($prName->product_name ?? "") . " " . ($pv['product_variant_name'] ?? ""));
                        if ($namaProduk === "") $namaProduk = $pv['product_variant_name'] ?? "-";
                    }
                    if (!in_array($namaProduk, $produk_tanpa_relasi, true)) $produk_tanpa_relasi[] = $namaProduk;
                    continue;
                }
            }

            // Masukkan ke dalam array agregat berdasarkan supplies_id
            $batchCount = $this->getBatchCount(
                (int) $value['pd_qty'],
                (int) $value['unit_id'],
                (int) $bom->bom_qty,
                (int) $bom->unit_id,
                (int) $value['product_variant_id']
            );

            foreach ($this->getBomDetailRows($bom) as $bd) {
                $id = $bd['supplies_id'];

                // ─────────────────────────────────────────────────────────
                // PERLAKUAN KHUSUS DOS/PACK: kebutuhan bahan kemasan (dos,
                // pack) tidak proporsional per pcs produk, tapi per DOS
                // PRODUK JADI yang terbentuk. Misal "Dos Karton 24pcs" cuma
                // terpakai 1 saat produk jadi genap 24pcs, bukan terpakai
                // sebagian untuk 19 liter / 9 pcs sisa.
                // ─────────────────────────────────────────────────────────
                $namaBahan      = Supplies::find($id)->supplies_name;
                $isKemasanBesar = preg_match('/dos|pack/i', $namaBahan);

                if ($isKemasanBesar) {
                    $relasiKonversi = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                        ->where('pr_unit_id_2', $bom['unit_id'])
                        ->where('status', 1)
                        ->first();

                    // Diperbaiki (2026-08-06): dulu pakai convertQtyToSmallestUnit(), yang jalan
                    // sampai unit paling bawah di rantai relasi — salah kalau rantainya lebih dari
                    // satu tingkat (satuan resep belum tentu unit paling bawah). Sekarang berhenti
                    // TEPAT di satuan resep ($bom['unit_id']) — lihat convertQtyBetweenUnits().
                    $nilaiIsiDos = $relasiKonversi ? $relasiKonversi->pr_unit_value_2 : 1;
                    $totalPcs    = $this->convertQtyBetweenUnits(
                        (int) $value['pd_qty'],
                        (int) $value['unit_id'],
                        (int) $bom['unit_id'],
                        (int) $value['product_variant_id']
                    );
                    $jumlahDos      = floor($totalPcs / $nilaiIsiDos);
                    $kebutuhanBaris = $jumlahDos * $bd['bom_detail_qty'];
                } else {
                    $kebutuhanBaris = $bd['bom_detail_qty'] * $batchCount;
                }

                if (!isset($aggregatedRequirements[$id])) {
                    $aggregatedRequirements[$id] = [
                        'total_butuh' => 0,
                        'details' => $bd // Simpan satu contoh detail untuk referensi
                    ];
                }
                $aggregatedRequirements[$id]['total_butuh'] += $kebutuhanBaris;
            }
        }

        if (count($produk_tanpa_relasi) > 0) {
            return response()->json([
                "status" => 0,
                "header" => "Gagal Insert",
                "message" => "Mohon masukkan relasi produk: " . implode(", ", $produk_tanpa_relasi)
            ]);
        }

        // 2. PROCESSING: Eksekusi Konversi Stok (Bongkar Satuan Besar) berdasarkan total agregat
        foreach ($aggregatedRequirements as $suppliesId => $butuh) {
            $butuhTersedia = (float) $butuh['total_butuh'];
            if ($butuhTersedia <= 0) continue;
            $bd = $butuh['details'];
            $reqUnitId = (int) $bd['unit_id'];

            $ss = $this->ensureSuppliesStockRows($suppliesId);
            if (
                $ss->isEmpty()
                || $this->getTotalSuppliesStockInUnit($suppliesId, $reqUnitId, $ss) < $butuhTersedia
            ) {
                $cek = 1;
                $s = Supplies::find($suppliesId);
                if (!in_array($s['supplies_name'], $bahan_kurang, true)) {
                    $bahan_kurang[] = $s['supplies_name'];
                }
                continue;
            }

            $virtualStock = [];
            $logSummary   = [];

            foreach ($ss as $stok) {
                $virtualStock[$stok->ss_id] = [
                    'model'   => $stok,
                    'current' => (float) $stok->ss_stock,
                    'unit_id' => $stok->unit_id,
                    'ss_id'   => $stok->ss_id,
                ];
            }

            $siapkanStok = function ($targetKey, $units, $jumlahDibutuhkan, $depth = 0) use (
                &$virtualStock, &$logSummary, &$siapkanStok, $bd, $suppliesId
            ) {
                // Ditambahkan (2026-08-06): depth guard — $keyAtas dicari lewat lookup relasi,
                // jadi rantai SuppliesRelation yang sirkular bisa membuat rekursi ini jalan
                // selamanya. Belum pernah tereproduksi dengan data nyata, murni defensive guard.
                // Lihat KNOWN_ISSUES.md.
                if ($depth >= 20) return false;

                $stokSekarang = $units[$targetKey];

                $sr = SuppliesRelation::where('supplies_id', $bd['supplies_id'])
                    ->where('su_id_2', $stokSekarang->unit_id)
                    ->where('status', 1)
                    ->first();

                if (!$sr) return false;

                $keyAtas = null;
                foreach ($units as $idx => $stok) {
                    if ($stok->unit_id == $sr->su_id_1) {
                        $keyAtas = $idx;
                        break;
                    }
                }

                if ($keyAtas === null) return false;

                $stokAtas = $units[$keyAtas];
                $nilaiKonversi = (float) $sr['sr_value_2'];
                if ($nilaiKonversi <= 0) return false;

                $kekurangan = $jumlahDibutuhkan - $virtualStock[$stokSekarang->ss_id]['current'];
                if ($kekurangan <= 0) return true;

                $butuhDariAtas = (int) ceil($kekurangan / $nilaiKonversi);

                if ($virtualStock[$stokAtas->ss_id]['current'] < $butuhDariAtas) {
                    $siapkanStok($keyAtas, $units, $butuhDariAtas, $depth + 1);
                }

                $bongkarSebenarnya = min($butuhDariAtas, (int) $virtualStock[$stokAtas->ss_id]['current']);

                if ($bongkarSebenarnya <= 0) return false;

                $virtualStock[$stokAtas->ss_id]['current'] -= $bongkarSebenarnya;
                $hasilBongkar = $bongkarSebenarnya * $nilaiKonversi;
                $virtualStock[$stokSekarang->ss_id]['current'] += $hasilBongkar;

                $baseOrder = $stokAtas->ss_id * 10;
                $logSummary[$stokAtas->unit_id . '_cat2'] = [
                    'unit_id'    => $stokAtas->unit_id,
                    'jumlah'     => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + $bongkarSebenarnya,
                    'cat'        => 2,
                    'note'       => "Konversi unit dari produksi (Bongkar)",
                    'sort_order' => $baseOrder,
                ];
                $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                    'unit_id'    => $stokSekarang->unit_id,
                    'jumlah'     => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                    'cat'        => 1,
                    'note'       => "Konversi unit dari produksi (Hasil)",
                    'sort_order' => $baseOrder + 1,
                ];
                return true;
            };

            $keyPalingBawah = $this->findSuppliesStockUnitIndex($ss, $reqUnitId, $suppliesId);
            $idPalingBawah = $ss[$keyPalingBawah]->ss_id;

            if ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                $siapkanStok($keyPalingBawah, $ss, $butuhTersedia);
            }

            if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                $simulasiHasil[$suppliesId] = [
                    'virtualStock'  => $virtualStock,
                    'logSummary'    => $logSummary,
                    'idPalingBawah' => $idPalingBawah,
                    'butuhTersedia' => $butuhTersedia,
                ];
            } else {
                $cek = 1;
                $s = Supplies::find($suppliesId);
                if (!in_array($s['supplies_name'], $bahan_kurang, true)) {
                    $bahan_kurang[] = $s['supplies_name'];
                }
            }
        }

        if ($cek == 1) {
            return response()->json([
                "status"  => -1,
                "message" => "Bahan baku tidak mencukupi untuk : " . implode(", ", $bahan_kurang)
            ]);
        }

        $p = (new Production())->insertProduction($data);
        foreach ($item as $key => $value) {
            $value['production_id'] = $p->production_id;
            $value['list_bahan'] = json_encode($bahan[$key]);
            (new ProductionDetails())->insertProductionDetail($value);
        }

        if ($isRevisionResubmit) {
            $sourceId = (int) ($req->input('revision_source_production_id') ?? 0);
            if ($sourceId > 0) {
                $staffId = (int) (session('user')->staff_id ?? 0);
                DB::table('dashboard_queue_dismissals')->updateOrInsert(
                    [
                        'staff_id' => $staffId,
                        'queue_section' => 'revision',
                        'queue_key' => 'pr:'.$sourceId,
                    ],
                    [
                        'status' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        if (!$isRevisionResubmit && $data['production_date'] != now()->toDateString()){
            $req->merge(['production_id' => $p->production_id]);
            $this->accProduction($req);
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

    /**
     * ACC produksi (pending → approved).
     *
     * Alur keamanan stok (wajib dijaga):
     * 1) self-heal log/stok orphan dari ACC gagal sebelumnya
     * 2) pre-check SEMUA bahan tanpa mutasi
     * 3) potong bahan + tambah produk + update status dalam 1 DB transaction
     *
     * Detail: docs/production-acc-stock-safety.md
     */
    function accProduction(Request $req){
        $data = $req->all();
        $p = Production::find($data['production_id']);
        if (!$p) {
            return response()->json([
                "status" => 0,
                "header" => "Gagal Update",
                "message" => "Data produksi tidak ditemukan"
            ]);
        }
        // pengecekan ACC
        if ($p->status != 1) {
            $staff = Staff::find($p->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }

        // ACC gagal di tengah jalan bisa potong stok + tulis log meski status
        // masih pending. Kembalikan dulu supaya pre-check tidak "stok kurang" palsu.
        DB::transaction(function () use ($p) {
            (new ProductionPendingStockRestorer())
                ->revertProductionCode($p->production_code, withHistory: false);
        });

        $item = ProductionDetails::where('production_id', $data['production_id'])->where('status', 1)->get();
        $produk_tanpa_relasi = [];
        $bahan_kurang = []; // ← ditambahkan untuk menangkap bahan yang ternyata kurang saat eksekusi

        $bahan_satuan_tidak_aktif = $this->validateProductionBomActiveUnits($item->toArray());
        if (count($bahan_satuan_tidak_aktif) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => 'Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bahan_satuan_tidak_aktif),
            ]);
        }

        $insertProductLogOnce = function($payload) use ($p) {
            $exists = LogStock::where('log_kode', '=', $p->production_code)
                ->where('log_type', '=', $payload['log_type'])
                ->where('log_category', '=', $payload['log_category'])
                ->where('log_item_id', '=', $payload['log_item_id'])
                ->where('unit_id', '=', $payload['unit_id'])
                ->where('log_jumlah', '=', $payload['log_jumlah'])
                ->where('log_notes', '=', $payload['log_notes'])
                ->exists();
            if (!$exists) {
                (new LogStock())->insertLog($payload);
            }
        };

        // --- TAHAP 2: EKSEKUSI REAL (PENGURANGAN & PENAMBAHAN) ---
        // 1. AGGREGASI: Hitung total kebutuhan bahan mentah dari SEMUA item produksi di awal
        $aggregatedRequirements = [];
        foreach ($item as $key => $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!isset($bom)) {
                return response()->json([
                    "status" => 0,
                    "header" => "Gagal Insert",
                    "message" => "Mohon cek kembali resep bahan mentah"
                ]);
            }

            // Pengecekan unit produksi punya relasi yang tersambung ke satuan resep atau tidak
            // (dos/pack di bawah butuh ini; getBatchCount() sendiri sudah aman lewat
            // convertQtyToSmallestUnit()'s fail-safe kalau tidak tersambung).
            if ($bom['unit_id'] != $value['unit_id']){
                $pr = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                    ->where('status', 1)
                    ->orderBy('pr_id', 'desc')
                    ->get();
                if (!$pr || $pr->isEmpty()) {
                    $pv = ProductVariant::find($value['product_variant_id']);
                    $namaProduk = "-";
                    if ($pv) {
                        $prName = Product::find($pv['product_id']);
                        $namaProduk = trim(($prName->product_name ?? "") . " " . ($pv['product_variant_name'] ?? ""));
                        if ($namaProduk === "") $namaProduk = $pv['product_variant_name'] ?? "-";
                    }
                    if (!in_array($namaProduk, $produk_tanpa_relasi, true)) $produk_tanpa_relasi[] = $namaProduk;
                    continue;
                }

                // Pengecekan apakah unit ini ada dalam relasi atau tidak
                $ada = false;
                foreach ($pr as $relasi) {
                    if ($relasi['pr_unit_id_1'] == $value['unit_id'] || $relasi['pr_unit_id_2'] == $value['unit_id']) {
                        $ada = true;
                        break;
                    }
                }
                if (!$ada){
                    $pv = ProductVariant::find($value['product_variant_id']);
                    $namaProduk = "-";
                    if ($pv) {
                        $prName = Product::find($pv['product_id']);
                        $namaProduk = trim(($prName->product_name ?? "") . " " . ($pv['product_variant_name'] ?? ""));
                        if ($namaProduk === "") $namaProduk = $pv['product_variant_name'] ?? "-";
                    }
                    if (!in_array($namaProduk, $produk_tanpa_relasi, true)) $produk_tanpa_relasi[] = $namaProduk;
                    continue;
                }
            }

            // Masukkan ke dalam array agregat berdasarkan supplies_id
            $batchCount = $this->getBatchCount(
                (int) $value['pd_qty'],
                (int) $value['unit_id'],
                (int) $bom->bom_qty,
                (int) $bom->unit_id,
                (int) $value['product_variant_id']
            );

            foreach ($this->getBomDetailRows($bom) as $bd) {
                $id = $bd['supplies_id'];
                $namaBahan      = Supplies::find($id)->supplies_name;
                $isKemasanBesar = preg_match('/dos|pack/i', $namaBahan);

                if ($isKemasanBesar) {
                    $relasiKonversi = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                        ->where('pr_unit_id_2', $bom['unit_id'])
                        ->where('status', 1)
                        ->first();

                    // Diperbaiki (2026-08-06): lihat comment di convertQtyBetweenUnits() —
                    // berhenti tepat di satuan resep, bukan sampai unit paling bawah rantai.
                    $nilaiIsiDos = $relasiKonversi ? $relasiKonversi->pr_unit_value_2 : 1;
                    $totalPcs    = $this->convertQtyBetweenUnits(
                        (int) $value['pd_qty'],
                        (int) $value['unit_id'],
                        (int) $bom['unit_id'],
                        (int) $value['product_variant_id']
                    );
                    $jumlahDos      = floor($totalPcs / $nilaiIsiDos);
                    $kebutuhanBaris = $jumlahDos * $bd['bom_detail_qty'];
                } else {
                    $kebutuhanBaris = $bd['bom_detail_qty'] * $batchCount;
                }

                if (!isset($aggregatedRequirements[$id])) {
                    $aggregatedRequirements[$id] = [
                        'total_butuh' => 0,
                        'details' => $bd // Simpan satu contoh detail untuk referensi
                    ];
                }
                $aggregatedRequirements[$id]['total_butuh'] += $kebutuhanBaris;
            }
        }

        if (count($produk_tanpa_relasi) > 0) {
            return response()->json([
                "status" => 0,
                "header" => "Gagal Insert",
                "message" => "Mohon masukkan relasi produk: " . implode(", ", $produk_tanpa_relasi)
            ]);
        }

        // PRE-CHECK: validasi SEMUA kebutuhan bahan dulu (tanpa mutasi stok).
        // Supaya tidak ada pengurangan stok sebagian kalau bahan terakhir kurang.
        $bahan_kurang = [];
        foreach ($aggregatedRequirements as $suppliesId => $butuh) {
            $butuhTersedia = (float) $butuh['total_butuh'];
            if ($butuhTersedia <= 0) {
                continue;
            }
            $bd = $butuh['details'];
            $reqUnitId = (int) $bd['unit_id'];
            $ss = $this->ensureSuppliesStockRows($suppliesId);

            if (
                $ss->isEmpty()
                || $this->getTotalSuppliesStockInUnit($suppliesId, $reqUnitId, $ss) < $butuhTersedia
            ) {
                $s = Supplies::find($suppliesId);
                if ($s && ! in_array($s['supplies_name'], $bahan_kurang, true)) {
                    $bahan_kurang[] = $s['supplies_name'];
                }
                continue;
            }

            $virtualStock = [];
            foreach ($ss as $stok) {
                $virtualStock[$stok->ss_id] = [
                    'current' => (float) $stok->ss_stock,
                    'unit_id' => $stok->unit_id,
                    'ss_id' => $stok->ss_id,
                ];
            }

            $siapkanStokCek = function ($targetKey, $units, $jumlahDibutuhkan, $depth = 0) use (
                &$virtualStock,
                &$siapkanStokCek,
                $bd
            ) {
                // Depth guard — mirrors $siapkanStok() above, see its comment.
                if ($depth >= 20) {
                    return false;
                }

                $stokSekarang = $units[$targetKey];
                $sr = SuppliesRelation::where('supplies_id', $bd['supplies_id'])
                    ->where('su_id_2', $stokSekarang->unit_id)
                    ->where('status', 1)
                    ->first();
                if (! $sr) {
                    return false;
                }

                $keyAtas = null;
                foreach ($units as $idx => $stok) {
                    if ($stok->unit_id == $sr->su_id_1) {
                        $keyAtas = $idx;
                        break;
                    }
                }
                if ($keyAtas === null) {
                    return false;
                }

                $stokAtas = $units[$keyAtas];
                $nilaiKonversi = (float) $sr['sr_value_2'];
                if ($nilaiKonversi <= 0) {
                    return false;
                }

                $kekurangan = $jumlahDibutuhkan - $virtualStock[$stokSekarang->ss_id]['current'];
                if ($kekurangan <= 0) {
                    return true;
                }

                $butuhDariAtas = (int) ceil($kekurangan / $nilaiKonversi);
                if ($virtualStock[$stokAtas->ss_id]['current'] < $butuhDariAtas) {
                    $siapkanStokCek($keyAtas, $units, $butuhDariAtas, $depth + 1);
                }

                $bongkarSebenarnya = min($butuhDariAtas, (int) $virtualStock[$stokAtas->ss_id]['current']);
                if ($bongkarSebenarnya <= 0) {
                    return false;
                }

                $virtualStock[$stokAtas->ss_id]['current'] -= $bongkarSebenarnya;
                $virtualStock[$stokSekarang->ss_id]['current'] += $bongkarSebenarnya * $nilaiKonversi;

                return true;
            };

            $keyPalingBawah = $this->findSuppliesStockUnitIndex($ss, $reqUnitId, $suppliesId);
            $idPalingBawah = $ss[$keyPalingBawah]->ss_id;
            if ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                $siapkanStokCek($keyPalingBawah, $ss, $butuhTersedia);
            }

            if ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                $s = Supplies::find($suppliesId);
                if ($s && ! in_array($s['supplies_name'], $bahan_kurang, true)) {
                    $bahan_kurang[] = $s['supplies_name'];
                }
            }
        }

        if (count($bahan_kurang) > 0) {
            return response()->json([
                'status' => -1,
                'header' => 'Gagal ACC',
                'message' => 'Bahan baku tidak mencukupi untuk : ' . implode(', ', $bahan_kurang),
            ]);
        }

        // 1b. PRE-CHECK: kalau produk punya ladder satuan (product_relations) dan produksi ini
        // akan memicu split ke satuan yang lebih besar, pastikan baris ProductStock di satuan
        // itu sudah ada — TANPA mutasi apa pun di sini. Kalau belum ada, jangan langsung dibuatkan
        // otomatis; minta konfirmasi user dulu (lewat confirm_create_stock), supaya user sadar ada
        // baris stok baru yang akan dibuat dengan stok awal 0.
        $missingProductStockRows = [];
        foreach ($item as $value) {
            $v = ProductStock::where('product_variant_id', $value['product_variant_id'])
                ->where('unit_id', $value['unit_id'])
                ->where('status', 1)
                ->first();
            if (!$v) {
                continue; // ditangani syncStock() di tahap eksekusi nanti, bukan bagian dari ladder ini
            }

            $jumlahTambah = (int) $value['pd_qty'];
            // Ditambahkan (2026-08-24): dulu cuma cek satu hop ke atas ($r tunggal) — sekarang
            // menyusuri seluruh rantai lewat UnitRollUp::planProductOutput() (read-only di sini)
            // supaya konfirmasi "baris stok baru akan dibuat" ini juga menyebutkan level KEDUA/KETIGA
            // dst, bukan cuma level pertama, sebelum accProduction() benar-benar mengkreditnya di
            // bawah. Existing-aware sejak GitHub #87, jadi ikut menangkap kasus split yang baru
            // ketahuan setelah stok lama di satuan ini digabung dengan tambahan produksi ini.
            $chain = UnitRollUp::planProductOutput((int) $value['product_variant_id'], (int) $v->unit_id, $jumlahTambah);

            if (count($chain) > 1) {
                foreach ($chain as $credit) {
                    $unitId = $credit['unit_id'];
                    $exists = ProductStock::where('product_variant_id', $value['product_variant_id'])
                        ->where('unit_id', $unitId)
                        ->where('status', 1)
                        ->exists();

                    if (!$exists) {
                        $key = $value['product_variant_id'] . '_' . $unitId;
                        if (!isset($missingProductStockRows[$key])) {
                            $pv = ProductVariant::find($value['product_variant_id']);
                            $productName = '-';
                            if ($pv) {
                                $prName = Product::find($pv->product_id);
                                $productName = trim(($prName->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
                                if ($productName === '') {
                                    $productName = $pv->product_variant_name ?? '-';
                                }
                            }
                            $unit = Unit::find($unitId);
                            $missingProductStockRows[$key] = [
                                'product_variant_id' => (int) $value['product_variant_id'],
                                'unit_id' => (int) $unitId,
                                'product_name' => $productName,
                                'unit_name' => $unit->unit_name ?? '-',
                            ];
                        }
                    }
                }
            }
        }

        if (count($missingProductStockRows) > 0 && !$req->boolean('confirm_create_stock')) {
            $labels = array_map(
                fn ($m) => $m['product_name'] . ' (' . $m['unit_name'] . ')',
                $missingProductStockRows
            );
            return response()->json([
                'status' => -3,
                'header' => 'Konfirmasi Diperlukan',
                'message' => 'Baris stok untuk ' . implode(', ', $labels)
                    . ' belum ada dan akan dibuat dengan stok awal 0. Lanjutkan approval?',
                'missing_stock' => array_values($missingProductStockRows),
            ]);
        }

        DB::beginTransaction();
        try {
        // 2. PENGURANGAN BAHAN (SUPPLIES) - dengan konversi dulu
        foreach ($aggregatedRequirements as $suppliesId => $butuh) {
            $butuhTersedia = (float)$butuh['total_butuh'];
            if ($butuhTersedia <= 0) continue;
            $bd = $butuh['details'];
            $reqUnitId = (int) $bd['unit_id'];

            $ss = $this->ensureSuppliesStockRows($suppliesId);
            if ($ss->isEmpty()) {
                // ← ditambahkan: sebelumnya silent continue tanpa pesan
                $s = Supplies::find($suppliesId);
                if ($s && !in_array($s['supplies_name'], $bahan_kurang, true)) {
                    $bahan_kurang[] = $s['supplies_name'];
                }
                continue;
            }

            $virtualStock = [];
            $logSummary   = [];

            foreach ($ss as $stok) {
                $virtualStock[$stok->ss_id] = [
                    'model'   => $stok,
                    'current' => (float) $stok->ss_stock,
                    'unit_id' => $stok->unit_id,
                    'ss_id'   => $stok->ss_id,
                ];
            }

            $siapkanStok = function ($targetKey, $units, $jumlahDibutuhkan, $depth = 0) use (
                &$virtualStock, &$logSummary, &$siapkanStok, $bd, $suppliesId
            ) {
                // Depth guard — mirrors the other $siapkanStok() in this controller, see its
                // comment.
                if ($depth >= 20) return false;

                $stokSekarang = $units[$targetKey];

                $sr = SuppliesRelation::where('supplies_id', $bd['supplies_id'])
                    ->where('su_id_2', $stokSekarang->unit_id)
                    ->where('status', 1)
                    ->first();

                if (!$sr) return false;

                $keyAtas = null;
                foreach ($units as $idx => $stok) {
                    if ($stok->unit_id == $sr->su_id_1) {
                        $keyAtas = $idx;
                        break;
                    }
                }

                if ($keyAtas === null) return false;

                $stokAtas = $units[$keyAtas];
                $nilaiKonversi = (float) $sr['sr_value_2'];
                if ($nilaiKonversi <= 0) return false;

                // Berapa kekurangan saat ini di level stokSekarang
                $kekurangan = $jumlahDibutuhkan - $virtualStock[$stokSekarang->ss_id]['current'];
                if ($kekurangan <= 0) return true;

                // Berapa unit atas yang perlu dibongkar untuk menutupi kekurangan
                $butuhDariAtas = (int) ceil($kekurangan / $nilaiKonversi);

                // Kalau stok atas tidak cukup, coba bongkar dulu dari level
                // yang lebih atas lagi (rekursif)
                if ($virtualStock[$stokAtas->ss_id]['current'] < $butuhDariAtas) {
                    $siapkanStok($keyAtas, $units, $butuhDariAtas, $depth + 1);
                }

                $bongkarSebenarnya = min($butuhDariAtas, (int) $virtualStock[$stokAtas->ss_id]['current']);

                if ($bongkarSebenarnya <= 0) return false;

                $virtualStock[$stokAtas->ss_id]['current'] -= $bongkarSebenarnya;
                $hasilBongkar = $bongkarSebenarnya * $nilaiKonversi;
                $virtualStock[$stokSekarang->ss_id]['current'] += $hasilBongkar;

                $baseOrder = $stokAtas->ss_id * 10;
                $logSummary[$stokAtas->unit_id . '_cat2'] = [
                    'unit_id'    => $stokAtas->unit_id,
                    'jumlah'     => ($logSummary[$stokAtas->unit_id . '_cat2']['jumlah'] ?? 0) + $bongkarSebenarnya,
                    'cat'        => 2,
                    'note'       => "Konversi unit dari produksi (Bongkar) " . LogStock::actorSuffix(),
                    'sort_order' => $baseOrder,
                ];
                $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                    'unit_id'    => $stokSekarang->unit_id,
                    'jumlah'     => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                    'cat'        => 1,
                    'note'       => "Konversi unit dari produksi (Hasil) " . LogStock::actorSuffix(),
                    'sort_order' => $baseOrder + 1,
                ];
                return true;
            };

            $keyPalingBawah = $this->findSuppliesStockUnitIndex($ss, $reqUnitId, $suppliesId);
            $idPalingBawah = $ss[$keyPalingBawah]->ss_id;

            if ($virtualStock[$idPalingBawah]['current'] < $butuhTersedia) {
                $siapkanStok($keyPalingBawah, $ss, $butuhTersedia);
            }

            if ($virtualStock[$idPalingBawah]['current'] >= $butuhTersedia) {
                // 1. Save hasil konversi KECUALI unit terbawah
                foreach ($virtualStock as $psId => $v) {
                    if ($psId == $idPalingBawah) continue;
                    $v['model']->ss_stock = (int)$v['current'];
                    $v['model']->save();
                }

                // 2. Catat log konversi
                usort($logSummary, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
                foreach ($logSummary as $l) {
                    (new LogStock())->insertLog([
                        'log_date'     => \Carbon\Carbon::parse($p->production_date)->setTimeFrom(now()),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => $l['cat'],
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => $l['note'],
                        'log_jumlah'   => $l['jumlah'],
                        'unit_id'      => $l['unit_id'],
                    ]);
                }

                // 3. Kurangi stok unit terbawah (Piece) sebesar kebutuhan
                $stokBawah = SuppliesStock::find($idPalingBawah);
                $cekLog = LogStock::where('log_kode', $p->production_code)
                    ->where('log_type', 2)
                    ->where('log_category', 2)
                    ->where('log_item_id', $suppliesId)
                    ->where('unit_id', $stokBawah->unit_id)
                    ->exists();

                if (!$cekLog) {
                    $stokBawah->ss_stock = (int)($virtualStock[$idPalingBawah]['current'] - $butuhTersedia);
                    $stokBawah->save();

                    (new LogStock())->insertLog([
                        'log_date'     => \Carbon\Carbon::parse($p->production_date)->setTimeFrom(now()),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => 2,
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => "Pengurangan bahan untuk produksi " . LogStock::actorSuffix(),
                        'log_jumlah'   => $butuhTersedia,
                        'unit_id'      => $stokBawah->unit_id,
                    ]);
                }
            } else {
                // ← ditambahkan: sebelumnya silent skip tanpa pesan apapun
                $s = Supplies::find($suppliesId);
                if ($s && !in_array($s['supplies_name'], $bahan_kurang, true)) {
                    $bahan_kurang[] = $s['supplies_name'];
                }
            }
        }

        if (count($bahan_kurang) > 0) {
            DB::rollBack();
            return response()->json([
                "status"  => -1,
                "header"  => "Gagal ACC",
                "message" => "Bahan baku tidak mencukupi untuk : " . implode(", ", $bahan_kurang)
            ]);
        }

        // Ditambahkan: kalau baris ProductStock tetap tidak bisa dibuat/ditemukan setelah
        // syncStock() (mis. tidak ada gudang aktif sama sekali), dulu blok kredit di bawah
        // ini di-skip diam-diam — production tetap berstatus Berhasil tapi stok produk jadi
        // TIDAK pernah bertambah dan tidak ada log_stocks yang tercatat. Sekarang dikumpulkan
        // dan di-rollback dengan pesan jelas, bukan gagal senyap.
        $produk_gagal_stok = [];
        foreach ($item as $key => $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            $unitIdInputUser = $value['unit_id'];

            // PENAMBAHAN PRODUK JADI
            $v = ProductStock::where("product_variant_id", $value["product_variant_id"])
                ->where("unit_id", $unitIdInputUser)
                ->where("status", 1)
                ->first();

            if(!$v){
                $pv = ProductVariant::find($value["product_variant_id"]);
                (new ProductStock())->syncStock($pv->product_id);
                $v = ProductStock::where("product_variant_id", $value["product_variant_id"])
                    ->where("unit_id", $unitIdInputUser)
                    ->where("status", 1)
                    ->first();
            }
            $jumlahTambah = (int) $value['pd_qty'];
            if ($v && $v->unit_id == $unitIdInputUser) {
                // GitHub #19 fix (2026-08-24): naik berjenjang lewat SELURUH rantai product_relations
                // (bukan cuma satu hop) -- lihat UnitRollUp::planProductOutput(). Setiap level yang
                // dilewati dikredit dan dicatat log_stocks-nya sendiri; level yang tidak berubah
                // sama sekali (qty 0) tidak perlu disentuh.
                //
                // GitHub #87 fix (2026-08-29): plan()-nya sekarang existing-aware, jadi satu kredit
                // BISA negatif -- itu berarti sebagian stok LAMA di level itu ikut naik satuan
                // bersama tambahan produksi ini (bukan hilang), bukan cuma level baru yang selalu
                // >=0 seperti dulu. Makanya loop di bawah ini tunggal untuk SEMUA kasus (baik naik
                // satuan maupun tidak) -- dulu ada percabangan count($chain)>1 vs flat-add, sekarang
                // tidak perlu lagi karena plan() dengan chain 1 level pun sudah menghasilkan hasil
                // yang identik dengan flat-add.
                $chain = UnitRollUp::planProductOutput((int) $value["product_variant_id"], (int) $v->unit_id, $jumlahTambah);

                foreach ($chain as $credit) {
                    if ($credit['qty'] === 0) continue;

                    $ps = $this->ensureProductStockRow((int) $value["product_variant_id"], $credit['unit_id']);
                    $ps->ps_stock += $credit['qty'];
                    $ps->save();

                    if ($credit['qty'] > 0) {
                        $insertProductLogOnce([
                            'log_date' => \Carbon\Carbon::parse($p->production_date)->setTimeFrom(now()),
                            'log_kode' => $p->production_code,
                            'log_type' => 1, 'log_category' => 1,
                            'log_item_id' => $value["product_variant_id"],
                            'log_notes' => "Hasil Produksi Produk " . LogStock::actorSuffix(),
                            'log_jumlah' => $credit['qty'], 'unit_id' => $credit['unit_id'],
                        ]);
                    } else {
                        // Stok lama di level ini ikut tergulung naik ke level di atasnya bersama
                        // hasil produksi -- catat sebagai konversi satuan (keluar di sini), bukan
                        // "hasil produksi", supaya ledger tidak menunjukkan penurunan stok tanpa
                        // penjelasan. Pasangannya (masuk di level atas) tercatat oleh iterasi
                        // berikutnya lewat cabang "qty > 0" di atas.
                        $insertProductLogOnce([
                            'log_date' => \Carbon\Carbon::parse($p->production_date)->setTimeFrom(now()),
                            'log_kode' => $p->production_code,
                            'log_type' => 1, 'log_category' => 2,
                            'log_item_id' => $value["product_variant_id"],
                            'log_notes' => "Konversi unit dari hasil produksi (Naik satuan) " . LogStock::actorSuffix(),
                            'log_jumlah' => abs($credit['qty']), 'unit_id' => $credit['unit_id'],
                        ]);
                    }
                }
            } else {
                $pv = ProductVariant::find($value["product_variant_id"]);
                $namaProduk = '-';
                if ($pv) {
                    $prName = Product::find($pv->product_id);
                    $namaProduk = trim(($prName->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
                    if ($namaProduk === '') {
                        $namaProduk = $pv->product_variant_name ?? '-';
                    }
                }
                if (!in_array($namaProduk, $produk_gagal_stok, true)) {
                    $produk_gagal_stok[] = $namaProduk;
                }
            }
        }

        if (count($produk_gagal_stok) > 0) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => 'Baris stok produk tidak dapat dibuat/ditemukan untuk: '
                    . implode(', ', $produk_gagal_stok)
                    . '. Pastikan minimal 1 gudang aktif tersedia, lalu coba lagi.',
            ]);
        }

        (new Production())->accProduction($data);
        DB::commit();
        return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function declineProduction(Request $req)
    {
        $data = $req->all();
        $data['delete_reason'] = "Tolak Produksi";

        // Pengecekan ACC
        $q = Production::find($data['production_id']);
        if ($q->status != 1) {
            $staff = Staff::find($q->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        (new Production())->declineProduction($data);
    }

    function tolakDeleteProduction(Request $req)
    {
        $data = $req->all();
        $q = Production::find($data['production_id']);
        if ($q->status != 4) {
            $staff = Staff::find($q->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        (new Production())->tolakDeleteProduction($data);
    }

    function accDeleteProduction(Request $req)
    {
        $data = $req->all();
        $p = (new Production())->getProduction(["production_id" => $data['production_id']])->first();
        if ($p['items']->count() == 0){
            (new Production())->cancelProduction($data);
            return 1;
        }

        // Pengecekan ACC
        if ($p->status != 4) {
            $staff = Staff::find($p->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }

        $produk_kurang = [];
        $cek = -1;

        foreach ($p['items'] as $key => $value) {
            $b = Bom::find($value['bom_id']);
            $jumlahTambah = intval($value['pd_qty']);

            $stok = ProductStock::where("product_variant_id", "=", $value['product_variant_id'])
                    ->where("unit_id", "=", $value['unit_id'])
                    ->where("status", 1)
                    ->first();

            $r = ProductRelation::where('pr_unit_id_2', '=', $value["unit_id"])
                    ->where('product_variant_id', '=', $value["product_variant_id"])
                    ->where('status', '=', 1)
                    ->first();

            if ($b['unit_id'] == $value['unit_id'] && $r) {
                $sisa   = $jumlahTambah % $r->pr_unit_value_2;
                $tambah = floor($jumlahTambah / $r->pr_unit_value_2);

                // Hitung total stok tersedia dalam satuan piece (gabung dari dos + piece)
                $stok_depan = ProductStock::where("product_variant_id", "=", $value['product_variant_id'])
                    ->where("unit_id", "=", $r->pr_unit_id_1)
                    ->where("status", 1)
                    ->first();

                $totalTersedia = ($stok ? $stok->ps_stock : 0)
                            + ($stok_depan ? $stok_depan->ps_stock * $r->pr_unit_value_2 : 0);

                if ($totalTersedia < $jumlahTambah) {
                    $cek = 1;
                    $pvr = ProductVariant::find($value['product_variant_id']);
                    $pr  = Product::find($pvr->product_id);
                    if (!in_array($pr['product_name'] . " " . $pvr['product_variant_name'], $produk_kurang, true)) {
                        $produk_kurang[] = $pr['product_name'] . " " . $pvr['product_variant_name'];
                    }
                }
            } else {
                // Tidak ada relasi — cek stok langsung
                $totalTersedia = $stok ? $stok->ps_stock : 0;
                if ($totalTersedia < $jumlahTambah) {
                    $cek = 1;
                    $pvr = ProductVariant::find($value['product_variant_id']);
                    $pr  = Product::find($pvr->product_id);
                    if (!in_array($pr['product_name'] . " " . $pvr['product_variant_name'], $produk_kurang, true)) {
                        $produk_kurang[] = $pr['product_name'] . " " . $pvr['product_variant_name'];
                    }
                }
            }
        }

        if ($cek == 1) {
            return response()->json([
                "status"  => -1,
                "message" => "Stok produk tidak mencukupi: " . implode(', ', $produk_kurang),
            ]);
        }

        DB::beginTransaction();
        try {
        // Status flip dipindah ke akhir (setelah semua mutasi berhasil) — dulu dijalankan di sini,
        // sebelum reversal, jadi production sudah permanen ber-status Batal walau reversal-nya
        // sendiri crash di tengah jalan.

        // Stok produk
        $produk_gagal_reversal = [];
        foreach ($p['items'] as $key => $value) {
            $b = Bom::find($value->bom_id);
            $jumlahKurang = intval($value['pd_qty']);

            $r = ProductRelation::where('pr_unit_id_2', $value["unit_id"])
                ->where('product_variant_id', $value["product_variant_id"])
                ->where('status', 1)
                ->first();

            if ($r && $jumlahKurang >= $r->pr_unit_value_2) {
                $kurangDos = floor($jumlahKurang / $r->pr_unit_value_2);
                $sisaPiece = $jumlahKurang % $r->pr_unit_value_2;

                $ps_depan = ProductStock::where("product_variant_id", $value["product_variant_id"])
                    ->where("unit_id", $r->pr_unit_id_1)
                    ->where("status", 1)
                    ->first();
                // Ditambahkan: dulu di-assign langsung tanpa null-check — pre-check di atas cuma
                // memastikan stok GABUNGAN (piece + dos*rasio) cukup, tidak memastikan baris
                // ProductStock di unit yang lebih besar ini benar-benar ada, jadi produk yang
                // seluruh stoknya nyangkut di unit kecil crash di sini ("Attempt to assign
                // property on null"). Sekarang dikumpulkan dan di-rollback dengan pesan jelas.
                if (!$ps_depan) {
                    $produk_gagal_reversal[] = $this->productLabelForReversalError($value['product_variant_id']);
                    continue;
                }
                $ps_depan->ps_stock -= $kurangDos;
                $ps_depan->save();

                (new LogStock())->insertLog([
                    'log_date'     => now(),
                    'log_kode'     => $p->production_code,
                    'log_type'     => 1,
                    'log_category' => 2,
                    'log_item_id'  => $value["product_variant_id"],
                    'log_notes'    => "Pembatalan produksi produk",
                    'log_jumlah'   => $kurangDos,
                    'unit_id'      => $r->pr_unit_id_1,
                ]);

                if ($sisaPiece > 0) {
                    $ps_belakang = ProductStock::where("product_variant_id", $value["product_variant_id"])
                        ->where("unit_id", $r->pr_unit_id_2)
                        ->where("status", 1)
                        ->first();
                    if (!$ps_belakang) {
                        $produk_gagal_reversal[] = $this->productLabelForReversalError($value['product_variant_id']);
                        continue;
                    }
                    $ps_belakang->ps_stock -= $sisaPiece;
                    $ps_belakang->save();

                    (new LogStock())->insertLog([
                        'log_date'     => now(),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 1,
                        'log_category' => 2,
                        'log_item_id'  => $value["product_variant_id"],
                        'log_notes'    => "Pembatalan produksi produk",
                        'log_jumlah'   => $sisaPiece,
                        'unit_id'      => $r->pr_unit_id_2,
                    ]);
                }

            } else {
                $v = ProductStock::where("product_variant_id", $value["product_variant_id"])
                    ->where("unit_id", $value["unit_id"])
                    ->where("status", 1)
                    ->first();
                if (!$v) {
                    $produk_gagal_reversal[] = $this->productLabelForReversalError($value['product_variant_id']);
                    continue;
                }
                $v->ps_stock -= $jumlahKurang;
                $v->save();

                (new LogStock())->insertLog([
                    'log_date'     => now(),
                    'log_kode'     => $p->production_code,
                    'log_type'     => 1,
                    'log_category' => 2,
                    'log_item_id'  => $value["product_variant_id"],
                    'log_notes'    => "Pembatalan produksi produk",
                    'log_jumlah'   => $jumlahKurang,
                    'unit_id'      => $value["unit_id"],
                ]);
            }
        }

        if (count($produk_gagal_reversal) > 0) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Batal Produksi',
                'message' => 'Baris stok produk tidak dapat ditemukan untuk: '
                    . implode(', ', array_unique($produk_gagal_reversal))
                    . '. Pembatalan dibatalkan, tidak ada perubahan data.',
            ]);
        }

        // Hitung aggregatedRequirements dari data produksi yang sudah ada
        $aggregatedRequirements = [];
        foreach ($p['items'] as $key => $value) {
            $b   = Bom::find($value['bom_id']);
            $bdetail = BomDetail::where('bom_id', $value['bom_id'])->where('status', 1)->get();
            if (!$b) continue;

            $batchCount = $this->getBatchCount(
                (int) $value['pd_qty'],
                (int) $value['unit_id'],
                (int) $b->bom_qty,
                (int) $b->unit_id,
                (int) $value['product_variant_id']
            );

            foreach ($bdetail as $bd) {
                $id = $bd['supplies_id'];

                $namaBahan      = Supplies::find($id)->supplies_name;
                $isKemasanBesar = preg_match('/dos|pack/i', $namaBahan);

                if ($isKemasanBesar) {
                    $relasiKonversi = ProductRelation::where('product_variant_id', $value['product_variant_id'])
                        ->where('pr_unit_id_2', $b['unit_id'])
                        ->where('status', 1)
                        ->first();

                    // Diperbaiki (2026-08-06): lihat comment di convertQtyBetweenUnits() —
                    // berhenti tepat di satuan resep, bukan sampai unit paling bawah rantai. Ini
                    // membuat reversal ini simetris dengan insertProduction()/accProduction().
                    $nilaiIsiDos = $relasiKonversi ? $relasiKonversi->pr_unit_value_2 : 1;
                    $totalPcs    = $this->convertQtyBetweenUnits(
                        (int) $value['pd_qty'],
                        (int) $value['unit_id'],
                        (int) $b['unit_id'],
                        (int) $value['product_variant_id']
                    );
                    $jumlahDos      = floor($totalPcs / $nilaiIsiDos);
                    $kebutuhanBaris = $jumlahDos * $bd['bom_detail_qty'];
                } else {
                    $kebutuhanBaris = $bd['bom_detail_qty'] * $batchCount;
                }

                if (!isset($aggregatedRequirements[$id])) {
                    $aggregatedRequirements[$id] = [
                        'total_butuh' => 0,
                        'details'     => $bd
                    ];
                }
                $aggregatedRequirements[$id]['total_butuh'] += $kebutuhanBaris;
            }
        }
        foreach ($aggregatedRequirements as $suppliesId => $butuh) {
            $butuhTersedia = (float)$butuh['total_butuh'];
            if ($butuhTersedia <= 0) continue;

            $ss = SuppliesStock::where('supplies_id', $suppliesId)
                ->where('status', 1)
                ->orderBy('ss_id', 'desc')
                ->get();

            if ($ss->isEmpty()) continue;

            // Cari unit terkecil berdasarkan relasi, bukan index
            $stokBawah = $ss->first(); // default
            foreach ($ss as $stok) {
                $adaBawahan = SuppliesRelation::where('supplies_id', $suppliesId)
                    ->where('su_id_1', $stok->unit_id)
                    ->where('status', 1)
                    ->exists();
                if (!$adaBawahan) {
                    $stokBawah = $stok; // tidak punya bawahan = unit terkecil (Piece)
                    break;
                }
            }

            // Cari relasi dari unit terkecil ke unit atas
            $sr = SuppliesRelation::where('supplies_id', $suppliesId)
                ->where('su_id_2', $stokBawah->unit_id) // su_id_2 = Piece
                ->where('status', 1)
                ->first();

            if ($sr && $butuhTersedia >= $sr->sr_value_2) {
                // Hitung konversi: 144 Piece / 24 = 6 DOS
                $kembalikanDos = floor($butuhTersedia / $sr->sr_value_2);
                $sisaPiece     = fmod($butuhTersedia, $sr->sr_value_2);

                // Kembalikan ke unit atas (DOS)
                $stokAtas = SuppliesStock::where('supplies_id', $suppliesId)
                    ->where('unit_id', $sr->su_id_1)
                    ->where('status', 1)
                    ->first();

                if ($stokAtas) {
                    $stokAtas->ss_stock += $kembalikanDos;
                    $stokAtas->save();

                    (new LogStock())->insertLog([
                        'log_date'     => now(),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => 1,
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => "Pengembalian stok bahan akibat pembatalan produksi " . LogStock::actorSuffix(),
                        'log_jumlah'   => $kembalikanDos,
                        'unit_id'      => $sr->su_id_1,
                    ]);
                }

                // Kembalikan sisa piece kalau ada
                if ($sisaPiece > 0) {
                    $stokBawah->ss_stock += $sisaPiece;
                    $stokBawah->save();

                    (new LogStock())->insertLog([
                        'log_date'     => now(),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => 1,
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => "Pengembalian stok bahan akibat pembatalan produksi " . LogStock::actorSuffix(),
                        'log_jumlah'   => $sisaPiece,
                        'unit_id'      => $stokBawah->unit_id,
                    ]);
                }

            } else {
                // Tidak ada relasi atau jumlah kurang dari 1 DOS — kembalikan langsung ke unit terkecil
                $stokBawah->ss_stock += $butuhTersedia;
                $stokBawah->save();

                (new LogStock())->insertLog([
                    'log_date'     => now(),
                    'log_kode'     => $p->production_code,
                    'log_type'     => 2,
                    'log_category' => 1,
                    'log_item_id'  => $suppliesId,
                    'log_notes'    => "Pengembalian stok bahan akibat pembatalan produksi " . LogStock::actorSuffix(),
                    'log_jumlah'   => $butuhTersedia,
                    'unit_id'      => $stokBawah->unit_id,
                ]);
            }
        }

        (new Production())->cancelProduction($data);
        (new ProductionDetails())->cancelProductionDetail($data);
        DB::commit();
        return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function getPemakaian(Request $req)
    {
        $bahan = [];
        $data = (new production)->getProduction(["date" => $req->date]);
        foreach ($data as $production) {
            foreach ($production->items as $item) {
                $bom = Bom::find($item->bom_id);
                if (!$bom) {
                    continue;
                }
                $bhan = BomDetail::where('bom_id', '=', $bom->bom_id)->where('status', 1)->get();
                foreach ($bhan as $valueBahan) {
                    $batchCount = $this->getBatchCount(
                        (int) $item->pd_qty,
                        (int) $item->unit_id,
                        (int) $bom->bom_qty,
                        (int) $bom->unit_id,
                        (int) $item->product_variant_id
                    );
                    $supVar = SuppliesVariant::find($valueBahan->supplies_id);
                    if (!$supVar) {
                        continue;
                    }
                    $sup = Supplies::find($supVar->supplies_id);
                    $unit_name = Unit::find($sup->supplies_unit)->unit_name;
                    $supVar->production_date = $production->production_date;
                    $supVar->kode_produksi = "PR" . str_pad($production->production_id, 4, "0", STR_PAD_LEFT);
                    $supVar->qtyPemakaian = ($batchCount * $valueBahan->bom_detail_qty) . " " . $unit_name;
                    $supVar->supplies_name = $sup->supplies_name . " " . $supVar->supplies_variant_name;
                    array_push($bahan, $supVar);
                }
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

    private function convertQtyToSmallestUnit(int $qty, int $unitId, int $productVariantId): int
    {
        // Bug #16: this used to fetch EVERY active product_relations row for the variant and
        // multiply all of their pr_unit_value_2 together whenever the row's base unit wasn't
        // $unitId — which is true for every sibling row when a product has more than one
        // independent "big unit -> Piece" relation (e.g. DOS=20pcs, kg=5pcs, LTR=10pcs all
        // mapping to Piece). That folded unrelated relations into the multiplier
        // (20 * 5 * 10 = 1000) instead of picking the single relation that actually matches
        // $unitId, producing a 100x+ raw-material overconsumption. Walk the chain one hop at a
        // time instead, exactly like the already-correct convertSuppliesQtyToSmallestUnit()
        // below — this also makes multi-level ladders (Sak -> DOS -> Piece) resolve correctly.
        $relations = ProductRelation::where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->get();

        $multiplier = 1;
        $currentUnit = $unitId;
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(fn ($r) => (int) $r->pr_unit_id_1 === (int) $currentUnit);
            if (!$rel) {
                break;
            }
            $multiplier *= (int) $rel->pr_unit_value_2;
            $currentUnit = (int) $rel->pr_unit_id_2;
        }

        return $qty * $multiplier;
    }

    /**
     * Diperbaiki (2026-08-06): dulu perhitungan "kemasan besar" (dos/pack) di bawah memakai
     * convertQtyToSmallestUnit(), yang SELALU jalan sampai unit paling bawah di rantai relasi —
     * padahal yang dibutuhkan di sana adalah qty dalam satuan RESEP (`$bom['unit_id']`), yang
     * belum tentu unit paling bawah kalau rantainya lebih dari satu tingkat (mis. Dos->Pcs->Liter,
     * resep pakai Pcs — bukan Liter). Contoh nyata bedanya: 1 Dos = 12 Pcs, 1 Pcs = 2 Liter. Resep
     * "Dos Karton isi 12 Pcs" butuh qty dalam satuan Pcs (12), tapi convertQtyToSmallestUnit()
     * jalan terus sampai Liter (12*2=24) — floor(24/12)=2 dos yang dianggap terpakai, padahal yang
     * benar floor(12/12)=1. Method ini berhenti TEPAT di $toUnitId, bukan di dasar rantai.
     *
     * Fail-safe: kalau $toUnitId tidak pernah ketemu sambil turun dari $fromUnitId (seharusnya
     * tidak terjadi untuk produk yang relasinya sudah tersambung benar, lihat validasi "$ada" di
     * pemanggil), kembalikan $qty apa adanya (anggap 1:1) daripada mengalikan dengan faktor yang
     * sebenarnya tidak berhubungan.
     */
    private function convertQtyBetweenUnits(int $qty, int $fromUnitId, int $toUnitId, int $productVariantId): int
    {
        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        $relations = ProductRelation::where('product_variant_id', $productVariantId)
            ->where('status', 1)
            ->get();

        $multiplier = 1;
        $currentUnit = $fromUnitId;
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(fn ($r) => (int) $r->pr_unit_id_1 === (int) $currentUnit);
            if (!$rel) {
                return $qty;
            }
            $multiplier *= (int) $rel->pr_unit_value_2;
            $currentUnit = (int) $rel->pr_unit_id_2;
            if ($currentUnit === $toUnitId) {
                return $qty * $multiplier;
            }
        }

        return $qty;
    }

    private function getBatchCount(
        int $pdQty,
        int $pdUnitId,
        int $bomQty,
        int $bomUnitId,
        int $productVariantId
    ): int {
        $pdSmallest = $this->convertQtyToSmallestUnit($pdQty, $pdUnitId, $productVariantId);
        $bomSmallest = $this->convertQtyToSmallestUnit($bomQty, $bomUnitId, $productVariantId);

        if ($bomSmallest <= 0) {
            return $pdQty > 0 ? 1 : 0;
        }

        return (int) ($pdSmallest / $bomSmallest);
    }

    /**
     * accDeleteProduction()'s stock-reversal loop: a missing ProductStock row here can't be
     * auto-provisioned like ensureProductStockRow() does for a fresh credit (there'd be nothing
     * real to subtract from), so it's reported as a clean rollback error instead — this just
     * builds the "Product Variant Name" label for that message.
     */
    private function productLabelForReversalError(int $productVariantId): string
    {
        $pv = ProductVariant::find($productVariantId);
        if (!$pv) {
            return "id {$productVariantId}";
        }
        $product = Product::find($pv->product_id);
        $label = trim(($product->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));

        return $label !== '' ? $label : ($pv->product_variant_name ?? "id {$productVariantId}");
    }

    /**
     * Mirrors ensureSuppliesStockRows() below, on the finished-goods side: accProduction()'s
     * ladder-split output crediting needs a ProductStock row at the larger unit (pr_unit_id_1) to
     * exist before it can be credited — auto-provision it at 0 stock instead of crashing on a
     * null lookup.
     */
    private function ensureProductStockRow(int $productVariantId, int $unitId)
    {
        $ps = ProductStock::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('status', 1)
            ->first();

        if ($ps) {
            return $ps;
        }

        $pv = ProductVariant::find($productVariantId);
        (new ProductStock())->insertProductStock([
            'product_id' => $pv->product_id,
            'product_variant_id' => $productVariantId,
            'unit_id' => $unitId,
            'ps_stock' => 0,
        ]);

        return ProductStock::where('product_variant_id', $productVariantId)
            ->where('unit_id', $unitId)
            ->where('status', 1)
            ->first();
    }

    private function ensureSuppliesStockRows(int $suppliesId)
    {
        (new SuppliesStock())->syncStock($suppliesId);

        $relationUnits = SuppliesRelation::where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->get()
            ->flatMap(fn ($rel) => [(int) $rel->su_id_1, (int) $rel->su_id_2])
            ->unique()
            ->filter();

        foreach ($relationUnits as $unitId) {
            $exists = SuppliesStock::where('supplies_id', $suppliesId)
                ->where('unit_id', $unitId)
                ->where('status', 1)
                ->exists();

            if (!$exists) {
                (new SuppliesStock())->insertProductStock([
                    'supplies_id' => $suppliesId,
                    'unit_id' => $unitId,
                    'ss_stock' => 0,
                ]);
            }
        }

        return SuppliesStock::where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->orderBy('ss_id', 'desc')
            ->get();
    }

    private function convertSuppliesQtyToSmallestUnit(float $qty, int $unitId, int $suppliesId): float
    {
        $relations = SuppliesRelation::where('supplies_id', $suppliesId)->where('status', 1)->get();
        $multiplier = 1.0;
        $currentUnit = $unitId;
        $guard = 0;

        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(fn ($r) => (int) $r->su_id_1 === (int) $currentUnit);
            if (!$rel) {
                break;
            }
            $multiplier *= (float) $rel->sr_value_2;
            $currentUnit = (int) $rel->su_id_2;
        }

        return $qty * $multiplier;
    }

    private function convertSuppliesQtyBetweenUnits(
        float $qty,
        int $fromUnitId,
        int $toUnitId,
        int $suppliesId
    ): float {
        if ((int) $fromUnitId === (int) $toUnitId) {
            return $qty;
        }

        $smallestQty = $this->convertSuppliesQtyToSmallestUnit($qty, $fromUnitId, $suppliesId);
        $toMultiplier = $this->convertSuppliesQtyToSmallestUnit(1, $toUnitId, $suppliesId);
        if ($toMultiplier <= 0) {
            return $qty;
        }

        return $smallestQty / $toMultiplier;
    }

    private function getTotalSuppliesStockInUnit(int $suppliesId, int $targetUnitId, $ss = null): float
    {
        if ($ss === null) {
            $ss = SuppliesStock::where('supplies_id', $suppliesId)->where('status', 1)->get();
        }

        $total = 0.0;
        foreach ($ss as $stok) {
            $total += $this->convertSuppliesQtyBetweenUnits(
                (float) $stok->ss_stock,
                (int) $stok->unit_id,
                $targetUnitId,
                $suppliesId
            );
        }

        return $total;
    }

    private function findSuppliesStockUnitIndex($ss, int $preferredUnitId, int $suppliesId): int
    {
        foreach ($ss as $idx => $stok) {
            if ((int) $stok->unit_id === $preferredUnitId) {
                return $idx;
            }
        }

        foreach ($ss as $idx => $stok) {
            $adaBawahan = SuppliesRelation::where('supplies_id', $suppliesId)
                ->where('su_id_1', $stok->unit_id)
                ->where('status', 1)
                ->exists();
            if (!$adaBawahan) {
                return $idx;
            }
        }

        return 0;
    }

    /**
     * @return array<int, mixed>
     */
    private function getBomDetailRows($bom): array
    {
        if (!$bom) {
            return [];
        }

        $details = $bom['details'] ?? $bom['items'] ?? [];
        if ($details instanceof \Illuminate\Support\Collection) {
            return $details->all();
        }

        return is_array($details) ? $details : [];
    }

    /**
     * Validasi bahwa satuan produk yang digunakan di resep (boms.unit_id) adalah
     * satuan terkecil berdasarkan ProductRelation.
     *
     * Satuan terkecil produk = unit yang TIDAK muncul sebagai pr_unit_id_1
     * di product_relations (tidak ada satuan yang lebih kecil di bawahnya).
     *
     * @param  array $productionItems  Array item dari request detail produksi
     * @return array  Daftar label "nama produk (satuan bom)" yang bukan satuan terkecil
     */
    private function validateBomProductSmallestUnit(array $productionItems): array
    {
        $invalid = [];

        foreach ($productionItems as $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!$bom) {
                continue;
            }

            $bomUnitId         = (int) $bom->unit_id;
            $productVariantId  = (int) $value['product_variant_id'];

            // Satuan terkecil produk = tidak ada ProductRelation yang memakai unit ini sebagai pr_unit_id_1
            $isNotSmallest = ProductRelation::where('product_variant_id', $productVariantId)
                ->where('pr_unit_id_1', $bomUnitId)
                ->where('status', 1)
                ->exists();

            if (!$isNotSmallest) {
                // Sudah satuan terkecil — aman
                continue;
            }

            // bom.unit_id masih bisa dikonversi ke satuan yang lebih kecil → bukan terkecil
            $pv      = ProductVariant::find($productVariantId);
            $unit    = Unit::find($bomUnitId);
            $prName  = $pv ? Product::find($pv->product_id) : null;
            $namaProduk = trim(($prName->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
            if ($namaProduk === '') {
                $namaProduk = $pv->product_variant_name ?? '-';
            }
            $unitLabel = $unit->unit_short_name ?? $unit->unit_name ?? '-';
            $label = "{$namaProduk} ({$unitLabel})";

            if (!in_array($label, $invalid, true)) {
                $invalid[] = $label;
            }
        }

        return $invalid;
    }

    /**
     * Validasi bahwa setiap satuan bahan mentah di resep (bom_details) adalah
     * satuan terkecil sesuai relasi supplies.
     *
     * Satuan terkecil didefinisikan sebagai unit yang TIDAK memiliki relasi
     * keluar (tidak ada baris supplies_relations dengan su_id_1 == unit_id),
     * sehingga tidak bisa dikonversi ke satuan yang lebih kecil lagi.
     *
     * @param  array $productionItems  Array item dari request detail produksi
     * @return array  Daftar label "nama bahan (satuan)" yang bukan satuan terkecil
     */
    private function validateBomSuppliesSmallestUnit(array $productionItems): array
    {
        $invalid = [];

        foreach ($productionItems as $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!$bom) {
                continue;
            }

            foreach ($this->getBomDetailRows($bom) as $bd) {
                $unitId     = (int) $bd['unit_id'];
                $suppliesId = (int) $bd['supplies_id'];

                // Satuan terkecil = unit yang tidak memiliki relasi turun (su_id_1 == unit_id)
                $hasDownwardRelation = SuppliesRelation::where('supplies_id', $suppliesId)
                    ->where('su_id_1', $unitId)
                    ->where('status', 1)
                    ->exists();

                if (!$hasDownwardRelation) {
                    // Unit ini sudah satuan terkecil — tidak perlu divalidasi
                    continue;
                }

                // Unit masih punya relasi ke bawah → bukan satuan terkecil
                $supplies = Supplies::find($suppliesId);
                $unit     = Unit::find($unitId);
                $label    = trim(
                    ($supplies->supplies_name ?? '-')
                    . ' ('
                    . ($unit->unit_short_name ?? $unit->unit_name ?? '-')
                    . ')'
                );

                if (!in_array($label, $invalid, true)) {
                    $invalid[] = $label;
                }
            }
        }

        return $invalid;
    }

    private function validateProductionBomActiveUnits(array $productionItems): array
    {
        $invalid = [];

        foreach ($productionItems as $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!$bom) {
                continue;
            }

            foreach ($this->getBomDetailRows($bom) as $bd) {
                if ((new Supplies())->isSuppliesUnitActive((int) $bd['supplies_id'], (int) $bd['unit_id'])) {
                    continue;
                }

                $supplies = Supplies::find($bd['supplies_id']);
                $unit = Unit::find($bd['unit_id']);
                $label = trim(
                    ($supplies->supplies_name ?? '-')
                    . ' ('
                    . ($unit->unit_short_name ?? $unit->unit_name ?? '-')
                    . ')'
                );

                if (!in_array($label, $invalid, true)) {
                    $invalid[] = $label;
                }
            }
        }

        return $invalid;
    }

    /**
     * @deprecated Gunakan App\Support\ProductionPendingStockRestorer
     * Tetap ada sebagai wrapper tipis bila ada pemanggilan lama.
     */
    private function revertPendingProductionStockMutations(string $productionCode): int
    {
        return (new ProductionPendingStockRestorer())
            ->revertProductionCode($productionCode, withHistory: false);
    }
}
