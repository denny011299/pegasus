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
use App\Support\SalesOrderStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        $retailErr = SalesOrderStock::validateRetailSelection($productsData, $data['retail_warehouse_id'] ?? null);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang eceran wajib',
                'message' => $retailErr,
            ]);
        }

        $stockErr = SalesOrderStock::assertStockAvailable($productsData, $data['retail_warehouse_id'] ?? null);
        if ($stockErr) {
            return response()->json($stockErr);
        }

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

    function updateSalesOrder(Request $req)
    {
        $data = $req->all();

        $productsData = json_decode($data['products'] ?? '[]', true);
        if (! is_array($productsData)) {
            return 'Data produk tidak valid';
        }

        $soBefore = SalesOrder::find($data['so_id'] ?? null);
        if (! $soBefore) {
            return 'Sales order tidak ditemukan';
        }

        // Mutasi stok/log hanya jika SO sudah disetujui (ACC). Sebelum itu stok belum dipotong (accSO);
        // jika revert+potong dijalankan saat status 1/3, stok ikut berubah padahal belum konfirmasi.
        if ((int) ($soBefore->status ?? 0) !== 2) {
            $retailErr = SalesOrderStock::validateRetailSelection($productsData, $data['retail_warehouse_id'] ?? null);
            if ($retailErr) {
                return response()->json([
                    'status' => 0,
                    'header' => 'Gudang eceran wajib',
                    'message' => $retailErr,
                ]);
            }
            $stockErr = SalesOrderStock::assertStockAvailable($productsData, $data['retail_warehouse_id'] ?? null);
            if ($stockErr) {
                return response()->json($stockErr);
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

            return 1;
        }

        $retailErr = SalesOrderStock::validateRetailSelection($productsData, $data['retail_warehouse_id'] ?? $soBefore->retail_warehouse_id);
        if ($retailErr) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang eceran wajib',
                'message' => $retailErr,
            ]);
        }

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

        $plan = SalesOrderStock::buildPlan($newLines, $newRetailWh > 0 ? $newRetailWh : null);
        if (! ($plan['ok'] ?? false)) {
            return response()->json([
                'status' => $plan['status'] ?? 0,
                'header' => $plan['header'] ?? 'Stok tidak cukup',
                'message' => $plan['message'] ?? 'Stok tidak mencukupi',
                'products' => $plan['products'] ?? [],
                'recommendations' => $plan['recommendations'] ?? [],
            ]);
        }

        try {
            DB::transaction(function () use ($data, $productsData, $oldLines, $oldRetailWh, $plan, $soBefore) {
                if ($oldLines !== []) {
                    $restore = SalesOrderStock::executeRestore(
                        $oldLines,
                        $oldRetailWh > 0 ? $oldRetailWh : null,
                        $data['so_number'] ?? ($soBefore->so_number ?? '-'),
                        'Update Pengiriman (kembalikan stok)'
                    );
                    if (! ($restore['ok'] ?? false)) {
                        throw new \RuntimeException($restore['message'] ?? 'Gagal kembalikan stok lama');
                    }
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

        $sod = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)->get();
        $lines = [];
        foreach ($sod as $row) {
            $lines[] = [
                'product_variant_id' => $row->product_variant_id,
                'unit_id' => $row->unit_id,
                'warehouse_id' => $row->warehouse_id ?? null,
                'qty' => (float) $row->sod_qty,
            ];
        }

        $retailWh = (int) ($so->retail_warehouse_id ?? 0);
        $plan = SalesOrderStock::buildPlan($lines, $retailWh > 0 ? $retailWh : null);
        if (! ($plan['ok'] ?? false)) {
            return response()->json([
                'status' => $plan['status'] ?? 0,
                'header' => $plan['header'] ?? 'Stok tidak cukup',
                'message' => $plan['message'] ?? 'Stok tidak mencukupi',
                'products' => $plan['products'] ?? [],
                'recommendations' => $plan['recommendations'] ?? [],
            ]);
        }

        try {
            DB::transaction(function () use ($plan, $so, $data) {
                $deduct = SalesOrderStock::executeDeduct(
                    $plan['plan'],
                    $so->so_invoice_no ?: $so->so_number,
                    'Pengiriman produk'
                );
                if (! ($deduct['ok'] ?? false)) {
                    throw new \RuntimeException($deduct['message'] ?? 'Gagal potong stok');
                }
                (new SalesOrder())->accSO($data);
            });
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal ACC',
                'message' => $e->getMessage(),
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
