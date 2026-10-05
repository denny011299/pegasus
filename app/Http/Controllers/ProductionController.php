<?php

namespace App\Http\Controllers;

use App\Models\Bom;
use App\Models\BomDetail;
use App\Models\LogStock;
use App\Models\Production;
use App\Models\ProductionDetails;
use App\Models\ProductionPhoto;
use App\Models\ProductionPlanning;
use App\Models\ProductionSkala;
use App\Models\ProductionWorkOrder;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\ShipmentShortageDocument;
use App\Models\Staff;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use App\Models\SuppliesVariant;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Support\ProductionPendingStockRestorer;
use App\Support\ProductUnitStock;
use App\Support\UnitRollUp;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Throwable;

class ProductionController extends Controller
{
    // BOM
    public function bom()
    {
        return view('Backoffice.Production.Bom');
    }

    function getBom(Request $req)
    {
        // Server-side DataTables (list Resep Bahan Mentah)
        if ($req->has('draw')) {
            return response()->json((new Bom())->getBomDataTable($req->all()));
        }

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
        $bahan = json_decode($req->bahan, true) ?: [];
        if ($msg = Supplies::rejectTradingSuppliesIds(collect($bahan)->pluck('supplies_id'))) {
            return [
                'status' => -1,
                'message' => 'Bahan Trading tidak boleh di resep (stok ada di produk). '.$msg,
            ];
        }

        // Pengecekan unique resep
        $bom = Bom::where('product_id', $data['product_id'])->where('status', 1)->get();
        if (count($bom) > 0) {
            return [
                "status" => -1,
                "message" => "Resep produk ini sudah ada. Mohon pilih produk lainnya"
            ];
        }
        $bom_id = (new Bom())->insertBom($data);
        foreach ($bahan as $key => $value) {
            $value['bom_id'] = $bom_id;
            (new BomDetail())->insertBomDetail($value);
        }
    }

