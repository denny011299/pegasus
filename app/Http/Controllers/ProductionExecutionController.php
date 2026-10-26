<?php

namespace App\Http\Controllers;

use App\Models\{Product, ProductVariant, ProductionExecutionDocument, ProductionOutputReport, ProductionPlanning,
    ProductionPlanningItem, ProductionWorkOrder, Staff, SuppliesStock, Unit};
use App\Support\{DataTableParams, ProductUnitStock, ProductionExecution};
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Throwable;

class ProductionExecutionController extends Controller
{
    public function index(Request $request)
    {
        // WO bukan halaman operasional terpisah — list di tab Job Order PP.
        // Route ini hanya untuk monitor fullscreen.
        if (! $request->boolean('monitor')) {
            return redirect()->route('productionPlanning', ['tab' => 'job']);
        }

        return view('Backoffice.Production.WorkOrders', ['monitor' => true]);
    }

    public function listing(Request $request)
    {
        $dt = DataTableParams::from($request->all());
        try { $wh = ProductionExecution::warehouse(); }
        catch (\RuntimeException $e) { abort(403, $e->getMessage()); }
        $base = ProductionWorkOrder::where('warehouse_id', $wh)->where('status', 1);
        // light=1 (Job tab pagination): skip COUNT total gudang — hemat 1 query berat.
        $light = $request->boolean('light');
        $total = $light ? 0 : (clone $base)->count();
        if ($request->filled('state')) {
            $base->where('execution_status', $request->state);
        } elseif ($request->boolean('active_only')) {
            // Job Order tab: WO aktif = masih in production (done = Completed)
            $base->where('execution_status', 'inprod');
        }
        if ($request->filled('line')) $base->where('production_line', $request->line);
        if ($request->filled('pic_staff_id')) $base->where('pic_staff_id', (int) $request->pic_staff_id);
        if ($request->filled('product_variant_id')) {
            $vid = (int) $request->product_variant_id;
            $base->whereExists(function ($q) use ($vid) {
                $q->selectRaw('1')
                    ->from('production_planning_items as ppi')
                    ->whereColumn('ppi.production_work_order_id', 'production_work_orders.production_work_order_id')
                    ->where('ppi.status', 1)
                    ->where('ppi.product_variant_id', $vid);
            });
        }
        // Teks cari produk/SKU (filter Job Order) — juga cocokkan wo_number via search.
        $productQ = trim((string) ($request->input('q') ?: ''));
        if ($productQ !== '') {
            $like = '%'.$productQ.'%';
            $base->where(function ($q) use ($like) {
                $q->where('wo_number', 'like', $like)
                    ->orWhereExists(function ($sq) use ($like) {
                        $sq->selectRaw('1')
                            ->from('production_planning_items as ppi')
                            ->whereColumn('ppi.production_work_order_id', 'production_work_orders.production_work_order_id')
                            ->where('ppi.status', 1)
                            ->where(function ($w) use ($like) {
                                $w->where('ppi.sku', 'like', $like)
                                    ->orWhere('ppi.product_name', 'like', $like);
                            });
                    });
            });
        }
        if ($request->filled('date_from')) {
            $base->where('wo_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $base->where('wo_date', '<=', $request->date_to);
        }
        if ($dt['search'] !== '' && $productQ === '') $base->where(function ($q) use ($dt) {
            $q->where('wo_number', 'like', '%'.$dt['search'].'%')->orWhere('production_line', 'like', '%'.$dt['search'].'%')
                ->orWhereIn('pic_staff_id', Staff::where('staff_name', 'like', '%'.$dt['search'].'%')->select('staff_id'));
        });
        $filtered = (clone $base)->count();
        if ($light) {
            $total = $filtered;
        }
        $sort = ['wo_number', 'wo_date', 'pic_staff_id', 'production_line', 'execution_status'][(int) $request->input('order.0.column', 1)] ?? 'wo_date';
        $direction = $request->input('order.0.dir') === 'asc' ? 'asc' : 'desc';
        $rows = $base->orderBy($sort, $direction)->orderByDesc('production_work_order_id')->skip($dt['start'])->take($dt['length'])
            ->get([
                'production_work_order_id', 'production_planning_id', 'pic_staff_id', 'wo_number', 'wo_date',
                'production_line', 'execution_status', 'production_completed_at', 'closed_at',
            ]);
        $staff = Staff::whereIn('staff_id', $rows->pluck('pic_staff_id'))->pluck('staff_name', 'staff_id');
        $pps = ProductionPlanning::whereIn('production_planning_id', $rows->pluck('production_planning_id'))
            ->get(['production_planning_id', 'pp_number', 'spkp_number'])
            ->keyBy('production_planning_id');
        $payload = [
            'draw' => $dt['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(function ($wo) use ($staff, $pps) {
                $pp = $pps->get($wo->production_planning_id);

                return [
                    'id' => $wo->production_work_order_id,
                    'code' => $wo->wo_number,
                    'date' => (string) $wo->wo_date,
                    'pic' => $staff[$wo->pic_staff_id] ?? '-',
                    'line' => $wo->production_line ?: '-',
                    'state' => $wo->execution_status,
                    'pp_number' => $pp->pp_number ?? '-',
                    'spkp_number' => $pp->spkp_number ?? null,
                    'production_planning_id' => (int) $wo->production_planning_id,
                    'print_url' => url('/printProductionWorkOrder/'.$wo->production_work_order_id),
                    'completed_at' => $wo->production_completed_at,
                    'closed_at' => $wo->closed_at,
                ];
            })->values(),
        ];
        // Monitor fullscreen saja yang butuh daftar lini (hindari query ekstra di tab Job PP).
        if ($request->boolean('with_lines')) {
            $payload['lines'] = ProductionWorkOrder::where('warehouse_id', $wh)
                ->where('status', 1)
                ->whereNotNull('production_line')
                ->distinct()
                ->pluck('production_line');
        }

        return response()->json($payload);
    }

