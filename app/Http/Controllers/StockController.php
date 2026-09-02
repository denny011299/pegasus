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
use App\Models\StockAlert;
use App\Models\StockAlertSupplies;
use App\Models\StockOpname;
use App\Models\StockOpnameBahan;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameDetailBahan;
use App\Models\StockOpnameLine;
use App\Models\StockOpnameBahanLine;
use App\Models\Supplier;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\ProductUnitStock;
use App\Support\RoleAccess;
use App\Support\UnitStockSorter;
use App\Support\StockOpname\OpnameLifecycle;
use App\Support\StockOpname\OpnameLineReader;
use App\Support\StockOpname\BahanOpnameLifecycle;
use App\Support\StockOpname\BahanOpnameLineReader;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

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

    /**
     * Dokumen draft hanya boleh dilihat/diubah/dihapus oleh staff pembuatnya
     * (atau super admin) — dipakai insert/update/delete/submit/acc/tolak/PDF
     * stock opname produk supaya aturan pemilik drafnya konsisten di semua
     * jalur, bukan hanya di listing.
     */
    private function canManageStockOpnameDraft($sto)
    {
        if (!$sto) return false;

        $user = session()->get('user');
        if (RoleAccess::isSuperAdmin($user)) return true;

        return $user && (int) $sto->created_by === (int) ($user->staff_id ?? 0);
    }

    /** Nama gudang aktif di navbar (untuk field disabled di form create/detail). */
    private function activeWarehouseLabel(): string
    {
        $id = (int) (Session::get('active_warehouse_id') ?? 0);
        if ($id <= 0) {
            return '';
        }
        $name = Warehouse::query()->whereKey($id)->value('warehouse_name');

        return trim((string) ($name ?? ''));
    }

    /**
     * Rancang ulang 2026-08-27 (merged from main's efef95e): dokumen baru ditulis ke
     * stock_opname_lines (satu baris per satuan, angka betulan), BUKAN lagi ke
     * stock_opname_details (tiga longtext siap-cetak). Dokumen lama tidak dimigrasikan dan tetap
     * dibaca lewat cabang legacy OpnameLineReader.
     *
     * Tidak butuh perubahan frontend: units[] dengan real_qty null-able sudah dikirim
     * CreateStockOpname.js sejak dulu, cuma dulu dibuang begitu saja di sini.
     */
    function insertStockOpname(Request $req)
    {
        $data = $req->all();
        $items = json_decode($req->item, true) ?: [];

        return DB::transaction(function () use ($data, $items) {
            $id = (new StockOpname())->insertStockOpname($data);
            StockOpnameLine::writeFromPayload($id, $items);

            $lifecycle = new OpnameLifecycle();
            // Keputusan PM 2026-08-27: gulung satuan (mis. 1 DOS = 12 pcs, isi 30 pcs -> tersimpan
            // 2 DOS + 6 pcs) di SETIAP simpan -- draft ATAUPUN langsung menunggu, bukan cuma saat
            // diajukan/diputuskan. Dipanggil SEBELUM publish() supaya identitas satuan yang baru
            // ikut tercipta dari gulungan (kalaupun ada) turut dibekukan pada publish yang sama.
            $lifecycle->rollUpUnits(StockOpname::find($id));

            // UI sekarang membuat dokumen LANGSUNG non-draft (.btn-save mengirim is_draft = 0 dan
            // tidak pernah lewat /submitStockOpname), jadi publish-nya terjadi di sini. Aman juga
            // untuk draft: publish() sendiri yang menolak selama is_draft masih true.
            $lifecycle->publish(StockOpname::find($id));

            return response()->json(['status' => 1, 'sto_id' => $id]);
        });
    }

    function updateStockOpname(Request $req)
    {
        $data = $req->all();
        $sto = StockOpname::find($data['sto_id'] ?? null);

        if (!$sto) {
            return ["status" => -1, "message" => "Dokumen ini tidak bisa diubah"];
        }
        // Draft: hanya pembuatnya (atau super admin) boleh mengedit -- lihat
        // canManageStockOpnameDraft(). Menunggu (bukan draft, belum diputuskan): siapa pun yang
        // berhak mengakses modul ini boleh mengoreksi hasil hitung sebelum ACC/tolak (merged from
        // main's efef95e, 2026-08-28 -- redesign v2's "koreksi ketikan sebelum ACC" scenario is
        // load-bearing in its own test suite; user confirmed 2026-08-28 this should be allowed the
        // same way accStockOpname()/tolakStockOpname() already aren't creator-restricted, loosening
        // fase2's original drafts-only gate here). Sudah diputuskan (disetujui/ditolak): snapshot
        // final, tidak boleh diedit lagi lewat endpoint ini sama sekali.
        if ($sto->is_draft) {
            if (!$this->canManageStockOpnameDraft($sto)) {
                return ["status" => -1, "message" => "Tidak diizinkan mengubah draft milik staff lain"];
            }
        } elseif ((int) $sto->status !== 1) {
            return ["status" => -1, "message" => "Dokumen ini tidak bisa diubah"];
        }

        // Jangan diam-diam mengubah status draft/menunggu di sini -- itu keputusan
        // /submitStockOpname, bukan /updateStockOpname.
        $data['is_draft'] = $sto->is_draft;
        $items = json_decode($req->item, true) ?: [];

        // NB (merged from main's efef95e, 2026-08-28): the is_old_version branch below keeps
        // fase2's own wipe-and-reinsert semantics (a draft may freely add/remove line items, so
        // diffing per-row isn't worth it) instead of main's update-or-insert-only version -- that's
        // what this endpoint has always done for fase2's draft feature, which main never had.
        DB::transaction(function () use ($data, $items) {
            $id = (new StockOpname())->updateStockOpname($data);
            $sto = StockOpname::find($id);
            if (! $sto) {
                return;
            }

            if ($sto->is_old_version) {
                foreach (StockOpnameDetail::where('sto_id', $id)->where('status', 1)->get() as $old) {
                    $old->status = 0;
                    $old->save();
                }
                foreach ($items as $value) {
                    $value["sto_id"] = $id;
                    (new StockOpnameDetail())->insertDetail($value);
                }

                return;
            }

            // Upsert lewat identitas alami baris (unique index) -- menyimpan ulang tidak bisa
            // menggandakan baris seperti alur lama.
            StockOpnameLine::writeFromPayload($id, $items);

            $lifecycle = new OpnameLifecycle();
            $lifecycle->rollUpUnits($sto->refresh());
            $lifecycle->publish($sto->refresh());
        });

        return 1;
    }

    /**
     * Ajukan draft: is_draft dimatikan, dokumen masuk alur approval biasa
     * (statusnya sudah 1=Menunggu sejak dibuat, jadi tidak perlu diubah).
     */
    function submitStockOpname(Request $req)
    {
        $sto = StockOpname::find($req->sto_id);

        if (!$sto || !$sto->is_draft) {
            return ["status" => -1, "message" => "Dokumen ini bukan draft"];
        }
        if (!$this->canManageStockOpnameDraft($sto)) {
            return ["status" => -1, "message" => "Tidak diizinkan mengajukan draft milik staff lain"];
        }

        $sto->is_draft = false;
        $sto->save();

        // NB (merged from main's efef95e, 2026-08-28): main's version called
        // (new StockOpname())->submitStockOpname($data), a model method that doesn't exist in this
        // tree -- it comes from an earlier main-only commit never merged into fase2/main. Kept
        // fase2's own is_draft flip above and only ported the new bit this commit actually added:
        // dokumen keluar dari draft di sini -- inilah saat snapshot identitas dibekukan untuk
        // alur draft (tombol .btn-ajukan). Idempoten, jadi tidak masalah kalau dokumen ini
        // ternyata sudah pernah publish lewat insert.
        (new OpnameLifecycle())->publish($sto->refresh());

        return 1;
    }

    function deleteStockOpname(Request $req)
    {
        $data = $req->all();
        $sto = StockOpname::find($data['sto_id'] ?? null);

        if ($sto && $sto->is_draft && !$this->canManageStockOpnameDraft($sto)) {
            return ["status" => -1, "message" => "Tidak diizinkan menghapus draft milik staff lain"];
        }

        return (new StockOpname())->deleteStockOpname($data);
    }

    // Stock Opname Detail
    public function DetailStockOpname($id)
    {
        if ($id == -1) {
            return view('Backoffice.Inventory.CreateStockOpname', [
                'data' => [],
                'mode' => 1,
                'warehouse_name' => $this->activeWarehouseLabel(),
            ]);
        }

        StockOpnameDetail::rebuildMissingFromLogs((int) $id);

        $sto = (new StockOpname())->getStockOpname(['sto_id' => $id, 'with_items' => true])->first();
        if (!$sto) {
            abort(404);
        }
        // GitHub #115: getStockOpname() sengaja TIDAK memfilter by-id lookup (komentar di
        // model-nya) supaya buka-lewat-menu tetap kefilter dengan benar tapi buka-lewat-URL-
        // langsung tidak diam-diam 404 untuk dokumen sendiri -- makanya draft milik staff lain
        // baru diblok di sini, bukan lewat query itu.
        if ($sto->is_draft && !$this->canManageStockOpnameDraft($sto)) {
            abort(404);
        }

        // Dokumen versi baru: isi $items dari stock_opname_lines lewat adaptor, dalam struktur
        // yang sama persis -- sisa method ini (urutan, $data, view) dipakai bersama, dan
        // renderMode2() di CreateStockOpname.js tidak berubah sama sekali.
        $items = ! $sto->is_old_version
            ? (new OpnameLineReader())->legacyItems(StockOpname::find($id))
            : [];

        foreach (($sto->is_old_version ? ($sto->item ?? []) : []) as $detail) {
            $units = [];

            foreach ($detail->stock as $s) {
                $units[] = [
                    'unit_id'          => $s->unit_id,
                    'unit_short_name'  => $s->unit_short_name,
                    'system_qty'       => $this->getQty($detail->stod_system, $s->unit_short_name),
                    'real_qty'         => $this->getQty($detail->stod_real, $s->unit_short_name),
                    'selisih_qty'      => $this->getQty($detail->stod_selisih, $s->unit_short_name),
                    // GitHub #78 follow-up: $detail->stock is already the LIVE ProductStock
                    // collection (see ProductVariant::getProductVariantBulk(), joined once in bulk
                    // for the whole document -- no extra query here). Handed to the frontend purely
                    // as a placeholder hint for units the staff hasn't counted yet (renderMode2() in
                    // CreateStockOpname.js) -- NEVER as real_qty itself, so an untouched unit still
                    // submits as "-" (not counted), not a value copy-pasted from this hint.
                    'live_qty'         => (int) $s->ps_stock,
                ];
            }

            if ($units === []) {
                $units = $this->buildUnitsFromQtyStrings($detail);
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
            'warehouse_id' => $sto->warehouse_id ?? null,
            'warehouse_name' => $sto->warehouse_name ?? '-',
            // Ditambahkan (2026-08-05): CreateStockOpname.js membaca data.is_draft/data.created_by
            // untuk canEditDraft — sebelumnya keduanya tidak ada di array ini sama sekali, jadi
            // selalu undefined di frontend (draft tidak pernah terdeteksi sebagai draft).
            'is_draft'    => (bool) $sto->is_draft,
            'created_by'  => $sto->created_by,
            'item'        => $items
        ];

        return view('Backoffice.Inventory.CreateStockOpname', [
            'data' => $data,
            'mode' => 2,
            'warehouse_name' => trim((string) ($sto->warehouse_name ?? '')) ?: $this->activeWarehouseLabel(),
        ]);
    }

    /**
     * @return int|null  null berarti satuan ini SENGAJA tidak dihitung staf (token "-", lihat
     *                    GitHub #78) atau tidak ditemukan sama sekali di string -- BUKAN nol.
     */
    private function getQty($string, $unit): ?int
    {
        // contoh: "12 jerigen, 0 DOS, - pcs" ("-" = tidak dihitung)
        foreach (explode(',', (string) $string) as $part) {
            $part = trim($part);
            if ($part === '') continue;
            [$qty, $u] = array_pad(explode(' ', $part, 2), 2, '');
            if ($u === $unit) {
                return $qty === '-' ? null : (int) $qty;
            }
        }
        return null;
    }

    /**
     * Fallback bila koleksi stock kosong (mis. filter gudang eceran) tapi string qty tersimpan ada.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildUnitsFromQtyStrings($detail): array
    {
        $unitNames = [];
        foreach (['stod_system', 'stod_real', 'stod_selisih'] as $key) {
            $str = trim((string) ($detail->{$key} ?? ''));
            if ($str === '') {
                continue;
            }
            foreach (explode(',', $str) as $part) {
                $part = trim($part);
                if ($part === '' || ! preg_match('/^(-?\d+)\s+(.+)$/u', $part, $m)) {
                    continue;
                }
                $unitNames[trim($m[2])] = true;
            }
        }

        if ($unitNames === []) {
            return [];
        }

        $unitsByShortName = Unit::whereIn('unit_short_name', array_keys($unitNames))
            ->get(['unit_id', 'unit_short_name'])
            ->keyBy('unit_short_name');

        $units = [];
        foreach (array_keys($unitNames) as $unitShortName) {
            $u = $unitsByShortName->get($unitShortName);
            $units[] = [
                'unit_id' => $u ? $u->unit_id : 0,
                'unit_short_name' => $unitShortName,
                'system_qty' => $this->getQty($detail->stod_system ?? '', $unitShortName),
                'real_qty' => $this->getQty($detail->stod_real ?? '', $unitShortName),
                'selisih_qty' => $this->getQty($detail->stod_selisih ?? '', $unitShortName),
            ];
        }

        return $units;
    }

    // Kebalikan dari getQty() -- rakit ['DOS' => 10, 'pcs' => null] jadi "10 DOS, - pcs".
    private function buildQtyString(array $qtyByUnit): string
    {
        $parts = [];
        foreach ($qtyByUnit as $unit => $qty) {
            $parts[] = ($qty === null ? '-' : $qty) . ' ' . $unit;
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
     * dokumen yang belum diputuskan (status masih 1/menunggu -- ini juga mencakup dokumen yang
     * masih draft/fase 2, karena is_draft tidak mengubah status, lihat StockOpname::insertStockOpname()).
     * Snapshot dokumen yang sudah disetujui/ditolak adalah catatan historis yang sengaja dibekukan
     * (lihat accStockOpname() -- dibekukan ke nilai sebenarnya saat itu terjadi) dan TIDAK BOLEH
     * dihitung ulang live, atau dokumen yang sudah disetujui akan selalu terlihat "tidak ada
     * selisih" selamanya setelahnya.
     *
     * PM task (2026-08-24): dulu hasil refresh ini cuma dipakai untuk tampilan PDF sesaat ($item di
     * sini adalah clone ProductVariant/Supplies, bukan row StockOpnameDetail/StockOpnameDetailBahan
     * asli -- lihat StockOpnameDetail::getDetail() -- jadi memanggil save() di atasnya akan salah
     * tabel). Sekarang juga ditulis balik ke baris detail ASLI (lewat $detailModelClass::find())
     * supaya data yang tersimpan di DB ikut mengikuti stok live setiap kali PDF-nya di-download,
     * bukan cuma pas di-ACC.
     */
    private function refreshLiveSystemQty($detail, string $realKey, string $systemKey, string $selisihKey, string $stockQtyKey, string $detailModelClass, string $detailIdKey)
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
                // Satuan yang tidak pernah benar-benar dihitung (real "-", GitHub #78) tidak boleh
                // dibandingkan terhadap stok live yang terus bergerak -- selisihnya tetap "-"
                // selamanya sampai staf benar-benar menghitungnya, bukan dihitung dari nilai
                // fallback basi yang sebelumnya diam-diam disamakan dengan stok sistem lama.
                $selisihByUnit[$unitName] = $realQty === null ? null : ($realQty - $systemQty);
            }

            $item->{$systemKey} = $this->buildQtyString($liveByUnit);
            $item->{$selisihKey} = $this->buildQtyString($selisihByUnit);

            $row = $detailModelClass::find($item->{$detailIdKey} ?? null);
            if ($row) {
                $row->{$systemKey} = $item->{$systemKey};
                $row->{$selisihKey} = $item->{$selisihKey};
                $row->save();
            }
        }

        return $detail;
    }

    /**
     * PDF-only cosmetic pass (GitHub #78 follow-up): the user wants an untouched unit's Real/
     * Selisih columns to read as "matches system" (system qty / 0) instead of a bare "-", since a
     * dash alone reads as missing data on a printed document. This must NOT touch the underlying
     * stod_real/stod_selisih strings in the DB -- "-" stays the stored, authoritative "not counted"
     * marker everywhere else (getQty()/accStockOpname()/refreshLiveSystemQty()) so the corruption
     * this whole chain of fixes closed stays closed. Only mutates the in-memory $item handed to the
     * PDF view, never calls ->save(), and runs for every status (a decided document's frozen
     * selisih is real historical data and untouched by this — only the "-" placeholder is
     * humanized).
     *
     * Perbaikan 2026-08-27: kolom Selisih yang dicetak TIDAK lagi dibaca dari string tersimpan,
     * tapi selalu dihitung ulang di sini sebagai (real - sistem) per satuan, dari dua kolom yang
     * persis dicetak di sebelahnya. Alasannya bukan karena string tersimpan pasti salah, tapi
     * karena tidak ada yang menjamin ketiganya konsisten: stod_system bisa ditulis ulang belakangan
     * oleh refreshLiveSystemQty() sementara stod_selisih berasal dari penyimpanan lain, dan dokumen
     * pra-#78 bisa membawa kombinasi apa pun. Dihitung ulang, aritmetika di satu baris cetakan
     * dijamin benar menurut definisi, bukan kebetulan. Ini juga yang membuat highlight di
     * Opname.blade.php/OpnameBahan.blade.php ikut benar, karena blade membaca hasil pass ini.
     * Pencocokan per NAMA satuan, bukan per posisi -- urutan satuan di kolom Sistem dan Real bisa
     * berbeda (lihat SP0071 baris SHPWW5L: sistem "0 pcs, 0 DOS", real "0 DOS, 0 pcs").
     */
    private function humanizeUntouchedForPdf($detail, string $realKey, string $systemKey, string $selisihKey)
    {
        foreach ($detail as $item) {
            $systemMap = [];
            foreach (explode(',', (string) ($item->{$systemKey} ?? '')) as $part) {
                $part = trim($part);
                if ($part === '') continue;
                [$qty, $u] = array_pad(explode(' ', $part, 2), 2, '');
                $systemMap[$u] = (int) $qty;
            }

            $realOut = [];
            $selisihOut = [];
            foreach (explode(',', (string) ($item->{$realKey} ?? '')) as $part) {
                $part = trim($part);
                if ($part === '') continue;
                [$qty, $u] = array_pad(explode(' ', $part, 2), 2, '');
                $untouched = $qty === '-';
                $realOut[$u] = $untouched ? ($systemMap[$u] ?? 0) : (int) $qty;
                $selisihOut[$u] = $realOut[$u] - ($systemMap[$u] ?? 0);
            }

            $item->{$realKey} = $this->buildQtyString($realOut);
            $item->{$selisihKey} = $this->buildQtyString($selisihOut);
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

    function accStockOpname(Request $req)
    {
        $data = $req->all();
        $stod = json_decode($data['item'], true);
        $sto = StockOpname::find($data['sto_id']);

        if (! is_array($stod) || count($stod) === 0) {
            return response()->json([
                'status' => -1,
                'header' => 'Gagal ACC',
                'message' => 'Tidak ada item produk pada dokumen ini',
            ]);
        }

        $warehouseId = (int) (
            ($sto->warehouse_id ?? null)
            ?: (Session::get('active_warehouse_id') ?? 0)
        );
        if ($warehouseId <= 0) {
            $warehouseId = (int) \App\Models\ProductStock::resolveWarehouseId();
        }

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

        // Rancang ulang 2026-08-27: dokumen versi baru mengambil angkanya DARI DATABASE.
        // Alur lama menulis ps_stock = $u['real_qty'] yang dikirim ULANG oleh browser penyetuju
        // (#btn-acc-sto di CreateStockOpname.js men-scrape ulang tabel di layar lalu POST lagi) --
        // artinya isi stok live ditentukan oleh halaman di layar orang yang menyetujui, bukan oleh
        // dokumen yang disetujui. Untuk dokumen versi baru $data['item'] DIABAIKAN TOTAL.
        if (! $sto->is_old_version) {
            return $this->accStockOpnameV2($sto, $warehouseId);
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
                $q = ProductStock::withoutGlobalScope('active_warehouse')
                    ->where('status', 1)
                    ->where('product_variant_id', $value['product_variant_id'])
                    ->where('unit_id', $u['unit_id']);
                if ($warehouseId > 0) {
                    $q->where('warehouse_id', $warehouseId);
                }
                $s = $q->first();

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

                $unitName = $unitNames[$u['unit_id']] ?? ('unit#'.$u['unit_id']);

                // GitHub #78 (merged from main's 54e564c): satuan yang tidak diisi staf sama sekali
                // (real_qty null, dulu diam-diam di-fallback ke stok sistem oleh JS) TIDAK BOLEH
                // menimpa stok live -- itu bukan hasil hitung fisik, cuma snapshot lama yang sudah
                // basi begitu ada pergerakan stok wajar (penjualan/produksi) di antara input dan
                // approve. Cukup catat stok live sekarang untuk histori dokumen, jangan disentuh
                // sama sekali.
                if (!array_key_exists('real_qty', $u) || $u['real_qty'] === null) {
                    $liveSystemByUnit[$unitName] = (int) $s->ps_stock;
                    continue;
                }

                $oldQty = (float) $s->ps_stock;
                $newQty = (float) $u['real_qty'];
                $s->ps_stock = $newQty;
                $s->save();

                $wid = (int) ($s->warehouse_id ?: $warehouseId);
                $diff = round($newQty - $oldQty, 4);
                if (abs($diff) < 1e-9) {
                    (new LogStock())->insertLog([
                        'log_date' => now(),
                        'log_kode' => $sto->sto_code,
                        'log_type' => 1,
                        'log_category' => 1,
                        'log_item_id' => $value['product_variant_id'],
                        'log_notes' => 'Stock Opname Produk (tidak berubah)',
                        'log_jumlah' => 0,
                        'log_saldo' => $newQty,
                        'unit_id' => $u['unit_id'],
                        'warehouse_id' => $wid > 0 ? $wid : null,
                    ]);
                    continue;
                }

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $sto->sto_code,
                    'log_type' => 1,
                    'log_category' => $diff > 0 ? 1 : 2,
                    'log_item_id' => $value['product_variant_id'],
                    'log_notes' => 'Stock Opname Produk',
                    'log_jumlah' => abs($diff),
                    'log_saldo' => $newQty,
                    'unit_id' => $u['unit_id'],
                    'warehouse_id' => $wid > 0 ? $wid : null,
                ]);

                $liveSystemByUnit[$unitName] = (int) $oldQty;
                $realByUnit[$unitName] = (int) $u['real_qty'];
            }

            $detailRow = $detailRows->get($value['product_variant_id']);
            if ($detailRow && !empty($liveSystemByUnit)) {
                $selisihByUnit = [];
                foreach ($liveSystemByUnit as $unitName => $systemQty) {
                    $selisihByUnit[$unitName] = array_key_exists($unitName, $realByUnit)
                        ? ($realByUnit[$unitName] - $systemQty)
                        : null;
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

    /**
     * ACC dokumen Stock Opname versi baru. Sumber angka: stock_opname_lines, bukan request body.
     *
     * Urutan wajib (lihat OpnameLifecycle::freezeSystemQty()):
     *   kunci baris stok -> bekukan stok sistem -> tulis ps_stock -> cap keputusan
     * Membekukan SESUDAH menulis akan menyimpan hasil hitung sebagai "stok sistem" dan membuat
     * selisih dokumen 0 selamanya.
     *
     * \$warehouseId (merged from main's efef95e, 2026-08-28): main has no real multi-warehouse
     * stock, so its version of this method queried ProductStock ambiently (whichever warehouse
     * happens to be active in the ACC-ing staff's OWN session) -- on fase2 that can silently write
     * the wrong warehouse's stock if it differs from the document's own \$sto->warehouse_id. Scoped
     * explicitly here, matching the legacy is_old_version branch above (accStockOpname()'s own
     * \$warehouseId, already resolved from \$sto->warehouse_id there).
     */
    private function accStockOpnameV2(StockOpname $sto, int $warehouseId)
    {
        $lines = StockOpnameLine::getLines($sto->sto_id);
        $lifecycle = new OpnameLifecycle();

        DB::beginTransaction();
        try {
            // Kunci semua baris stok yang terlibat lebih dulu, sekali, supaya pembekuan dan
            // penulisan di bawah melihat angka yang sama dan tidak bisa disusupi transaksi lain
            // di antaranya (TOCTOU).
            $scopeStock = function () use ($warehouseId) {
                return $warehouseId > 0
                    ? ProductStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                    : ProductStock::query();
            };

            $scopeStock()
                ->where('status', 1)
                ->whereIn('product_variant_id', $lines->pluck('product_variant_id')->filter()->unique()->all())
                ->lockForUpdate()
                ->get();

            $lifecycle->freezeSystemQty($sto);

            $produk_gagal = [];
            foreach ($lines as $line) {
                // NULL = satuan ini tidak pernah dihitung. Stok live TIDAK BOLEH disentuh
                // (inti GitHub #78) -- sekarang keadaannya terbaca langsung dari tipe datanya.
                if ($line->sol_counted_qty === null) {
                    continue;
                }

                $stock = $scopeStock()
                    ->where('product_variant_id', $line->product_variant_id)
                    ->where('unit_id', $line->unit_id)
                    ->first();

                if (! $stock) {
                    $nama = trim(($line->sol_product_name ?? '-').' '.($line->sol_variant_name ?? ''));
                    if (! in_array($nama, $produk_gagal, true)) $produk_gagal[] = $nama;
                    continue;
                }

                $beforeStock = (int) $stock->ps_stock;

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $sto->sto_code,
                    'log_type' => 1,
                    'log_category' => 2,
                    'log_item_id' => $line->product_variant_id,
                    'log_notes' => "Stock Opname Produk",
                    'log_jumlah' => $beforeStock,
                    'unit_id' => $line->unit_id,
                    'warehouse_id' => (int) ($stock->warehouse_id ?: $warehouseId) ?: null,
                ]);

                $stock->ps_stock = (int) $line->sol_counted_qty;
                $stock->save();

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $sto->sto_code,
                    'log_type' => 1,
                    'log_category' => 1,
                    'log_item_id' => $line->product_variant_id,
                    'log_notes' => "Stock Opname Produk",
                    'log_jumlah' => (int) $stock->ps_stock,
                    'unit_id' => $line->unit_id,
                    'warehouse_id' => (int) ($stock->warehouse_id ?: $warehouseId) ?: null,
                ]);
            }

            if (count($produk_gagal) > 0) {
                DB::rollBack();

                return response()->json([
                    "status" => 0,
                    "header" => "Gagal ACC",
                    "message" => "Baris stok tidak ditemukan untuk: ".implode(', ', $produk_gagal),
                ]);
            }

            $sto->status = 2;
            $sto->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
            $sto->save();
            $lifecycle->stampDecision($sto, $sto->acc_by);

            DB::commit();

            return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function tolakStockOpname(Request $req)
    {
        $data = $req->all();
        $sto = StockOpname::find($data["sto_id"]);
        if (!$sto || $sto->is_draft) {
            return ["status" => -1, "message" => "Dokumen draft belum bisa diproses"];
        }

        $sto->status = 3; // Tolak
        $sto->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $sto->save();

        // Dokumen yang DITOLAK pun harus berhenti bergerak: tidak ada stok yang ditulis, tapi
        // angkanya dibekukan supaya cetakan ulang tahun depan menunjukkan hal yang sama persis.
        // Persis inilah yang tidak terjadi pada SP0071 (ditolak, tapi stod_system-nya masih ikut
        // ditulis ulang refreshLiveSystemQty() setiap kali PDF-nya di-download).
        if (! $sto->is_old_version) {
            DB::transaction(function () use ($sto) {
                $lifecycle = new OpnameLifecycle();
                $lifecycle->freezeSystemQty($sto);
                $lifecycle->stampDecision($sto, $sto->acc_by);
            });
        }
    }

    function generateStockOpname($id)
    {
        $sto = StockOpname::find($id);
        $param['stockOpname'] = $sto;
        if (!$sto) {
            abort(404);
        }
        if ($sto->is_draft && !$this->canManageStockOpnameDraft($sto)) {
            abort(404);
        }

        if ($sto && ! $sto->is_old_version) {
            // Dokumen versi baru: satu pembaca untuk semuanya. Selisih diturunkan saat dibaca,
            // stok sistem live selama menunggu dan beku setelah diputuskan -- dan MEMBACA TIDAK
            // MENULIS APA PUN (bandingkan cabang lama di bawah: refreshLiveSystemQty() menyimpan
            // balik ke DB tiap kali PDF di-download, itu yang membuat selisih SP0071 bergeser).
            $param['detail'] = (new OpnameLineReader())->read($sto);
            // Penanggung jawab dari snapshot: dokumen final tidak boleh gagal dicetak cuma karena
            // staff-nya sudah dihapus (cabang lama meneruskan Staff::find() yang bisa null ke
            // blade yang langsung mengakses ['staff_name']).
            $param['staff_name'] = ['staff_name' => $sto->sto_staff_name ?: '-'];
        } else {
            $param['staff_name'] = Staff::find($param['stockOpname']['staff_id']);
            $rawDetail = (new StockOpnameDetail())->getDetail(['sto_id' => $id]);
            $param["detail"] = collect($rawDetail)
                ->sortBy(fn($item) => strtolower((data_get($item, 'pr_name') ?? '') . '|' . (data_get($item, 'product_variant_name') ?? '')))
                ->values()
                ->all();

            if ((int) $param['stockOpname']['status'] === 1) {
                $this->refreshLiveSystemQty($param['detail'], 'stod_real', 'stod_system', 'stod_selisih', 'ps_stock', StockOpnameDetail::class, 'stod_id');
            }
            $this->humanizeUntouchedForPdf($param['detail'], 'stod_real', 'stod_system', 'stod_selisih');
        }

        if ($param['stockOpname']['status'] == 1) $param['status'] = "Menunggu";
        else if ($param['stockOpname']['status'] == 2) $param['status'] = "Disetujui";
        else if ($param['stockOpname']['status'] == 3) $param['status'] = "Ditolak";

        if (count($param["detail"]) <= 0) {
            return -1;
        }

        $u = session()->get('user');
        $param['printed_by'] = $u ? ($u->staff_name ?? '-') : '-';
        $param['printed_at'] = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('Backoffice.PDF.Opname', $param);
        return $pdf->download('Stock Opname_' . $param["stockOpname"]["sto_code"] . '.pdf');
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

    /**
     * Dokumen draft hanya boleh dilihat/diubah/dihapus oleh staff pembuatnya
     * (atau super admin) — dipakai insert/update/delete/submit/acc/tolak/PDF
     * stock opname bahan supaya aturan pemilik drafnya konsisten di semua
     * jalur, bukan hanya di listing.
     */
    private function canManageStockOpnameBahanDraft($stob)
    {
        if (!$stob) return false;

        $user = session()->get('user');
        if (RoleAccess::isSuperAdmin($user)) return true;

        return $user && (int) $stob->created_by === (int) ($user->staff_id ?? 0);
    }

    /**
     * Rancang ulang 2026-08-27 (kembaran insertStockOpname() Produk): dokumen baru ditulis ke
     * stock_opname_bahan_lines, BUKAN lagi stock_opname_detail_bahans. Dokumen lama tidak
     * dimigrasikan, tetap dibaca via BahanOpnameLineReader cabang legacy.
     */
    function insertStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $items = json_decode($req->item, true) ?: [];

        return DB::transaction(function () use ($data, $items) {
            $id = (new StockOpnameBahan())->insertStockOpnameBahan($data);
            StockOpnameBahanLine::writeFromPayload($id, $items);

            $lifecycle = new BahanOpnameLifecycle();
            // Kembaran keputusan PM di insertStockOpname() Produk -- gulung di SETIAP simpan,
            // draft ataupun langsung menunggu, sebelum publish() membekukan identitasnya.
            $lifecycle->rollUpUnits(StockOpnameBahan::find($id));
            $lifecycle->publish(StockOpnameBahan::find($id));

            return response()->json(['status' => 1, 'stob_id' => $id]);
        });
    }

    function updateStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $stob = StockOpnameBahan::find($data['stob_id'] ?? null);

        if (!$stob) {
            return ["status" => -1, "message" => "Dokumen ini tidak bisa diubah"];
        }
        // Kembaran updateStockOpname() Produk -- lihat komentarnya untuk alasan lengkap kenapa
        // menunggu (bukan draft) juga boleh diedit sekarang.
        if ($stob->is_draft) {
            if (!$this->canManageStockOpnameBahanDraft($stob)) {
                return ["status" => -1, "message" => "Tidak diizinkan mengubah draft milik staff lain"];
            }
        } elseif ((int) $stob->status !== 1) {
            return ["status" => -1, "message" => "Dokumen ini tidak bisa diubah"];
        }

        $data['is_draft'] = $stob->is_draft;
        $items = json_decode($req->item, true) ?: [];

        // NB (merged from main's efef95e, 2026-08-28): kembaran updateStockOpname() Produk -- lihat
        // komentarnya untuk alasan is_old_version branch di bawah tetap pakai wipe-and-reinsert
        // fase2, bukan update-or-insert-only main.
        DB::transaction(function () use ($data, $items) {
            $id = (new StockOpnameBahan())->updateStockOpnameBahan($data);
            $stob = StockOpnameBahan::find($id);
            if (! $stob) {
                return;
            }

            if ($stob->is_old_version) {
                foreach (StockOpnameDetailBahan::where('stob_id', $id)->where('status', 1)->get() as $old) {
                    $old->status = 0;
                    $old->save();
                }
                foreach ($items as $value) {
                    $value["stob_id"] = $id;
                    (new StockOpnameDetailBahan())->insertDetail($value);
                }

                return;
            }

            StockOpnameBahanLine::writeFromPayload($id, $items);

            $lifecycle = new BahanOpnameLifecycle();
            $lifecycle->rollUpUnits($stob->refresh());
            $lifecycle->publish($stob->refresh());
        });

        return 1;
    }

    /**
     * Ajukan draft: is_draft dimatikan, dokumen masuk alur approval biasa
     * (statusnya sudah 1=Menunggu sejak dibuat, jadi tidak perlu diubah).
     */
    function submitStockOpnameBahan(Request $req)
    {
        $stob = StockOpnameBahan::find($req->stob_id);

        if (!$stob || !$stob->is_draft) {
            return ["status" => -1, "message" => "Dokumen ini bukan draft"];
        }
        if (!$this->canManageStockOpnameBahanDraft($stob)) {
            return ["status" => -1, "message" => "Tidak diizinkan mengajukan draft milik staff lain"];
        }

        $stob->is_draft = false;
        $stob->save();

        // NB (merged from main's efef95e, 2026-08-28): main called
        // (new StockOpnameBahan())->submitStockOpnameBahan($data), a model method that doesn't
        // exist in this tree -- see submitStockOpname()'s (Produk) matching note.
        (new BahanOpnameLifecycle())->publish($stob->refresh());

        return 1;
    }

    function deleteStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $stob = StockOpnameBahan::find($data['stob_id'] ?? null);

        if ($stob && $stob->is_draft && !$this->canManageStockOpnameBahanDraft($stob)) {
            return ["status" => -1, "message" => "Tidak diizinkan menghapus draft milik staff lain"];
        }

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

            // GitHub #115: kembaran guard DetailStockOpname() -- draft milik staff lain tidak
            // boleh terbuka lewat URL langsung.
            if ($param['data']->is_draft && !$this->canManageStockOpnameBahanDraft($param['data'])) {
                abort(404);
            }

            // Dokumen versi baru: ->item di atas datang dari getStockOpnameBahan() yang membaca
            // stock_opname_detail_bahans -- tabel itu tidak lagi ditulis untuk dokumen ini. Timpa
            // dengan bentuk yang SAMA PERSIS lewat adaptor, supaya CreateStockOpnameSupplies.js
            // (renderMode2()) tidak berubah sama sekali. Di-cast ke object supaya kompatibel
            // dengan sortBy() di bawah, yang mengharap akses ->supplies_name seperti item lama.
            if (! $param['data']->is_old_version) {
                $param['data']->item = collect((new BahanOpnameLineReader())->legacyItems(StockOpnameBahan::find($id)))
                    ->map(fn ($a) => (object) $a);
            }

            if (!empty($param['data']->item)) {
                $sorted = collect($param['data']->item)
                    ->sortBy(fn($i) => strtolower($i->supplies_name ?? ''), SORT_STRING)
                    ->values();
                $param['data']->item = $sorted;
            }
            $param['mode'] = 2;
            $param['warehouse_name'] = trim((string) ($param['data']->warehouse_name ?? ''))
                ?: $this->activeWarehouseLabel();
        } else {
            $param["data"] = [];
            $param["mode"] = 1;
            $param['warehouse_name'] = $this->activeWarehouseLabel();
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

    function accStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $stod = json_decode($data['item'], true);
        $stob = StockOpnameBahan::find($data['stob_id']);
        $warehouseId = (int) (
            ($stob->warehouse_id ?? null)
            ?: (Session::get('active_warehouse_id') ?? 0)
        );
        if ($warehouseId <= 0) {
            $warehouseId = (int) \App\Models\SuppliesStock::resolveWarehouseId();
        }

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

        // Rancang ulang 2026-08-27 (kembaran accStockOpname() Produk): dokumen versi baru
        // mengambil angkanya DARI DATABASE, mengabaikan total $data['item'] -- lihat
        // accStockOpnameV2() untuk alasan lengkap (isi stok live tidak boleh ditentukan oleh
        // halaman di layar orang yang menyetujui).
        if (! $stob->is_old_version) {
            return $this->accStockOpnameBahanV2($stob, $warehouseId);
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
                $q = SuppliesStock::withoutGlobalScope('active_warehouse')
                    ->where('status', 1)
                    ->where('supplies_id', $value['supplies_id'])
                    ->where('unit_id', $u['unit_id']);
                if ($warehouseId > 0) {
                    $q->where('warehouse_id', $warehouseId);
                }
                $s = $q->first();

                // Mirrors accStockOpname()'s null-guard above.
                if (!$s) {
                    $sup = Supplies::find($value['supplies_id']);
                    $namaBahan = $sup->supplies_name ?? "id {$value['supplies_id']}";
                    if (!in_array($namaBahan, $bahan_gagal, true)) $bahan_gagal[] = $namaBahan;
                    continue;
                }

                $unitName = $unitNames[$u['unit_id']] ?? ('unit#'.$u['unit_id']);

                // GitHub #78 (merged from main's 54e564c): mirrors accStockOpname() above --
                // satuan yang tidak diisi staf (real_qty null) tidak boleh menimpa stok live, cukup
                // catat stok live untuk histori dokumen.
                if (!array_key_exists('real_qty', $u) || $u['real_qty'] === null) {
                    $liveSystemByUnit[$unitName] = (int) $s->ss_stock;
                    continue;
                }

                $oldQty = (float) $s->ss_stock;
                $newQty = (float) $u['real_qty'];
                $s->ss_stock = $newQty;
                $s->save();

                $wid = (int) ($s->warehouse_id ?: $warehouseId);
                $diff = round($newQty - $oldQty, 4);
                if (abs($diff) < 1e-9) {
                    (new LogStock())->insertLog([
                        'log_date' => now(),
                        'log_kode' => $stob->stob_code,
                        'log_type' => 2,
                        'log_category' => 1,
                        'log_item_id' => $value['supplies_id'],
                        'log_notes' => 'Stock Opname Bahan Mentah (tidak berubah)',
                        'log_jumlah' => 0,
                        'log_saldo' => $newQty,
                        'unit_id' => $u['unit_id'],
                        'warehouse_id' => $wid > 0 ? $wid : null,
                    ]);
                    continue;
                }

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $stob->stob_code,
                    'log_type' => 2,
                    'log_category' => $diff > 0 ? 1 : 2,
                    'log_item_id' => $value['supplies_id'],
                    'log_notes' => 'Stock Opname Bahan Mentah',
                    'log_jumlah' => abs($diff),
                    'log_saldo' => $newQty,
                    'unit_id' => $u['unit_id'],
                    'warehouse_id' => $wid > 0 ? $wid : null,
                ]);

                $liveSystemByUnit[$unitName] = (int) $oldQty;
                $realByUnit[$unitName] = (int) $u['real_qty'];
            }

            $detailRow = $detailRows->get($value['supplies_id']);
            if ($detailRow && !empty($liveSystemByUnit)) {
                $selisihByUnit = [];
                foreach ($liveSystemByUnit as $unitName => $systemQty) {
                    $selisihByUnit[$unitName] = array_key_exists($unitName, $realByUnit)
                        ? ($realByUnit[$unitName] - $systemQty)
                        : null;
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

    /**
     * ACC dokumen Stock Opname Bahan versi baru. Kembaran persis accStockOpnameV2() (Produk) --
     * lihat kelas itu untuk alasan urutan wajib (kunci -> bekukan stok sistem -> tulis ss_stock
     * -> cap keputusan) dan alasan \$warehouseId dipin ke gudang dokumen ini.
     */
    private function accStockOpnameBahanV2(StockOpnameBahan $stob, int $warehouseId)
    {
        $lines = StockOpnameBahanLine::getLines($stob->stob_id);
        $lifecycle = new BahanOpnameLifecycle();

        DB::beginTransaction();
        try {
            $scopeStock = function () use ($warehouseId) {
                return $warehouseId > 0
                    ? SuppliesStock::withoutGlobalScope('active_warehouse')->where('warehouse_id', $warehouseId)
                    : SuppliesStock::query();
            };

            $scopeStock()
                ->where('status', 1)
                ->whereIn('supplies_id', $lines->pluck('supplies_id')->filter()->unique()->all())
                ->lockForUpdate()
                ->get();

            $lifecycle->freezeSystemQty($stob);

            $bahan_gagal = [];
            foreach ($lines as $line) {
                if ($line->sobl_counted_qty === null) {
                    continue;
                }

                $stock = $scopeStock()
                    ->where('supplies_id', $line->supplies_id)
                    ->where('unit_id', $line->unit_id)
                    ->first();

                if (! $stock) {
                    $nama = $line->sobl_supplies_name ?? "id {$line->supplies_id}";
                    if (! in_array($nama, $bahan_gagal, true)) $bahan_gagal[] = $nama;
                    continue;
                }

                $beforeStock = (int) $stock->ss_stock;

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $stob->stob_code,
                    'log_type' => 2,
                    'log_category' => 2,
                    'log_item_id' => $line->supplies_id,
                    'log_notes' => "Stock Opname Bahan Mentah",
                    'log_jumlah' => $beforeStock,
                    'unit_id' => $line->unit_id,
                    'warehouse_id' => (int) ($stock->warehouse_id ?: $warehouseId) ?: null,
                ]);

                $stock->ss_stock = (int) $line->sobl_counted_qty;
                $stock->save();

                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode' => $stob->stob_code,
                    'log_type' => 2,
                    'log_category' => 1,
                    'log_item_id' => $line->supplies_id,
                    'log_notes' => "Stock Opname Bahan Mentah",
                    'log_jumlah' => (int) $stock->ss_stock,
                    'unit_id' => $line->unit_id,
                    'warehouse_id' => (int) ($stock->warehouse_id ?: $warehouseId) ?: null,
                ]);
            }

            if (count($bahan_gagal) > 0) {
                DB::rollBack();

                return response()->json([
                    "status" => 0,
                    "header" => "Gagal ACC",
                    "message" => "Baris stok tidak ditemukan untuk: ".implode(', ', $bahan_gagal),
                ]);
            }

            $stob->status = 2;
            $stob->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
            $stob->save();
            $lifecycle->stampDecision($stob, $stob->acc_by);

            DB::commit();

            return 1;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    function tolakStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        $stob = StockOpnameBahan::find($data["stob_id"]);
        if (!$stob || $stob->is_draft) {
            return ["status" => -1, "message" => "Dokumen draft belum bisa diproses"];
        }

        $stob->status = 3; // Tolak
        $stob->acc_by = session()->get('user') ? session()->get('user')->staff_id : null;
        $stob->save();

        // Kembaran tolakStockOpname() Produk -- dokumen yang ditolak pun harus berhenti bergerak.
        if (! $stob->is_old_version) {
            DB::transaction(function () use ($stob) {
                $lifecycle = new BahanOpnameLifecycle();
                $lifecycle->freezeSystemQty($stob);
                $lifecycle->stampDecision($stob, $stob->acc_by);
            });
        }
    }

    function generateStockOpnameBahan($id)
    {
        $stob = StockOpnameBahan::find($id);
        $param['stockOpname'] = $stob;
        if (!$stob) {
            abort(404);
        }
        if ($stob->is_draft && !$this->canManageStockOpnameBahanDraft($stob)) {
            abort(404);
        }

        if ($stob && ! $stob->is_old_version) {
            // Kembaran cabang versi-baru generateStockOpname() Produk -- lihat itu untuk alasan
            // lengkap (satu pembaca, selisih diturunkan, membaca tidak menulis apa pun).
            $param['detail'] = (new BahanOpnameLineReader())->read($stob);
            $param['staff_name'] = ['staff_name' => $stob->stob_staff_name ?: '-'];
        } else {
            $param['staff_name'] = Staff::find($param['stockOpname']['staff_id']);
            $rawDetailBahan = (new StockOpnameDetailBahan())->getDetail(['stob_id' => $id]);
            $param["detail"] = collect($rawDetailBahan)
                ->sortBy(fn($item) => strtolower(data_get($item, 'supplies_name') ?? ''))
                ->values()
                ->all();

            if ((int) $param['stockOpname']['status'] === 1) {
                $this->refreshLiveSystemQty($param['detail'], 'stobd_real', 'stobd_system', 'stobd_selisih', 'ss_stock', StockOpnameDetailBahan::class, 'stobd_id');
            }
            $this->humanizeUntouchedForPdf($param['detail'], 'stobd_real', 'stobd_system', 'stobd_selisih');
        }

        if ($param['stockOpname']['status'] == 1) $param['status'] = "Menunggu";
        else if ($param['stockOpname']['status'] == 2) $param['status'] = "Disetujui";
        else if ($param['stockOpname']['status'] == 3) $param['status'] = "Ditolak";

        if (count($param["detail"]) <= 0) {
            return -1;
        }

        $u = session()->get('user');
        $param['printed_by'] = $u ? ($u->staff_name ?? '-') : '-';
        $param['printed_at'] = now()->format('d/m/Y H:i');

        $pdf = Pdf::loadView('Backoffice.PDF.OpnameBahan', $param);
        return $pdf->download('Stock Opname_' . $param["stockOpname"]["stob_code"] . '.pdf');
    }

    // Stock Alert
    public function StockAlert()
    {
        return view('Backoffice.Inventory.Stock_Alert');
    }

    function getStockAlert(Request $req)
    {
        $warehouseId = \App\Models\ProductStock::resolveWarehouseId($req->warehouse_id ?? null);
        if (! $warehouseId) {
            return response()->json([]);
        }
        $data = (new StockAlert())->getStockAlert([
            "mode" => $req->mode,
            "warehouse_id" => $warehouseId,
        ]);
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

    function updateMinOrder(Request $req)
    {
        $data = $req->all();
        return (new StockAlert())->updateMinOrder($data);
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
        $warehouseId = \App\Models\SuppliesStock::resolveWarehouseId($req->warehouse_id ?? null);
        if (! $warehouseId) {
            return response()->json([]);
        }
        $data = (new StockAlertSupplies())->getStockAlertSupplies([
            "mode" => $req->mode,
            "warehouse_id" => $warehouseId,
        ]);
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

    function updateMinOrderSupplies(Request $req)
    {
        $data = $req->all();
        return (new StockAlertSupplies())->updateMinOrderSupplies($data);
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
        if (isset($req->photo)) {
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

        if ($data['tipe_return'] == 1) {
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
                    "status" => -1,
                    "message" => "Bahan tidak ditemukan dalam invoice : " . implode(", ", $bermasalah)
                ];
            }
            if (count($kurang) > 0) {
                return [
                    "status" => -1,
                    "message" => "Stok dalam invoice tidak mencukupi : " . implode(", ", $kurang)
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
        // Ditambahkan (2026-08-24): ProductIssues::insertProductIssues() dan
        // ProductIssuesDetail::insertProductIssuesDetail() sama-sama memutasi ps_stock/ss_stock,
        // dan dulu tidak ada transaksi -- gagal di tengah loop item meninggalkan header
        // ProductIssues yang sudah jadi dengan sebagian detail + sebagian stok saja yang termutasi.
        // Semua pre-check (stockCheck dkk) di atas murni baca, jadi tetap di luar transaksi.
        DB::beginTransaction();
        try {
        $t = (new ProductIssues())->insertProductIssues($data);
        foreach (json_decode($data['items'], true) as $key => $value) {
            $value['pi_id'] = $t->pi_id;
            // if (isset($t->ref_num)) $value['ref_num'] = $t->ref_num;
            (new ProductIssuesDetail())->insertProductIssuesDetail($value);
        }
        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
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
        // Ditambahkan (2026-08-24): dulu tidak ada transaksi di sini sama sekali. Perhatikan
        // urutannya -- updateProductIssues() di bawah SUDAH memutasi stok, baru setelah itu blok
        // "Cek stock" menjalankan stockCheck() yang bisa `return -1`. Artinya jalur gagal yang
        // normal pun meninggalkan mutasi stok yang terlanjur tersimpan. Sekarang seluruh method
        // (write + cek + loop detail di bawah) satu transaksi, jadi setiap `return -1` ikut
        // ter-rollback bersih.
        DB::beginTransaction();
        try {
        $pi = (new ProductIssues())->updateProductIssues($data);

        // Cek stock
        $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
        if (count($getPi) > 0) {
            foreach ($getPi as $key => $val) {
                foreach (json_decode($data['items'], true) as $key => $value) {
                    if ($data['tipe_return'] == 1) {
                        if ($value['supplies_variant_id'] == $val['item_id'] && $value['unit_id'] == $val['unit_id']) {
                            $val['tipe_return'] = $data['tipe_return'];
                            $val['pid_qty'] = $value['pid_qty'];
                            $c = (new ProductIssuesDetail())->stockCheck($val);
                            if ($c == -1) { DB::rollBack(); return -1; }
                        }
                    }
                    if ($data['tipe_return'] == 2) {
                        if ($value['product_variant_id'] == $val['item_id'] && $value['unit_id'] == $val['unit_id']) {
                            $val['tipe_return'] = $data['tipe_return'];
                            $val['pid_qty'] = $value['pid_qty'];
                            $c = (new ProductIssuesDetail())->stockCheck($val);
                            if ($c == -1) { DB::rollBack(); return -1; }
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
                $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name . ' ' . LogStock::actorSuffix();
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
                if ($pi->tipe_return == 2) {
                    $getPi = ProductIssuesDetail::where('pi_id', $pi->pi_id)->where('status', '>=', 1)->get();
                    if (count($getPi) > 0) {
                        foreach ($getPi as $key => $val) {
                            $ps = ProductStock::where('product_variant_id', $value['product_variant_id'])->where('unit_id', $val->unit_id)->first();

                            $ps->ps_stock -= $val->pid_qty;
                            $ps->save();

                            // Catat Log
                            $logNotes = "";
                            $logNotes = 'Perubahan data produk bermasalah retur Armada ' . LogStock::actorSuffix();
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
                if ($pi->tipe_return == 1) {
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);

                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name . ' ' . LogStock::actorSuffix();
                    $logCategory = 2;
                    $logType = 2;

                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2) {
                    $logNotes = 'Perubahan data produk bermasalah retur Armada ' . LogStock::actorSuffix();
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
            } else {
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1) {
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);

                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name . ' ' . LogStock::actorSuffix();
                    $logCategory = 1;
                    $logType = 2;
                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2) {
                    $logNotes = 'Perubahan data produk bermasalah retur Armada ' . LogStock::actorSuffix();
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
                if ($pi->tipe_return == 1) {
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);

                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name . ' ' . LogStock::actorSuffix();
                    $logCategory = 2;
                    $logType = 2;
                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2) {
                    $logNotes = 'Perubahan data produk bermasalah retur Armada ' . LogStock::actorSuffix();
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
        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
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
        $w = ProductIssuesDetail::where('pi_id', '=', $data["pi_id"])->where('status', '>=', 1)->get();

        // Ditambahkan (2026-08-24): dulu tidak ada transaksi sama sekali. Transaksi sengaja dibuka
        // di sini (sebelum percabangan tipe_return) supaya KEDUA cabang ikut terlindungi -- kalau
        // dibuka di dalam blok tipe_return==2 saja, deleteProductIssues() + loop penghapusan detail
        // di bawahnya (yang jalan untuk semua tipe) tetap tanpa proteksi. Fase 1-3 di bawah murni
        // simulasi in-memory, jadi ikut masuk transaksi pun tidak menambah biaya tulis.
        // Jalur gagal yang paling berbahaya: `$del == -1` ("Invoice sudah terbayar") keluar SETELAH
        // Fase 4 menulis semua perubahan stok ke DB.
        DB::beginTransaction();
        try {

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
                            'note'       => 'Konversi unit dari retur armada (Bongkar) ' . LogStock::actorSuffix(),
                            'sort_order' => $baseOrder,
                        ];
                        $logSummary[$stokSekarang->unit_id . '_cat1'] = [
                            'unit_id'    => $stokSekarang->unit_id,
                            'jumlah'     => ($logSummary[$stokSekarang->unit_id . '_cat1']['jumlah'] ?? 0) + $hasilBongkar,
                            'cat'        => 1,
                            'note'       => 'Konversi unit dari retur armada (Hasil) ' . LogStock::actorSuffix(),
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
                DB::rollBack();
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
            // Rollback dulu: Fase 4 di atas sudah menulis seluruh perubahan stok ke DB, dan tanpa
            // ini perubahan itu tetap permanen padahal penghapusannya sendiri dibatalkan.
            DB::rollBack();
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

                $logNotes    = 'Penghapusan data produk bermasalah retur supplier ' . $spr->supplier_name . ' ' . LogStock::actorSuffix();
                $logCategory = 1;
                $logType     = 2;
                $itemId      = $sup->supplies_id;
            } elseif ($pi->tipe_return == 2) {
                $logNotes    = 'Penghapusan data produk bermasalah retur Armada ' . LogStock::actorSuffix();
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
        DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
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

    function declineProductIssues(Request $req)
    {
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
        $warehouseId = $req->warehouse_id ?: Session::get('active_warehouse_id');
        $warehouseId = $warehouseId ? (int) $warehouseId : null;

        $warehouse = null;
        $isMain = true;
        $viewMode = 'main';
        if ($warehouseId) {
            $warehouse = Warehouse::query()
                ->with(['type' => fn($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
                ->find($warehouseId);
            $isMain = $warehouse
                && $warehouse->type
                && (int) $warehouse->type->is_main_warehouse === 1;
            $viewMode = $isMain ? 'main' : 'retail';
        }

        // Server-side DataTables
        if ($req->has('draw')) {
            $draw = (int) $req->input('draw', 1);
            $start = max(0, (int) $req->input('start', 0));
            $length = (int) $req->input('length', 25);
            if ($length < 1) {
                $length = 25;
            }
            if ($length > 100) {
                $length = 100;
            }

            // Tanpa gudang aktif: jangan query berat
            if (! $warehouseId) {
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'view_mode' => $viewMode,
                    'is_main_warehouse' => $isMain ? 1 : 0,
                ]);
            }

            $search = trim((string) data_get($req->input('search'), 'value', ''));
            $orderColIdx = (int) data_get($req->input('order'), '0.column', 0);
            $orderDir = strtolower((string) data_get($req->input('order'), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';

            if ($viewMode === 'retail') {
                $columns = [
                    0 => 'product_variants.product_variant_sku',
                    1 => 'pr.product_name',
                    2 => 'product_variants.product_variant_name',
                    3 => 'cat.category_name',
                    4 => 'pr.product_name',
                    5 => 'pr.product_name',
                ];
            } else {
                $columns = [
                    0 => 'product_variants.product_variant_sku',
                    1 => 'pr.product_name',
                    2 => 'product_variants.product_variant_name',
                    3 => 'cat.category_name',
                    4 => 'pr.product_name',
                    5 => 'pr.product_name',
                ];
            }
            $orderCol = $columns[$orderColIdx] ?? 'pr.product_name';

            $base = ProductVariant::query()
                ->from('product_variants')
                ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
                ->leftJoin('categories as cat', 'cat.category_id', '=', 'pr.category_id')
                ->where('product_variants.status', 1)
                ->where('pr.status', 1);

            // Total tanpa search — query lebih ringan (tanpa category join untuk count)
            $recordsTotal = ProductVariant::query()
                ->from('product_variants')
                ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
                ->where('product_variants.status', 1)
                ->where('pr.status', 1)
                ->count('product_variants.product_variant_id');

            if ($search !== '') {
                $like = '%' . $search . '%';
                $base->where(function ($q) use ($like) {
                    $q->where('product_variants.product_variant_sku', 'like', $like)
                        ->orWhere('pr.product_name', 'like', $like)
                        ->orWhere('product_variants.product_variant_name', 'like', $like)
                        ->orWhere('cat.category_name', 'like', $like);
                });
                $recordsFiltered = (clone $base)->count('product_variants.product_variant_id');
            } else {
                $recordsFiltered = $recordsTotal;
            }

            $warehouseName = $warehouse->warehouse_name ?? '-';

            $hasAlertCol = Schema::hasColumn('product_stocks', 'ps_alert_stock');
            $hasMinOrderCol = Schema::hasColumn('product_stocks', 'ps_min_order');

            $rows = $base
                ->select([
                    'product_variants.product_variant_id',
                    'product_variants.product_variant_sku',
                    'product_variants.product_variant_name',
                    'product_variants.product_id',
                    'product_variants.unit_id as variant_unit_id',
                    'product_variants.product_variant_alert',
                    Schema::hasColumn('product_variants', 'retail_unit')
                        ? 'product_variants.retail_unit'
                        : DB::raw('NULL as retail_unit'),
                    'pr.product_name as pr_name',
                    'cat.category_name as product_category',
                ])
                ->orderBy($orderCol, $orderDir)
                ->orderBy('product_variants.product_variant_id', 'asc')
                ->skip($start)
                ->take($length)
                ->get();

            $variantIds = $rows->pluck('product_variant_id')->all();
            $stocksByVariant = [];
            $relationsByVariant = collect();

            if ($variantIds !== []) {
                // Batch stock (1 query)
                $stockQuery = ProductStock::withoutGlobalScope('active_warehouse')
                    ->where('product_stocks.status', 1)
                    ->where('product_stocks.warehouse_id', $warehouseId)
                    ->whereIn('product_stocks.product_variant_id', $variantIds);

                // Gudang utama: multi satuan (semua unit stok).
                // Gudang eceran: hanya satuan eceran.
                if (! $isMain) {
                    if (Schema::hasColumn('product_variants', 'retail_unit')) {
                        $stockQuery
                            ->join(
                                'product_variants as stock_variant',
                                'stock_variant.product_variant_id',
                                '=',
                                'product_stocks.product_variant_id'
                            )
                            ->whereNotNull('stock_variant.retail_unit')
                            ->where('stock_variant.retail_unit', '>', 0)
                            ->whereColumn('product_stocks.unit_id', 'stock_variant.retail_unit');
                    } else {
                        $stockQuery->whereRaw('1 = 0');
                    }
                }

                $stockSelect = [
                    'product_stocks.ps_id',
                    'product_stocks.product_variant_id',
                    'product_stocks.unit_id',
                    'product_stocks.ps_stock',
                    'product_stocks.ps_safety_stock',
                ];
                if ($hasAlertCol) {
                    $stockSelect[] = 'product_stocks.ps_alert_stock';
                }
                if ($hasMinOrderCol) {
                    $stockSelect[] = 'product_stocks.ps_min_order';
                }
                $stockRows = $stockQuery->get($stockSelect);

                $unitIds = $stockRows->pluck('unit_id')->unique()->filter()->values()->all();
                $units = $unitIds !== []
                    ? Unit::whereIn('unit_id', $unitIds)->get(['unit_id', 'unit_name', 'unit_short_name'])->keyBy('unit_id')
                    : collect();

                foreach ($stockRows as $stock) {
                    $unit = $units->get($stock->unit_id);
                    $stock->unit_name = $unit->unit_name ?? '-';
                    $stock->unit_short_name = $unit->unit_short_name ?? '-';
                    $stocksByVariant[$stock->product_variant_id][] = $stock;
                }

                // Batch relations (1 query) — UnitStockSorter cukup butuh unit id
                $relationsByVariant = ProductRelation::query()
                    ->where('status', 1)
                    ->whereIn('product_variant_id', $variantIds)
                    ->get(['product_variant_id', 'pr_unit_id_1', 'pr_unit_id_2'])
                    ->groupBy('product_variant_id');
            }

            $data = [];
            $canViewSafety = \App\Support\RoleAccess::can(Session::get('user'), 'Safety Stock', 'view')
                || \App\Support\RoleAccess::can(Session::get('user'), 'Safety Stock', 'edit');
            foreach ($rows as $row) {
                $stocks = $stocksByVariant[$row->product_variant_id] ?? [];
                $relations = $relationsByVariant->get($row->product_variant_id, collect());

                if ($stocks !== [] && $relations->isNotEmpty()) {
                    $stocks = UnitStockSorter::sort(collect($stocks), $relations)->all();
                }

                $unitsPayload = [];
                $parts = [];
                $safetyParts = [];
                foreach ($stocks as $element) {
                    $qty = (float) $element->ps_stock;
                    $safetyQty = (float) ($element->ps_safety_stock ?? 0);
                    $unitName = $element->unit_name ?? '-';
                    $unitItem = [
                        'unit_id' => (int) $element->unit_id,
                        'unit_name' => $unitName,
                        'unit_short_name' => $element->unit_short_name ?? $unitName,
                        'ps_stock' => $qty,
                        'ps_stock_text' => number_format($qty, 0, ',', '.'),
                    ];
                    if ($canViewSafety) {
                        $unitItem['ps_safety_stock'] = $safetyQty;
                        $unitItem['ps_safety_stock_text'] = number_format($safetyQty, 0, ',', '.');
                        if ($safetyQty > 0) {
                            $safetyParts[] = number_format($safetyQty, 0, ',', '.') . ' ' . $unitName;
                        }
                    }
                    $unitsPayload[] = $unitItem;
                    $parts[] = number_format($qty, 0, ',', '.') . ' ' . $unitName;
                }

                $defaultUnitId = $isMain
                    ? (int) ($row->variant_unit_id ?? 0)
                    : (int) ($row->retail_unit ?? 0);

                $rowData = [
                    'product_variant_id' => $row->product_variant_id,
                    'product_id' => $row->product_id,
                    'product_variant_sku' => $row->product_variant_sku,
                    'pr_name' => $row->pr_name,
                    'product_variant_name' => $row->product_variant_name,
                    'product_category' => $row->product_category ?: '-',
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $warehouseName,
                    'is_main_warehouse' => $isMain ? 1 : 0,
                    'default_unit_id' => $defaultUnitId > 0 ? $defaultUnitId : null,
                    'image_url' => null,
                    'units' => $unitsPayload,
                    'product_variant_stock_text' => $parts !== [] ? implode(', ', $parts) : '-',
                ];
                if ($canViewSafety) {
                    $rowData['product_variant_safety_text'] = $safetyParts !== []
                        ? implode(', ', $safetyParts)
                        : '-';
                }

                $rowData = array_merge(
                    $rowData,
                    $this->buildProductMinOrderMeta($row, $stocks, $isMain, $units, $hasAlertCol, $hasMinOrderCol)
                );

                $data[] = $rowData;
            }

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
                'view_mode' => $viewMode,
                'is_main_warehouse' => $isMain ? 1 : 0,
                'can_view_safety_stock' => $canViewSafety ? 1 : 0,
            ]);
        }

        // Legacy (non-DataTables): tetap support, filter gudang aktif
        $data = (new ProductVariant())->getProductVariant();
        $defaultUnits = $isMain
            ? Product::query()
            ->whereIn('product_id', $data->pluck('product_id')->filter()->unique()->all())
            ->pluck('unit_id', 'product_id')
            : collect();
        foreach ($data as $key => $value) {
            $value->stock = (new ProductStock())->getProductStock([
                "product_variant_id" => $value->product_variant_id,
                "warehouse_id" => $warehouseId,
                "unit_id" => $isMain ? ($defaultUnits[$value->product_id] ?? null) : null,
                "relations" => $value->relasi,
            ]);
            $value->warehouse_id = $warehouseId;
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
        $warehouseId = $req->warehouse_id ?: Session::get('active_warehouse_id');
        $warehouseId = $warehouseId ? (int) $warehouseId : null;

        $warehouse = null;
        $isMain = true;
        if ($warehouseId) {
            $warehouse = Warehouse::query()
                ->with(['type' => fn ($q) => $q->select('id', 'warehouse_type_name', 'is_main_warehouse')])
                ->find($warehouseId);
            $isMain = $warehouse
                && $warehouse->type
                && (int) $warehouse->type->is_main_warehouse === 1;
        }

        // Server-side DataTables
        if ($req->has('draw')) {
            $draw = (int) $req->input('draw', 1);
            $start = max(0, (int) $req->input('start', 0));
            $length = (int) $req->input('length', 25);
            if ($length < 1) {
                $length = 25;
            }
            if ($length > 100) {
                $length = 100;
            }

            if (! $warehouseId) {
                return response()->json([
                    'draw' => $draw,
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                ]);
            }

            $search = trim((string) data_get($req->input('search'), 'value', ''));
            $orderColIdx = (int) data_get($req->input('order'), '0.column', 0);
            $orderDir = strtolower((string) data_get($req->input('order'), '0.dir', 'asc')) === 'desc' ? 'desc' : 'asc';
            $columns = [
                0 => 'supplies.supplies_name',
                1 => 'supplies.supplies_name',
                2 => 'supplies.supplies_name',
            ];
            $orderCol = $columns[$orderColIdx] ?? 'supplies.supplies_name';
            $hasSuppliesMinCol = Schema::hasColumn('supplies', 'supplies_min_stock');

            $base = Supplies::query()
                ->from('supplies')
                ->where('supplies.status', 1);

            $recordsTotal = Supplies::query()
                ->where('status', 1)
                ->count('supplies_id');

            if ($search !== '') {
                $like = '%' . $search . '%';
                $base->where('supplies.supplies_name', 'like', $like);
                $recordsFiltered = (clone $base)->count('supplies.supplies_id');
            } else {
                $recordsFiltered = $recordsTotal;
            }

            $warehouseName = $warehouse->warehouse_name ?? '-';

            $rows = $base
                ->select([
                    'supplies.supplies_id',
                    'supplies.supplies_name',
                    'supplies.supplies_default_unit',
                    'supplies.supplies_alert',
                    $hasSuppliesMinCol ? 'supplies.supplies_min_stock' : DB::raw('NULL as supplies_min_stock'),
                ])
                ->orderBy($orderCol, $orderDir)
                ->orderBy('supplies.supplies_id', 'asc')
                ->skip($start)
                ->take($length)
                ->get();

            $suppliesIds = $rows->pluck('supplies_id')->all();
            $stocksBySupply = [];
            $relationsBySupply = collect();
            $units = collect();

            if ($suppliesIds !== []) {
                $stockRows = SuppliesStock::withoutGlobalScope('active_warehouse')
                    ->where('status', 1)
                    ->where('warehouse_id', $warehouseId)
                    ->whereIn('supplies_id', $suppliesIds)
                    ->get(['ss_id', 'supplies_id', 'unit_id', 'ss_stock', 'warehouse_id']);

                $unitIds = $stockRows->pluck('unit_id')->unique()->filter()->values()->all();
                $defaultUnitIds = $rows->pluck('supplies_default_unit')->filter()->unique()->values()->all();
                $allUnitIds = array_values(array_unique(array_merge($unitIds, $defaultUnitIds)));
                $units = $allUnitIds !== []
                    ? Unit::whereIn('unit_id', $allUnitIds)->get(['unit_id', 'unit_name', 'unit_short_name'])->keyBy('unit_id')
                    : collect();

                foreach ($stockRows as $stock) {
                    $unit = $units->get($stock->unit_id);
                    $stock->unit_name = $unit->unit_name ?? '-';
                    $stock->unit_short_name = $unit->unit_short_name ?? '-';
                    $stocksBySupply[$stock->supplies_id][] = $stock;
                }

                $relationsBySupply = \App\Models\SuppliesRelation::query()
                    ->where('status', 1)
                    ->whereIn('supplies_id', $suppliesIds)
                    ->get(['supplies_id', 'su_id_1', 'su_id_2', 'sr_value_2'])
                    ->groupBy('supplies_id');
            }

            $data = [];
            $isEceranWarehouse = ! $isMain;
            foreach ($rows as $row) {
                $stocks = $stocksBySupply[$row->supplies_id] ?? [];
                $relations = $relationsBySupply->get($row->supplies_id, collect());

                if ($stocks !== [] && $relations->isNotEmpty()) {
                    $stocks = UnitStockSorter::sort(collect($stocks), $relations, 'su_id_1', 'su_id_2')->all();
                }

                $parts = [];
                foreach ($stocks as $element) {
                    $qty = (float) $element->ss_stock;
                    $unitLabel = $element->unit_short_name ?? ($element->unit_name ?? '-');
                    $parts[] = number_format($qty, 0, ',', '.') . ' ' . $unitLabel;
                }

                $data[] = array_merge([
                    'supplies_id' => $row->supplies_id,
                    'supplies_name' => $row->supplies_name,
                    'warehouse_id' => $warehouseId,
                    'warehouse_name' => $warehouseName,
                    'supplies_variant_stock_text' => $parts !== [] ? implode(', ', $parts) : '-',
                ], $this->buildSuppliesMinOrderMeta($row, $stocks, $relations, $isEceranWarehouse, $units));
            }

            return response()->json([
                'draw' => $draw,
                'recordsTotal' => $recordsTotal,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data,
            ]);
        }

        // Legacy (non-DataTables): filter gudang aktif via global scope / param
        $data = (new Supplies())->getSupplies();
        foreach ($data as $value) {
            $value->warehouse_id = $warehouseId;
            if (! isset($value->stock) || $value->stock === null) {
                $value->stock = (new SuppliesStock())->getProductStock([
                    'supplies_id' => $value->supplies_id,
                    'warehouse_id' => $warehouseId,
                    'relations' => $value->supplies_relasi ?? [],
                ]);
            }
        }

        return response()->json($data);
    }

    /**
     * Set safety stock 1 satuan saja (sama pola master produk).
     * Body: product_variant_id, warehouse_id?, unit_id, ps_safety_stock
     */
    public function updateProductSafetyStock(Request $req)
    {
        if (! RoleAccess::can(Session::get('user'), 'Safety Stock', 'edit')) {
            return response()->json(['status' => 0, 'message' => 'Tidak punya akses edit Safety Stock'], 403);
        }

        $variantId = (int) ($req->product_variant_id ?? 0);
        $warehouseId = (int) ($req->warehouse_id ?: Session::get('active_warehouse_id') ?? 0);
        $unitId = (int) ($req->unit_id ?? 0);
        $safety = max(0, (int) ($req->ps_safety_stock ?? 0));

        // Backward-compat: items[{unit_id, ps_safety_stock}] → ambil item pertama
        if ($unitId <= 0) {
            $items = $req->items;
            if (is_string($items)) {
                $items = json_decode($items, true);
            }
            if (is_array($items) && $items !== []) {
                $unitId = (int) ($items[0]['unit_id'] ?? 0);
                $safety = max(0, (int) ($items[0]['ps_safety_stock'] ?? 0));
            }
        }

        if ($variantId <= 0 || $warehouseId <= 0 || $unitId <= 0) {
            return response()->json(['status' => 0, 'message' => 'Data tidak valid'], 422);
        }

        DB::beginTransaction();
        try {
            ProductStock::withoutGlobalScope('active_warehouse')
                ->where('status', 1)
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->update(['ps_safety_stock' => 0]);

            $row = ProductStock::withoutGlobalScope('active_warehouse')
                ->where('status', 1)
                ->where('warehouse_id', $warehouseId)
                ->where('product_variant_id', $variantId)
                ->where('unit_id', $unitId)
                ->first();
            if (! $row) {
                throw new \RuntimeException('Satuan stok tidak ditemukan');
            }
            $row->ps_safety_stock = $safety;
            $row->save();

            DB::commit();

            return response()->json(['status' => 1, 'message' => 'Safety stock berhasil disimpan']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Pindah qty Safety → Stok Produk (kurangi ps_safety_stock, tambah ps_stock).
     * Body: product_variant_id, warehouse_id?, items: [{unit_id, qty}]
     */
    public function transferSafetyToStock(Request $req)
    {
        if (! RoleAccess::can(Session::get('user'), 'Safety Stock', 'edit')) {
            return response()->json(['status' => 0, 'message' => 'Tidak punya akses edit Safety Stock'], 403);
        }

        $variantId = (int) ($req->product_variant_id ?? 0);
        $warehouseId = (int) ($req->warehouse_id ?: Session::get('active_warehouse_id') ?? 0);
        $items = $req->items;
        if (is_string($items)) {
            $items = json_decode($items, true);
        }
        if ($variantId <= 0 || $warehouseId <= 0 || ! is_array($items) || $items === []) {
            return response()->json(['status' => 0, 'message' => 'Data tidak valid'], 422);
        }

        DB::beginTransaction();
        try {
            $logger = new LogStock();
            $moved = 0;
            foreach ($items as $item) {
                $unitId = (int) ($item['unit_id'] ?? 0);
                $qty = (float) ($item['qty'] ?? 0);
                if ($unitId <= 0 || $qty <= 0) {
                    continue;
                }
                $row = ProductStock::withoutGlobalScope('active_warehouse')
                    ->where('status', 1)
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_variant_id', $variantId)
                    ->where('unit_id', $unitId)
                    ->lockForUpdate()
                    ->first();
                if (! $row) {
                    throw new \RuntimeException('Stok satuan tidak ditemukan');
                }
                $safety = (float) ($row->ps_safety_stock ?? 0);
                if ($qty > $safety) {
                    throw new \RuntimeException('Qty transfer melebihi safety stock');
                }
                $row->ps_safety_stock = round($safety - $qty, 4);
                $row->ps_stock = round((float) $row->ps_stock + $qty, 4);
                $row->save();

                $logger->insertLog([
                    'log_date' => now(),
                    'log_kode' => 'SS' . $row->ps_id,
                    'log_type' => 1,
                    'log_category' => 1,
                    'log_item_id' => $variantId,
                    'log_notes' => 'Transfer Safety Stock ke Stok Produk',
                    'log_jumlah' => $qty,
                    'unit_id' => $unitId,
                    'warehouse_id' => $warehouseId,
                ]);
                $moved++;
            }
            if ($moved === 0) {
                throw new \RuntimeException('Tidak ada qty yang ditransfer');
            }
            DB::commit();

            return response()->json(['status' => 1, 'message' => 'Transfer berhasil']);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['status' => 0, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Meta pemesanan min produk untuk Daftar Stok (sama rumus Peringatan Stok).
     *
     * @param  array<int, object>  $stocks
     */
    private function buildProductMinOrderMeta(
        object $row,
        array $stocks,
        bool $isMain,
        $units,
        bool $hasAlertCol,
        bool $hasMinOrderCol
    ): array {
        $vid = (int) $row->product_variant_id;
        $variantUnitId = (int) ($row->variant_unit_id ?? 0);
        $retailUnitId = (int) ($row->retail_unit ?? 0);
        $displayUnitId = (! $isMain && $retailUnitId > 0)
            ? $retailUnitId
            : ($variantUnitId > 0 ? $variantUnitId : 0);

        $alertQty = (float) ($row->product_variant_alert ?? 0);
        $alertUnitId = $variantUnitId > 0 ? $variantUnitId : 0;
        if ($hasAlertCol) {
            foreach ($stocks as $stockRow) {
                if ((float) ($stockRow->ps_alert_stock ?? 0) > 0) {
                    $alertQty = (float) $stockRow->ps_alert_stock;
                    $alertUnitId = (int) $stockRow->unit_id;
                    break;
                }
            }
        }
        if ($alertUnitId > 0 && $displayUnitId > 0 && $alertUnitId !== $displayUnitId) {
            $alertQty = ProductUnitStock::convertQty($alertQty, $alertUnitId, $displayUnitId, $vid);
        }

        $currentStock = 0.0;
        foreach ($stocks as $stockRow) {
            if ((int) $stockRow->unit_id === $displayUnitId) {
                $currentStock += (float) ($stockRow->ps_stock ?? 0);
            }
        }

        $storedMinOrder = null;
        if ($hasMinOrderCol) {
            foreach ($stocks as $stockRow) {
                if ($stockRow->ps_min_order !== null) {
                    $minStored = (float) $stockRow->ps_min_order;
                    $minUnitId = (int) $stockRow->unit_id;
                    if ($minUnitId > 0 && $displayUnitId > 0 && $minUnitId !== $displayUnitId) {
                        $minStored = ProductUnitStock::convertQty($minStored, $minUnitId, $displayUnitId, $vid);
                    }
                    $storedMinOrder = (int) round($minStored);
                    break;
                }
            }
        }

        $orderThreshold = $storedMinOrder !== null ? $storedMinOrder : $alertQty;
        $calculatedMinOrder = (int) max(0, round($orderThreshold - $currentStock));
        $displayUnitName = '-';
        if ($displayUnitId > 0) {
            foreach ($stocks as $stockRow) {
                if ((int) $stockRow->unit_id === $displayUnitId) {
                    $displayUnitName = $stockRow->unit_name ?? '-';
                    break;
                }
            }
            if ($displayUnitName === '-') {
                $unit = $units->get($displayUnitId);
                $displayUnitName = $unit->unit_name ?? ($unit->unit_short_name ?? '-');
            }
        }

        return [
            'min_order' => (int) round($orderThreshold),
            'minim_order' => $calculatedMinOrder,
            'min_order_is_manual' => $storedMinOrder !== null,
            'min_order_unit_id' => $displayUnitId,
            'min_order_unit_name' => $displayUnitName,
            'min_order_current_stock' => round($currentStock, 4),
            'min_order_alert_qty' => round($alertQty, 4),
            'product_display_name' => trim(($row->pr_name ?? '') . ' ' . ($row->product_variant_name ?? '')),
        ];
    }

    /**
     * Meta pemesanan min bahan untuk Daftar Stok (sama rumus Peringatan Stok Bahan).
     *
     * @param  array<int, object>  $stocks
     */
    private function buildSuppliesMinOrderMeta(
        object $row,
        array $stocks,
        $relations,
        bool $isEceranWarehouse,
        $units
    ): array {
        $defaultUnitId = (int) ($row->supplies_default_unit ?? 0);
        $eceranUnitId = $this->resolveSuppliesEceranUnitId($defaultUnitId, $relations);
        $displayUnitId = ($isEceranWarehouse && $eceranUnitId > 0)
            ? $eceranUnitId
            : ($defaultUnitId > 0 ? $defaultUnitId : $eceranUnitId);

        $alertStored = max(0, (float) ($row->supplies_alert ?? 0));
        $alertDisplay = $this->convertSuppliesQty($alertStored, $defaultUnitId, $displayUnitId, $relations);

        $currentStock = 0.0;
        foreach ($stocks as $stockRow) {
            if ((int) $stockRow->unit_id === $displayUnitId) {
                $currentStock += (float) ($stockRow->ss_stock ?? 0);
            }
        }

        $storedMinOrder = null;
        if ($row->supplies_min_stock !== null && $row->supplies_min_stock !== '') {
            $storedMinOrder = (int) round($this->convertSuppliesQty(
                (float) $row->supplies_min_stock,
                $defaultUnitId,
                $displayUnitId,
                $relations
            ));
        }

        $orderThreshold = $storedMinOrder !== null ? $storedMinOrder : $alertDisplay;
        $calculatedMinOrder = (int) max(0, round($orderThreshold - $currentStock));
        $displayUnit = $units->get($displayUnitId) ?: $units->get($defaultUnitId);
        $displayUnitName = $displayUnit
            ? ($displayUnit->unit_name ?? $displayUnit->unit_short_name ?? '-')
            : '-';

        return [
            'min_order' => (int) round($orderThreshold),
            'minim_order' => $calculatedMinOrder,
            'min_order_is_manual' => $storedMinOrder !== null,
            'min_order_unit_name' => $displayUnitName,
            'min_order_current_stock' => round($currentStock, 4),
            'min_order_alert_qty' => round($alertDisplay, 4),
        ];
    }

    private function resolveSuppliesEceranUnitId(int $defaultUnitId, $relations): int
    {
        $relations = collect($relations);
        if ($relations->isEmpty()) {
            return $defaultUnitId > 0 ? $defaultUnitId : 0;
        }

        $parents = [];
        $children = [];
        foreach ($relations as $rel) {
            $parent = (int) ($rel->su_id_1 ?? 0);
            $child = (int) ($rel->su_id_2 ?? 0);
            if ($parent <= 0 || $child <= 0) {
                continue;
            }
            $parents[$parent] = true;
            $children[$child] = true;
        }

        $leaves = array_keys(array_diff_key($children, $parents));
        if ($leaves === []) {
            return $defaultUnitId > 0 ? $defaultUnitId : 0;
        }
        if ($defaultUnitId > 0 && isset($children[$defaultUnitId]) && ! isset($parents[$defaultUnitId])) {
            return $defaultUnitId;
        }
        sort($leaves);

        return (int) $leaves[0];
    }

    private function convertSuppliesQty(float $qty, int $fromUnitId, int $toUnitId, $relations): float
    {
        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        $queue = [[$fromUnitId, 1.0]];
        $visited = [];
        while ($queue !== []) {
            [$unitId, $factor] = array_shift($queue);
            if ($unitId === $toUnitId) {
                return $qty * $factor;
            }
            if (isset($visited[$unitId])) {
                continue;
            }
            $visited[$unitId] = true;

            foreach ($relations as $rel) {
                $parent = (int) $rel->su_id_1;
                $child = (int) $rel->su_id_2;
                $value = (float) $rel->sr_value_2;
                if ($value <= 0) {
                    continue;
                }
                if ($unitId === $parent && ! isset($visited[$child])) {
                    $queue[] = [$child, $factor * $value];
                } elseif ($unitId === $child && ! isset($visited[$parent])) {
                    $queue[] = [$parent, $factor / $value];
                }
            }
        }

        return $qty;
    }
}