    function updateBom(Request $req)
    {
        $data = $req->all();
        $bahan = json_decode($req->bahan, true) ?: [];
        if ($msg = Supplies::rejectTradingSuppliesIds(collect($bahan)->pluck('supplies_id'))) {
            return [
                'status' => -1,
                'message' => 'Bahan Trading tidak boleh di resep (stok ada di produk). '.$msg,
            ];
        }
        $list_id_detail = [];
        $bom_id = (new Bom())->updateBom($data);
        foreach ($bahan as $key => $value) {
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

    /** Production Planning FE shell — refine via /productionPlanning. */
    public function productionPlanning()
    {
        return view('Backoffice.Production.Production_Planning');
    }

    /** Mode View: PP mode fullscreen tanpa sidebar & navbar. */
    public function productionPlanningView()
    {
        return view('Backoffice.Production.Production_Planning', [
            'ppFullscreen' => true,
        ]);
    }

    // ── Master Skala ───────────────────────────────────────────────
    public function productionSkala()
    {
        return view('Backoffice.Production.Production_Skala');
    }

    function getProductionSkala(Request $req)
    {
        $data = (new ProductionSkala())->getProductionSkala([
            'code' => $req->code,
            'name' => $req->name,
        ]);

        return response()->json($data);
    }

    function insertProductionSkala(Request $req)
    {
        return (new ProductionSkala())->insertProductionSkala($req->all());
    }

    function updateProductionSkala(Request $req)
    {
        return (new ProductionSkala())->updateProductionSkala($req->all());
    }

    function deleteProductionSkala(Request $req)
    {
        return (new ProductionSkala())->deleteProductionSkala($req->all());
    }

    // ── Production Planning CRUD ─────────────────────────────────
    function getProductionPlanning(Request $req)
    {
        return response()->json((new ProductionPlanning())->paginateList($req->all()));
    }

    /** Polling ringan KPI + badge tab (tanpa payload DataTables). */
    function getProductionPlanningLive()
    {
        return response()->json(ProductionPlanning::liveSummary());
    }

    /** 4 kartu stage bawah — 1 request, ikut filter tanggal. */
    function getProductionPlanningStageCards(Request $req)
    {
        return response()->json(ProductionPlanning::stageMiniCards($req->all()));
    }

    function getProductionPlanningDetail(Request $req)
    {
        $detail = (new ProductionPlanning())->getDetail($req->production_planning_id ?? $req->id);
        if (! $detail) {
            return response()->json(['status' => -1, 'message' => 'Planning tidak ditemukan'], 404);
        }

        return response()->json($detail);
    }

    /**
     * Cek stok gudang (FG multi-satuan) + bahan mentah untuk Release draft.
     * Satu batch snapshot + satu validateProductionItems — untuk modal async.
     */
    function checkProductionPlanningRelease(Request $req)
    {
        $ppId = (int) $req->input('production_planning_id', $req->input('id', 0));
        $whId = (int) (ProductionPlanning::activeWarehouseId() ?: 0);
        if ($ppId <= 0 || $whId <= 0) {
            return response()->json([
                'status' => -1,
                'can_release' => false,
                'message' => 'Planning / gudang aktif tidak valid',
                'items' => [],
                'shortages' => [],
            ]);
        }

        $pp = ProductionPlanning::query()
            ->where('production_planning_id', $ppId)
            ->where('warehouse_id', $whId)
            ->where('status', 1)
            ->first();
        if (! $pp || $pp->pp_status !== 'draft') {
            return response()->json([
                'status' => -1,
                'can_release' => false,
                'message' => 'Hanya draft yang dicek untuk Release',
                'materials' => [],
                'shortages' => [],
            ]);
        }

        $built = $this->buildReleaseStockCheckItems($ppId);
        if (isset($built['error'])) {
            $err = $built['error'];

            return response()->json([
                'status' => (int) ($err['status'] ?? 0),
                'can_release' => false,
                'header' => $err['header'] ?? 'Tidak bisa Release',
                'message' => $err['message'] ?? 'Validasi gagal',
                'code' => $err['code'] ?? null,
                'shortages' => $err['shortages'] ?? [],
                'materials' => [],
            ]);
        }

        $checkItems = $built['items'];
        $destinationValidation = $this->normalizeProductionDestinations($checkItems);
        if (! $destinationValidation['ok']) {
            return response()->json([
                'status' => 0,
                'can_release' => false,
                'header' => 'Gudang Tidak Valid',
                'message' => $destinationValidation['message'],
                'shortages' => [],
                'materials' => [],
            ]);
        }

        // Preview modal: agregat + per produk (tanpa validateProductionItems penuh).
        $recipeBlock = $this->validateProductionRecipeGates($checkItems);
        $bundle = $this->summarizeProductionMaterialsBundle($checkItems);
        $materials = $bundle['materials'];
        $itemMaterials = $bundle['items'];
        if ($recipeBlock !== null) {
            return response()->json([
                'status' => (int) ($recipeBlock['status'] ?? 0),
                'can_release' => false,
                'header' => $recipeBlock['header'] ?? 'Resep Tidak Valid',
                'message' => $recipeBlock['message'] ?? 'Validasi resep gagal',
                'code' => $recipeBlock['code'] ?? null,
                'bom_id' => $recipeBlock['bom_id'] ?? null,
                'shortages' => [],
                'materials' => $materials,
                'items' => $itemMaterials,
            ]);
        }

        $shortages = $this->materialsToShortages($materials);
        $allOk = $shortages === [];

        return response()->json([
            'status' => $allOk ? 1 : -1,
            'can_release' => $allOk,
            'header' => $allOk ? null : 'Stock Tidak Mencukupi',
            'message' => $allOk ? null : 'Stok bahan mentah tidak mencukupi untuk Release.',
            'shortages' => $shortages,
            'materials' => $materials,
            'items' => $itemMaterials,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $materials
     * @return array<int, array<string, mixed>>
     */
    private function materialsToShortages(array $materials): array
    {
        $shortages = [];
        foreach ($materials as $m) {
            if (! empty($m['ok'])) {
                continue;
            }
            $shortages[] = [
                'supplies_id' => $m['supplies_id'],
                'supplies_name' => $m['supplies_name'],
                'unit_id' => $m['unit_id'],
                'unit_name' => $m['unit_name'],
                'needed' => $m['needed'],
                'needed_text' => $m['needed_text'] ?? null,
                'available' => $m['available'],
                'available_text' => $m['available_text'] ?? null,
                'shortage' => $m['shortage'],
            ];
        }

        return $shortages;
    }

    /**
     * Gate resep ringan (tanpa simulasi bongkar stok) — untuk preview Release yang cepat.
     *
     * @param  array<int, array<string, mixed>>  $item
     * @return array<string, mixed>|null
     */
    private function validateProductionRecipeGates(array $item): ?array
    {
        $bahan_satuan_tidak_aktif = $this->validateProductionBomActiveUnits($item);
        if (count($bahan_satuan_tidak_aktif) > 0) {
            return [
                'status' => 0,
                'header' => 'Satuan Resep Tidak Aktif',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithInactiveUnits($item),
                'message' => 'Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: '
                    .implode(', ', $bahan_satuan_tidak_aktif),
            ];
        }

        $bahan_bukan_satuan_terkecil = $this->validateBomSuppliesSmallestUnit($item);
        if (count($bahan_bukan_satuan_terkecil) > 0) {
            return [
                'status' => 0,
                'header' => 'Gagal Insert',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithNonSmallestSupplyUnit($item),
                'message' => 'Satuan bahan mentah pada resep bukan satuan terkecil sesuai relasi. Perbarui resep terlebih dahulu: '
                    .implode(', ', $bahan_bukan_satuan_terkecil),
            ];
        }

        $bom_satuan_produk_bukan_terkecil = $this->validateBomProductSmallestUnit($item);
        if (count($bom_satuan_produk_bukan_terkecil) > 0) {
            return [
                'status' => 0,
                'header' => 'Gagal Insert',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithNonSmallestProductUnit($item),
                'message' => 'Satuan produk pada resep bukan satuan terkecil sesuai relasi produk. Perbarui resep terlebih dahulu: '
                    .implode(', ', $bom_satuan_produk_bukan_terkecil),
            ];
        }

        return null;
    }

    /**
     * Agregat kebutuhan bahan mentah + stok (batch query, read-only, tanpa insert stok kosong).
     *
     * @param  array<int, array<string, mixed>>  $item
     * @return array<int, array<string, mixed>>
     */
    private function summarizeProductionMaterials(array $item): array
    {
        return $this->summarizeProductionMaterialsBundle($item)['materials'];
    }

    /**
     * Bundle: agregat (penentu Release) + bahan per produk (untuk accordion UI).
     *
     * @param  array<int, array<string, mixed>>  $item
     * @return array{materials: array<int, array<string, mixed>>, items: array<int, array<string, mixed>>}
     */
    private function summarizeProductionMaterialsBundle(array $item): array
    {
        if ($item === []) {
            return ['materials' => [], 'items' => []];
        }

        $bomIds = collect($item)->pluck('bom_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
        $boms = Bom::query()->whereIn('bom_id', $bomIds)->where('status', 1)->get()->keyBy('bom_id');
        $detailsByBom = BomDetail::query()
            ->whereIn('bom_id', $bomIds)
            ->where('status', 1)
            ->get()
            ->groupBy('bom_id');

        $variantIds = collect($item)->pluck('product_variant_id')->map(fn ($id) => (int) $id)->unique()->filter()->values()->all();
        $packRels = ProductRelation::query()
            ->whereIn('product_variant_id', $variantIds)
            ->where('status', 1)
            ->get()
            ->groupBy('product_variant_id');

        $aggregated = [];
        $perItemParts = [];
        foreach ($item as $idx => $value) {
            $bomId = (int) ($value['bom_id'] ?? 0);
            $bom = $boms->get($bomId);
            if (! $bom) {
                continue;
            }
            $variantId = (int) $value['product_variant_id'];
            $rels = $packRels->get($variantId, collect());
            $batchCount = $this->getBatchCountFromRelations(
                (int) $value['pd_qty'],
                (int) $value['unit_id'],
                (int) $bom->bom_qty,
                (int) $bom->unit_id,
                $rels
            );
            $rows = $detailsByBom->get($bomId, collect());
            $perItemParts[$idx] = [
                'meta' => $value,
                'parts' => [],
            ];
            foreach ($rows as $bd) {
                $id = (int) $bd->supplies_id;
                $part = [
                    'supplies_id' => $id,
                    'unit_id' => (int) $bd->unit_id,
                    'qty_line' => (float) $bd->bom_detail_qty,
                    'batch' => $batchCount,
                    'variant_id' => $variantId,
                    'pd_qty' => (int) $value['pd_qty'],
                    'unit_id_pd' => (int) $value['unit_id'],
                    'bom_unit_id' => (int) $bom->unit_id,
                ];
                $perItemParts[$idx]['parts'][] = $part;
                if (! isset($aggregated[$id])) {
                    $aggregated[$id] = [
                        'supplies_id' => $id,
                        'unit_id' => (int) $bd->unit_id,
                        'needed' => 0.0,
                        'needed_parts' => [],
                    ];
                }
                $aggregated[$id]['needed_parts'][] = [
                    'qty_line' => $part['qty_line'],
                    'batch' => $part['batch'],
                    'variant_id' => $part['variant_id'],
                    'pd_qty' => $part['pd_qty'],
                    'unit_id' => $part['unit_id_pd'],
                    'bom_unit_id' => $part['bom_unit_id'],
                ];
            }
        }

        if ($aggregated === []) {
            return ['materials' => [], 'items' => []];
        }

        $supplyIds = array_keys($aggregated);
        $supplyCols = ['supplies_id', 'supplies_name'];
        if (Supplies::hasKindColumn()) {
            $supplyCols[] = 'supplies_kind';
            if (Schema::hasColumn('supplies', 'trading_product_variant_id')) {
                $supplyCols[] = 'trading_product_variant_id';
            }
        }
        $supplies = Supplies::query()->whereIn('supplies_id', $supplyIds)->get($supplyCols)->keyBy('supplies_id');
        $unitIds = collect($aggregated)->pluck('unit_id')->unique()->filter()->values()->all();
        $units = Unit::query()->whereIn('unit_id', $unitIds)->get()->keyBy('unit_id');
        $supplyRels = SuppliesRelation::query()
            ->whereIn('supplies_id', $supplyIds)
            ->where('status', 1)
            ->get()
            ->groupBy('supplies_id');
        $stocksBySupply = SuppliesStock::query()
            ->whereIn('supplies_id', $supplyIds)
            ->where('status', 1)
            ->get()
            ->groupBy('supplies_id');
        $whId = (int) (SuppliesStock::resolveWarehouseId() ?: 0);
        $hasTradingCol = Supplies::hasKindColumn();

        $availableBySupply = [];
        $nameBySupply = [];
        $unitNameBySupply = [];
        foreach ($aggregated as $suppliesId => $row) {
            $reqUnitId = (int) $row['unit_id'];
            $supply = $supplies->get($suppliesId);
            $name = (string) ($supply->supplies_name ?? ('Bahan #'.$suppliesId));
            $nameBySupply[$suppliesId] = $name;
            $unit = $units->get($reqUnitId);
            $unitNameBySupply[$suppliesId] = (string) ($unit->unit_short_name ?? $unit->unit_name ?? '');
            $isTrading = $hasTradingCol && $supply && Supplies::isTradingKind($supply->supplies_kind ?? null);
            if ($isTrading) {
                $pvId = Schema::hasColumn('supplies', 'trading_product_variant_id')
                    ? (int) ($supply->trading_product_variant_id ?? 0)
                    : 0;
                $availableBySupply[$suppliesId] = ($whId > 0 && $pvId > 0)
                    ? ProductUnitStock::totalAvailable($whId, $pvId, $reqUnitId, false, true)
                    : 0.0;
            } else {
                $ss = $stocksBySupply->get($suppliesId, collect());
                $rels = $supplyRels->get($suppliesId, collect());
                $availableBySupply[$suppliesId] = $ss->isEmpty()
                    ? 0.0
                    : $this->getTotalSuppliesStockInUnitFromRelations($reqUnitId, $ss, $rels);
            }
        }

        $calcNeeded = function (array $parts, string $name) use ($packRels): float {
            $isKemasanBesar = (bool) preg_match('/dos|pack/i', $name);
            $needed = 0.0;
            foreach ($parts as $part) {
                if ($isKemasanBesar) {
                    $rels = $packRels->get($part['variant_id'], collect());
                    $relasiKonversi = $rels->first(fn ($r) => (int) $r->pr_unit_id_2 === (int) $part['bom_unit_id']);
                    $nilaiIsiDos = $relasiKonversi ? (float) $relasiKonversi->pr_unit_value_2 : 1.0;
                    $totalPcs = $this->convertQtyBetweenUnitsFromRelations(
                        (int) $part['pd_qty'],
                        (int) $part['unit_id'],
                        (int) $part['bom_unit_id'],
                        $rels
                    );
                    $jumlahDos = floor($totalPcs / max(1.0, $nilaiIsiDos));
                    $needed += $jumlahDos * (float) $part['qty_line'];
                } else {
                    $needed += (float) $part['qty_line'] * (float) $part['batch'];
                }
            }

            return $needed;
        };

        $materials = [];
        foreach ($aggregated as $suppliesId => $row) {
            $name = $nameBySupply[$suppliesId];
            $needed = $calcNeeded($row['needed_parts'], $name);
            $available = (float) ($availableBySupply[$suppliesId] ?? 0);
            $unitName = $unitNameBySupply[$suppliesId] ?? '';
            $ok = $needed <= 0 || $available >= $needed;
            $materials[] = [
                'supplies_id' => (int) $suppliesId,
                'supplies_name' => $name,
                'unit_id' => (int) $row['unit_id'],
                'unit_name' => $unitName,
                'needed' => $needed,
                'needed_text' => $this->formatShortageQty($needed).($unitName !== '' ? ' '.$unitName : ''),
                'available' => $available,
                'available_text' => $this->formatShortageQty($available).($unitName !== '' ? ' '.$unitName : ''),
                'shortage' => max(0, $needed - $available),
                'ok' => $ok,
            ];
        }
        usort($materials, fn ($a, $b) => strcmp((string) $a['supplies_name'], (string) $b['supplies_name']));

        $itemGroups = [];
        foreach ($perItemParts as $pack) {
            $meta = $pack['meta'];
            $bySupply = [];
            foreach ($pack['parts'] as $part) {
                $sid = (int) $part['supplies_id'];
                if (! isset($bySupply[$sid])) {
                    $bySupply[$sid] = [
                        'unit_id' => (int) $part['unit_id'],
                        'parts' => [],
                    ];
                }
                $bySupply[$sid]['parts'][] = [
                    'qty_line' => $part['qty_line'],
                    'batch' => $part['batch'],
                    'variant_id' => $part['variant_id'],
                    'pd_qty' => $part['pd_qty'],
                    'unit_id' => $part['unit_id_pd'],
                    'bom_unit_id' => $part['bom_unit_id'],
                ];
            }
            $mats = [];
            $itemOk = true;
            foreach ($bySupply as $sid => $block) {
                $name = $nameBySupply[$sid] ?? ('Bahan #'.$sid);
                $needed = $calcNeeded($block['parts'], $name);
                $available = (float) ($availableBySupply[$sid] ?? 0);
                $unitName = $unitNameBySupply[$sid] ?? '';
                $ok = $needed <= 0 || $available >= $needed;
                if (! $ok) {
                    $itemOk = false;
                }
                $mats[] = [
                    'supplies_id' => (int) $sid,
                    'supplies_name' => $name,
                    'unit_id' => (int) $block['unit_id'],
                    'unit_name' => $unitName,
                    'needed' => $needed,
                    'needed_text' => $this->formatShortageQty($needed).($unitName !== '' ? ' '.$unitName : ''),
                    'available' => $available,
                    'available_text' => $this->formatShortageQty($available).($unitName !== '' ? ' '.$unitName : ''),
                    'shortage' => max(0, $needed - $available),
                    'ok' => $ok,
                ];
            }
            usort($mats, function ($a, $b) {
                $ao = ! empty($a['ok']) ? 1 : 0;
                $bo = ! empty($b['ok']) ? 1 : 0;
                if ($ao !== $bo) {
                    return $ao <=> $bo;
                }

                return strcmp((string) $a['supplies_name'], (string) $b['supplies_name']);
            });

            $itemGroups[] = [
                'ppi_id' => (int) ($meta['ppi_id'] ?? 0),
                'product_variant_id' => (int) ($meta['product_variant_id'] ?? 0),
                'product_name' => (string) ($meta['product_name'] ?? 'Produk'),
                'sku' => (string) ($meta['sku'] ?? ''),
                'qty' => (int) ($meta['pd_qty'] ?? 0),
                'unit_name' => (string) ($meta['unit_name'] ?? ''),
                'ok' => $itemOk,
                'materials' => $mats,
            ];
        }

        // Produk kurang di atas
        usort($itemGroups, function ($a, $b) {
            $ao = ! empty($a['ok']) ? 1 : 0;
            $bo = ! empty($b['ok']) ? 1 : 0;

            return $ao <=> $bo;
        });

        return ['materials' => $materials, 'items' => $itemGroups];
    }

    function insertProductionPlanning(Request $req)
    {
        return response()->json((new ProductionPlanning())->insertDraft($req->all()));
    }

    function approveProductionPlanning(Request $req)
    {
        // Cek ulang stok bahan di backend saat klik Release (bisa kepotong proses lain).
        // Tidak mutasi stok di release (hanya status PP).
        $ppId = (int) $req->input('production_planning_id', 0);
        $built = $this->buildReleaseStockCheckItems($ppId);
        if (isset($built['error'])) {
            $err = $built['error'];
            $err['materials'] = $err['materials'] ?? [];

            return response()->json($err);
        }

        $items = $built['items'];
        $destinationValidation = $this->normalizeProductionDestinations($items);
        if (! $destinationValidation['ok']) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang Tidak Valid',
                'message' => $destinationValidation['message'],
                'materials' => $this->summarizeProductionMaterials($items),
            ]);
        }

        // Fail-fast: gate resep + agregat stok (ringan) sebelum simulasi bongkar penuh.
        $materials = $this->summarizeProductionMaterials($items);
        $recipeBlock = $this->validateProductionRecipeGates($items);
        if ($recipeBlock !== null) {
            $recipeBlock['materials'] = $materials;

            return response()->json($recipeBlock);
        }
        $shortages = $this->materialsToShortages($materials);
        if ($shortages !== []) {
            return response()->json([
                'status' => -1,
                'header' => 'Stock Tidak Mencukupi',
                'message' => 'Stok bahan mentah tidak mencukupi untuk Release.',
                'shortages' => $shortages,
                'materials' => $materials,
            ]);
        }

        // Simulasi konversi/bongkar (edge case) — hanya jika preview stok sudah OK.
        $validation = $this->validateProductionItems($items);
        if ($validation) {
            $validation['materials'] = $materials;

            return response()->json($validation);
        }

        return response()->json((new ProductionPlanning())->approveDraft($req->all()));
    }

    /**
     * Susun payload mirip insertProduction dari item PP draft (resolve bom per varian).
     *
     * @return array{items: array<int, array<string, mixed>>}|array{error: array<string, mixed>}
     */
    private function buildReleaseStockCheckItems(int $ppId): array
    {
        $whId = (int) (ProductionPlanning::activeWarehouseId() ?: 0);
        if ($ppId <= 0 || $whId <= 0) {
            return [
                'error' => [
                    'status' => -1,
                    'message' => 'Planning tidak ditemukan di gudang aktif',
                ],
            ];
        }

        $pp = ProductionPlanning::query()
            ->where('production_planning_id', $ppId)
            ->where('warehouse_id', $whId)
            ->where('status', 1)
            ->first();
        if (! $pp) {
            return [
                'error' => [
                    'status' => -1,
                    'message' => 'Planning tidak ditemukan di gudang aktif',
                ],
            ];
        }
        if ($pp->pp_status !== 'draft') {
            return [
                'error' => [
                    'status' => -1,
                    'message' => 'Hanya draft yang bisa di-release',
                ],
            ];
        }

        $rows = \App\Models\ProductionPlanningItem::query()
            ->where('production_planning_id', $pp->production_planning_id)
            ->where('status', 1)
            ->orderBy('ppi_id')
            ->get();
        if ($rows->isEmpty()) {
            return [
                'error' => [
                    'status' => -1,
                    'message' => 'Planning tidak punya item',
                ],
            ];
        }

        $ppWhId = (int) ($pp->warehouse_id ?: $whId);
        $items = [];
        $missingBom = [];
        $missingVariant = [];

        // 1 query BOM untuk semua varian (ambil bom_id terbaru per product_id).
        $variantIds = $rows->pluck('product_variant_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $bomByVariant = [];
        if ($variantIds !== []) {
            $bomRows = Bom::query()
                ->whereIn('product_id', $variantIds)
                ->where('status', 1)
                ->orderByDesc('bom_id')
                ->get(['bom_id', 'product_id']);
            foreach ($bomRows as $bom) {
                $pid = (int) $bom->product_id;
                if (! isset($bomByVariant[$pid])) {
                    $bomByVariant[$pid] = (int) $bom->bom_id;
                }
            }
        }

        foreach ($rows as $row) {
            $variantId = (int) ($row->product_variant_id ?? 0);
            if ($variantId <= 0) {
                $missingVariant[] = trim((string) ($row->product_name ?: $row->sku ?: 'Item #'.$row->ppi_id));
                continue;
            }
            // boms.product_id = product_variant_id (pola master resep)
            $bomId = (int) ($bomByVariant[$variantId] ?? 0);
            if ($bomId <= 0) {
                $missingBom[] = trim((string) ($row->product_name ?: $row->sku ?: 'Varian #'.$variantId));
                continue;
            }
            $qty = (int) round((float) $row->qty);
            if ($qty <= 0) {
                $missingVariant[] = trim((string) ($row->product_name ?: 'Item #'.$row->ppi_id)).' (qty invalid)';
                continue;
            }
            $unitId = (int) ($row->unit_id ?? 0);
            if ($unitId <= 0) {
                $missingVariant[] = trim((string) ($row->product_name ?: 'Item #'.$row->ppi_id)).' (satuan kosong)';
                continue;
            }

            $items[] = [
                'ppi_id' => (int) ($row->ppi_id ?? 0),
                'product_variant_id' => $variantId,
                'product_name' => $row->product_name,
                'sku' => $row->sku,
                'pd_qty' => $qty,
                'unit_id' => $unitId,
                'unit_name' => $row->unit_label,
                'bom_id' => $bomId,
                'destination_warehouse_id' => $ppWhId > 0 ? $ppWhId : null,
            ];
        }

        if ($missingVariant !== []) {
            return [
                'error' => [
                    'status' => 0,
                    'header' => 'Item Planning Tidak Valid',
                    'message' => 'Item planning belum lengkap (varian/satuan/qty): '.implode(', ', $missingVariant),
                ],
            ];
        }
        if ($missingBom !== []) {
            return [
                'error' => [
                    'status' => 0,
                    'header' => 'Resep Tidak Ditemukan',
                    'code' => 'recipe_needs_update',
                    'message' => 'Produk belum punya resep bahan mentah aktif. Buat/perbarui resep dulu: '.implode(', ', $missingBom),
                ],
            ];
        }
        if ($items === []) {
            return [
                'error' => [
                    'status' => -1,
                    'message' => 'Tidak ada item valid untuk dicek stok.',
                ],
            ];
        }

        return ['items' => $items];
    }

    function deleteProductionPlanning(Request $req)
    {
        return response()->json((new ProductionPlanning())->deletePlanning($req->all()));
    }

    function assignProductionPlanningWorkOrder(Request $req)
    {
        try {
            return response()->json((new ProductionPlanning())->assignWorkOrder($req->all()));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => -1,
                'message' => 'Gagal simpan Work Order: '.$e->getMessage(),
            ], 500);
        }
    }

    public function printProductionPlanning(int $id)
    {
        $planning = (new ProductionPlanning())->getDetail($id);
        if (! $planning) {
            abort(404, 'Planning tidak ditemukan di gudang aktif');
        }
        if (! in_array($planning['pp_status'], ['released', 'work_order', 'inprod', 'done'], true)) {
            abort(422, 'Surat Perintah Kerja dapat dicetak setelah Release to Production');
        }

        $settings = (new \App\Models\Setting())->getSetting([
            'select' => ['company_name', 'company_address', 'logo', 'company_logo'],
        ]);
        $payload = [
            'planning' => $planning,
            'company_name' => $settings['company_name'] ?? 'Pegasus Hikari Group',
            'company_address' => $settings['company_address'] ?? '',
            'logo_base64' => ProductionWorkOrder::resolveCompanyLogoBase64($settings['logo'] ?? $settings['company_logo'] ?? null),
            'skalas' => ProductionSkala::where('status', 1)->orderBy('code')->get(),
        ];
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) $planning['pp_number']);

        return Pdf::loadView('Backoffice.PDF.ProductionPlanningOrder', $payload)
            ->setPaper('a5', 'landscape')
            ->stream('Surat_Perintah_Kerja_'.$safeName.'.pdf');
    }

