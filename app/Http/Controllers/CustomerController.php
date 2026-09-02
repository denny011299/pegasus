<?php

namespace App\Http\Controllers;

use App\Models\SalesOrder;
use App\Models\SalesOrderDelivery;
use App\Models\SalesOrderDetailInvoice;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\SalesOrderDeliveryDetail;
use App\Models\SalesOrderDetail;
use App\Models\Staff;
use App\Support\SalesOrderApproval;
use App\Support\SalesOrderStock;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Support\UnitRollUp;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class CustomerController extends Controller
{
    public function SalesOrder()
    {
        return view('Backoffice.Customers.Sales_Order');
    }

    public function SalesOrderDetail($id)
    {
        $param["data"] = (new SalesOrder())->getSalesOrder(["so_id" => $id, "with_items" => true])[0];
        return view('Backoffice.Customers.Sales_Order_Detail')->with($param);
    }

    function getSalesOrder(Request $req)
    {
        if ($req->has('draw')) {
            try {
                return response()->json((new SalesOrder())->getSalesOrderDataTable($req->all()));
            } catch (\Throwable $e) {
                Log::error('getSalesOrder DataTable failed: ' . $e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
                return response()->json([
                    'draw' => (int) $req->input('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'Gagal memuat data pengiriman',
                ]);
            }
        }
        $data = (new SalesOrder())->getSalesOrder($req->all());
        return response()->json($data);
    }

    function insertSalesOrder(Request $req)
    {
        $data = $req->all();

        $productsData = json_decode($data['products'] ?? '[]', true);
        if (! is_array($productsData)) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Data produk tidak valid',
            ]);
        }
        $productsData = SalesOrderStock::assignBulkWarehouseToProducts($productsData);
        $retailErr = SalesOrderStock::validateRetailWarehouseUnits($productsData);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Satuan tidak valid',
                'message' => $retailErr,
            ]);
        }
        $retailErr = SalesOrderStock::validateRetailSelection($productsData, $data['retail_warehouse_id'] ?? null);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang eceran wajib',
                'message' => $retailErr,
            ]);
        }

        // GitHub #99 (2026-09-01): JANGAN tambahkan lagi pengecekan ketersediaan stok di sini.
        // Membuat Pengiriman = mengajukan dokumen (status 1 = "Sedang Diajukan"), stok BELUM
        // dipotong sama sekali — pemotongan + pengecekan stok baru terjadi di accSO()
        // (SalesOrderApproval::confirm(), yang memanggil SalesOrderStock::buildPlan() sendiri di
        // dalam satu DB::transaction()). Memblokir pembuatan dokumen dengan stok SAAT INI salah
        // waktunya: stok bisa bertambah (produksi selesai, PO diterima, transfer masuk) antara
        // pengajuan dan ACC, jadi pengiriman yang terjadwal untuk besok jadi tidak bisa dibuat
        // hari ini. Validasi struktural (satuan eceran + gudang eceran wajib) di atas tetap
        // dipertahankan karena itu tentang bentuk data, bukan jumlah stok.

        $img = [];
        foreach (json_decode($data["so_img"]) as $key => $value) {
            $image = $value;

            // Hilangkan prefix base64
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $image);

            // Decode
            $imageData = base64_decode($image);

            // Nama file
            $imageName = 'photo_' . uniqid() . '.png';

            // Path tujuan di public/produksi
            $path = public_path('issue/' . $imageName);
            // Simpan file
            file_put_contents($path, $imageData);
            array_push($img, $imageName);
        }
        $data["so_img"] = json_encode($img);
        $so = (new SalesOrder())->insertSalesOrder($data);
        if ($so->so_id == -1) {
            return -1;
        }
        foreach ($productsData as $key => $value) {
            $value['so_id'] = $so->so_id;
            (new SalesOrderDetail())->insertSalesOrderDetail($value);
        }
        return 1;
    }

    /**
     * Endpoint dipanggil dari JS setiap kali user menambah baris produk ke
     * "Daftar Produk" pada modal Tambah/Update Pengiriman - GitHub #116, meniru
     * pola checkProductionStock() di Produksi (GitHub #101/#105). Menerima
     * `products` yang SUDAH termasuk baris yang baru mau ditambahkan (client
     * kirim seluruh array setelah push/merge), supaya simulasi stok per gudang
     * dihitung dari kondisi akhir yang benar.
     *
     * Read-only - hanya mensimulasikan ketersediaan lewat SalesOrderStock::buildPlan(),
     * TIDAK memutasi stok. Ini murni peringatan dini di UI: insertSalesOrder()/
     * updateSalesOrder() sendiri SENGAJA tetap tidak diblokir oleh stok saat ini
     * (lihat catatan GitHub #99 di insertSalesOrder() di atas) - pemotongan +
     * pengecekan stok yang sesungguhnya tetap terjadi di accSO(). Baris yang lolos
     * cek ini saat ditambahkan bisa saja tetap gagal di ACC kalau stok berkurang
     * lagi sebelum diterima, dan sebaliknya baris yang gagal di sini bisa saja
     * lolos nanti kalau stok bertambah (produksi/PO/transfer) sebelum ACC.
     */
    function checkSalesOrderStock(Request $req)
    {
        $productsData = json_decode($req->input('products', '[]'), true);
        if (! is_array($productsData) || empty($productsData)) {
            return response()->json(['status' => 1]);
        }

        $productsData = SalesOrderStock::assignBulkWarehouseToProducts($productsData);

        $retailErr = SalesOrderStock::validateRetailWarehouseUnits($productsData);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Satuan tidak valid',
                'message' => $retailErr,
            ]);
        }

        $retailWarehouseId = $req->input('retail_warehouse_id');
        $retailErr = SalesOrderStock::validateRetailSelection($productsData, $retailWarehouseId);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang eceran wajib',
                'message' => $retailErr,
            ]);
        }

        $plan = SalesOrderStock::buildPlan($productsData, $retailWarehouseId ?: null);
        if (! ($plan['ok'] ?? false)) {
            return response()->json([
                'status' => 0,
                'header' => $plan['header'] ?? 'Stok tidak cukup',
                'message' => $plan['message'] ?? 'Stok tidak mencukupi',
                'products' => $plan['products'] ?? [],
                'recommendations' => $plan['recommendations'] ?? [],
            ]);
        }

        return response()->json(['status' => 1]);
    }

    function updateSalesOrder(Request $req)
    {
        $data = $req->all();

        $productsData = json_decode($data['products'] ?? '[]', true);
        if (! is_array($productsData)) {
            return 'Data produk tidak valid';
        }
        $productsData = SalesOrderStock::assignBulkWarehouseToProducts($productsData);

        $soBefore = SalesOrder::find($data['so_id'] ?? null);
        if (! $soBefore) {
            return 'Sales order tidak ditemukan';
        }

        // Mutasi stok/log hanya jika SO sudah disetujui (ACC). Sebelum itu stok belum dipotong (accSO);
        // jika revert+potong dijalankan saat status 1/3, stok ikut berubah padahal belum konfirmasi.
        if ((int) ($soBefore->status ?? 0) !== 2) {
            $retailErr = SalesOrderStock::validateRetailWarehouseUnits($productsData);
            if ($retailErr) {
                return response()->json([
                    'status' => 0,
                    'header' => 'Satuan tidak valid',
                    'message' => $retailErr,
                ]);
            }
            $retailErr = SalesOrderStock::validateRetailSelection($productsData, $data['retail_warehouse_id'] ?? null);
            if ($retailErr) {
                return response()->json([
                    'status' => 0,
                    'header' => 'Gudang eceran wajib',
                    'message' => $retailErr,
                ]);
            }
            // GitHub #99 (2026-09-01): sama seperti insertSalesOrder() — cabang ini hanya jalan
            // saat status != 2 (belum ACC), jadi stok memang belum dipotong dan tidak boleh
            // dicek/diblokir di sini. Pengecekan stok yang sesungguhnya ada di accSO().
            $so = (new SalesOrder())->updateSalesOrder($data);
            $list_id_detail = [];
            foreach ($productsData as $val) {
                $val['so_id'] = $so->so_id;
                $id = isset($val['sod_id'])
                    ? (new SalesOrderDetail())->updateSalesOrderDetail($val)
                    : (new SalesOrderDetail())->insertSalesOrderDetail($val);
                $list_id_detail[] = $id;
            }
            SalesOrderDetail::where('so_id', $so->so_id)->whereNotIn('sod_id', $list_id_detail)->update(['status' => 0]);

            return 1;
        }

        $retailErr = SalesOrderStock::validateRetailWarehouseUnits($productsData);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Satuan tidak valid',
                'message' => $retailErr,
            ]);
        }
        $retailErr = SalesOrderStock::validateRetailSelection($productsData, $data['retail_warehouse_id'] ?? $soBefore->retail_warehouse_id);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang eceran wajib',
                'message' => $retailErr,
            ]);
        }

        // NB (merged from main's 51684f3/PR #75, 2026-08-28): main's version of this conflict was
        // its OWN inline revert-then-deduct loop (ProductStock reads/writes directly in this
        // method), including a fix making a deleted line's stock revert roll up the unit ladder.
        // fase2's updateSalesOrder() delegates all of that to SalesOrderStock::executeRestore()/
        // buildPlan()/executeDeduct() below -- the equivalent roll-up-on-restore fix has been
        // ported there instead (see SalesOrderStock::executeRestore()'s $rollUp param), since
        // that's what actually writes the revert. $oldLines is split below into kept-vs-removed so
        // only removed lines' revert rolls up (a kept line is re-deducted by executeDeduct() in
        // this same transaction, so rolling it up first is pure churn).
        $oldRetailWh = (int) ($soBefore->retail_warehouse_id ?? 0);
        $newRetailWh = (int) ($data['retail_warehouse_id'] ?? $oldRetailWh);

        $oldLines = [];
        foreach (SalesOrderDetail::where('so_id', $data['so_id'])->where('status', '>=', 1)->get() as $oldDetail) {
            $oldLines[] = [
                'product_variant_id' => $oldDetail->product_variant_id,
                'unit_id' => $oldDetail->unit_id,
                'warehouse_id' => $oldDetail->warehouse_id ?? null,
                'qty' => (float) $oldDetail->sod_qty,
            ];
        }

        $newLines = [];
        foreach ($productsData as $value) {
            $newLines[] = [
                'product_variant_id' => $value['product_variant_id'],
                'unit_id' => $value['unit_id'],
                'warehouse_id' => $value['warehouse_id'] ?? null,
                'qty' => (float) ($value['so_qty'] ?? 0),
            ];
        }

        $newLineKeys = [];
        foreach ($newLines as $newLine) {
            $newLineKeys[$newLine['product_variant_id'] . '_' . $newLine['unit_id']] = true;
        }
        $oldLinesKept = [];
        $oldLinesRemoved = [];
        foreach ($oldLines as $oldLine) {
            $key = $oldLine['product_variant_id'] . '_' . $oldLine['unit_id'];
            if (isset($newLineKeys[$key])) {
                $oldLinesKept[] = $oldLine;
            } else {
                $oldLinesRemoved[] = $oldLine;
            }
        }

        // Diperbaiki (2026-08-08): buildPlan() (cek kecukupan stok untuk item BARU) dulu dijalankan
        // di sini, SEBELUM executeRestore() di bawah mengembalikan stok item LAMA — jadi SO yang
        // stoknya sudah habis terpakai (kasus normal: menjual persis sisa stok) ditolak "Stok tidak
        // cukup" bahkan untuk edit no-op yang sama sekali tidak menambah komitmen stok apa pun.
        // Sekarang plan dibangun DI DALAM transaction, SETELAH restore, supaya mengecek terhadap
        // stok yang sudah benar mencerminkan pengembalian item lama. $plan ditangkap lewat
        // referensi supaya detail respons (products/recommendations) masih bisa dibaca di blok
        // catch kalau gagal.
        $plan = null;
        try {
            DB::transaction(function () use ($data, $productsData, $oldLinesKept, $oldLinesRemoved, $oldRetailWh, $newLines, $newRetailWh, $soBefore, &$plan) {
                if ($oldLinesKept !== []) {
                    $restore = SalesOrderStock::executeRestore(
                        $oldLinesKept,
                        $oldRetailWh > 0 ? $oldRetailWh : null,
                        $data['so_number'] ?? ($soBefore->so_number ?? '-'),
                        'Update Pengiriman (kembalikan stok)'
                    );
                    if (! ($restore['ok'] ?? false)) {
                        throw new \RuntimeException($restore['message'] ?? 'Gagal kembalikan stok lama');
                    }
                }
                if ($oldLinesRemoved !== []) {
                    // rollUp: true -- these lines were deleted from the SO, so this revert is
                    // final (nothing re-deducts them later in this same request).
                    $restore = SalesOrderStock::executeRestore(
                        $oldLinesRemoved,
                        $oldRetailWh > 0 ? $oldRetailWh : null,
                        $data['so_number'] ?? ($soBefore->so_number ?? '-'),
                        'Update Pengiriman (kembalikan stok)',
                        true
                    );
                    if (! ($restore['ok'] ?? false)) {
                        throw new \RuntimeException($restore['message'] ?? 'Gagal kembalikan stok lama');
                    }
                }

                // NB (merged from main's 51684f3/PR #75, 2026-08-28): main's version of this
                // conflict was its OWN inline "bongkar" closure (same position-vs-relation unit
                // bug already fixed elsewhere on 2026-08-05/08-06 -- see
                // ReturnSuppliesBongkarFailsOnStockRowInsertionOrderTest.php). fase2's
                // updateSalesOrder() has no such inline closure -- deduction runs entirely through
                // SalesOrderStock::executeDeduct() below, so this doesn't transplant. Confirmed
                // the actual bongkar logic executeDeduct() delegates to,
                // ProductUnitStock::deductQty()'s ensure() closure, already resolves the parent
                // unit via the ProductRelation lookup (`pr_unit_id_1`), not array position.
                $plan = SalesOrderStock::buildPlan($newLines, $newRetailWh > 0 ? $newRetailWh : null);
                if (! ($plan['ok'] ?? false)) {
                    throw new \RuntimeException($plan['message'] ?? 'Stok tidak mencukupi');
                }

                $deduct = SalesOrderStock::executeDeduct(
                    $plan['plan'],
                    $data['so_invoice_no'] ?? ($soBefore->so_invoice_no ?? '-'),
                    'Update Pengiriman'
                );
                if (! ($deduct['ok'] ?? false)) {
                    throw new \RuntimeException($deduct['message'] ?? 'Gagal potong stok');
                }

                $so = (new SalesOrder())->updateSalesOrder($data);
                $list_id_detail = [];
                foreach ($productsData as $val) {
                    $val['so_id'] = $so->so_id;
                    $id = isset($val['sod_id'])
                        ? (new SalesOrderDetail())->updateSalesOrderDetail($val)
                        : (new SalesOrderDetail())->insertSalesOrderDetail($val);
                    $list_id_detail[] = $id;
                }
                SalesOrderDetail::where('so_id', $so->so_id)->whereNotIn('sod_id', $list_id_detail)->update(['status' => 0]);
            });
        } catch (\Throwable $e) {
            if ($plan && ! ($plan['ok'] ?? false)) {
                return response()->json([
                    'status' => $plan['status'] ?? 0,
                    'header' => $plan['header'] ?? 'Stok tidak cukup',
                    'message' => $plan['message'] ?? 'Stok tidak mencukupi',
                    'products' => $plan['products'] ?? [],
                    'recommendations' => $plan['recommendations'] ?? [],
                ]);
            }
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Update',
                'message' => $e->getMessage(),
            ]);
        }

        return 1;
    }

    function deleteSalesOrder(Request $req)
    {
        $data = $req->all();
        (new SalesOrder())->deleteSalesOrder($data);
        $v = SalesOrderDetail::where('so_id', '=', $data["so_id"])->where('status', 1)->get();
        foreach ($v as $key => $value) {
            (new SalesOrderDetail())->deleteSalesOrderDetail($value);
        }
    }

    function accSO(Request $req)
    {
        $data = $req->all();

        $so = SalesOrder::find($data['so_id'] ?? null);
        if (! $so) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => 'Pengiriman tidak ditemukan',
            ]);
        }

        if ((int) $so->status !== 1) {
            $staff = Staff::find($so->acc_by);
            return response()->json([
                'status' => -2,
                'header' => 'Gagal ACC',
                'message' => 'Pengajuan sudah diterima/ditolak oleh ' . ($staff->staff_name ?? '-'),
            ]);
        }

        // Logika potong stok + set status Confirmed ada di SalesOrderApproval::confirm() -
        // dipakai bersama External API POST /shipments/shipped. Pengecekan status di atas
        // sengaja tetap di sini (bukan dipindah seluruhnya ke confirm()) supaya pesan "sudah
        // diterima/ditolak oleh {staff}" yang menyebut nama tetap ada di jalur admin ini.
        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        $result = SalesOrderApproval::confirm($so, $staffId);

        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'status' => $result['status'] ?? 0,
                'header' => $result['header'] ?? 'Gagal ACC',
                'message' => $result['message'] ?? 'Stok tidak mencukupi',
                'products' => $result['products'] ?? [],
                'recommendations' => $result['recommendations'] ?? [],
            ]);
        }

        return 1;
    }
    function declineSO(Request $req)
    {
        $data = $req->all();
        $q = SalesOrder::find($data['so_id']);
        if ($q->status != 1) {
            $staff = Staff::find($q->acc_by)->staff_name;
            return response()->json([
                "status" => -2,
                "header" => "Gagal ACC",
                "message" => "Pengajuan sudah diterma/ditolak oleh " . $staff
            ]);
        }
        (new SalesOrder())->declineSO($data);
        return 1;
    }

    function updateSalesOrderDetail(Request $req)
    {
        foreach (json_decode($req->so_detail, true) as $key => $value) {
            (new SalesOrderDetail())->updateSalesOrderDetail($value);
        }
    }

    function searchProduct(Request $req)
    {
        $data = (new ProductVariant())->getProductVariant(["search" => $req->search]);
        if (count($data) > 0) return response()->json($data[0]);
        else return -1;
    }

    // DEPRECATED (2026-08-04): Sales Order Delivery (getSoDelivery through declineSoDelivery
    // below) is no longer used — its stock mutation double-counted what accSO() already deducts
    // at SO-approval time. Confirmed by product owner as unused, not fixed, not tested. See
    // KNOWN_ISSUES.md.
    function getSoDelivery(Request $req)
    {
        $data = (new SalesOrderDelivery())->getSoDelivery([
            "so_id" => $req->so_id
        ]);
        return response()->json($data);
    }

    function insertSoDelivery(Request $req)
    {
        $data = $req->all();
        $id = (new SalesOrderDelivery())->insertSoDelivery($data);
        foreach (json_decode($data['sdo_detail'], true) as $key => $value) {
            $value['sdo_id'] = $id;
            (new SalesOrderDeliveryDetail())->insertSoDeliveryDetail($value);
        }
    }

    function updateSoDelivery(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new SalesOrderDelivery())->updateSoDelivery($data);
        foreach (json_decode($data['sdo_detail'], true) as $key => $value) {
            $value['sdo_id'] = $data["sdo_id"];
            $value['statusSO'] = $value["status"];
            if (!isset($value["sdod_id"])) $t = (new SalesOrderDeliveryDetail())->insertSoDeliveryDetail($value);
            else {

                $t = (new SalesOrderDeliveryDetail())->updateSoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        SalesOrderDeliveryDetail::where('sdo_id', '=', $data["sdo_id"])->whereNotIn("sdod_id", $id)->update(["status" => 0]);
    }

    function deleteSoDelivery(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDelivery())->deleteSoDelivery($data);
    }
    function accSoDelivery(Request $req)
    {
        $data = $req->all();
        $id = [];
        (new SalesOrderDelivery())->updateSoDelivery($data);
        (new SalesOrderDelivery())->statusSoDelivery($data);
        $status = SalesOrderDelivery::find($data["sdo_id"])->status; // approved
        foreach (json_decode($data['sdo_detail'], true) as $key => $value) {
            $value['sdo_id'] = $data["sdo_id"];
            $value['statusSO'] = $status;
            if (!isset($value["sdod_id"])) $t = (new SalesOrderDeliveryDetail())->insertSoDeliveryDetail($value);
            else {
                $t = (new SalesOrderDeliveryDetail())->updateSoDeliveryDetail($value);
            }
            array_push($id, $t);
        }
        SalesOrderDeliveryDetail::where('sdo_id', '=', $data["sdo_id"])->whereNotIn("sdod_id", $id)->update(["status" => 0]);
    }

    function declineSoDelivery(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDelivery())->statusSoDelivery($data);
    }

    function getSoInvoice(Request $req)
    {
        $data = (new SalesOrderDetailInvoice())->getSoInvoice($req->all());
        return response()->json($data);
    }

    function insertInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->insertInvoiceSO($data);
    }

    function updateInvoiceSO(Request $req)
    {
        $data = $req->all();
        if (isset($req->image) && $req->image != "undefined") $data["supplier_image"] = (new HelperController)->insertFile($req->image, "supplier");
        return (new SalesOrderDetailInvoice())->updateInvoiceSO($data);
    }

    function deleteInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->deleteInvoiceSO($data);
    }

    // Customer
    function customer()
    {
        return view('Backoffice.Customer.customer');
    }

    function customerDetail($id)
    {
        // $param["data"] =(new Customer())->getCustomer(["cus_id"=>$id])[0];
        $param["cus_id"] = $id;
        return view('Backoffice.Customer.CustomerDetails')->with($param);
    }
    function viewInsertCustomer()
    {
        $param["mode"] = 1;
        $param["data"] = [];
        return view('Backoffice.Customer.insertCustomer')->with($param);
    }
    function ViewUpdateCustomer($id)
    {
        $param["mode"] = 2; // 1 = insert, 2 = update
        $param["data"] = (new Customer())->getCustomer(["customer_id" => $id])[0];
        return view('Backoffice.Customer.insertCustomer')->with($param);
    }

    function getCustomer(Request $req)
    {
        $data =  (new Customer())->getCustomer([
            "cus_name" => $req->cus_name,
            "city_id" => $req->city_id
        ]);
        return response()->json($data);
    }

    function insertCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->insertCustomer($data);
    }

    function updateCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->updateCustomer($data);
    }

    function deleteCustomer(Request $req)
    {
        $data = $req->all();
        return (new Customer())->deleteCustomer($data);
    }

    function declineInvoiceSO(Request $req)
    {
        $data = $req->all();
        return (new SalesOrderDetailInvoice())->changeStatusInvoiceSO($data);
    }
    function acceptInvoiceSO(Request $req)
    {
        $data = $req->all();
        // Ditambahkan: dulu tidak ada guard sama sekali — menerima 2+ invoice yang gabungan
        // totalnya melebihi so_total selalu berhasil. Mirroring acceptInvoicePO's pattern exactly
        // (SupplierController.php): jumlahkan invoice yang sudah diterima (status=2) + invoice
        // ini sendiri, tolak kalau melebihi so_total.
        $soi = SalesOrderDetailInvoice::find($data["soi_id"]);
        $total = SalesOrderDetailInvoice::where("so_id", "=", $soi->so_id)->where("status", "=", 2)->sum("soi_total");
        $so = SalesOrder::find($soi->so_id);
        if ($total + $soi->soi_total <= $so->so_total) {
            return (new SalesOrderDetailInvoice())->changeStatusInvoiceSO($data);
        } else {
            return -1;
        }
    }
}