    public function detail(int $id)
    {
        try {
            $wo = ProductionExecution::workOrder($id);
            $items = ProductionPlanningItem::where('production_work_order_id', $id)->where('status', 1)->get();
            $totals = ProductionExecution::totals($id);

            // Batch master — hindari N+1 ProductVariant/Product/Unit per baris item.
            $variantIds = $items->pluck('product_variant_id')->map(fn ($v) => (int) $v)->unique()->filter()->values()->all();
            $variants = $variantIds
                ? ProductVariant::whereIn('product_variant_id', $variantIds)->get()->keyBy('product_variant_id')
                : collect();
            $productIds = $variants->pluck('product_id')->map(fn ($v) => (int) $v)->unique()->filter()->values()->all();
            $products = $productIds
                ? Product::whereIn('product_id', $productIds)->get()->keyBy('product_id')
                : collect();

            $unitIdSet = [];
            foreach ($items as $item) {
                $unitIdSet[(int) $item->unit_id] = true;
                $variant = $variants->get((int) $item->product_variant_id);
                if (! $variant) {
                    continue;
                }
                foreach (ProductUnitStock::orderedUnitIds((int) $variant->product_variant_id) as $uid) {
                    $unitIdSet[(int) $uid] = true;
                }
                $product = $products->get((int) $variant->product_id);
                if (! $product) {
                    continue;
                }
                $unitIdSet[(int) $product->unit_id] = true;
                foreach (json_decode($product->product_unit ?? '[]', true) ?: [] as $uid) {
                    $unitIdSet[(int) $uid] = true;
                }
            }
            $unitsById = $unitIdSet
                ? Unit::whereIn('unit_id', array_keys($unitIdSet))->where('status', 1)->get()->keyBy('unit_id')
                : collect();

            $palletCache = [];
            $rows = $items->map(function ($item) use ($totals, $variants, $products, $unitsById, &$palletCache) {
                $variant = $variants->get((int) $item->product_variant_id);
                $product = $variant ? $products->get((int) $variant->product_id) : null;
                $candidateIds = [];
                if ($variant) {
                    $candidateIds = ProductUnitStock::orderedUnitIds((int) $variant->product_variant_id);
                }
                $candidateIds[] = (int) $item->unit_id;
                if ($product) {
                    $candidateIds[] = (int) $product->unit_id;
                    foreach (json_decode($product->product_unit ?? '[]', true) ?: [] as $uid) {
                        $candidateIds[] = (int) $uid;
                    }
                }
                $units = collect(array_unique(array_filter($candidateIds)))
                    ->map(fn ($uid) => $unitsById->get((int) $uid))
                    ->filter()
                    ->filter(fn ($u) => $variant && ProductUnitStock::canConvertUnits(
                        (int) $u->unit_id,
                        (int) $item->unit_id,
                        (int) $variant->product_variant_id
                    ));
                $pkey = ((int) $item->product_variant_id).':'.((int) $item->unit_id);
                if (! isset($palletCache[$pkey])) {
                    $palletCache[$pkey] = ProductionExecution::resolvePalletMeta(
                        $variant,
                        $product,
                        (int) $item->unit_id
                    );
                }
                $pallet = $palletCache[$pkey];
                $actual = round($totals[$item->ppi_id] ?? 0, 4);

                return [
                    'id' => $item->ppi_id,
                    'name' => $item->product_name,
                    'target' => (float) $item->qty,
                    'unit_id' => $item->unit_id,
                    'unit' => $item->unit_label,
                    'actual' => $actual,
                    'excess' => max(0, $actual - $item->qty),
                    'result' => $actual + 0.0001 >= $item->qty ? 'OK' : 'NOK',
                    'qty_per_pallet' => $pallet['qty'],
                    'pallet_unit' => $pallet['base_unit_name'] ?: null,
                    'units' => $units->map(fn ($u) => [
                        'id' => $u->unit_id,
                        'name' => $u->unit_short_name ?: $u->unit_name,
                    ])->values(),
                ];
            });

            $user = ProductionExecution::actor();
            $picName = Staff::where('staff_id', $wo->pic_staff_id)->value('staff_name');
            $ppNumber = ProductionPlanning::where('production_planning_id', $wo->production_planning_id)
                ->value('pp_number');
            $docs = ProductionExecutionDocument::where('production_work_order_id', $id)->orderBy('id')->get()->map(function ($doc) {
                $out = $doc->toArray();
                $out['signers'] = collect($doc->signatures)->map(fn ($s) => ['name' => $s['name'], 'staff_id' => $s['staff_id']])->all();
                unset($out['signatures']);

                return $out;
            });
            $isPicOrElevated = ((int) $wo->pic_staff_id === (int) $user->staff_id)
                || in_array((int) $user->role_id, [-1, 4], true);
            $materialsReady = ProductionExecution::materialsReadyForReport($id);

            return response()->json([
                'wo' => $wo,
                'pic' => $picName,
                'pp' => $ppNumber,
                'items' => $rows,
                'documents' => $docs,
                'history' => DB::table('production_execution_events as e')
                    ->leftJoin('staffs as s', 's.staff_id', '=', 'e.staff_id')
                    ->where('production_work_order_id', $id)
                    ->orderByDesc('e.id')
                    ->get(['e.action', 'e.occurred_at', 's.staff_name']),
                'material_balance' => ProductionExecution::materialBalance($id),
                'materials_ready' => $materialsReady,
                // Permission isi hasil; tombol aktif hanya jika materials_ready (FE + assert backend).
                'can_report' => $isPicOrElevated && ! $wo->closed_at,
                'can_materials' => $isPicOrElevated && ! $wo->closed_at && ($wo->execution_status === 'inprod'),
                // Serah terima / kembalikan sisa setelah target produksi tercapai.
                'can_return_materials' => $isPicOrElevated && ! $wo->closed_at && (bool) $wo->production_completed_at,
                'can_ops' => ProductionExecution::isOps((int) $wo->warehouse_id),
                'can_qc' => ProductionExecution::isQc((int) $wo->warehouse_id),
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => -1, 'message' => $e->getMessage()], 403);
        }
    }