    /** Print FORM-OPS-09 — DomPDF stream, 1 lembar per PIC (SPV). */
    public function printProductionWorkOrder(int $id)
    {
        $payload = ProductionWorkOrder::printPayload($id);
        if (! $payload) {
            abort(404, 'Work Order tidak ditemukan di gudang aktif');
        }

        $u = session()->get('user') ?? Session::get('user');
        if ($u) {
            $payload['printed_by'] = $u->staff_name ?? $u->name ?? 'Admin';
        }
        $payload['printed_at'] = now()->translatedFormat('d F Y H:i');

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string) ($payload['wo_number'] ?? 'WO'));
        $pdf = Pdf::loadView('Backoffice.PDF.WorkOrderProduction', $payload)
            ->setPaper('a6', 'landscape');

        return $pdf->stream('Work_Order_'.$safeName.'.pdf');
    }

    /**
     * Dokumen Form Kekurangan aktif — prefill item di modal Buat PP.
     * Nama produk = product_name + variasi (lookup SKU).
     */
    public function shortageDocsForPlanning()
    {
        $docs = ShipmentShortageDocument::query()
            ->where('status', 1)
            ->orderByDesc('id')
            ->limit(80)
            ->get(['id', 'doc_number', 'so_id', 'ref_shipment_id', 'items', 'created_at']);

        $soIds = $docs->pluck('so_id')->filter()->unique()->values()->all();
        $soById = $soIds === []
            ? collect()
            : SalesOrder::query()
                ->whereIn('so_id', $soIds)
                ->get(['so_id', 'so_number', 'so_date'])
                ->keyBy('so_id');

        $allSkus = [];
        $allUnitIds = [];
        foreach ($docs as $doc) {
            foreach ($doc->items ?? [] as $row) {
                $sku = trim((string) ($row['sku'] ?? ''));
                if ($sku !== '') {
                    $allSkus[$sku] = true;
                }
                if (isset($row['unit_id']) && $row['unit_id'] !== '' && $row['unit_id'] !== null) {
                    $allUnitIds[(int) $row['unit_id']] = true;
                }
            }
        }
        $skuList = array_keys($allSkus);
        $unitIdList = array_keys($allUnitIds);

        $namesBySku = $skuList === []
            ? collect()
            : ProductVariant::query()
                ->join('products as pr', 'pr.product_id', '=', 'product_variants.product_id')
                ->whereIn('product_variants.product_variant_sku', $skuList)
                ->get([
                    'product_variants.product_variant_id',
                    'product_variants.product_variant_sku',
                    'pr.product_name',
                    'product_variants.product_variant_name',
                ])
                ->mapWithKeys(function ($v) {
                    $label = trim(($v->product_name ?? '') . ' ' . ($v->product_variant_name ?? ''));

                    return [(string) $v->product_variant_sku => [
                        'product_variant_id' => (int) $v->product_variant_id,
                        'product' => $label !== '' ? $label : (string) $v->product_variant_sku,
                    ]];
                });

        $unitsByRef = collect();
        $unitsById = collect();
        if ($unitIdList !== []) {
            $unitRows = Unit::query()
                ->where(function ($q) use ($unitIdList) {
                    $q->whereIn('ref_unit_id', $unitIdList)->orWhereIn('unit_id', $unitIdList);
                })
                ->get(['unit_id', 'ref_unit_id', 'unit_name', 'unit_short_name']);
            foreach ($unitRows as $u) {
                $label = trim((string) ($u->unit_short_name ?: $u->unit_name)) ?: '—';
                $unitsById->put((int) $u->unit_id, $label);
                if ($u->ref_unit_id !== null && $u->ref_unit_id !== '') {
                    $unitsByRef->put((int) $u->ref_unit_id, $label);
                }
            }
        }

        $payload = [];
        foreach ($docs as $doc) {
            $so = $soById->get($doc->so_id);
            $items = [];
            foreach ($doc->items ?? [] as $row) {
                $sku = trim((string) ($row['sku'] ?? ''));
                $meta = $namesBySku->get($sku);
                $unitKey = isset($row['unit_id']) ? (int) $row['unit_id'] : null;
                $unitLabel = '—';
                if ($unitKey !== null) {
                    $unitLabel = $unitsByRef->get($unitKey)
                        ?: $unitsById->get($unitKey)
                        ?: '—';
                }
                $shortage = (int) ($row['shortage'] ?? 0);
                if ($shortage <= 0) {
                    continue;
                }
                $items[] = [
                    'sku' => $sku !== '' ? $sku : '—',
                    'product' => $meta['product'] ?? ($sku !== '' ? $sku : '—'),
                    'product_variant_id' => $meta['product_variant_id'] ?? null,
                    'unit_id' => $unitKey,
                    'unit' => $unitLabel,
                    'shortage' => $shortage,
                    'requested' => (int) ($row['requested'] ?? 0),
                    'available' => (int) ($row['available'] ?? 0),
                    'qty' => $shortage,
                ];
            }
            if ($items === []) {
                continue;
            }
            $payload[] = [
                'id' => (int) $doc->id,
                'doc_number' => $doc->doc_number,
                'so_id' => (int) $doc->so_id,
                'so_number' => $so->so_number ?? null,
                'so_date' => $so->so_date ?? null,
                'ref_shipment_id' => $doc->ref_shipment_id,
                'item_count' => count($items),
                'label' => trim(
                    ($doc->doc_number ?? '')
                    . ' · '
                    . ($so->so_number ?? ('SO#' . $doc->so_id))
                    . ' · '
                    . count($items) . ' item kurang'
                ),
                'items' => $items,
            ];
        }

        return response()->json(['status' => 1, 'data' => $payload]);
    }

    function getProduction(Request $req)
    {
        $data = (new Production())->getProduction([
            "created_at" => $req->created_at,
            "date" => $req->date,
            "status" => $req->status,
            "report" => $req->report ? $req->report : null
        ]);
        $this->attachProductionCancelFlags($data);
        return response()->json($data);
    }

    function insertProduction(Request $req)
    {
        $data = $req->all();
        $isRevisionResubmit = (int) ($req->input('revision_source_production_id') ?? 0) > 0;
        if ($isRevisionResubmit) {
            // Revisi wajib dianggap pengajuan baru di tanggal hari ini.
            $data['production_date'] = now()->toDateString();
        } else {
            $rawDate = trim((string) ($data['production_date'] ?? ''));
            try {
                $prodDate = \Carbon\Carbon::hasFormat($rawDate, 'Y-m-d')
                    ? \Carbon\Carbon::createFromFormat('Y-m-d', $rawDate)->startOfDay()
                    : \Carbon\Carbon::parse($rawDate)->startOfDay();
            } catch (\Throwable $e) {
                return response()->json([
                    'status' => 0,
                    'header' => 'Tanggal Tidak Valid',
                    'message' => 'Format tanggal produksi tidak valid.',
                ]);
            }
            if ($prodDate->lt(now()->startOfDay())) {
                return response()->json([
                    'status' => 0,
                    'header' => 'Tanggal Tidak Valid',
                    'message' => 'Tanggal produksi minimal hari ini (tanggal sebelumnya tidak diizinkan).',
                ]);
            }
            if ($prodDate->gt(now()->startOfDay()->addDay())) {
                return response()->json([
                    'status' => 0,
                    'header' => 'Tanggal Tidak Valid',
                    'message' => 'Tanggal produksi maksimal 1 hari setelah hari ini.',
                ]);
            }
            $data['production_date'] = $prodDate->toDateString();
        }
        $item = json_decode($req->detail, true);
        $bahan = json_decode($req->list_bahan, true);
        // GitHub #134: harus ada minimal 1 produk. Validasi array kosong lolos begitu saja lewat
        // validateProductionItems() di bawah (loop kosong = tidak ada yang gagal), jadi tanpa
        // guard ini submit yang detail-nya kosong (mis. klik "Tambah Produksi" race dengan
        // checkProductionStock yang belum selesai mendorong baris ke `items`) tetap membuat
        // baris Production tanpa satu pun ProductionDetails — transaksi "kosongan" di tabel.
        if (! is_array($item) || count($item) === 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Harus ada 1 produk dipilih',
            ]);
        }
        $destinationValidation = $this->normalizeProductionDestinations($item);
        if (! $destinationValidation['ok']) {
            return response()->json([
                'status' => 0,
                'header' => 'Tujuan Hasil Produksi Tidak Valid',
                'message' => $destinationValidation['message'],
            ]);
        }
        $validation = $this->validateProductionItems($item);
        if ($validation) {
            return response()->json($validation);
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
                        'queue_key' => 'pr:' . $sourceId,
                    ],
                    [
                        'status' => 1,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        // Jangan auto-ACC di sini. Simpan = Pending di list Produksi.
        // Stock Transfer (tujuan eceran) baru dibuat saat Acc produksi manual
        // (atau lewat production:resolve-overdue).

        return response()->json([
            "status" => 1,
            "message" => "Berhasil"
        ]);
    }

    /**
     * Endpoint dipanggil dari JS setiap kali user menambah baris produk ke
     * "Daftar Produk" pada modal Tambah/Konfirmasi Produksi (bukan cuma saat
     * klik Tambah Produksi) - lihat GitHub #101. Menerima daftar produk yang
     * SUDAH termasuk baris yang baru mau ditambahkan (client kirim seluruh
     * `items` setelah merge/push), supaya validasi & simulasi stok agregat
     * (dos/pack, dsb.) tetap dihitung dari kondisi akhir yang benar.
     *
     * Read-only, tidak memutasi apa pun - dipakai juga oleh insertProduction()
     * lewat validateProductionItems() supaya keduanya konsisten.
     */
    function checkProductionStock(Request $req)
    {
        $item = json_decode($req->input('detail'), true) ?: [];
        if (empty($item)) {
            return response()->json(['status' => 1]);
        }

        $destinationValidation = $this->normalizeProductionDestinations($item);
        if (! $destinationValidation['ok']) {
            return response()->json([
                'status' => 0,
                'header' => 'Tujuan Hasil Produksi Tidak Valid',
                'message' => $destinationValidation['message'],
            ]);
        }

        $validation = $this->validateProductionItems($item);
        if ($validation) {
            return response()->json($validation);
        }

        return response()->json(['status' => 1]);
    }

    /**
     * Validasi konsistensi resep (satuan aktif, satuan terkecil, relasi
     * produk, qty kelipatan resep) + simulasi ketersediaan stok bahan mentah
     * agregat (termasuk konversi unit "bongkar" & perlakuan khusus dos/pack)
     * untuk sekumpulan baris produksi. Read-only - tidak memutasi stok/DB.
     *
     * Dipakai baik oleh checkProductionStock() (tiap kali user menambah baris
     * ke daftar produk) maupun insertProduction() (submit akhir), supaya
     * pesan error & aturan validasinya selalu identik di kedua titik.
     *
     * @return array|null null kalau valid; array response (status/header/message/...) kalau tidak.
     */
    protected function validateProductionItems(array $item): ?array
    {
        $cek = -1;
        $bahan_kurang = [];
        $shortages = []; // supplies_id => detail kekurangan (butuh / stok / kurang)
        $produk_tanpa_relasi = [];
        $produk_qty_tidak_kelipatan = [];
        $simulasiHasil = [];

        $bahan_satuan_tidak_aktif = $this->validateProductionBomActiveUnits($item);
        if (count($bahan_satuan_tidak_aktif) > 0) {
            return [
                'status' => 0,
                'header' => 'Satuan Resep Tidak Aktif',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithInactiveUnits($item),
                'message' => 'Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bahan_satuan_tidak_aktif),
            ];
        }

        $bahan_bukan_satuan_terkecil = $this->validateBomSuppliesSmallestUnit($item);
        if (count($bahan_bukan_satuan_terkecil) > 0) {
            return [
                'status' => 0,
                'header' => 'Gagal Insert',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithNonSmallestSupplyUnit($item),
                'message' => 'Satuan bahan mentah pada resep bukan satuan terkecil sesuai relasi. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bahan_bukan_satuan_terkecil),
            ];
        }

        $bom_satuan_produk_bukan_terkecil = $this->validateBomProductSmallestUnit($item);
        if (count($bom_satuan_produk_bukan_terkecil) > 0) {
            return [
                'status' => 0,
                'header' => 'Gagal Insert',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithNonSmallestProductUnit($item),
                'message' => 'Satuan produk pada resep bukan satuan terkecil sesuai relasi produk. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bom_satuan_produk_bukan_terkecil),
            ];
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
            return [
                'status' => 0,
                'header' => 'Gagal Insert',
                'message' => 'Qty produksi harus kelipatan resep bahan mentah untuk produk: ' . implode(', ', $produk_qty_tidak_kelipatan),
            ];
        }

        // 1. AGGREGASI: Hitung total kebutuhan bahan mentah dari SEMUA item produksi di awal
        $aggregatedRequirements = [];
        foreach ($item as $key => $value) {
            $bom = (new Bom())->getBom(['bom_id' => $value['bom_id']])->first();
            if (!isset($bom)) {
                return [
                    "status" => 0,
                    "header" => "Gagal Insert",
                    "message" => "Mohon cek kembali resep bahan mentah"
                ];
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
                if (!$ada) {
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
            return [
                "status" => 0,
                "header" => "Gagal Insert",
                "message" => "Mohon masukkan relasi produk: " . implode(", ", $produk_tanpa_relasi)
            ];
        }

        // 2. PROCESSING: Eksekusi Konversi Stok (Bongkar Satuan Besar) berdasarkan total agregat
        foreach ($aggregatedRequirements as $suppliesId => $butuh) {
            $butuhTersedia = (float) $butuh['total_butuh'];
            if ($butuhTersedia <= 0) continue;
            $bd = $butuh['details'];
            $reqUnitId = (int) $bd['unit_id'];

            // Trading → cek stok product_variant link (bukan supplies_stocks).
            $trading = $this->tradingBomSupplyMeta((int) $suppliesId);
            if ($trading !== null) {
                $whId = (int) (SuppliesStock::resolveWarehouseId() ?: 0);
                $availableNow = ($whId > 0 && $trading['pv_id'] > 0)
                    ? ProductUnitStock::totalAvailable($whId, $trading['pv_id'], $reqUnitId, false, true)
                    : 0.0;
                if ($availableNow < $butuhTersedia) {
                    $cek = 1;
                    $name = $trading['name'];
                    if (! in_array($name, $bahan_kurang, true)) {
                        $bahan_kurang[] = $name;
                    }
                    $this->mergeProductionShortage($shortages, $suppliesId, $name, $reqUnitId, $butuhTersedia, $availableNow);
                }
                continue;
            }

            $ss = $this->ensureSuppliesStockRows($suppliesId);
            $availableNow = $ss->isEmpty()
                ? 0.0
                : $this->getTotalSuppliesStockInUnit($suppliesId, $reqUnitId, $ss);
            if ($ss->isEmpty() || $availableNow < $butuhTersedia) {
                $cek = 1;
                $s = Supplies::find($suppliesId);
                $name = $s['supplies_name'] ?? ('Bahan #'.$suppliesId);
                if (! in_array($name, $bahan_kurang, true)) {
                    $bahan_kurang[] = $name;
                }
                $this->mergeProductionShortage($shortages, $suppliesId, $name, $reqUnitId, $butuhTersedia, $availableNow);
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
                $name = $s['supplies_name'] ?? ('Bahan #'.$suppliesId);
                if (! in_array($name, $bahan_kurang, true)) {
                    $bahan_kurang[] = $name;
                }
                $availableAfter = (float) ($virtualStock[$idPalingBawah]['current'] ?? 0);
                $this->mergeProductionShortage($shortages, $suppliesId, $name, $reqUnitId, $butuhTersedia, $availableAfter);
            }
        }

        if ($cek == 1) {
            $lines = [];
            foreach ($shortages as $sh) {
                $unit = $sh['unit_name'] !== '' ? ' '.$sh['unit_name'] : '';
                $lines[] = sprintf(
                    '%s — butuh %s%s, stok %s%s, kurang %s%s',
                    $sh['supplies_name'],
                    $this->formatShortageQty($sh['needed']),
                    $unit,
                    $this->formatShortageQty($sh['available']),
                    $unit,
                    $this->formatShortageQty($sh['shortage']),
                    $unit
                );
            }
            if ($lines === []) {
                $lines[] = 'Bahan baku tidak mencukupi untuk : '.implode(', ', $bahan_kurang);
            }

            return [
                'status' => -1,
                'header' => 'Stock Tidak Mencukupi',
                'message' => "Bahan baku tidak mencukupi:\n".implode("\n", $lines),
                'shortages' => array_values($shortages),
            ];
        }

        return null;
    }

    /** Agregasi detail kekurangan bahan untuk response checkProductionStock / insert. */
    private function mergeProductionShortage(
        array &$shortages,
        int $suppliesId,
        string $name,
        int $unitId,
        float $needed,
        float $available
    ): void {
        $unitName = '';
        if ($unitId > 0) {
            $unit = Unit::find($unitId);
            $unitName = (string) ($unit->unit_short_name ?? $unit->unit_name ?? '');
        }
        $needed = max(0, $needed);
        $available = max(0, $available);
        $shortage = max(0, $needed - $available);
        if (isset($shortages[$suppliesId])) {
            // Ambil kekurangan terburuk (butuh terbesar / stok tersisa terkecil)
            $shortages[$suppliesId]['needed'] = max($shortages[$suppliesId]['needed'], $needed);
            $shortages[$suppliesId]['available'] = min($shortages[$suppliesId]['available'], $available);
            $shortages[$suppliesId]['shortage'] = max(
                0,
                $shortages[$suppliesId]['needed'] - $shortages[$suppliesId]['available']
            );

            return;
        }
        $shortages[$suppliesId] = [
            'supplies_id' => $suppliesId,
            'supplies_name' => $name,
            'unit_id' => $unitId,
            'unit_name' => $unitName,
            'needed' => $needed,
            'available' => $available,
            'shortage' => $shortage,
        ];
    }

    private function formatShortageQty(float $qty): string
    {
        $rounded = round($qty, 2);
        if (abs($rounded - (int) $rounded) < 0.001) {
            return (string) (int) $rounded;
        }

        return rtrim(rtrim(number_format($rounded, 2, '.', ''), '0'), '.');
    }

    function updateProduction(Request $req) {}

    function deleteProduction(Request $req)
    {
        $data = $req->all();
        $p = Production::find($data['production_id'] ?? null);
        if (! $p || (int) $p->status !== 2) {
            return response()->json([
                'status' => 0,
                'header' => 'Pembatalan Tidak Diizinkan',
                'message' => 'Hanya produksi berstatus Berhasil yang dapat diajukan pembatalan.',
            ]);
        }
        // ST sudah Kirim/Terkirim → stok sudah keluar dari gudang asal; jangan izinkan ajukan batal.
        if ($this->productionHasShippedStockTransfer((int) $p->production_id)) {
            return response()->json([
                'status' => 0,
                'header' => 'Pembatalan Tidak Diizinkan',
                'message' => 'Stock Transfer hasil produksi sudah dikirim. Produksi tidak dapat dibatalkan.',
            ]);
        }

        // Soft-block: ajukan batal setelah ACC — dilarang saat opname produk/bahan open.
        $mainWarehouse = $this->activeMainProductionWarehouse();
        if (! $mainWarehouse) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang Utama Wajib Aktif',
                'message' => 'Pembatalan produksi hanya dapat diajukan saat gudang aktif adalah gudang utama.',
            ]);
        }
        $mainWhId = (int) $mainWarehouse->id;
        $softBlock = \App\Support\PendingStockSoftBlock::messageIfBlocked(
            $mainWhId,
            \App\Support\StockOpname\OpenOpnameGuard::DOMAIN_PRODUCT
        ) ?? \App\Support\PendingStockSoftBlock::messageIfBlocked(
            $mainWhId,
            \App\Support\StockOpname\OpenOpnameGuard::DOMAIN_SUPPLIES
        );
        if ($softBlock !== null) {
            return response()->json([
                'status' => -1,
                'header' => 'Stock Opname',
                'message' => $softBlock,
            ]);
        }

        // Maks. 3 hari sejak tanggal produksi (H..H+2).
        $prodDate = $p->production_date ? \Carbon\Carbon::parse($p->production_date)->startOfDay() : null;
        if ($prodDate && $prodDate->diffInDays(\Carbon\Carbon::today()) > 2) {
            return response()->json([
                'status' => 0,
                'header' => 'Pembatalan Tidak Diizinkan',
                'message' => 'Pembatalan produksi hanya dapat diajukan maksimal 3 hari sejak tanggal produksi.',
            ]);
        }

        (new Production())->deleteProduction($data);
        return 1;
    }

    /**
     * ST production sudah dikirim (status 2) atau diterima (4) — produksi tidak boleh dibatalkan.
     * Pending (1) boleh: stok masih di gudang asal.
     */
    private function productionHasShippedStockTransfer(int $productionId): bool
    {
        if (
            ! Schema::hasColumn('stock_transfers', 'source_type')
            || ! Schema::hasColumn('stock_transfers', 'source_id')
        ) {
            return false;
        }

        return StockTransfer::query()
            ->where('source_type', 'production')
            ->where('source_id', $productionId)
            ->whereIn('status', [2, 4])
            ->exists();
    }

    /**
     * Batalkan ST pending hasil produksi (belum kirim). Stok tetap di gudang asal.
     */
    private function cancelPendingProductionStockTransfers(int $productionId): void
    {
        if (
            ! Schema::hasColumn('stock_transfers', 'source_type')
            || ! Schema::hasColumn('stock_transfers', 'source_id')
        ) {
            return;
        }

        $pending = StockTransfer::query()
            ->where('source_type', 'production')
            ->where('source_id', $productionId)
            ->where('status', 1)
            ->lockForUpdate()
            ->get();

        $staffId = Session::get('user') ? Session::get('user')->staff_id : null;
        foreach ($pending as $st) {
            if (Schema::hasColumn('stock_transfers', 'disposition')) {
                $st->disposition = 'return_warehouse';
            }
            $st->status = 3;
            $st->accept_note = 'Dibatalkan otomatis karena pembatalan produksi';
            $st->acc_by = $staffId;
            $st->save();
        }
    }

    /**
     * ACC produksi (pending → approved).
     *
     * Alur keamanan stok (wajib dijaga):
     * 1) self-heal log/stok orphan dari ACC gagal sebelumnya — HANYA kalau belum ada Stock
     *    Opname yang disetujui sejak orphan itu ditulis (lihat findLockingOpnameCode()); kalau
     *    sudah dikunci opname, ACC ditolak (status -4) dan minta review manual, TIDAK dipaksa
     *    auto-restore. Lihat docs/laporan-opname-vs-pending-production-2026-08-01.md — insiden
     *    PR0258 yang jadi alasan awal fitur ini dimatikan dari ACC.
     * 2) pre-check SEMUA bahan tanpa mutasi
     * 3) potong bahan + tambah produk + update status dalam 1 DB transaction
     *
     * Perbaikan massal manual (tanpa cek opname, dipakai admin yang sudah meninjau sendiri):
     * php artisan production:restore-pending-stock
     *
     * Detail: docs/production-acc-stock-safety.md, GitHub #83
     */
    function accProduction(Request $req)
    {
        $data = $req->all();
        if (
            ! Schema::hasColumn('production_details', 'destination_warehouse_id')
            || ! Schema::hasColumn('stock_transfers', 'source_type')
            || ! Schema::hasColumn('stock_transfers', 'source_id')
        ) {
            return response()->json([
                'status' => 0,
                'header' => 'Migrasi Belum Dijalankan',
                'message' => 'Jalankan migrasi production stock transfer sebelum ACC produksi.',
            ]);
        }
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

        // Self-heal (GitHub #83): produksi ini masih "Menunggu", tapi kalau sebuah ACC
        // sebelumnya sempat memotong bahan lalu gagal sebelum status berubah (mis. proses PHP
        // mati mendadak, bukan exception biasa -- kasus normal sudah aman lewat DB transaction
        // di bawah), log_stocks-nya masih aktif menggantung. Balikkan dulu supaya retry ini
        // tidak memotong bahan untuk kedua kalinya.
        $stockRestorer = new ProductionPendingStockRestorer();
        $orphanLogs = $stockRestorer->activeLogsFor($p->production_code);
        if ($orphanLogs->isNotEmpty()) {
            $lockingOpnameCode = $stockRestorer->findLockingOpnameCode($orphanLogs);
            if ($lockingOpnameCode !== null) {
                return response()->json([
                    'status' => -4,
                    'header' => 'Perlu Peninjauan Manual',
                    'message' => "Produksi ini punya potongan stok menggantung dari percobaan ACC "
                        . "sebelumnya, tapi Stock Opname {$lockingOpnameCode} sudah terlanjur "
                        . "disetujui setelah potongan itu terjadi. Tidak bisa dikembalikan otomatis "
                        . "supaya tidak merusak angka opname tersebut -- hubungi admin untuk "
                        . "peninjauan manual (php artisan production:restore-pending-stock).",
                ]);
            }

            $staffId = (int) (session('user')->staff_id ?? 0);
            DB::transaction(function () use ($stockRestorer, $p, $staffId) {
                $stockRestorer->revertProductionCode($p->production_code, true, $staffId > 0 ? $staffId : null);
            });
        }

        $item = ProductionDetails::where('production_id', $data['production_id'])->where('status', 1)->get();
        $produk_tanpa_relasi = [];
        $bahan_kurang = []; // ← ditambahkan untuk menangkap bahan yang ternyata kurang saat eksekusi

        $accItems = $item->toArray();
        $bahan_satuan_tidak_aktif = $this->validateProductionBomActiveUnits($accItems);
        if (count($bahan_satuan_tidak_aktif) > 0) {
            return response()->json([
                'status' => 0,
                'header' => 'Satuan Resep Tidak Aktif',
                'code' => 'recipe_needs_update',
                'bom_id' => $this->firstBomIdWithInactiveUnits($accItems),
                'message' => 'Satuan bahan pada resep sudah tidak aktif. Perbarui resep terlebih dahulu: '
                    . implode(', ', $bahan_satuan_tidak_aktif),
            ]);
        }

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
                if (!$ada) {
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
        $mainWarehouse = $this->activeMainProductionWarehouse();
        if (! $mainWarehouse) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang Utama Wajib Aktif',
                'message' => 'Produksi hanya dapat di-ACC saat gudang aktif adalah gudang utama.',
            ]);
        }

        // Soft-block: ACC ditolak saat opname open (produk atau bahan).
        $mainWhId = (int) $mainWarehouse->id;
        $softBlock = \App\Support\PendingStockSoftBlock::messageIfBlocked(
            $mainWhId,
            \App\Support\StockOpname\OpenOpnameGuard::DOMAIN_PRODUCT
        ) ?? \App\Support\PendingStockSoftBlock::messageIfBlocked(
            $mainWhId,
            \App\Support\StockOpname\OpenOpnameGuard::DOMAIN_SUPPLIES
        );
        if ($softBlock !== null) {
            return response()->json([
                'status' => -1,
                'header' => 'Stock Opname',
                'message' => $softBlock,
            ]);
        }

        $transferPlan = $this->buildProductionTransferPlan($item, (int) $mainWarehouse->id);
        if (! $transferPlan['ok']) {
            return response()->json([
                'status' => 0,
                'header' => 'Hasil Produksi Tidak Valid',
                'message' => $transferPlan['message'],
            ]);
        }

        // PRE-CHECK: validasi SEMUA kebutuhan bahan dulu (tanpa mutasi stok).
        // Supaya gagal cepat sebelum masuk transaction potong bahan.
        $bahan_kurang = [];
        foreach ($aggregatedRequirements as $suppliesId => $butuh) {
            $butuhTersedia = (float) $butuh['total_butuh'];
            if ($butuhTersedia <= 0) {
                continue;
            }
            $bd = $butuh['details'];
            $reqUnitId = (int) $bd['unit_id'];

            $trading = $this->tradingBomSupplyMeta((int) $suppliesId);
            if ($trading !== null) {
                $availableNow = $trading['pv_id'] > 0
                    ? ProductUnitStock::totalAvailable($mainWhId, $trading['pv_id'], $reqUnitId, false, true)
                    : 0.0;
                if ($availableNow < $butuhTersedia) {
                    if (! in_array($trading['name'], $bahan_kurang, true)) {
                        $bahan_kurang[] = $trading['name'];
                    }
                }
                continue;
            }

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

        // PRE-CHECK: hasil produksi selalu di-credit ke gudang utama lewat
        // ProductUnitStock::addQty(), yang otomatis membuat baris ProductStock baru
        // (stok awal 0) kalau belum ada untuk kombinasi varian+satuan itu — TANPA mutasi
        // apa pun di sini. Kalau ada baris yang bakal dibuat baru, minta konfirmasi user dulu
        // (lewat confirm_create_stock), supaya user sadar ada baris stok baru yang akan dibuat.
        // NB (merged from main's ebadabf/GH #19, 2026-08-28): main's version of this pre-check
        // walked the whole product_relations ladder via creditProductOutputUpChain(), because
        // main's accProduction() still credited finished goods itself via ProductRelation/
        // ensureProductStockRow(). fase2's accProduction() has since been rearchitected around
        // buildProductionTransferPlan() + ProductUnitStock::addQty(), so that ladder-walking code
        // doesn't transplant -- the GH #19 fix lives in addQty()'s $rollUp instead.
        //
        // This block must therefore ask addQty() what it will ACTUALLY do rather than assume the
        // credit lands flat on the produced unit: with $rollUp on, a qty that divides evenly into a
        // higher unit is credited THERE instead, so (a) warning about the produced unit would be
        // wrong when its remainder rolls up to 0 -- no row gets created for it, addQty() skips
        // qty <= 0 credits -- and (b) a higher unit could be the one needing a row. Same planner,
        // same warehouse, so this preview can't drift from the real thing.
        //
        // Produksi meneruskan SELURUH tangga satuan (ladderUnitIds()), bukan cuma satuan yang sudah
        // punya baris stok: justru blok inilah mekanisme "provisioning yang disetujui user" yang
        // membuat itu aman -- satuan yang belum punya baris dilaporkan di sini, dan barulah setelah
        // user menekan konfirmasi (confirm_create_stock) addQty() membuatnya. Caller lain yang
        // TIDAK punya langkah konfirmasi tetap memakai kebijakan ketat bawaan addQty().
        $missingProductStockRows = [];
        foreach ($transferPlan['groups'] as $group) {
            foreach ($group['items'] as $output) {
                // planProductFolded() (GitHub #151, 2026-09-06): folds stock ALREADY at the produced
                // unit into the roll-up decision too -- must match addQty()'s actual call below
                // exactly, or this preview can drift from what really gets credited/provisioned.
                $credits = UnitRollUp::planProductFolded(
                    (int) $output['product_variant_id'],
                    (int) $output['unit_id'],
                    (int) $output['qty'],
                    (int) $mainWarehouse->id,
                    UnitRollUp::ladderUnitIds((int) $output['product_variant_id'])
                );

                foreach ($credits as $credit) {
                    if ($credit['qty'] <= 0) {
                        continue; // addQty() tidak menyentuh satuan ini, jadi tidak ada baris baru
                    }

                    $key = (int) $output['product_variant_id'] . '_' . (int) $credit['unit_id'];
                    if (isset($missingProductStockRows[$key])) {
                        continue;
                    }

                    $exists = ProductStock::withoutGlobalScope('active_warehouse')
                        ->where('warehouse_id', (int) $mainWarehouse->id)
                        ->where('product_variant_id', $output['product_variant_id'])
                        ->where('unit_id', $credit['unit_id'])
                        ->where('status', 1)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $pv = ProductVariant::find($output['product_variant_id']);
                    $productName = '-';
                    if ($pv) {
                        $prName = Product::find($pv->product_id);
                        $productName = trim(($prName->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
                        if ($productName === '') {
                            $productName = $pv->product_variant_name ?? '-';
                        }
                    }
                    $unit = Unit::find($credit['unit_id']);
                    $missingProductStockRows[$key] = [
                        'product_variant_id' => (int) $output['product_variant_id'],
                        'unit_id' => (int) $credit['unit_id'],
                        'product_name' => $productName,
                        'unit_name' => $unit->unit_name ?? '-',
                    ];
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

        $createdTransferIds = [];
        DB::beginTransaction();
        try {
            $p = Production::query()
                ->where('production_id', (int) $data['production_id'])
                ->lockForUpdate()
                ->first();
            if (! $p || (int) $p->status !== 1) {
                throw new \RuntimeException('Produksi sudah diproses sebelumnya.');
            }
            if (StockTransfer::query()
                ->where('source_type', 'production')
                ->where('source_id', (int) $p->production_id)
                ->whereIn('status', [1, 2, 4])
                ->exists()
            ) {
                throw new \RuntimeException('Stock Transfer hasil produksi sudah pernah dibuat.');
            }
            foreach ($item as $productionDetail) {
                $productionDetail->save();
            }

            // 2. PENGURANGAN BAHAN (SUPPLIES) - dengan konversi dulu
            foreach ($aggregatedRequirements as $suppliesId => $butuh) {
                $butuhTersedia = (float)$butuh['total_butuh'];
                if ($butuhTersedia <= 0) continue;
                $bd = $butuh['details'];
                $reqUnitId = (int) $bd['unit_id'];

                // Trading → potong stok product_variant link (roll-up/bongkar lewat ProductUnitStock).
                $trading = $this->tradingBomSupplyMeta((int) $suppliesId);
                if ($trading !== null) {
                    if ($trading['pv_id'] <= 0) {
                        if (! in_array($trading['name'], $bahan_kurang, true)) {
                            $bahan_kurang[] = $trading['name'];
                        }
                        continue;
                    }
                    $deduct = ProductUnitStock::deductQty(
                        (int) $mainWarehouse->id,
                        $trading['pv_id'],
                        $reqUnitId,
                        $butuhTersedia,
                        $p->production_code,
                        'Pengurangan bahan Trading untuk produksi '.$p->production_code,
                        false,
                        true,
                        'Produksi'
                    );
                    if (! ($deduct['ok'] ?? false)) {
                        if (! in_array($trading['name'], $bahan_kurang, true)) {
                            $bahan_kurang[] = $trading['name'];
                        }
                    }
                    continue;
                }

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
                    &$virtualStock,
                    &$logSummary,
                    &$siapkanStok,
                    $bd,
                    $suppliesId
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
                        $v['model']->ss_stock = round((float) $v['current'], 4);
                        $v['model']->save();
                    }

                    // 2. Catat log konversi
                    usort($logSummary, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
                    foreach ($logSummary as $l) {
                        $saldoUnit = null;
                        foreach ($virtualStock as $v) {
                            if ((int) $v['model']->unit_id === (int) $l['unit_id']) {
                                $saldoUnit = (float) $v['current'];
                                break;
                            }
                        }
                        (new LogStock())->insertLog([
                            'log_date'     => \Carbon\Carbon::parse($p->production_date)->setTimeFrom(now()),
                            'log_kode'     => $p->production_code,
                            'log_type'     => 2,
                            'log_category' => $l['cat'],
                            'log_item_id'  => $suppliesId,
                            'log_notes'    => $l['note'],
                            'log_jumlah'   => $l['jumlah'],
                            'log_saldo'    => $saldoUnit,
                            'unit_id'      => $l['unit_id'],
                            'warehouse_id' => (int) $mainWarehouse->id,
                        ]);
                    }

                    // 3. Kurangi stok unit terbawah (Piece) sebesar kebutuhan
                    // (tanpa cekLog — lock log orphan pending sudah dibersihkan terpisah;
                    //  ACC dalam transaction harus selalu potong konsisten atau rollback)
                    $stokBawah = SuppliesStock::find($idPalingBawah);
                    $stokBawah->ss_stock = round(
                        (float) $virtualStock[$idPalingBawah]['current'] - $butuhTersedia,
                        4
                    );
                    $stokBawah->save();

                    (new LogStock())->insertLog([
                        'log_date'     => \Carbon\Carbon::parse($p->production_date)->setTimeFrom(now()),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => 2,
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => "Pengurangan bahan untuk produksi",
                        'log_jumlah'   => $butuhTersedia,
                        'log_saldo'    => (float) $stokBawah->ss_stock,
                        'unit_id'      => $stokBawah->unit_id,
                        'warehouse_id' => (int) $mainWarehouse->id,
                    ]);
                } else {
                    // ← ditambahkan: sebelumnya silent skip tanpa pesan apapun
                    $s = Supplies::find($suppliesId);
                    if ($s && !in_array($s['supplies_name'], $bahan_kurang, true)) {
                        $bahan_kurang[] = $s['supplies_name'];
                    }
                }
            }

            if (count($bahan_kurang) > 0) {
                throw new \RuntimeException(
                    "Bahan baku tidak mencukupi untuk : " . implode(", ", $bahan_kurang)
                );
            }

            // Inventori hasil ke gudang asal dulu (agar ST Kirim bisa potong stok).
            // ST hanya dibuat jika tujuan ≠ gudang asal (mis. eceran). Main→main tidak perlu ST.
            $inventoryBuckets = [];
            foreach ($transferPlan['groups'] as $group) {
                foreach ($group['items'] as $output) {
                    $key = (int) $output['product_variant_id'] . ':' . (int) $output['unit_id'];
                    $inventoryBuckets[$key] ??= [
                        'product_id' => (int) $output['product_id'],
                        'product_variant_id' => (int) $output['product_variant_id'],
                        'unit_id' => (int) $output['unit_id'],
                        'qty' => 0,
                    ];
                    $inventoryBuckets[$key]['qty'] += (float) $output['qty'];
                }
            }
            ProductUnitStock::clearCache();
            foreach ($inventoryBuckets as $output) {
                // rollUp: true (GH #19, merged from main's ebadabf/51684f3, 2026-08-28) -- an exact
                // multiple of a higher product_relations unit (e.g. 24 Piece = 2 DOS) is credited as
                // that higher unit, mirroring physical packing, instead of always staying flat at
                // the produced unit. See ProductUnitStock::addQty()'s $rollUp doc.
                //
                // Allow-list = SELURUH tangga satuan, sama persis dengan yang dipakai pre-check di
                // atas -- kalau keduanya tidak sama, user bisa dimintai konfirmasi untuk baris yang
                // ternyata tidak dibuat (atau lebih buruk: baris dibuat tanpa pernah dikonfirmasi).
                $add = ProductUnitStock::addQty(
                    (int) $mainWarehouse->id,
                    (int) $output['product_id'],
                    (int) $output['product_variant_id'],
                    (int) $output['unit_id'],
                    (float) $output['qty'],
                    $p->production_code,
                    'Hasil produksi ' . $p->production_code,
                    true,
                    UnitRollUp::ladderUnitIds((int) $output['product_variant_id'])
                );
                if (! $add['ok']) {
                    throw new \RuntimeException(
                        $add['message'] ?? 'Gagal menambah stok hasil produksi ke gudang asal'
                    );
                }
            }

            $mainWarehouseId = (int) $mainWarehouse->id;
            foreach ($transferPlan['groups'] as $destinationId => $group) {
                if ((int) $destinationId === $mainWarehouseId) {
                    continue;
                }
                $transfer = StockTransfer::query()->create([
                    'transfer_code' => 'ST-' . $p->production_code . '-' . $destinationId,
                    'transfer_date' => $p->production_date,
                    'sender_id' => session('user')->staff_id ?? $p->production_created_by,
                    'from_warehouse_id' => $mainWarehouseId,
                    'to_warehouse_id' => (int) $destinationId,
                    'note' => 'Hasil produksi ' . $p->production_code,
                    'source_type' => 'production',
                    'source_id' => (int) $p->production_id,
                    'status' => 1,
                    'created_by' => session('user')->staff_id ?? null,
                ]);
                foreach ($group['items'] as $output) {
                    StockTransferDetail::query()->create([
                        'st_id' => $transfer->st_id,
                        'product_id' => $output['product_id'],
                        'product_variant_id' => $output['product_variant_id'],
                        'unit_id' => $output['unit_id'],
                        'received_unit_id' => null,
                        'qty' => $output['qty'],
                        'qty_received' => null,
                        'status' => 1,
                    ]);
                }
                $createdTransferIds[] = (int) $transfer->st_id;
            }

            (new Production())->accProduction($data);
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            return response()->json([
                // NB (merged from main's ebadabf/GH #19, 2026-08-28): this conflict was mostly
                // main's old ProductRelation/ensureProductStockRow ladder-crediting block (already
                // replaced on fase2 by buildProductionTransferPlan() + ProductUnitStock::addQty(),
                // see the pre-check above) misaligned by diff onto this catch{} block. Nothing to
                // port here -- see that pre-check's comment for where the actual GH #19 fix went.
                'status' => -1,
                'header' => 'Gagal ACC',
                'message' => $e->getMessage() ?: 'Gagal membuat Stock Transfer hasil produksi.',
            ]);
        }

        $transferController = new StockTransferController();
        foreach ($createdTransferIds as $transferId) {
            $transferController->logProductionTransferCreated($transferId);
        }

        $stCount = count($createdTransferIds);
        $message = $stCount > 0
            ? $stCount . ' Stock Transfer hasil produksi dibuat (Pending). Stok sudah masuk gudang asal.'
            : 'Hasil produksi masuk gudang asal. Tidak ada Stock Transfer (tujuan sama dengan gudang produksi).';

        return response()->json([
            'status' => 1,
            'message' => $message,
            'stock_transfer_ids' => $createdTransferIds,
        ]);
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
        $productionId = (int) ($data['production_id'] ?? 0);
        $p = (new Production())->getProduction(["production_id" => $productionId])->first();
        if (! $p) {
            return response()->json([
                'status' => 0,
                'header' => 'Gagal Update',
                'message' => 'Data produksi tidak ditemukan',
            ]);
        }
        // Hanya blokir jika ST sudah Kirim (2) / Terkirim (4). Pending (1) boleh dibatalkan
        // bersama produksi (stok masih di gudang asal) — jangan biarkan stuck Menunggu Batal.
        if ($this->productionHasShippedStockTransfer($productionId)) {
            return response()->json([
                'status' => 0,
                'header' => 'Pembatalan Tidak Diizinkan',
                'message' => 'Stock Transfer hasil produksi sudah dikirim. Produksi tidak dapat dibatalkan.',
            ]);
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

        // Soft-block: ACC batal mengembalikan stok bahan + potong hasil produk — dilarang saat opname open.
        $mainWarehouse = $this->activeMainProductionWarehouse();
        if (! $mainWarehouse) {
            return response()->json([
                'status' => 0,
                'header' => 'Gudang Utama Wajib Aktif',
                'message' => 'Pembatalan produksi hanya dapat dilakukan saat gudang aktif adalah gudang utama.',
            ]);
        }
        $mainWhId = (int) $mainWarehouse->id;
        $softBlock = \App\Support\PendingStockSoftBlock::messageIfBlocked(
            $mainWhId,
            \App\Support\StockOpname\OpenOpnameGuard::DOMAIN_PRODUCT
        ) ?? \App\Support\PendingStockSoftBlock::messageIfBlocked(
            $mainWhId,
            \App\Support\StockOpname\OpenOpnameGuard::DOMAIN_SUPPLIES
        );
        if ($softBlock !== null) {
            return response()->json([
                'status' => -1,
                'header' => 'Stock Opname',
                'message' => $softBlock,
            ]);
        }

        if ($p['items']->count() == 0) {
            DB::beginTransaction();
            try {
                $this->cancelPendingProductionStockTransfers($productionId);
                (new Production())->cancelProduction($data);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
            return 1;
        }

        // accProduction() credits finished-goods stock into the MAIN warehouse only, via
        // ProductUnitStock::addQty() (single unit, no ladder-split). Reversal here must mirror
        // that exact operation instead of the old direct-ProductStock ladder logic, which assumed
        // a split across two unit rows that addQty() no longer creates and could null-deref when
        // the larger-unit row didn't exist.
        // $mainWarehouse already resolved above for soft-block.

        $outputTotals = [];
        foreach ($p['items'] as $value) {
            $key = (int) $value['product_variant_id'] . '_' . (int) $value['unit_id'];
            $outputTotals[$key] ??= [
                'product_variant_id' => (int) $value['product_variant_id'],
                'unit_id' => (int) $value['unit_id'],
                'qty' => 0.0,
            ];
            $outputTotals[$key]['qty'] += (float) $value['pd_qty'];
        }

        ProductUnitStock::clearCache();
        $checkItems = [];
        foreach ($outputTotals as $output) {
            $pv = ProductVariant::find($output['product_variant_id']);
            $label = '-';
            if ($pv) {
                $prod = Product::find($pv->product_id);
                $label = trim(($prod->product_name ?? '') . ' ' . ($pv->product_variant_name ?? ''));
                if ($label === '') {
                    $label = $pv->product_variant_name ?? '-';
                }
            }
            $checkItems[] = [
                'product_variant_id' => $output['product_variant_id'],
                'unit_id' => $output['unit_id'],
                'qty' => $output['qty'],
                'label' => $label,
            ];
        }

        $check = ProductUnitStock::checkItems((int) $mainWarehouse->id, $checkItems);
        if (! $check['ok']) {
            $names = array_map(fn($s) => $s['label'], $check['shortages']);
            return response()->json([
                "status"  => -1,
                "message" => "Stok produk tidak mencukupi: " . implode(', ', $names),
            ]);
        }

        // Ditambahkan: seluruh reversal (stok produk, pengembalian bahan, dan status flip)
        // sekarang jalan dalam satu DB::transaction(). Dulu tidak ada transaksi sama sekali,
        // dan cancelProduction()/cancelProductionDetail() dipanggil DI AWAL sebelum reversal
        // stok berjalan — kalau loop reversal di bawah gagal di tengah jalan (mis. deductQty()
        // salah satu item gagal), produksi sudah kadung ditandai batal padahal stoknya belum
        // benar-benar direversal semua. Status flip dipindah ke akhir supaya itu tidak lagi bisa
        // terjadi, dan sebuah exception di tengah jalan (bukan cuma error terkontrol) sekarang
        // roll back semuanya alih-alih meninggalkan reversal setengah jalan.
        DB::beginTransaction();
        try {
        // Batalkan ST pending (belum kirim) dulu — stok belum potong dari gudang asal.
        $this->cancelPendingProductionStockTransfers((int) $data['production_id']);

        // Stok produk
        foreach ($outputTotals as $output) {
            $cut = ProductUnitStock::deductQty(
                (int) $mainWarehouse->id,
                $output['product_variant_id'],
                $output['unit_id'],
                $output['qty'],
                $p->production_code,
                'Pembatalan produksi ' . $p->production_code,
                false,
                true,
                // GitHub #134: reversal produksi mode stok gudang bukan alur Stock Transfer —
                // kalau butuh bongkar satuan, Log Stock harus menyebut ini pembatalan produksi,
                // bukan ikut default "Stock Transfer - bongkar satuan".
                'Pembatalan produksi'
            );
            if (! $cut['ok']) {
                DB::rollBack();
                return response()->json([
                    "status"  => -1,
                    "message" => $cut['message'] ?? 'Gagal membatalkan stok hasil produksi.',
                ]);
            }
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

            // Trading → kembalikan ke product_variant link.
            $trading = $this->tradingBomSupplyMeta((int) $suppliesId);
            if ($trading !== null) {
                if ($trading['pv_id'] <= 0) {
                    continue;
                }
                $pv = ProductVariant::find($trading['pv_id']);
                if (! $pv) {
                    continue;
                }
                $bd = $butuh['details'];
                $reqUnitId = (int) $bd['unit_id'];
                ProductUnitStock::addQty(
                    (int) $mainWarehouse->id,
                    (int) $pv->product_id,
                    $trading['pv_id'],
                    $reqUnitId,
                    $butuhTersedia,
                    $p->production_code,
                    'Pengembalian stok Trading akibat pembatalan produksi '.LogStock::actorSuffix(),
                    true
                );
                continue;
            }

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

            // GitHub #159 (2026-09-07): dulu konversi satu-tingkat manual lewat SuppliesRelation
            // langsung (`floor($butuhTersedia / $sr->sr_value_2)`), dan TIDAK melipat stok yang
            // SUDAH ADA di $stokBawah sebelum kredit ini masuk -- bug class yang sama dengan #151
            // (6 Piece sudah ada + 6 Piece kembali = tetap 12 Piece, bukan naik jadi 1 Dos). Sekarang
            // lewat UnitRollUp::planSuppliesFolded(), yang melipat stok existing DAN menelusuri
            // seluruh tangga satuan (bukan cuma satu tingkat ke atas), hanya menaikkan ke satuan
            // yang SUDAH punya baris stok aktif -- sama seperti
            // ProductIssuesDetail::deleteProductIssuesDetail() dan
            // PurchaseOrderDeliveryDetail::insertPoDeliveryDetail().
            $credits = UnitRollUp::planSuppliesFolded((int) $suppliesId, (int) $stokBawah->unit_id, (int) $butuhTersedia);
            $base = array_shift($credits);      // selalu satuan bawah (bisa DELTA negatif kalau naik satuan)
            $naikLevel = $credits;               // level-level di atasnya (kosong kalau tidak naik)

            // GitHub #194: log_saldo dari log utama di bawah harus mencerminkan Sisa TEPAT SETELAH
            // kredit mentah ini, sebelum ikut dilipat naik satuan oleh $naikLevel -- ditangkap di
            // sini (sebelum += ) supaya tidak bergantung pada fallback resolveCurrentSaldo() milik
            // insertLog(), yang kalau dibiarkan akan membaca ss_stock SETELAH semua level naik
            // satuan selesai diterapkan (log ditulis paling akhir), bukan angka "baru saja masuk".
            $stokBawahLama = (float) $stokBawah->ss_stock;

            $stokBawah->ss_stock += $base['qty'];
            $stokBawah->save();

            foreach ($naikLevel as $credit) {
                if ($credit['qty'] <= 0) continue;

                $row = SuppliesStock::where('supplies_id', $suppliesId)
                    ->where('unit_id', $credit['unit_id'])
                    ->where('status', 1)
                    ->first();
                if (!$row) continue;

                $row->ss_stock += $credit['qty'];
                $row->save();
            }

            // Log utama: qty penuh yang dikembalikan, di satuan bawah tempat butuhTersedia dihitung
            // -- riwayat tetap menyebut "pengembalian" walau sebagian/semua ikut naik satuan.
            (new LogStock())->insertLog([
                'log_date'     => now(),
                'log_kode'     => $p->production_code,
                'log_type'     => 2,
                'log_category' => 1,
                'log_item_id'  => $suppliesId,
                'log_notes'    => "Pengembalian stok bahan akibat pembatalan produksi " . LogStock::actorSuffix(),
                'log_jumlah'   => $butuhTersedia,
                'unit_id'      => $stokBawah->unit_id,
                'log_saldo'    => $stokBawahLama + $butuhTersedia,
            ]);

            // Jejak konversi kalau memang naik satuan: satuan asal keluar (cat 2), satuan hasil
            // masuk (cat 1) -- pola sama persis dengan deleteProductIssuesDetail().
            if ($naikLevel !== []) {
                $naik = (int) $butuhTersedia - (int) $base['qty'];
                if ($naik > 0) {
                    (new LogStock())->insertLog([
                        'log_date'     => now(),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => 2,
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => "Konversi unit (Naik satuan) pembatalan produksi " . LogStock::actorSuffix(),
                        'log_jumlah'   => $naik,
                        'unit_id'      => $stokBawah->unit_id,
                    ]);
                }
                foreach ($naikLevel as $credit) {
                    if ($credit['qty'] <= 0) continue;
                    (new LogStock())->insertLog([
                        'log_date'     => now(),
                        'log_kode'     => $p->production_code,
                        'log_type'     => 2,
                        'log_category' => 1,
                        'log_item_id'  => $suppliesId,
                        'log_notes'    => "Konversi unit (Hasil naik satuan) pembatalan produksi " . LogStock::actorSuffix(),
                        'log_jumlah'   => $credit['qty'],
                        'unit_id'      => $credit['unit_id'],
                    ]);
                }
            }
        }

        // Status flip pindah ke sini (dulu di paling awal, sebelum reversal stok berjalan
        // sama sekali) — lihat catatan di awal blok transaksi.
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

    /** Batch-count tanpa query — pakai ProductRelation yang sudah di-load. */
    private function getBatchCountFromRelations(
        int $pdQty,
        int $pdUnitId,
        int $bomQty,
        int $bomUnitId,
        $relations
    ): int {
        $pdSmallest = $this->convertQtyToSmallestUnitFromRelations($pdQty, $pdUnitId, $relations);
        $bomSmallest = $this->convertQtyToSmallestUnitFromRelations($bomQty, $bomUnitId, $relations);

        if ($bomSmallest <= 0) {
            return $pdQty > 0 ? 1 : 0;
        }

        return (int) ($pdSmallest / $bomSmallest);
    }

    private function convertQtyToSmallestUnitFromRelations(int $qty, int $unitId, $relations): int
    {
        $multiplier = 1;
        $currentUnit = $unitId;
        $guard = 0;
        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(fn ($r) => (int) $r->pr_unit_id_1 === (int) $currentUnit);
            if (! $rel) {
                break;
            }
            $multiplier *= (int) $rel->pr_unit_value_2;
            $currentUnit = (int) $rel->pr_unit_id_2;
        }

        return $qty * $multiplier;
    }

    private function convertQtyBetweenUnitsFromRelations(int $qty, int $fromUnitId, int $toUnitId, $relations): int
    {
        if ($fromUnitId === $toUnitId) {
            return $qty;
        }

        $multiplier = 1;
        $currentUnit = $fromUnitId;
        $guard = 0;
        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(fn ($r) => (int) $r->pr_unit_id_1 === (int) $currentUnit);
            if (! $rel) {
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

    private function getTotalSuppliesStockInUnitFromRelations(int $targetUnitId, $ss, $relations): float
    {
        $total = 0.0;
        foreach ($ss as $stok) {
            $total += $this->convertSuppliesQtyBetweenUnitsFromRelations(
                (float) $stok->ss_stock,
                (int) $stok->unit_id,
                $targetUnitId,
                $relations
            );
        }

        return $total;
    }

    private function convertSuppliesQtyToSmallestUnitFromRelations(float $qty, int $unitId, $relations): float
    {
        $multiplier = 1.0;
        $currentUnit = $unitId;
        $guard = 0;
        while ($guard < 20) {
            $guard++;
            $rel = $relations->first(fn ($r) => (int) $r->su_id_1 === (int) $currentUnit);
            if (! $rel) {
                break;
            }
            $multiplier *= (float) $rel->sr_value_2;
            $currentUnit = (int) $rel->su_id_2;
        }

        return $qty * $multiplier;
    }

    private function convertSuppliesQtyBetweenUnitsFromRelations(
        float $qty,
        int $fromUnitId,
        int $toUnitId,
        $relations
    ): float {
        if ((int) $fromUnitId === (int) $toUnitId) {
            return $qty;
        }

        $smallestQty = $this->convertSuppliesQtyToSmallestUnitFromRelations($qty, $fromUnitId, $relations);
        $toMultiplier = $this->convertSuppliesQtyToSmallestUnitFromRelations(1, $toUnitId, $relations);
        if ($toMultiplier <= 0) {
            return $qty;
        }

        return $smallestQty / $toMultiplier;
    }

    private function ensureSuppliesStockRows(int $suppliesId)
    {
        (new SuppliesStock())->syncStock($suppliesId);

        $relationUnits = SuppliesRelation::where('supplies_id', $suppliesId)
            ->where('status', 1)
            ->get()
            ->flatMap(fn($rel) => [(int) $rel->su_id_1, (int) $rel->su_id_2])
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
            $rel = $relations->first(fn($r) => (int) $r->su_id_1 === (int) $currentUnit);
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

    private function firstBomIdWithInactiveUnits(array $productionItems): ?int
    {
        foreach ($productionItems as $value) {
            $bomId = (int) ($value['bom_id'] ?? 0);
            if ($bomId <= 0) {
                continue;
            }
            $bom = (new Bom())->getBom(['bom_id' => $bomId])->first();
            if (!$bom) {
                continue;
            }
            foreach ($this->getBomDetailRows($bom) as $bd) {
                if (!(new Supplies())->isSuppliesUnitActive((int) $bd['supplies_id'], (int) $bd['unit_id'])) {
                    return $bomId;
                }
            }
        }

        return null;
    }

    private function firstBomIdWithNonSmallestSupplyUnit(array $productionItems): ?int
    {
        foreach ($productionItems as $value) {
            $bomId = (int) ($value['bom_id'] ?? 0);
            if ($bomId <= 0) {
                continue;
            }
            $bom = (new Bom())->getBom(['bom_id' => $bomId])->first();
            if (!$bom) {
                continue;
            }
            foreach ($this->getBomDetailRows($bom) as $bd) {
                $hasDownwardRelation = SuppliesRelation::where('supplies_id', (int) $bd['supplies_id'])
                    ->where('su_id_1', (int) $bd['unit_id'])
                    ->where('status', 1)
                    ->exists();
                if ($hasDownwardRelation) {
                    return $bomId;
                }
            }
        }

        return null;
    }

    private function firstBomIdWithNonSmallestProductUnit(array $productionItems): ?int
    {
        foreach ($productionItems as $value) {
            $bomId = (int) ($value['bom_id'] ?? 0);
            $productVariantId = (int) ($value['product_variant_id'] ?? 0);
            if ($bomId <= 0 || $productVariantId <= 0) {
                continue;
            }
            $bom = (new Bom())->getBom(['bom_id' => $bomId])->first();
            if (!$bom) {
                continue;
            }
            $isNotSmallest = ProductRelation::where('product_variant_id', $productVariantId)
                ->where('pr_unit_id_1', (int) $bom->unit_id)
                ->where('status', 1)
                ->exists();
            if ($isNotSmallest) {
                return $bomId;
            }
        }

        return null;
    }

    /**
     * Trading di BOM: stok fisik di product_variant link, bukan supplies_stocks.
     *
     * @return array{pv_id:int,name:string}|null
     */
    private function tradingBomSupplyMeta(int $suppliesId): ?array
    {
        if ($suppliesId <= 0 || ! Supplies::hasKindColumn()) {
            return null;
        }
        $s = Supplies::find($suppliesId);
        if (! $s || ! Supplies::isTradingKind($s->supplies_kind ?? null)) {
            return null;
        }

        return [
            'pv_id' => Schema::hasColumn('supplies', 'trading_product_variant_id')
                ? (int) ($s->trading_product_variant_id ?? 0)
                : 0,
            'name' => (string) ($s->supplies_name ?? ('Bahan #'.$suppliesId)),
        ];
    }

    private function activeMainProductionWarehouse(): ?Warehouse
    {
        $activeWarehouseId = (int) (session('active_warehouse_id') ?? 0);
        if ($activeWarehouseId <= 0) {
            return null;
        }

        return Warehouse::query()
            ->active()
            ->whereKey($activeWarehouseId)
            ->whereHas('type', fn($query) => $query->where('is_main_warehouse', 1))
            ->first();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{ok:bool,message:?string}
     */
    private function normalizeProductionDestinations(array &$items): array
    {
        if (! Schema::hasColumn('production_details', 'destination_warehouse_id')) {
            return ['ok' => false, 'message' => 'Migrasi production stock transfer belum dijalankan.'];
        }

        $mainWarehouse = $this->activeMainProductionWarehouse();
        if (! $mainWarehouse) {
            return [
                'ok' => false,
                'message' => 'Pilih gudang utama sebagai gudang aktif sebelum membuat produksi.',
            ];
        }

        $variantIds = collect($items)->pluck('product_variant_id')->map(fn($id) => (int) $id)->unique();
        $variants = ProductVariant::query()
            ->whereIn('product_variant_id', $variantIds)
            ->get()
            ->keyBy('product_variant_id');

        foreach ($items as &$item) {
            $variant = $variants->get((int) ($item['product_variant_id'] ?? 0));
            if (! $variant) {
                return ['ok' => false, 'message' => 'Varian produk tidak ditemukan.'];
            }

            $isRetail = (int) ($variant->retail_unit ?? 0) > 0
                && (int) ($item['unit_id'] ?? 0) === (int) $variant->retail_unit;
            if (! $isRetail) {
                $item['destination_warehouse_id'] = (int) $mainWarehouse->id;
                continue;
            }

            // Satuan eceran: boleh gudang eceran (ST) ATAU gudang utama/aktif (stok tanpa ST).
            $destinationId = (int) ($item['destination_warehouse_id'] ?? 0);
            $destWh = $destinationId > 0
                ? Warehouse::query()->active()->with('type')->whereKey($destinationId)->first()
                : null;
            if (! $destWh) {
                return [
                    'ok' => false,
                    'message' => 'Pilih gudang tujuan aktif untuk hasil produksi bersatuan eceran.',
                ];
            }
            $destIsMain = (int) ($destWh->type->is_main_warehouse ?? 0) === 1;
            if ($destIsMain || $destinationId === (int) $mainWarehouse->id) {
                $item['destination_warehouse_id'] = (int) $mainWarehouse->id;
            }
        }
        unset($item);

        return ['ok' => true, 'message' => null];
    }

    /**
     * @return array{ok:bool,message:?string,groups:array<int,array{items:array<int,array<string,mixed>>}>}
     */
    private function buildProductionTransferPlan($details, int $mainWarehouseId): array
    {
        $groups = [];
        $activeWarehouses = Warehouse::query()
            ->active()
            ->with('type')
            ->get()
            ->keyBy('id');

        foreach ($details as $detail) {
            $variant = ProductVariant::query()->find((int) $detail->product_variant_id);
            $product = $variant ? Product::query()->find((int) $variant->product_id) : null;
            $inputUnitId = (int) $detail->unit_id;
            $qty = (float) $detail->pd_qty;
            if (! $variant || ! $product || $inputUnitId <= 0 || $qty <= 0) {
                return ['ok' => false, 'message' => 'Produk, satuan, atau qty hasil produksi tidak valid.', 'groups' => []];
            }
            if (abs($qty - round($qty)) > 1e-9) {
                return [
                    'ok' => false,
                    'message' => 'Qty hasil produksi harus bilangan bulat untuk '
                        . trim($product->product_name . ' ' . $variant->product_variant_name) . '.',
                    'groups' => [],
                ];
            }

            $destinationId = (int) ($detail->destination_warehouse_id ?? 0);
            if ($destinationId <= 0) {
                $destinationId = $mainWarehouseId;
            }
            $destWh = $activeWarehouses->get($destinationId);
            if (! $destWh) {
                return [
                    'ok' => false,
                    'message' => 'Gudang tujuan hasil produksi tidak aktif atau belum dipilih.',
                    'groups' => [],
                ];
            }

            $isRetail = (int) ($variant->retail_unit ?? 0) > 0
                && $inputUnitId === (int) $variant->retail_unit;
            if ($isRetail) {
                // Eceran → gudang eceran (ST). Eceran → gudang utama = stok di asal, tanpa ST.
                $destIsMain = (int) ($destWh->type->is_main_warehouse ?? 0) === 1;
                if ($destIsMain || $destinationId === $mainWarehouseId) {
                    $destinationId = $mainWarehouseId;
                }
            } else {
                $destinationId = $mainWarehouseId;
            }

            // Hasil selalu diinventori ke gudang asal; ST hanya jika tujuan beda (eceran/lain).
            $detail->destination_warehouse_id = $destinationId;
            $key = (int) $variant->product_variant_id . ':' . $inputUnitId;
            $groups[$destinationId] ??= ['items' => []];
            $groups[$destinationId]['items'][$key] ??= [
                'product_id' => (int) $product->product_id,
                'product_variant_id' => (int) $variant->product_variant_id,
                'unit_id' => $inputUnitId,
                'qty' => 0,
            ];
            $groups[$destinationId]['items'][$key]['qty'] += (int) round($qty);
        }

        foreach ($groups as &$group) {
            $group['items'] = array_values($group['items']);
        }
        unset($group);

        return ['ok' => true, 'message' => null, 'groups' => $groups];
    }

    /**
     * Flag list/UI: can_cancel false jika ada ST linked status Kirim(2)/Terkirim(4).
     *
     * @param  \Illuminate\Support\Collection|array  $productions
     */
    private function attachProductionCancelFlags($productions): void
    {
        if ($productions === null || (is_countable($productions) && count($productions) === 0)) {
            return;
        }

        $shippedIds = [];
        if (
            Schema::hasColumn('stock_transfers', 'source_type')
            && Schema::hasColumn('stock_transfers', 'source_id')
        ) {
            $ids = collect($productions)->pluck('production_id')->map(fn ($id) => (int) $id)->filter()->unique()->all();
            if ($ids !== []) {
                $shippedIds = StockTransfer::query()
                    ->where('source_type', 'production')
                    ->whereIn('source_id', $ids)
                    ->whereIn('status', [2, 4])
                    ->pluck('source_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->flip()
                    ->all();
            }
        }

        foreach ($productions as $production) {
            $blocked = isset($shippedIds[(int) $production->production_id]);
            $production->has_shipped_stock_transfer = $blocked;
            $production->can_cancel = ! $blocked;
            $production->can_cancel_production = ! $blocked;
        }
    }
}
