<?php

namespace App\Http\Controllers;

use App\Models\LogStock;
use App\Models\ManageStock;
use App\Models\ProductIssues;
use App\Models\ProductIssuesDetail;
use App\Models\ProductStock;
use App\Models\ProductVariant;
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
use Illuminate\Http\Request;

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
    }

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

        $sto = (new StockOpname())->getStockOpname(['sto_id' => $id])->first();
        $items = [];
        foreach ($sto->item as $detail) {
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

        $data = [
            'sto_id'      => $sto->sto_id,
            'sto_date'    => $sto->sto_date,
            'staff_id'    => $sto->staff_id,
            'staff_name'  => $sto->staff_name,
            'category_id' => $sto->category_id,
            'sto_notes'   => $sto->sto_notes,
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

    function getDetailStockOpname(Request $req)
    {
        $data = (new StockOpnameDetail())->getDetailStockOpname();
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

    function deleteStockOpnameBahan(Request $req)
    {
        $data = $req->all();
        return (new StockOpnameBahan())->deleteStockOpnameBahan($data);
    }

    // Stock Opname Detail
    public function DetailStockOpnameBahan($id)
    {
        if ($id != -1) {
            $param["data"] = (new StockOpnameBahan())->getStockOpnameBahan(["stob_id" => $id])[0];
            $param["mode"] = 2;
        } else {
            $param["data"] = [];
            $param["mode"] = 1;
        }
        return view('Backoffice.Inventory.CreateStockOpnameSupplies')->with($param);
    }

    function getDetailStockOpnameBahan(Request $req)
    {
        $data = (new StockOpnameDetailBahan())->getDetailStockOpname();
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
        $t = (new ProductIssues())->insertProductIssues($data);
        foreach (json_decode($data['items'], true) as $key => $value) {
            $value['pi_id'] = $t->pi_id;
            (new ProductIssuesDetail())->insertProductIssuesDetail($value);

            // Catat Log
            $logNotes = "";
            $logCategory = 0;
            $logType = 0;
            $itemId = 0;
            if ($t->tipe_return == 1){
                $sup = SuppliesVariant::find($value['supplies_variant_id']);
                $spr = Supplier::find($sup->supplier_id);
                $logNotes = 'Produk bermasalah retur supplier ' . $spr->supplier_name;
                $logCategory = 2;
                $logType = 2;

                $itemId = $sup->supplies_id;
            } elseif ($t->tipe_return == 2){
                $logNotes = 'Produk bermasalah retur pelanggan';
                $logCategory = 1;
                $logType = 1;
                $itemId = $value['product_variant_id'];
            }
            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode'    => $t->pi_code,
                'log_type'    => $logType,
                'log_category' => $logCategory,
                'log_item_id' => $itemId,
                'log_notes'  => $logNotes,
                'log_jumlah' => $value['pid_qty'],
                'unit_id'    => $value['unit_id'],
            ]);
        }
    }

    function updateProductIssue(Request $req)
    {
        $data = $req->all();
        $id = [];
        $pi = (new ProductIssues())->updateProductIssues($data);
        foreach (json_decode($data['product'], true) as $key => $value) {
            $value['pi_id'] = $data["pi_id"];
            if (!isset($value["pid_id"])) {
                $t = (new ProductIssuesDetail())->insertProductIssuesDetail($value);
                
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($value['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 2;
                    $logType = 2;

                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Produk bermasalah retur pelanggan';
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
                $pid = ProductIssuesDetail::find($value['pid_id']);
                // Catat Log
                $logNotes = "";
                $logCategory = 0;
                $logType = 0;
                $itemId = 0;
                if ($pi->tipe_return == 1){
                    $sup = SuppliesVariant::find($pid['supplies_variant_id']);
                    $spr = Supplier::find($sup->supplier_id);
                    
                    $logNotes = 'Perubahan data produk bermasalah retur supplier ' . $spr->supplier_name;
                    $logCategory = 1;
                    $logType = 2;

                    $itemId = $sup->supplies_id;
                } elseif ($pi->tipe_return == 2){
                    $logNotes = 'Perubahan data produk bermasalah retur pelanggan';
                    $logCategory = 2;
                    $logType = 1;
                    $itemId = $pid['product_variant_id'];
                }
                (new LogStock())->insertLog([
                    'log_date' => now(),
                    'log_kode'    => $pi->pi_code,
                    'log_type'    => $logType,
                    'log_category' => $logCategory,
                    'log_item_id' => $itemId,
                    'log_notes'  => $logNotes,
                    'log_jumlah' => $pid['pid_qty'],
                    'unit_id'    => $pid['unit_id'],
                ]);
                
                $t = (new ProductIssuesDetail())->updateProductIssuesDetail($value);
            }
            array_push($id, $t);

            if (!isset($value["pid_id"])){
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
                    $logNotes = 'Perubahan data produk bermasalah retur pelanggan';
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
        }
        ProductIssuesDetail::where('pi_id', '=', $data["pi_id"])->whereNotIn("pid_id", $id)->update(["status" => 0]);
    }

    function deleteProductIssue(Request $req)
    {
        $data = $req->all();
        (new ProductIssues())->deleteProductIssues($data);
        $pi = ProductIssues::find($data['pi_id']);
        $v = ProductIssuesDetail::where('pi_id','=',$data["pi_id"])->get();
        foreach ($v as $key => $value) {
            (new ProductIssuesDetail())->deleteProductIssuesDetail($value);

            // Catat Log
            $logNotes = "";
            $logCategory = 0;
            $logType = 0;
            $itemId = 0;
            if ($pi->tipe_return == 1){
                $sup = SuppliesVariant::find($value['supplies_variant_id']);
                $spr = Supplier::find($sup->supplier_id);
                
                $logNotes = 'Penghapusan data produk bermasalah retur supplier ' . $spr->supplier_name;
                $logCategory = 1;
                $logType = 2;

                $itemId = $sup->supplies_id;
            } elseif ($pi->tipe_return == 2){
                $logNotes = 'Penghapusan data produk bermasalah retur pelanggan';
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
        }
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
            $value->stock = (new ProductStock())->getProductStock(["product_variant_id" => $value->product_variant_id]);
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
