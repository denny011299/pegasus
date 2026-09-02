<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetailInvoice extends Model
{
    protected $table = "purchase_order_detail_invoices";
    protected $primaryKey = "poi_id";
    public $timestamps = true;
    public $incrementing = true;

    protected $fillable = [
        'po_id',
        'poi_date',
        'poi_due',
        'poi_code',
        'poi_total',
    ];

    // === GET DATA ===
    /**
     * Base Hutang/invoice list query (filters only — no order/select/get).
     * Date semantics: only start → through today; only end → from beginning; both → range; neither → no date filter.
     */
    public static function queryPoInvoice(array $data = [])
    {
        $data = array_merge([
            "poi_id" => null,
            "po_id"  => null,
            "bank_id"  => null,
            "status"  => null,
            "po_supplier"  => null,
            "dates" => null,
        ], $data);

        $result = PurchaseOrderDetailInvoice::where('purchase_order_detail_invoices.status', '>=', 0);
        $result->join('purchase_orders', 'purchase_orders.po_id', '=', 'purchase_order_detail_invoices.po_id');
        $result->where('purchase_orders.status', '>=', -1)->where('purchase_orders.status', '!=', 0);

        if ($data["poi_id"]) {
            $result->where("purchase_order_detail_invoices.poi_id", "=", $data["poi_id"]);
        }

        if ($data["po_id"]) {
            $result->where("purchase_order_detail_invoices.po_id", "=", $data["po_id"]);
        }

        if ($data["bank_id"]) {
            $result->where("purchase_order_detail_invoices.bank_id", "=", $data["bank_id"]);
        }

        if ($data["status"]) {
            if ($data["status"] == 5) {
                $result->where('purchase_orders.status', -1);
            } else {
                $result->where("purchase_orders.pembayaran", "=", $data["status"])->where("purchase_orders.status", '>', 0);
            }
        }

        if ($data["po_supplier"]) {
            $result->where("purchase_orders.po_supplier", "=", $data["po_supplier"]);
        }

        self::applyPoInvoiceDateFilter($result, $data["dates"] ?? null);

        return $result;
    }

    public static function applyPoInvoiceDateFilter($query, $dates): void
    {
        if (!$dates) {
            return;
        }

        if (is_array($dates)) {
            $start = trim((string) ($dates[0] ?? ''));
            $end = trim((string) ($dates[1] ?? ''));

            if ($start === '' && $end === '') {
                return;
            }

            if ($start !== '') {
                $query->whereDate(
                    'purchase_orders.po_date',
                    '>=',
                    \Carbon\Carbon::parse($start)->toDateString()
                );
            }

            if ($end !== '') {
                $query->whereDate(
                    'purchase_orders.po_date',
                    '<=',
                    \Carbon\Carbon::parse($end)->toDateString()
                );
            } elseif ($start !== '') {
                // Only Dari Tanggal → through today
                $query->whereDate(
                    'purchase_orders.po_date',
                    '<=',
                    \Carbon\Carbon::today()->toDateString()
                );
            }

            return;
        }

        $date = \Carbon\Carbon::parse($dates)->toDateString();
        $query->whereDate('purchase_orders.po_date', $date);
    }

    public static function applyPoInvoiceOrder($query)
    {
        return $query
            ->orderByRaw('FIELD(purchase_orders.status, 1, 2, 3, -1)')
            ->orderByRaw('FIELD(purchase_orders.pembayaran, 1, 3, 2)')
            ->orderBy("purchase_orders.po_date", "asc")
            ->orderBy("purchase_order_detail_invoices.poi_date", "asc");
    }

    public static function enrichPoInvoiceRows($rows): void
    {
        foreach ($rows as $value) {
            $po = PurchaseOrder::find($value->po_id);
            $value->supplier_name = Supplier::find($po->po_supplier)->supplier_name;
            $value->po_code = $po->po_number;
            $value->po_date = $po->po_date;
            $value->pembayaran = $po->pembayaran;

            $b = Bank::find($value->bank_id);
            $value->bank_kode = $b->bank_kode ?? "-";
        }
    }

    /**
     * Print/PDF path: same row shape as enrichPoInvoiceRows, via join (no per-row find).
     * Does not change list/DataTable getPoInvoice + enrichPoInvoiceRows.
     */
    public static function getPoInvoiceForPrint(array $data = [])
    {
        $result = self::queryPoInvoice($data);
        $result
            ->leftJoin('suppliers', 'suppliers.supplier_id', '=', 'purchase_orders.po_supplier')
            ->leftJoin('banks', 'banks.bank_id', '=', 'purchase_order_detail_invoices.bank_id')
            ->select(
                'purchase_order_detail_invoices.*',
                'purchase_orders.po_number as po_code',
                'purchase_orders.po_date',
                'purchase_orders.pembayaran',
                'suppliers.supplier_name',
                \DB::raw('COALESCE(banks.bank_kode, "-") as bank_kode')
            );
        self::applyPoInvoiceOrder($result);

        return $result->get();
    }

    function getPoInvoice($data = [])
    {
        $result = self::queryPoInvoice($data);
        $result->select('purchase_order_detail_invoices.*');
        self::applyPoInvoiceOrder($result);
        $result = $result->get();

        self::enrichPoInvoiceRows($result);
        return $result;
    }

    // === INSERT ===
    function insertInvoicePO($data)
    {
        $t = new PurchaseOrderDetailInvoice();
        $t->po_id      = $data["po_id"];
        $t->poi_date   = $data["poi_date"]??date("Y-m-d");
        $t->poi_due    = $data["poi_due"];
        $t->poi_code   = $this->generateInvoicePurchaseOrderID();
        $t->poi_total  = $data["poi_total"];
        $t->status  = $data["status"]??1;
        $t->bank_id  = $data["bank_id"]??null;
        $t->save();
        //$this->cekInvoice($t->po_id);
        return $t->poi_id;
    }

    // === UPDATE ===
    function updateInvoicePO($data)
    {
        $t = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        if (!$t) return null;

        $t->po_id      = $data["po_id"];
        $t->poi_date   = $data["poi_date"];
        $t->poi_due    = $data["poi_due"];
        $t->poi_total  = $data["poi_total"];
        $t->save();

        $this->cekInvoice($t->po_id);
        return $t->poi_id;
    }

    // === DELETE ===
    function deleteInvoicePO($data)
    {
        $t = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        if ($t) {
            $t->status=-1;
            $t->save(); // hard delete
            $this->cekInvoice($t->po_id);
        }
    }
    // === DELETE ===
    function changeStatusInvoicePO($data)
    {
        $t = PurchaseOrderDetailInvoice::find($data["poi_id"]);
        if ($t) {
            $t->status=$data["status"];
            $t->save(); // hard delete
        }
       $this->cekInvoice($t->po_id);
    }

    /**
     * Dinonaktifkan (2026-08-06, GitHub issue #14): dulu method ini men-set purchase_orders.status
     * jadi 3 ("belum lunas") atau 4 ("lunas penuh") berdasarkan total invoice yang sudah diterima —
     * dipanggil dari updateInvoicePO/deleteInvoicePO/changeStatusInvoicePO SETIAP kali sebuah invoice
     * diedit/dihapus/diterima/ditolak, tanpa syarat apa pun.
     *
     * Dikonfirmasi oleh PM (2026-08-06): alur status PO yang benar hanya berisi 3 nilai —
     * status=1 (menunggu konfirmasi) -> status=2 (disetujui) -> status=-1 (ditolak), dan progres
     * "belum terbayar / menunggu tanda terima / terbayar" SEPENUHNYA dikendalikan oleh kolom
     * `pembayaran` (1/3/2), bukan oleh `status`. Nilai status=3/4 yang ditulis di sini tidak pernah
     * ada dalam alur itu — write-nya murni mengotori `purchase_orders.status` di luar domain
     * {1, 2, -1} yang jadi asumsi di tempat lain, dengan 2 akibat nyata:
     *   1. `SupplierController::tolakPO()` hanya membalik supplies_stocks kalau `status == 2` —
     *      begitu status jadi 3/4, membatalkan PO itu TIDAK membalik stok yang sudah ditambah
     *      accPO, walau permintaan pembatalannya sendiri tetap "berhasil".
     *   2. `Purchase_Order_Detail.js`'s tombol Batalkan/Terima hanya muncul untuk status 1 atau 2 —
     *      begitu status jadi 3/4, tombolnya hilang sama sekali, jadi staff tidak bisa lagi menolak
     *      PO yang menurut alur PM seharusnya masih boleh (belum terbayar/menunggu tanda terima
     *      -> ditolak).
     *
     * Method ini dipertahankan (bukan dihapus) karena masih dipanggil dari 3 tempat — tapi sekarang
     * sengaja tidak melakukan apa pun, supaya updateInvoicePO/deleteInvoicePO/changeStatusInvoicePO
     * tidak lagi bisa memindahkan purchase_orders.status sama sekali. Total invoice yang sudah
     * diterima tetap bisa dihitung ulang kapan pun lewat query yang sama kalau nanti dibutuhkan
     * untuk field terpisah — lihat cdocs/testing/KNOWN_ISSUES.md.
     */
    function cekInvoice($po_id) {
        // Sengaja kosong — lihat docblock di atas.
    }

    function generateInvoicePurchaseOrderID()
    {
        $id = self::max('poi_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "INV" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }
}