    /** Saran bahan dari BOM item WO + stok tersedia (untuk form Ambil Bahan). */
    public function materialRecipe(int $id)
    {
        try {
            return response()->json(ProductionExecution::materialRecipe($id));
        } catch (\RuntimeException $e) {
            return response()->json(['status' => -1, 'message' => $e->getMessage()], 403);
        }
    }

    /** DataTables: STB/KBB menunggu ACC Ops atau QC. */
    public function pendingMaterials(Request $request)
    {
        $dt = DataTableParams::from($request->all());
        try {
            $wh = ProductionExecution::warehouse();
        } catch (\RuntimeException $e) {
            abort(403, $e->getMessage());
        }

        $base = ProductionExecutionDocument::query()
            ->where('warehouse_id', $wh)
            ->whereIn('type', ['material_issue', 'material_return'])
            ->whereIn('document_status', ['awaiting_ops', 'awaiting_qc']);

        $total = (clone $base)->count();
        if ($request->filled('stage')) {
            $stage = (string) $request->stage;
            if (in_array($stage, ['awaiting_ops', 'awaiting_qc'], true)) {
                $base->where('document_status', $stage);
            }
        }
        // Range tanggal shared (sama tab Planning/Job) — pakai tgl dokumen.
        if ($request->filled('date_from')) {
            $base->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $base->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('wo_id')) {
            $base->where('production_work_order_id', (int) $request->wo_id);
        }
        if ($request->filled('pic_staff_id')) {
            $picId = (int) $request->pic_staff_id;
            $base->whereIn('production_work_order_id', ProductionWorkOrder::query()
                ->where('pic_staff_id', $picId)
                ->select('production_work_order_id'));
        }
        if ($dt['search'] !== '') {
            $like = '%'.$dt['search'].'%';
            $base->where(function ($q) use ($like) {
                $q->where('number', 'like', $like)
                    ->orWhereIn('production_work_order_id', ProductionWorkOrder::query()
                        ->where('wo_number', 'like', $like)
                        ->select('production_work_order_id'));
            });
        }
        $filtered = (clone $base)->count();
        $rows = $base->orderByDesc('id')->skip($dt['start'])->take($dt['length'])->get();
        $woIds = $rows->pluck('production_work_order_id')->unique()->all();
        $wos = ProductionWorkOrder::whereIn('production_work_order_id', $woIds)
            ->get(['production_work_order_id', 'wo_number', 'pic_staff_id', 'production_planning_id', 'production_line'])
            ->keyBy('production_work_order_id');
        $staff = Staff::whereIn('staff_id', $wos->pluck('pic_staff_id'))->pluck('staff_name', 'staff_id');
        $pps = ProductionPlanning::whereIn('production_planning_id', $wos->pluck('production_planning_id'))
            ->pluck('pp_number', 'production_planning_id');
        $user = ProductionExecution::actor();
        $canOps = ProductionExecution::isOps($wh);
        $canQc = ProductionExecution::isQc($wh);

        return response()->json([
            'draw' => $dt['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $rows->map(function ($doc) use ($wos, $staff, $pps, $canOps, $canQc) {
                $wo = $wos->get($doc->production_work_order_id);

                return [
                    'id' => $doc->id,
                    'wo_id' => (int) $doc->production_work_order_id,
                    'number' => $doc->number,
                    'type' => $doc->type,
                    'stage' => $doc->document_status,
                    'wo_number' => $wo->wo_number ?? '-',
                    'pic' => $staff[$wo->pic_staff_id ?? 0] ?? '-',
                    'pp_number' => $pps[$wo->production_planning_id ?? 0] ?? '-',
                    'line' => $wo->production_line ?: '-',
                    'items' => collect($doc->items ?? [])->values()->all(),
                    'items_count' => count($doc->items ?? []),
                    'confirmed_at' => (string) ($doc->confirmed_at ?? $doc->created_at ?? ''),
                    'ops_approved_at' => (string) ($doc->ops_approved_at ?? ''),
                    'qc_approved_at' => (string) ($doc->qc_approved_at ?? ''),
                    'can_ops' => $canOps && $doc->document_status === 'awaiting_ops',
                    'can_qc' => $canQc && $doc->document_status === 'awaiting_qc',
                    'print_url' => url('/printProductionDocument/'.$doc->id.'/form'),
                ];
            })->values(),
        ]);
    }

    public function mutate(Request $request, int $id, string $action)
    {
        $ability = in_array($action, ['ops','qc','close'], true) ? 'others' : 'edit';
        if (! \App\Support\RoleAccess::can(Session::get('user'), 'Produksi', $ability)) abort(403);
        try {
            $data = $request->all();
            $result = match ($action) {
                'cancel_material' => ProductionExecution::cancelMaterial($id),
                'no_materials' => ProductionExecution::noMaterials($id),
                'report' => ProductionExecution::report($id, $data),
                'material_issue', 'material_return' => ProductionExecution::requestMaterial($id, $action, $data),
                'ops', 'qc' => ProductionExecution::approve($id, $action, $data),
                'hand_pallet', 'palletized' => ProductionExecution::acknowledge($id, $action),
                'close' => ProductionExecution::close($id, $data),
                default => throw new \RuntimeException('Aksi tidak valid.'),
            };
            return response()->json($result);
        } catch (\RuntimeException $e) {
            return response()->json(['status' => -1, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            report($e);
            return response()->json(['status' => -1, 'message' => 'Gagal menyimpan. Tidak ada perubahan stok yang diterapkan.'], 500);
        }
    }

    public function materials(Request $request)
    {
        try { ProductionExecution::warehouse(); }
        catch (\RuntimeException $e) { abort(403, $e->getMessage()); }
        $rows = SuppliesStock::withoutGlobalScope('active_warehouse')->from('supplies_stocks as ss')
            ->join('supplies as s', 's.supplies_id', '=', 'ss.supplies_id')->join('units as u', 'u.unit_id', '=', 'ss.unit_id')
            ->where('ss.warehouse_id', (int) Session::get('active_warehouse_id'))->where('ss.status', 1)->where('s.status', 1)
            ->where('s.supplies_name', 'like', '%'.trim((string) $request->input('search', '')).'%')
            ->groupBy('s.supplies_id', 's.supplies_name', 'u.unit_id', 'u.unit_name')->limit(50)
            ->selectRaw('s.supplies_id, s.supplies_name, u.unit_id, u.unit_name, SUM(ss.ss_stock) AS available')->get();
        return response()->json($rows->map(fn ($r) => ['id' => $r->supplies_id.':'.$r->unit_id,
            'text' => $r->supplies_name.' / '.$r->unit_name.' (stok '.$r->available.')']));
    }

    public function printDocument(int $id, string $kind = 'form')
    {
        $doc = ProductionExecutionDocument::findOrFail($id);
        try { $wo = ProductionExecution::workOrder((int) $doc->production_work_order_id); }
        catch (\RuntimeException $e) { abort(403, $e->getMessage()); }
        if ($kind === 'tally' && ! $doc->tally_number) abort(422, 'Tally tersedia setelah seluruh approval Form Gudang.');
        if (! in_array($kind, ['form', 'tally'], true)) abort(404);
        $settings = (new \App\Models\Setting())->getSetting(['select' => ['company_name', 'company_address', 'logo', 'company_logo']]);
        return Pdf::loadView('Backoffice.PDF.ProductionExecutionDocument', [
            'doc' => $doc, 'wo' => $wo, 'pp' => ProductionPlanning::findOrFail($wo->production_planning_id),
            'tally' => $kind === 'tally', 'company' => $settings,
            'logo' => ProductionWorkOrder::resolveCompanyLogoBase64($settings['logo'] ?? null),
            'reports' => ProductionOutputReport::where('production_work_order_id', $wo->production_work_order_id)->orderBy('id')->get(),
        ])->setPaper('a5', 'landscape')->stream(($kind === 'tally' ? $doc->tally_number : $doc->number).'.pdf');
    }

    /** FORM-OPS-08 — Laporan Serah Terima Bahan Mentah (setelah produksi selesai). */
    public function printMaterialHandover(int $id)
    {
        try {
            $payload = ProductionExecution::materialHandoverPayload($id);
        } catch (\RuntimeException $e) {
            abort(403, $e->getMessage());
        }
        $settings = (new \App\Models\Setting())->getSetting(['select' => ['company_name', 'company_address', 'logo', 'company_logo']]);
        $wo = $payload['wo'];
        $safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $wo->wo_number);

        return Pdf::loadView('Backoffice.PDF.ProductionMaterialHandover', [
            'wo' => $wo,
            'pp' => $payload['pp'],
            'items' => $payload['items'],
            'pic_name' => $payload['pic_name'],
            'company' => $settings,
            'logo' => ProductionWorkOrder::resolveCompanyLogoBase64($settings['logo'] ?? null),
        ])->setPaper('a5', 'landscape')->stream('Serah_Terima_Bahan_'.$safe.'.pdf');
    }
}
