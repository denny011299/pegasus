<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;

class ProductIssues extends Model
{
    protected $table = "product_issues";
    protected $primaryKey = "pi_id";
    public $timestamps = true;
    public $incrementing = true;

    function getProductIssues($data = [])
    {
        $data = array_merge([
            "pi_type"=>null,
            "date"=>null,
            "all"=>null,
            "tipe_return"=>null,//default = product
        ], $data);

        $result = self::where('status', '>=', 1);
        if($data["pi_type"])$result->where('pi_type','=',$data["pi_type"]);
        if($data["tipe_return"])$result->where('tipe_return','=',$data["tipe_return"]);
        
        if($data["date"]) {
            if (is_array($data["date"]) && count($data["date"]) === 2) {
                // Jika date adalah array [start_date, end_date]]
                $startDate = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][0])->format('Y-m-d');
                $endDate   = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"][1])->format('Y-m-d');
                $result->whereBetween('created_at', [$startDate, $endDate]);
            } else {
                // Jika date hanya satu nilai
                $date = $data["date"];
                if (!\Carbon\Carbon::hasFormat($data["date"], 'Y-m-d'))$date = \Carbon\Carbon::createFromFormat('d-m-Y', $data["date"])->format('Y-m-d');
                
                $result->where('created_at', '=', $date);
            }
        }

        $result->orderBy('status', 'asc')->orderBy('created_at', 'desc');
       
        $result = $result->get();
        foreach ($result as $key => $value) {
            // $pvr = ProductVariant::find($value->product_variant_id);
            // $sup = Product::find($pvr->product_id);
            // $value->product_variant_name = $pvr->product_variant_name;
            // $value->pr_sku = $pvr->product_variant_sku;
            // $u = Unit::find($value->unit_id);
            // $value->unit_name = $u->unit_name;
            if ($value->ref_num > 0){
                $inv = PurchaseOrderDetailInvoice::find($value->ref_num);
                $value->poi_code = $inv->poi_code ?? "";
                $po = PurchaseOrder::find($inv->po_id);
                $sup = Supplier::where('supplier_id', $po->po_supplier)->first();
                $value->supplier_name = $sup->supplier_name ?? "";
            }
            if ($value->po_id > 0) $value->po_number = PurchaseOrder::find($value['po_id'])->po_number;
            $value->items = (new ProductIssuesDetail())->getProductIssuesDetail(["pi_id" => $value->pi_id, "tipe_return" => $value->tipe_return]);
            $value->created_by_name = $value->created_by ? (Staff::find($value->created_by)->staff_name ?? '-') : '-';
            $value->acc_by_name = $value->acc_by ? (Staff::find($value->acc_by)->staff_name ?? '-') : '-';
        }
 
        return $result;
    }

    function insertProductIssues($data)
    {   
        // $m = ProductVariant::find($data["product_variant_id"]);
        // $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
        // // return $m;  

        // // Return to Supplier
        // $stocks = $s->ps_stock ?? 0;
        // if ($data["tipe_return"] == 1) {
        //     if ($stocks - $data["pi_qty"] > 0) {
        //         $stocks -= $data["pi_qty"];
        //     } else {
        //         return -1;
        //     }
        // }
        
        // // Return from customer
        // elseif ($data["tipe_return"] == 2) {
        //     $stocks += $data["pi_qty"];
        // }

        // $s->ps_stock = $stocks;

        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');   
        $t = new self();
        $t->pi_code   = $this->generateProductIssueID();
        $t->pi_type = $data["pi_type"];
        $t->ref_num = $data["ref_num"] ?? 0;
        $t->po_id = $data["po_id"] ?? 0;
        $t->pi_date = $pi_date;
        $t->pi_notes = $data["pi_notes"];    
        $t->tipe_return = $data["tipe_return"];     
        $t->pi_img = $data["pi_img"] ?? null;
        $t->status = $data['status'] ?? 1;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        // $t->pi_qty = $data["pi_qty"];   
        // $t->product_variant_id = $data["product_variant_id"];
        // $t->unit_id = $data["unit_id"]; 
        $t->save(); 
        // $m->save();
        // $s->save();

        return $t;  
    }

    function updateProductIssues($data)
    {
        $t =  self::find($data["pi_id"]);
        $pi_date = Carbon::createFromFormat('d-m-Y', $data['pi_date'])->format('Y-m-d');
        // $m = ProductVariant::find($data["product_variant_id"]);
        // $s = ProductStock::where('product_variant_id','=',$m->product_variant_id)->where('unit_id','=',$data["unit_id"])->first();
  
        // // return $m;  
        // if($m->pi_qty != $data["pi_qty"]){
        //     // kembalikan stock ke kondisi sebelum update
        //     if($data["tipe_return"]  == 1){
        //         $s->ps_stock += $t->pi_qty;
        //     }elseif($data["tipe_return"] == 2){
                
        //         $s->ps_stock -= $t->pi_qty;
        //     }
        //     $s->save();

        
        //       // Return to Supplier
        //     if ($data["tipe_return"] == 1) {
        //         if ($s->ps_stock - $data["pi_qty"] > 0) {
        //             $s->ps_stock -= $data["pi_qty"];
        //         } else {
        //             return -1;
        //         }
        //     }
        //     // Return from customer
        //     elseif ($data["tipe_return"] == 2) {
        //         $s->ps_stock += $data["pi_qty"];
        //     }

        // } 
        $t->pi_code   = $data['pi_code'];
        $t->pi_type = $data["pi_type"];
        $t->po_id = $data["po_id"] ?? 0;
        $t->ref_num = $data["ref_num"];
        $t->pi_date = $pi_date;
        $t->pi_notes = $data["pi_notes"];
        $t->tipe_return = $data["tipe_return"];
        $incomingStatus = isset($data['status']) ? (int) $data['status'] : null;
        if ($incomingStatus !== null && $incomingStatus !== 3) {
            $t->status = $incomingStatus;
        } elseif ((int) ($t->status ?? 0) === 3) {
            // Jika sebelumnya ditolak lalu direvisi, kembalikan ke antrean ACC.
            $t->status = 1;
            if (Schema::hasColumn($t->getTable(), 'acc_by')) {
                $t->acc_by = null;
            }
        } else {
            $t->status = $incomingStatus ?? 1;
        }
        if (isset($data['pi_img'])) $t->pi_img = $data["pi_img"];
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        // $t->pi_qty = $data["pi_qty"];   
        // $t->product_variant_id = $data["product_variant_id"];
        // $t->unit_id = $data["unit_id"];
        $t->save(); 
        // $s->save();
        // $m->save();

        return $t;  
    }

    function deleteProductIssues($data)
    {
        $t = self::find($data["pi_id"]);
        if (isset($t->ref_num) && $t->ref_num != 0){
            $inv = PurchaseOrderDetailInvoice::find($t->ref_num);
            $po = PurchaseOrder::find($inv->po_id);
            if ($po->tt_id != null) {
                return -1;
            }
        }
        $t->status = 3;
        $t->created_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $t->save();

        // $m = ProductVariant::find($t->product_variant_id);
        // $s = ProductStock::where('product_variant_id',$m->product_variant_id)->where('unit_id',$t->unit_id)->first();
        // if($t->tipe_return == 1){
        //     $s->ps_stock += $t->pi_qty;
        // }else if($t->tipe_return == 2){
        //     $s->ps_stock -= $t->pi_qty;
        // }
        // $s->save();
    }

    function accProductIssues($data){
        $pi = ProductIssues::find($data['pi_id']);
        $pi->status = 2;
        $pi->acc_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $pi->save();
    }

    function declineProductIssues($data){
        $pi = ProductIssues::find($data['pi_id']);
        $pi->status = 3;
        $pi->acc_by = Session::get('user') ? Session::get('user')->staff_id : null;
        $pi->save();
    }

    function generateProductIssueID()
    {
        $id = self::max('pi_id');
        if (is_null($id)) $id = 0;
        $id++;
        return "PI" . str_pad($id, 4, "0", STR_PAD_LEFT);
    }

    /**
     * Laporan retur produk dari Product Issue — pengembalian Armada (tipe_return = 2).
     * Hanya transaksi yang sudah disetujui (status header = 2) agar selaras dengan mutasi stok setelah ACC.
     *
     * @param  array{date?: mixed, product_variant_id?: int|null}  $data
     * @return array<int, array<string, mixed>>
     */
    function getArmadaReturnReport(array $data = []): array
    {
        $data = array_merge([
            'date' => null,
            'product_variant_id' => null,
        ], $data);

        $query = DB::table('product_issues_details as pid')
            ->join('product_issues as pi', 'pi.pi_id', '=', 'pid.pi_id')
            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'pid.item_id')
            ->join('products as pr', 'pr.product_id', '=', 'pv.product_id')
            ->leftJoin('units as u', 'u.unit_id', '=', 'pid.unit_id')
            ->where('pi.tipe_return', 2)
            ->where('pi.status', 2)
            ->where('pid.status', '>=', 1)
            ->select(
                'pid.pid_id',
                'pid.pi_id',
                'pid.item_id as product_variant_id',
                'pid.pid_qty',
                'u.unit_name',
                'pi.pi_code',
                'pi.pi_date',
                'pi.pi_notes',
                'pv.product_variant_name',
                'pr.product_name'
            );

        if (!empty($data['product_variant_id'])) {
            $query->where('pid.item_id', (int) $data['product_variant_id']);
        }

        if (!empty($data['date'])) {
            if (is_array($data['date']) && count($data['date']) === 2) {
                $startRaw = trim((string) ($data['date'][0] ?? ''));
                $endRaw = trim((string) ($data['date'][1] ?? ''));
                $startDate = null;
                $endDate = null;
                if ($startRaw !== '') {
                    $startDate = Carbon::hasFormat($startRaw, 'Y-m-d')
                        ? $startRaw
                        : Carbon::createFromFormat('d-m-Y', $startRaw)->format('Y-m-d');
                }
                if ($endRaw !== '') {
                    $endDate = Carbon::hasFormat($endRaw, 'Y-m-d')
                        ? $endRaw
                        : Carbon::createFromFormat('d-m-Y', $endRaw)->format('Y-m-d');
                }
                if ($startDate && $endDate) {
                    $query->whereBetween('pi.pi_date', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('pi.pi_date', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('pi.pi_date', '<=', $endDate);
                }
            } else {
                $dateRaw = trim((string) $data['date']);
                if ($dateRaw !== '') {
                    $date = Carbon::hasFormat($dateRaw, 'Y-m-d')
                        ? $dateRaw
                        : Carbon::createFromFormat('d-m-Y', $dateRaw)->format('Y-m-d');
                    $query->whereDate('pi.pi_date', $date);
                }
            }
        }

        $rows = $query->orderBy('pi.pi_date', 'desc')->orderBy('pi.pi_id', 'desc')->orderBy('pid.pid_id', 'desc')->get();

        $grouped = [];
        foreach ($rows as $row) {
            $key = (int) $row->product_variant_id;
            $itemName = trim(($row->product_name ?? '') . ' ' . ($row->product_variant_name ?? ''));
            if ($itemName === '') {
                $itemName = '-';
            }

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'product_variant_id' => $key,
                    'item_name' => $itemName,
                    'transaction_count' => 0,
                    'details' => [],
                ];
            }

            $grouped[$key]['transaction_count'] += 1;
            $grouped[$key]['details'][] = [
                'pi_date' => $row->pi_date,
                'pi_code' => $row->pi_code,
                'qty' => (int) $row->pid_qty,
                'unit_name' => $row->unit_name,
                'pi_notes' => $row->pi_notes ?? '',
            ];
        }

        return array_values($grouped);
    }
}

