<?php

namespace App\Http\Controllers;

use App\Models\CustomerProductReturn;
use App\Models\CustomerProductReturnDetail;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\LogStock;
use App\Models\ProductStock;
use App\Models\SuppliesStock;
use App\Support\RoleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Unified façade over customer_supply_returns + customer_product_returns.
 * Campuran = both sides linked by return_group (display number PKR####).
 */
class CustomerReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility('view');

        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        $orderIndex = (int) data_get($request->all(), 'order.0.column', 1);
        $orderDirection = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';

        $rows = $this->buildUnifiedRows($search);
        $recordsTotal = $this->countUnifiedRows('');
        $recordsFiltered = count($rows);

        $sortKeys = [
            'return_number',
            'return_date',
            'return_type',
            'ref_number',
            'customer_name',
            'status',
            'created_by_name',
            'acc_by_name',
        ];
        $sortKey = $sortKeys[$orderIndex] ?? 'return_date';
        usort($rows, function ($a, $b) use ($sortKey, $orderDirection) {
            $av = $a[$sortKey] ?? '';
            $bv = $b[$sortKey] ?? '';
            if ($sortKey === 'return_date' || $sortKey === 'status') {
                $cmp = $av <=> $bv;
            } else {
                $cmp = strcasecmp((string) $av, (string) $bv);
            }
            if ($cmp === 0) {
                $cmp = strcmp((string) ($b['doc_key'] ?? ''), (string) ($a['doc_key'] ?? ''));
            }

            return $orderDirection === 'asc' ? $cmp : -$cmp;
        });

        $page = array_slice($rows, $start, $length);

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $page,
        ]);
    }

    public function context(): JsonResponse
    {
        $this->authorizeAbility('view');

        return response()->json($this->buildReturnContext());
    }

    public function show(string $docKey): JsonResponse
    {
        $this->authorizeAbility('view');
        $bundle = $this->resolveBundle($docKey);
        if (! $bundle) {
            return response()->json(['message' => 'Pengembalian tidak ditemukan.'], 404);
        }

        $header = $bundle['header'];
        $header['doc_key'] = $bundle['doc_key'];
        $header['return_type'] = $bundle['return_type'];
        $header['return_number'] = $bundle['display_number'];
        $header['supply_return_id'] = $bundle['supply']->return_id ?? null;
        $header['product_return_id'] = $bundle['product']->return_id ?? null;
        $header['proof_url'] = ! empty($header['proof_path']) ? asset($header['proof_path']) : null;
        $header['supply_details'] = $bundle['supply_details'];
        $header['product_details'] = $bundle['product_details'];
        $header['context'] = $this->buildReturnContext();

        return response()->json($header);
    }

    public function printForm(string $docKey)
    {
        $this->authorizeAbility('view');
        $bundle = $this->resolveBundle($docKey);
        if (! $bundle) {
            abort(404, 'Pengembalian tidak ditemukan.');
        }
        if ((int) ($bundle['status'] ?? 0) !== 2) {
            abort(403, 'Pengembalian harus dikonfirmasi terlebih dahulu.');
        }

        $param = $this->buildPrintPayload($bundle);
        $pdf = Pdf::loadView('Backoffice.PDF.FormSisaBarang', $param)
            ->setPaper([0, 0, 419.0, 596.0], 'portrait');

        return $pdf->stream('Form_Sisa_Barang_' . $param['nomor_dokumen'] . '.pdf');
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility('create');

        [$data, $supplyDetails, $productDetails] = $this->validatedPayload($request, true);
        $newProofPath = null;

        try {
            $newProofPath = $this->storeProof($request, true);
            $record = DB::transaction(function () use ($data, $supplyDetails, $productDetails, $newProofPath) {
                $this->validateSupplyDetails($supplyDetails);
                $this->validateProductDetails($productDetails);

                $group = $this->generateReturnGroup();
                $supply = null;
                $product = null;

                if ($supplyDetails !== []) {
                    $supply = CustomerSupplyReturn::create([
                        'return_number' => (new CustomerSupplyReturn())->generateReturnNumber(),
                        'return_group' => $group,
                        'so_id' => null,
                        'customer_id' => $data['customer_id'],
                        'return_date' => $data['return_date'],
                        'ref_number' => $data['ref_number'] ?: null,
                        'notes' => $data['notes'] ?: null,
                        'proof_path' => $newProofPath,
                        'status' => 1,
                        'created_by' => $this->userId(),
                    ]);
                    $this->replaceSupplyDetails($supply->return_id, $supplyDetails);
                }

                if ($productDetails !== []) {
                    $product = CustomerProductReturn::create([
                        'return_number' => (new CustomerProductReturn())->generateReturnNumber(),
                        'return_group' => $group,
                        'customer_id' => $data['customer_id'],
                        'return_date' => $data['return_date'],
                        'ref_number' => $data['ref_number'] ?: null,
                        'notes' => $data['notes'] ?: null,
                        'proof_path' => $newProofPath,
                        'status' => 1,
                        'created_by' => $this->userId(),
                    ]);
                    $this->replaceProductDetails($product->return_id, $productDetails);
                }

                return [
                    'doc_key' => $group,
                    'return_group' => $group,
                    'return_type' => $this->resolveType($supply !== null, $product !== null),
                    'supply_return_id' => $supply?->return_id,
                    'product_return_id' => $product?->return_id,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Pengembalian berhasil disimpan.',
                'data' => $record,
            ]);
        } catch (\Throwable $e) {
            $this->deleteProof($newProofPath);
            throw $e;
        }
    }

    public function update(Request $request, string $docKey): JsonResponse
    {
        $this->authorizeAbility('edit');
        [$data, $supplyDetails, $productDetails] = $this->validatedPayload($request, false);
        $newProofPath = null;
        $oldProofPaths = [];

        try {
            if ($request->hasFile('proof') || $request->filled('proof_base64')) {
                $newProofPath = $this->storeProof($request, false);
            }

            DB::transaction(function () use ($docKey, $data, $supplyDetails, $productDetails, $newProofPath, &$oldProofPaths) {
                $bundle = $this->resolveBundle($docKey, true);
                if (! $bundle) {
                    throw ValidationException::withMessages(['doc_key' => 'Pengembalian tidak ditemukan.']);
                }
                if ((int) $bundle['status'] !== 1) {
                    throw ValidationException::withMessages(['status' => 'Hanya pengembalian Pending yang dapat diubah.']);
                }

                $this->validateSupplyDetails($supplyDetails);
                $this->validateProductDetails($productDetails);

                $group = $bundle['return_group'] ?: $this->generateReturnGroup();
                $proofPath = $newProofPath ?: ($bundle['header']['proof_path'] ?? null);

                if ($supplyDetails !== []) {
                    if ($bundle['supply']) {
                        $oldProofPaths[] = $bundle['supply']->proof_path;
                        $bundle['supply']->fill([
                            'return_group' => $group,
                            'customer_id' => $data['customer_id'],
                            'return_date' => $data['return_date'],
                            'ref_number' => $data['ref_number'] ?: null,
                            'notes' => $data['notes'] ?: null,
                        ]);
                        if ($newProofPath) {
                            $bundle['supply']->proof_path = $newProofPath;
                        }
                        $bundle['supply']->save();
                        $this->replaceSupplyDetails($bundle['supply']->return_id, $supplyDetails);
                    } else {
                        $supply = CustomerSupplyReturn::create([
                            'return_number' => (new CustomerSupplyReturn())->generateReturnNumber(),
                            'return_group' => $group,
                            'so_id' => null,
                            'customer_id' => $data['customer_id'],
                            'return_date' => $data['return_date'],
                            'ref_number' => $data['ref_number'] ?: null,
                            'notes' => $data['notes'] ?: null,
                            'proof_path' => $proofPath,
                            'status' => 1,
                            'created_by' => $this->userId(),
                        ]);
                        $this->replaceSupplyDetails($supply->return_id, $supplyDetails);
                    }
                } elseif ($bundle['supply']) {
                    $oldProofPaths[] = $bundle['supply']->proof_path;
                    $bundle['supply']->status = 0;
                    $bundle['supply']->return_group = $group;
                    $bundle['supply']->save();
                    CustomerSupplyReturnDetail::where('return_id', $bundle['supply']->return_id)->update(['status' => 0]);
                }

                if ($productDetails !== []) {
                    if ($bundle['product']) {
                        $oldProofPaths[] = $bundle['product']->proof_path;
                        $bundle['product']->fill([
                            'return_group' => $group,
                            'customer_id' => $data['customer_id'],
                            'return_date' => $data['return_date'],
                            'ref_number' => $data['ref_number'] ?: null,
                            'notes' => $data['notes'] ?: null,
                        ]);
                        if ($newProofPath) {
                            $bundle['product']->proof_path = $newProofPath;
                        }
                        $bundle['product']->save();
                        $this->replaceProductDetails($bundle['product']->return_id, $productDetails);
                    } else {
                        $product = CustomerProductReturn::create([
                            'return_number' => (new CustomerProductReturn())->generateReturnNumber(),
                            'return_group' => $group,
                            'customer_id' => $data['customer_id'],
                            'return_date' => $data['return_date'],
                            'ref_number' => $data['ref_number'] ?: null,
                            'notes' => $data['notes'] ?: null,
                            'proof_path' => $proofPath,
                            'status' => 1,
                            'created_by' => $this->userId(),
                        ]);
                        $this->replaceProductDetails($product->return_id, $productDetails);
                    }
                } elseif ($bundle['product']) {
                    $oldProofPaths[] = $bundle['product']->proof_path;
                    $bundle['product']->status = 0;
                    $bundle['product']->return_group = $group;
                    $bundle['product']->save();
                    CustomerProductReturnDetail::where('return_id', $bundle['product']->return_id)->update(['status' => 0]);
                }

                // Keep sibling group in sync for remaining active side
                if ($bundle['supply'] && (int) $bundle['supply']->status === 1 && $bundle['supply']->return_group !== $group) {
                    $bundle['supply']->return_group = $group;
                    $bundle['supply']->save();
                }
                if ($bundle['product'] && (int) $bundle['product']->status === 1 && $bundle['product']->return_group !== $group) {
                    $bundle['product']->return_group = $group;
                    $bundle['product']->save();
                }
            });

            if ($newProofPath) {
                foreach (array_unique(array_filter($oldProofPaths)) as $old) {
                    if ($old !== $newProofPath) {
                        $this->deleteProof($old);
                    }
                }
            }

            return response()->json(['success' => true, 'message' => 'Pengembalian berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $this->deleteProof($newProofPath);
            throw $e;
        }
    }

    public function destroy(string $docKey): JsonResponse
    {
        $this->authorizeAbility('delete');

        DB::transaction(function () use ($docKey) {
            $bundle = $this->resolveBundle($docKey, true);
            if (! $bundle) {
                throw ValidationException::withMessages(['doc_key' => 'Pengembalian tidak ditemukan.']);
            }
            if ((int) $bundle['status'] !== 1) {
                throw ValidationException::withMessages(['status' => 'Hanya pengembalian Pending yang dapat dihapus.']);
            }
            if ($bundle['supply']) {
                $bundle['supply']->status = 0;
                $bundle['supply']->save();
                CustomerSupplyReturnDetail::where('return_id', $bundle['supply']->return_id)->update(['status' => 0]);
            }
            if ($bundle['product']) {
                $bundle['product']->status = 0;
                $bundle['product']->save();
                CustomerProductReturnDetail::where('return_id', $bundle['product']->return_id)->update(['status' => 0]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Pengembalian berhasil dihapus.']);
    }

    public function accept(string $docKey): JsonResponse
    {
        $this->authorizeAbility('others');

        DB::transaction(function () use ($docKey) {
            $bundle = $this->resolveBundle($docKey, true);
            if (! $bundle) {
                throw ValidationException::withMessages(['doc_key' => 'Pengembalian tidak ditemukan.']);
            }
            if ((int) $bundle['status'] !== 1) {
                throw ValidationException::withMessages(['status' => 'Pengembalian sudah diproses dan tidak dapat di-ACC ulang.']);
            }

            $customerName = DB::table('customers')
                ->where('customer_id', $bundle['header']['customer_id'])
                ->value('customer_notes') ?: '-';

            if ($bundle['supply']) {
                $this->acceptSupply($bundle['supply'], $customerName);
            }
            if ($bundle['product']) {
                $this->acceptProduct($bundle['product'], $customerName);
            }
        }, 3);

        return response()->json(['success' => true, 'message' => 'Pengembalian diterima dan stok telah ditambahkan.']);
    }

    public function decline(string $docKey): JsonResponse
    {
        $this->authorizeAbility('others');

        DB::transaction(function () use ($docKey) {
            $bundle = $this->resolveBundle($docKey, true);
            if (! $bundle) {
                throw ValidationException::withMessages(['doc_key' => 'Pengembalian tidak ditemukan.']);
            }
            if ((int) $bundle['status'] !== 1) {
                throw ValidationException::withMessages(['status' => 'Pengembalian sudah diproses.']);
            }
            $userId = $this->userId();
            if ($bundle['supply']) {
                $bundle['supply']->status = 3;
                $bundle['supply']->acc_by = $userId;
                $bundle['supply']->save();
            }
            if ($bundle['product']) {
                $bundle['product']->status = 3;
                $bundle['product']->acc_by = $userId;
                $bundle['product']->save();
            }
        });

        return response()->json(['success' => true, 'message' => 'Pengembalian ditolak tanpa perubahan stok.']);
    }

    private function acceptSupply(CustomerSupplyReturn $record, string $customerName): void
    {
        $details = CustomerSupplyReturnDetail::where('return_id', $record->return_id)
            ->where('status', 1)
            ->lockForUpdate()
            ->get()
            ->map(fn ($detail) => [
                'supplies_id' => (int) $detail->supplies_id,
                'unit_id' => (int) $detail->unit_id,
                'warehouse_id' => (int) $detail->warehouse_id,
                'qty' => (int) $detail->qty,
            ])->all();
        $this->validateSupplyDetails($details);

        foreach ($details as $detail) {
            $stock = SuppliesStock::withoutGlobalScope('active_warehouse')
                ->where('supplies_id', $detail['supplies_id'])
                ->where('unit_id', $detail['unit_id'])
                ->where('warehouse_id', $detail['warehouse_id'])
                ->lockForUpdate()
                ->first();
            if (! $stock) {
                $stock = new SuppliesStock([
                    'supplies_id' => $detail['supplies_id'],
                    'unit_id' => $detail['unit_id'],
                    'warehouse_id' => $detail['warehouse_id'],
                    'ss_stock' => 0,
                    'status' => 1,
                    'created_by' => $this->userId(),
                ]);
            }
            $stock->status = 1;
            $stock->ss_stock = (int) $stock->ss_stock + $detail['qty'];
            $stock->created_by = $this->userId();
            $stock->save();

            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode' => $record->return_group ?: $record->return_number,
                'log_type' => 2,
                'log_category' => 1,
                'log_item_id' => $detail['supplies_id'],
                'log_notes' => 'Pengembalian bahan/kemasan dari armada ' . $customerName,
                'log_jumlah' => $detail['qty'],
                'unit_id' => $detail['unit_id'],
                'warehouse_id' => $detail['warehouse_id'],
            ]);
        }

        $record->status = 2;
        $record->acc_by = $this->userId();
        $record->save();
    }

    private function acceptProduct(CustomerProductReturn $record, string $customerName): void
    {
        $details = CustomerProductReturnDetail::where('return_id', $record->return_id)
            ->where('status', 1)
            ->lockForUpdate()
            ->get()
            ->map(fn ($detail) => [
                'product_variant_id' => (int) $detail->product_variant_id,
                'unit_id' => (int) $detail->unit_id,
                'warehouse_id' => (int) $detail->warehouse_id,
                'qty' => (int) $detail->qty,
            ])->all();
        $this->validateProductDetails($details);

        foreach ($details as $detail) {
            $variant = DB::table('product_variants')
                ->where('product_variant_id', $detail['product_variant_id'])
                ->where('status', 1)
                ->first(['product_variant_id', 'product_id']);
            if (! $variant) {
                throw ValidationException::withMessages([
                    'product_details' => 'Varian produk tidak aktif: ' . $detail['product_variant_id'],
                ]);
            }

            $stock = ProductStock::withoutGlobalScope('active_warehouse')
                ->where('product_variant_id', $detail['product_variant_id'])
                ->where('unit_id', $detail['unit_id'])
                ->where('warehouse_id', $detail['warehouse_id'])
                ->lockForUpdate()
                ->first();
            if (! $stock) {
                $stock = new ProductStock([
                    'product_id' => (int) $variant->product_id,
                    'product_variant_id' => $detail['product_variant_id'],
                    'unit_id' => $detail['unit_id'],
                    'warehouse_id' => $detail['warehouse_id'],
                    'ps_stock' => 0,
                    'status' => 1,
                    'created_by' => $this->userId(),
                ]);
            }
            $stock->status = 1;
            $stock->ps_stock = (float) $stock->ps_stock + $detail['qty'];
            $stock->created_by = $this->userId();
            $stock->save();

            (new LogStock())->insertLog([
                'log_date' => now(),
                'log_kode' => $record->return_group ?: $record->return_number,
                'log_type' => 1,
                'log_category' => 1,
                'log_item_id' => $detail['product_variant_id'],
                'log_notes' => 'Pengembalian produk jadi dari armada ' . $customerName,
                'log_jumlah' => $detail['qty'],
                'unit_id' => $detail['unit_id'],
                'warehouse_id' => $detail['warehouse_id'],
            ]);
        }

        $record->status = 2;
        $record->acc_by = $this->userId();
        $record->save();
    }

    private function countUnifiedRows(string $search): int
    {
        return count($this->buildUnifiedRows($search));
    }

    private function activeWarehouseId(): int
    {
        return (int) (Session::get('active_warehouse_id') ?? 0);
    }

    /**
     * return_group yang punya minimal 1 detail (bahan/produk) di gudang aktif.
     *
     * @return array<int, string>
     */
    private function returnGroupsForWarehouse(int $warehouseId): array
    {
        if ($warehouseId <= 0) {
            return [];
        }

        $fromSupply = DB::table('customer_supply_return_details as d')
            ->join('customer_supply_returns as h', 'h.return_id', '=', 'd.return_id')
            ->where('d.warehouse_id', $warehouseId)
            ->where('d.status', '>', 0)
            ->where('h.status', '>', 0)
            ->whereNotNull('h.return_group')
            ->distinct()
            ->pluck('h.return_group');

        $fromProduct = DB::table('customer_product_return_details as d')
            ->join('customer_product_returns as h', 'h.return_id', '=', 'd.return_id')
            ->where('d.warehouse_id', $warehouseId)
            ->where('d.status', '>', 0)
            ->where('h.status', '>', 0)
            ->whereNotNull('h.return_group')
            ->distinct()
            ->pluck('h.return_group');

        return $fromSupply->merge($fromProduct)->filter()->unique()->values()->all();
    }

    /**
     * @return array<int, int>
     */
    private function standaloneSupplyReturnIdsForWarehouse(int $warehouseId): array
    {
        if ($warehouseId <= 0) {
            return [];
        }

        return DB::table('customer_supply_return_details as d')
            ->join('customer_supply_returns as h', 'h.return_id', '=', 'd.return_id')
            ->where('d.warehouse_id', $warehouseId)
            ->where('d.status', '>', 0)
            ->where('h.status', '>', 0)
            ->whereNull('h.return_group')
            ->distinct()
            ->pluck('d.return_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function standaloneProductReturnIdsForWarehouse(int $warehouseId): array
    {
        if ($warehouseId <= 0) {
            return [];
        }

        return DB::table('customer_product_return_details as d')
            ->join('customer_product_returns as h', 'h.return_id', '=', 'd.return_id')
            ->where('d.warehouse_id', $warehouseId)
            ->where('d.status', '>', 0)
            ->where('h.status', '>', 0)
            ->whereNull('h.return_group')
            ->distinct()
            ->pluck('d.return_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function applyWarehouseScope($query, string $alias, int $warehouseId): void
    {
        if ($warehouseId <= 0) {
            $query->whereRaw('1 = 0');

            return;
        }

        $groups = $this->returnGroupsForWarehouse($warehouseId);
        $standaloneIds = $alias === 'csr'
            ? $this->standaloneSupplyReturnIdsForWarehouse($warehouseId)
            : $this->standaloneProductReturnIdsForWarehouse($warehouseId);

        if ($groups === [] && $standaloneIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function ($scoped) use ($alias, $groups, $standaloneIds) {
            if ($groups !== []) {
                $scoped->whereIn($alias . '.return_group', $groups);
            }
            if ($standaloneIds !== []) {
                $scoped->orWhereIn($alias . '.return_id', $standaloneIds);
            }
        });
    }

    private function buildUnifiedRows(string $search): array
    {
        $like = $search !== '' ? '%' . $search . '%' : null;
        $activeWh = $this->activeWarehouseId();

        $supplyQuery = DB::table('customer_supply_returns as csr')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'csr.customer_id')
            ->leftJoin('staffs as creator', 'creator.staff_id', '=', 'csr.created_by')
            ->leftJoin('staffs as approver', 'approver.staff_id', '=', 'csr.acc_by')
            ->where('csr.status', '>', 0)
            ->select([
                'csr.return_id',
                'csr.return_number',
                'csr.return_group',
                'csr.return_date',
                'csr.ref_number',
                'csr.status',
                'c.customer_notes as customer_name',
                'creator.staff_name as created_by_name',
                'approver.staff_name as acc_by_name',
                DB::raw("'supply' as side"),
            ]);
        $this->applyWarehouseScope($supplyQuery, 'csr', $activeWh);
        if ($like) {
            $supplyQuery->where(function ($query) use ($like) {
                $query->where('csr.return_number', 'like', $like)
                    ->orWhere('csr.return_group', 'like', $like)
                    ->orWhere('csr.ref_number', 'like', $like)
                    ->orWhere('c.customer_notes', 'like', $like)
                    ->orWhere('creator.staff_name', 'like', $like)
                    ->orWhere('approver.staff_name', 'like', $like);
            });
        }

        $productQuery = DB::table('customer_product_returns as cpr')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'cpr.customer_id')
            ->leftJoin('staffs as creator', 'creator.staff_id', '=', 'cpr.created_by')
            ->leftJoin('staffs as approver', 'approver.staff_id', '=', 'cpr.acc_by')
            ->where('cpr.status', '>', 0)
            ->select([
                'cpr.return_id',
                'cpr.return_number',
                'cpr.return_group',
                'cpr.return_date',
                'cpr.ref_number',
                'cpr.status',
                'c.customer_notes as customer_name',
                'creator.staff_name as created_by_name',
                'approver.staff_name as acc_by_name',
                DB::raw("'product' as side"),
            ]);
        $this->applyWarehouseScope($productQuery, 'cpr', $activeWh);
        if ($like) {
            $productQuery->where(function ($query) use ($like) {
                $query->where('cpr.return_number', 'like', $like)
                    ->orWhere('cpr.return_group', 'like', $like)
                    ->orWhere('cpr.ref_number', 'like', $like)
                    ->orWhere('c.customer_notes', 'like', $like)
                    ->orWhere('creator.staff_name', 'like', $like)
                    ->orWhere('approver.staff_name', 'like', $like);
            });
        }

        $merged = [];
        foreach ($supplyQuery->get() as $row) {
            $key = $row->return_group ?: ('S:' . $row->return_id);
            if (! isset($merged[$key])) {
                $merged[$key] = $this->emptyUnifiedRow($key, $row);
            }
            $merged[$key]['has_supply'] = true;
            $merged[$key]['supply_return_id'] = (int) $row->return_id;
            $this->enrichUnifiedRow($merged[$key], $row);
        }
        foreach ($productQuery->get() as $row) {
            $key = $row->return_group ?: ('P:' . $row->return_id);
            if (! isset($merged[$key])) {
                $merged[$key] = $this->emptyUnifiedRow($key, $row);
            }
            $merged[$key]['has_product'] = true;
            $merged[$key]['product_return_id'] = (int) $row->return_id;
            $this->enrichUnifiedRow($merged[$key], $row);
        }

        foreach ($merged as &$row) {
            $row['return_type'] = $this->resolveType(! empty($row['has_supply']), ! empty($row['has_product']));
            $row['return_number'] = $row['return_group'] ?: $row['legacy_number'];
            unset($row['has_supply'], $row['has_product'], $row['legacy_number'], $row['return_group']);
        }
        unset($row);

        return array_values($merged);
    }

    private function emptyUnifiedRow(string $key, object $row): array
    {
        return [
            'doc_key' => $key,
            'return_group' => $row->return_group,
            'legacy_number' => $row->return_number,
            'return_number' => $row->return_group ?: $row->return_number,
            'return_date' => $row->return_date,
            'ref_number' => $row->ref_number,
            'customer_name' => $row->customer_name,
            'status' => (int) $row->status,
            'created_by_name' => $row->created_by_name,
            'acc_by_name' => $row->acc_by_name,
            'has_supply' => false,
            'has_product' => false,
            'supply_return_id' => null,
            'product_return_id' => null,
            'return_type' => 'supply',
        ];
    }

    private function enrichUnifiedRow(array &$target, object $row): void
    {
        // Prefer later / richer header fields when merging mixed sides
        if ($row->return_date) {
            $target['return_date'] = $row->return_date;
        }
        if ($row->ref_number) {
            $target['ref_number'] = $row->ref_number;
        }
        if ($row->customer_name) {
            $target['customer_name'] = $row->customer_name;
        }
        if ($row->created_by_name) {
            $target['created_by_name'] = $row->created_by_name;
        }
        if ($row->acc_by_name) {
            $target['acc_by_name'] = $row->acc_by_name;
        }
        // If sides diverge on status, surface the more "open" one (pending wins)
        $status = (int) $row->status;
        if (! isset($target['status']) || $status === 1 || ((int) $target['status'] !== 1 && $status < (int) $target['status'])) {
            $target['status'] = $status;
        }
        if ($row->return_group) {
            $target['return_group'] = $row->return_group;
        }
    }

    private function resolveType(bool $hasSupply, bool $hasProduct): string
    {
        if ($hasSupply && $hasProduct) {
            return 'mixed';
        }
        if ($hasProduct) {
            return 'product';
        }

        return 'supply';
    }

    private function resolveBundle(string $docKey, bool $lock = false): ?array
    {
        $supply = null;
        $product = null;

        if (str_starts_with($docKey, 'S:')) {
            $id = (int) substr($docKey, 2);
            $q = CustomerSupplyReturn::where('return_id', $id)->where('status', '>', 0);
            $supply = $lock ? $q->lockForUpdate()->first() : $q->first();
            if ($supply && $supply->return_group) {
                $pq = CustomerProductReturn::where('return_group', $supply->return_group)->where('status', '>', 0);
                $product = $lock ? $pq->lockForUpdate()->first() : $pq->first();
            }
        } elseif (str_starts_with($docKey, 'P:')) {
            $id = (int) substr($docKey, 2);
            $q = CustomerProductReturn::where('return_id', $id)->where('status', '>', 0);
            $product = $lock ? $q->lockForUpdate()->first() : $q->first();
            if ($product && $product->return_group) {
                $sq = CustomerSupplyReturn::where('return_group', $product->return_group)->where('status', '>', 0);
                $supply = $lock ? $sq->lockForUpdate()->first() : $sq->first();
            }
        } else {
            $sq = CustomerSupplyReturn::where('return_group', $docKey)->where('status', '>', 0);
            $pq = CustomerProductReturn::where('return_group', $docKey)->where('status', '>', 0);
            $supply = $lock ? $sq->lockForUpdate()->first() : $sq->first();
            $product = $lock ? $pq->lockForUpdate()->first() : $pq->first();
        }

        if (! $supply && ! $product) {
            return null;
        }

        $primary = $supply ?: $product;
        $customerId = $primary->customer_id;
        $customer = DB::table('customers')->where('customer_id', $customerId)->first([
            'customer_notes',
            'customer_code',
            'customer_pic',
            'customer_pic_phone',
        ]);
        $customerName = $customer?->customer_notes;
        $createdBy = $primary->created_by;
        $accBy = $primary->acc_by;
        $createdByName = $createdBy
            ? DB::table('staffs')->where('staff_id', $createdBy)->value('staff_name')
            : null;
        $accByName = $accBy
            ? DB::table('staffs')->where('staff_id', $accBy)->value('staff_name')
            : null;

        $group = $supply->return_group ?? $product->return_group ?? null;
        $docKeyResolved = $group ?: ($supply ? ('S:' . $supply->return_id) : ('P:' . $product->return_id));
        $display = $group ?: $primary->return_number;
        $hasSupply = $supply !== null;
        $hasProduct = $product !== null;

        $supplyDetails = [];
        if ($supply) {
            $supplyDetails = DB::table('customer_supply_return_details as d')
                ->join('supplies as s', 's.supplies_id', '=', 'd.supplies_id')
                ->join('units as u', 'u.unit_id', '=', 'd.unit_id')
                ->join('warehouses as w', 'w.id', '=', 'd.warehouse_id')
                ->where('d.return_id', $supply->return_id)
                ->where('d.status', 1)
                ->orderBy('d.return_detail_id')
                ->get([
                    'd.return_detail_id',
                    'd.supplies_id',
                    'd.unit_id',
                    'd.warehouse_id',
                    'd.qty',
                    's.supplies_name',
                    'u.unit_name',
                    'u.unit_short_name',
                    'w.warehouse_name',
                ])->all();
        }

        $productDetails = [];
        if ($product) {
            $productDetails = DB::table('customer_product_return_details as d')
                ->join('product_variants as pv', 'pv.product_variant_id', '=', 'd.product_variant_id')
                ->join('products as p', 'p.product_id', '=', 'pv.product_id')
                ->join('units as u', 'u.unit_id', '=', 'd.unit_id')
                ->join('warehouses as w', 'w.id', '=', 'd.warehouse_id')
                ->where('d.return_id', $product->return_id)
                ->where('d.status', 1)
                ->orderBy('d.return_detail_id')
                ->get([
                    'd.return_detail_id',
                    'd.product_variant_id',
                    'd.unit_id',
                    'd.warehouse_id',
                    'd.qty',
                    'p.product_name',
                    'pv.product_variant_name',
                    'pv.product_variant_sku',
                    'u.unit_name',
                    'u.unit_short_name',
                    'w.warehouse_name',
                ]);
            foreach ($productDetails as $detail) {
                $detail->product_label = $this->formatProductVariantLabel(
                    $detail->product_name ?? '',
                    $detail->product_variant_name ?? '',
                    $detail->product_variant_sku ?? ''
                );
            }
            $productDetails = $productDetails->all();
        }

        $status = 1;
        $statuses = array_filter([
            $supply ? (int) $supply->status : null,
            $product ? (int) $product->status : null,
        ], fn ($v) => $v !== null);
        if (in_array(1, $statuses, true)) {
            $status = 1;
        } else {
            $status = (int) ($statuses[0] ?? 1);
        }

        return [
            'doc_key' => $docKeyResolved,
            'return_group' => $group,
            'display_number' => $display,
            'return_type' => $this->resolveType($hasSupply, $hasProduct),
            'status' => $status,
            'supply' => $supply,
            'product' => $product,
            'supply_details' => $supplyDetails,
            'product_details' => $productDetails,
            'header' => [
                'customer_id' => (int) $customerId,
                'customer_name' => $customerName,
                'customer_code' => $customer?->customer_code,
                'customer_pic' => $customer?->customer_pic,
                'customer_pic_phone' => $customer?->customer_pic_phone,
                'return_date' => optional($primary->return_date)->format('Y-m-d') ?? (string) $primary->return_date,
                'ref_number' => $primary->ref_number,
                'notes' => $primary->notes,
                'proof_path' => $primary->proof_path,
                'status' => $status,
                'created_by_name' => $createdByName,
                'acc_by_name' => $accByName,
                'created_at' => $primary->created_at?->toDateTimeString(),
            ],
        ];
    }

    /**
     * Payload FORM-OPS-32 (Formulir Sisa Barang) dari satu dokumen pengembalian.
     *
     * @param  array<string, mixed>  $bundle
     * @return array<string, mixed>
     */
    private function buildPrintPayload(array $bundle): array
    {
        $header = $bundle['header'];
        $tanggal = $this->formatPrintDate($header['return_date'] ?? null);
        $jam = $this->formatPrintTime($header['created_at'] ?? null);
        $items = [];

        foreach ($bundle['supply_details'] ?? [] as $detail) {
            $unit = trim((string) ($detail->unit_short_name ?: $detail->unit_name ?: ''));
            $name = trim((string) ($detail->supplies_name ?? '-'));
            $items[] = [
                'nama' => $name,
                'qty' => (int) ($detail->qty ?? 0),
                'unit' => $unit,
            ];
        }
        foreach ($bundle['product_details'] ?? [] as $detail) {
            $unit = trim((string) ($detail->unit_short_name ?: $detail->unit_name ?: ''));
            $name = trim(preg_replace(
                '/\s+/',
                ' ',
                trim((string) ($detail->product_name ?? '')) . ' ' . trim((string) ($detail->product_variant_name ?? ''))
            ));
            if ($name === '') {
                $label = (string) ($detail->product_label ?? '');
                $name = str_contains($label, ' | ') ? trim(explode(' | ', $label, 2)[1]) : $label;
            }
            $items[] = [
                'nama' => $name !== '' ? $name : '-',
                'qty' => (int) ($detail->qty ?? 0),
                'unit' => $unit,
            ];
        }

        $minRows = 6;
        while (count($items) < $minRows) {
            $items[] = ['nama' => '', 'qty' => null, 'unit' => ''];
        }

        $armadaName = trim((string) ($header['customer_name'] ?? ''));
        $sopirArmada = trim((string) ($header['customer_pic'] ?? ''));
        if ($sopirArmada === '') {
            $sopirArmada = $armadaName;
        }

        return [
            'nomor_dokumen' => $bundle['display_number'] ?: '-',
            'tanggal' => $tanggal,
            'jam' => $jam,
            'pic_name' => $sopirArmada !== '' ? $sopirArmada : '-',
            'sopir_name' => $armadaName !== '' ? $armadaName : ($sopirArmada !== '' ? $sopirArmada : '-'),
            'kepala_operasional' => $header['acc_by_name'] ?: '',
            'items' => $items,
        ];
    }

    private function formatPrintDate($value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function formatPrintTime($value): string
    {
        if (! $value) {
            return '';
        }
        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function validatedPayload(Request $request, bool $proofRequired): array
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'customer_id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'return_date' => ['required', 'date'],
            'ref_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'supply_details' => ['nullable'],
            'product_details' => ['nullable'],
            'proof' => [$proofRequired ? 'required_without:proof_base64' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'proof_base64' => [$proofRequired ? 'required_without:proof' : 'nullable', 'string'],
        ]);
        $validator->validate();

        $supplyDetails = $this->parseSupplyDetails($request->input('supply_details'));
        $productDetails = $this->parseProductDetails($request->input('product_details'));
        if ($supplyDetails === [] && $productDetails === []) {
            throw ValidationException::withMessages([
                'supply_details' => 'Minimal satu bahan mentah atau produk jadi harus ditambahkan.',
            ]);
        }

        return [[
            'customer_id' => (int) $request->input('customer_id'),
            'return_date' => $request->input('return_date'),
            'ref_number' => trim((string) $request->input('ref_number', '')),
            'notes' => trim((string) $request->input('notes', '')),
        ], $supplyDetails, $productDetails];
    }

    private function parseSupplyDetails($raw): array
    {
        $details = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($details) || $details === []) {
            return [];
        }
        $merged = [];
        foreach ($details as $index => $detail) {
            $line = Validator::make((array) $detail, [
                'supplies_id' => ['required', 'integer', 'min:1'],
                'unit_id' => ['required', 'integer', 'min:1'],
                'warehouse_id' => ['required', 'integer', 'min:1'],
                'qty' => ['required', 'integer', 'min:1', 'max:999999999999'],
            ])->validate();
            $key = $line['supplies_id'] . '|' . $line['unit_id'] . '|' . $line['warehouse_id'];
            if (! isset($merged[$key])) {
                $merged[$key] = $line;
            } else {
                $merged[$key]['qty'] = (int) $merged[$key]['qty'] + (int) $line['qty'];
            }
            if ($merged[$key]['qty'] <= 0) {
                throw ValidationException::withMessages(["supply_details.$index.qty" => 'Qty harus lebih dari 0.']);
            }
        }

        return array_values($merged);
    }

    private function parseProductDetails($raw): array
    {
        $details = is_string($raw) ? json_decode($raw, true) : $raw;
        if (! is_array($details) || $details === []) {
            return [];
        }
        $merged = [];
        foreach ($details as $index => $detail) {
            $line = Validator::make((array) $detail, [
                'product_variant_id' => ['required', 'integer', 'min:1'],
                'unit_id' => ['required', 'integer', 'min:1'],
                'warehouse_id' => ['required', 'integer', 'min:1'],
                'qty' => ['required', 'integer', 'min:1', 'max:999999999999'],
            ])->validate();
            $key = $line['product_variant_id'] . '|' . $line['unit_id'] . '|' . $line['warehouse_id'];
            if (! isset($merged[$key])) {
                $merged[$key] = $line;
            } else {
                $merged[$key]['qty'] = (int) $merged[$key]['qty'] + (int) $line['qty'];
            }
            if ($merged[$key]['qty'] <= 0) {
                throw ValidationException::withMessages(["product_details.$index.qty" => 'Qty harus lebih dari 0.']);
            }
        }

        return array_values($merged);
    }

    private function validateSupplyDetails(array $details): void
    {
        if ($details === []) {
            return;
        }
        $context = $this->buildReturnContext();
        $allowed = collect($context['supplies'])->keyBy('supplies_id');
        $mainWarehouseIds = collect($context['supply_warehouses'])->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($details as $index => $detail) {
            $supply = $allowed->get((int) $detail['supplies_id']);
            if (! $supply) {
                throw ValidationException::withMessages(["supply_details.$index.supplies_id" => 'Bahan tidak aktif atau tidak valid.']);
            }
            if (! collect($supply['units'])->contains(fn ($unit) => (int) $unit['unit_id'] === (int) $detail['unit_id'])) {
                throw ValidationException::withMessages(["supply_details.$index.unit_id" => 'Satuan tidak aktif untuk bahan yang dipilih.']);
            }
            if (! $mainWarehouseIds->contains((int) $detail['warehouse_id'])) {
                throw ValidationException::withMessages(["supply_details.$index.warehouse_id" => 'Gudang tujuan bahan harus gudang utama aktif.']);
            }
        }
    }

    private function validateProductDetails(array $details): void
    {
        if ($details === []) {
            return;
        }
        $context = $this->buildReturnContext();
        $allowed = collect($context['products'])->keyBy('product_variant_id');
        $warehousesById = collect($context['product_warehouses'])->keyBy('id');
        $warehouseIds = $warehousesById->keys()->map(fn ($id) => (int) $id);
        $mainWarehouseIds = collect($context['supply_warehouses'])->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($details as $index => $detail) {
            $product = $allowed->get((int) $detail['product_variant_id']);
            if (! $product) {
                throw ValidationException::withMessages(["product_details.$index.product_variant_id" => 'Produk tidak aktif atau tidak valid.']);
            }
            if (! collect($product['units'])->contains(fn ($unit) => (int) $unit['unit_id'] === (int) $detail['unit_id'])) {
                throw ValidationException::withMessages(["product_details.$index.unit_id" => 'Satuan tidak aktif untuk produk yang dipilih.']);
            }
            if (! $warehouseIds->contains((int) $detail['warehouse_id'])) {
                throw ValidationException::withMessages(["product_details.$index.warehouse_id" => 'Gudang tujuan tidak valid.']);
            }
            $retailUnitId = (int) ($product['retail_unit'] ?? 0);
            $isRetailUnit = $retailUnitId > 0 && (int) $detail['unit_id'] === $retailUnitId;
            if ($isRetailUnit) {
                $warehouse = $warehousesById->get((int) $detail['warehouse_id']);
                $isRetailWarehouse = $warehouse && (int) ($warehouse['is_main_warehouse'] ?? 1) === 0;
                if (! $isRetailWarehouse) {
                    throw ValidationException::withMessages([
                        "product_details.$index.warehouse_id" => 'Satuan eceran harus dikembalikan ke gudang eceran.',
                    ]);
                }
                if ($retailUnitId <= 0) {
                    throw ValidationException::withMessages([
                        "product_details.$index.unit_id" => 'Produk tidak punya satuan eceran; tidak bisa dikembalikan ke gudang eceran.',
                    ]);
                }
            } elseif (! $mainWarehouseIds->contains((int) $detail['warehouse_id'])) {
                throw ValidationException::withMessages([
                    "product_details.$index.warehouse_id" => 'Satuan selain eceran harus dikembalikan ke gudang utama.',
                ]);
            }
        }
    }

    private function buildReturnContext(): array
    {
        return [
            'supplies' => $this->buildSuppliesContext(),
            'products' => $this->buildProductsContext(),
            'supply_warehouses' => $this->mainWarehouses(),
            'product_warehouses' => $this->allActiveWarehouses(),
            'active_warehouse' => $this->activeWarehouseContext(),
        ];
    }

    private function activeWarehouseContext(): ?array
    {
        $activeId = (int) (session('active_warehouse_id') ?? 0);
        if ($activeId <= 0) {
            return null;
        }

        $row = DB::table('warehouses as w')
            ->leftJoin('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.id', $activeId)
            ->where('w.status', 1)
            ->first(['w.id', 'w.warehouse_name', 'wt.is_main_warehouse']);

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'warehouse_name' => $row->warehouse_name,
            'is_main_warehouse' => (int) ($row->is_main_warehouse ?? 1),
        ];
    }

    private function buildSuppliesContext(): array
    {
        $suppliesRows = DB::table('supplies')
            ->where('status', 1)
            ->orderBy('supplies_name')
            ->get(['supplies_id', 'supplies_name', 'supplies_unit', 'supplies_default_unit']);

        $supplyIds = $suppliesRows->pluck('supplies_id')->map(fn ($id) => (int) $id)->values();
        $relationUnits = $supplyIds->isEmpty()
            ? collect()
            : DB::table('supplies_relations')
                ->where('status', 1)
                ->whereIn('supplies_id', $supplyIds)
                ->get(['supplies_id', 'su_id_1', 'su_id_2'])
                ->groupBy('supplies_id');

        $allUnitIds = [];
        foreach ($suppliesRows as $row) {
            if ((int) ($row->supplies_default_unit ?? 0) > 0) {
                $allUnitIds[(int) $row->supplies_default_unit] = true;
            }
            foreach ((array) (json_decode($row->supplies_unit ?? '[]', true) ?: []) as $unitId) {
                $allUnitIds[(int) $unitId] = true;
            }
            foreach ($relationUnits->get($row->supplies_id, collect()) as $relation) {
                $allUnitIds[(int) $relation->su_id_1] = true;
                $allUnitIds[(int) $relation->su_id_2] = true;
            }
        }

        $units = $allUnitIds === []
            ? collect()
            : DB::table('units')->where('status', 1)->whereIn('unit_id', array_keys($allUnitIds))
                ->get(['unit_id', 'unit_name', 'unit_short_name'])->keyBy('unit_id');

        return $suppliesRows->map(function ($row) use ($relationUnits, $units) {
            $unitIds = collect(json_decode($row->supplies_unit ?? '[]', true) ?: []);
            $defaultUnitId = (int) ($row->supplies_default_unit ?? 0);
            if ($defaultUnitId > 0) {
                $unitIds->prepend($defaultUnitId);
            }
            foreach ($relationUnits->get($row->supplies_id, collect()) as $relation) {
                $unitIds->push($relation->su_id_1)->push($relation->su_id_2);
            }

            return [
                'supplies_id' => (int) $row->supplies_id,
                'supplies_name' => $row->supplies_name,
                'default_unit_id' => $defaultUnitId,
                'units' => $unitIds->map(fn ($unitId) => $units->get((int) $unitId))
                    ->filter()
                    ->unique('unit_id')
                    ->map(fn ($unit) => [
                        'unit_id' => (int) $unit->unit_id,
                        'unit_name' => $unit->unit_name,
                        'unit_short_name' => $unit->unit_short_name,
                    ])->values()->all(),
            ];
        })->values()->all();
    }

    private function buildProductsContext(): array
    {
        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');
        $hasProductUnitId = Schema::hasColumn('products', 'unit_id');
        $variantCols = [
            'pv.product_variant_id',
            'pv.product_id',
            'pv.product_variant_name',
            'pv.product_variant_sku',
            'p.product_name',
            'p.product_unit',
        ];
        if ($hasProductUnitId) {
            $variantCols[] = 'p.unit_id as default_unit_id';
        }
        if ($hasRetailCol) {
            $variantCols[] = 'pv.retail_unit';
        }

        $variants = DB::table('product_variants as pv')
            ->join('products as p', 'p.product_id', '=', 'pv.product_id')
            ->where('pv.status', 1)
            ->where('p.status', 1)
            ->orderBy('p.product_name')
            ->orderBy('pv.product_variant_name')
            ->get($variantCols);

        $variantIds = $variants->pluck('product_variant_id')->map(fn ($id) => (int) $id)->values();
        $relationUnits = $variantIds->isEmpty()
            ? collect()
            : DB::table('product_relations')
                ->where('status', 1)
                ->whereIn('product_variant_id', $variantIds)
                ->get(['product_variant_id', 'pr_unit_id_1', 'pr_unit_id_2'])
                ->groupBy('product_variant_id');

        $allUnitIds = [];
        foreach ($variants as $variant) {
            $defaultUnitId = $hasProductUnitId ? (int) ($variant->default_unit_id ?? 0) : 0;
            if ($defaultUnitId > 0) {
                $allUnitIds[$defaultUnitId] = true;
            }
            foreach ((array) (json_decode($variant->product_unit ?? '[]', true) ?: []) as $unitId) {
                $allUnitIds[(int) $unitId] = true;
            }
            if ($hasRetailCol && (int) ($variant->retail_unit ?? 0) > 0) {
                $allUnitIds[(int) $variant->retail_unit] = true;
            }
            foreach ($relationUnits->get($variant->product_variant_id, collect()) as $relation) {
                $allUnitIds[(int) $relation->pr_unit_id_1] = true;
                $allUnitIds[(int) $relation->pr_unit_id_2] = true;
            }
        }

        $units = $allUnitIds === []
            ? collect()
            : DB::table('units')->where('status', 1)->whereIn('unit_id', array_keys($allUnitIds))
                ->get(['unit_id', 'unit_name', 'unit_short_name'])->keyBy('unit_id');

        return $variants->map(function ($variant) use ($relationUnits, $units, $hasRetailCol, $hasProductUnitId) {
            $unitIds = collect(json_decode($variant->product_unit ?? '[]', true) ?: []);
            $defaultUnitId = $hasProductUnitId ? (int) ($variant->default_unit_id ?? 0) : 0;
            if ($defaultUnitId > 0) {
                $unitIds->prepend($defaultUnitId);
            } elseif ($unitIds->isNotEmpty()) {
                $defaultUnitId = (int) $unitIds->first();
            }
            if ($hasRetailCol && (int) ($variant->retail_unit ?? 0) > 0) {
                $unitIds->push((int) $variant->retail_unit);
            }
            foreach ($relationUnits->get($variant->product_variant_id, collect()) as $relation) {
                $unitIds->push($relation->pr_unit_id_1)->push($relation->pr_unit_id_2);
            }

            $retailUnitId = $hasRetailCol ? (int) ($variant->retail_unit ?? 0) : 0;

            return [
                'product_variant_id' => (int) $variant->product_variant_id,
                'product_id' => (int) $variant->product_id,
                'product_variant_sku' => $variant->product_variant_sku,
                'product_label' => $this->formatProductVariantLabel(
                    $variant->product_name ?? '',
                    $variant->product_variant_name ?? '',
                    $variant->product_variant_sku ?? ''
                ),
                'default_unit_id' => $defaultUnitId,
                'retail_unit' => $retailUnitId > 0 ? $retailUnitId : null,
                'units' => $unitIds->map(fn ($unitId) => $units->get((int) $unitId))
                    ->filter()
                    ->unique('unit_id')
                    ->map(fn ($unit) => [
                        'unit_id' => (int) $unit->unit_id,
                        'unit_name' => $unit->unit_name,
                        'unit_short_name' => $unit->unit_short_name,
                    ])->values()->all(),
            ];
        })->values()->all();
    }

    private function mainWarehouses(): array
    {
        return DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.status', 1)
            ->where('wt.status', 1)
            ->where('wt.is_main_warehouse', 1)
            ->orderBy('w.warehouse_name')
            ->get(['w.id', 'w.warehouse_name'])
            ->map(fn ($warehouse) => ['id' => (int) $warehouse->id, 'warehouse_name' => $warehouse->warehouse_name])
            ->values()->all();
    }

    private function allActiveWarehouses(): array
    {
        return DB::table('warehouses as w')
            ->leftJoin('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.status', 1)
            ->orderByDesc('wt.is_main_warehouse')
            ->orderBy('w.warehouse_name')
            ->get(['w.id', 'w.warehouse_name', 'wt.is_main_warehouse'])
            ->map(fn ($warehouse) => [
                'id' => (int) $warehouse->id,
                'warehouse_name' => $warehouse->warehouse_name,
                'is_main_warehouse' => (int) ($warehouse->is_main_warehouse ?? 1),
            ])
            ->values()->all();
    }

    private function replaceSupplyDetails(int $returnId, array $details): void
    {
        CustomerSupplyReturnDetail::where('return_id', $returnId)->delete();
        $now = now();
        CustomerSupplyReturnDetail::insert(array_map(fn ($detail) => [
            'return_id' => $returnId,
            'supplies_id' => $detail['supplies_id'],
            'unit_id' => $detail['unit_id'],
            'warehouse_id' => $detail['warehouse_id'],
            'qty' => $detail['qty'],
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $details));
    }

    private function replaceProductDetails(int $returnId, array $details): void
    {
        CustomerProductReturnDetail::where('return_id', $returnId)->delete();
        $now = now();
        CustomerProductReturnDetail::insert(array_map(fn ($detail) => [
            'return_id' => $returnId,
            'product_variant_id' => $detail['product_variant_id'],
            'unit_id' => $detail['unit_id'],
            'warehouse_id' => $detail['warehouse_id'],
            'qty' => $detail['qty'],
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $details));
    }

    private function generateReturnGroup(): string
    {
        $maxSupply = (int) DB::table('customer_supply_returns')
            ->where('return_group', 'like', 'PKR%')
            ->selectRaw("MAX(CAST(SUBSTRING(return_group, 4) AS UNSIGNED)) as max_no")
            ->value('max_no');
        $maxProduct = (int) DB::table('customer_product_returns')
            ->where('return_group', 'like', 'PKR%')
            ->selectRaw("MAX(CAST(SUBSTRING(return_group, 4) AS UNSIGNED)) as max_no")
            ->value('max_no');
        $next = max($maxSupply, $maxProduct) + 1;

        return 'PKR' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    private function storeProof(Request $request, bool $required): ?string
    {
        $binary = null;
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $binary = File::get($file->getRealPath());
        } elseif ($request->filled('proof_base64')) {
            $value = (string) $request->input('proof_base64');
            if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $value, $matches)) {
                throw ValidationException::withMessages(['proof_base64' => 'Format bukti tidak valid.']);
            }
            $binary = base64_decode($matches[2], true);
            if ($binary === false || strlen($binary) > 5 * 1024 * 1024) {
                throw ValidationException::withMessages(['proof_base64' => 'Ukuran bukti maksimal 5 MB.']);
            }
        }
        if ($binary === null) {
            if ($required) {
                throw ValidationException::withMessages(['proof' => 'Bukti foto wajib diunggah.']);
            }

            return null;
        }

        $imageInfo = @getimagesizefromstring($binary);
        $mime = $imageInfo['mime'] ?? '';
        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (! isset($extensions[$mime])) {
            throw ValidationException::withMessages(['proof' => 'Isi file bukti bukan gambar JPEG, PNG, atau WebP yang valid.']);
        }
        $extension = $extensions[$mime];
        $directory = public_path('customer_returns');
        File::ensureDirectoryExists($directory);
        $filename = now()->format('YmdHis') . '_' . Str::random(24) . '.' . $extension;
        if (File::put($directory . DIRECTORY_SEPARATOR . $filename, $binary) === false) {
            throw ValidationException::withMessages(['proof' => 'Bukti gagal disimpan.']);
        }

        return 'customer_returns/' . $filename;
    }

    private function deleteProof(?string $path): void
    {
        if (! $path) {
            return;
        }
        $allowed = ['customer_returns/', 'customer_supply_returns/', 'customer_product_returns/'];
        $ok = false;
        foreach ($allowed as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $ok = true;
                break;
            }
        }
        if (! $ok) {
            return;
        }
        File::delete(public_path(str_replace('/', DIRECTORY_SEPARATOR, $path)));
    }

    private function formatProductVariantLabel($productName, $variantName = '', $sku = ''): string
    {
        $name = trim(preg_replace(
            '/\s+/',
            ' ',
            trim((string) $productName) . ' ' . trim((string) $variantName)
        ));
        $sku = trim((string) $sku);
        if ($sku !== '' && $sku !== '-') {
            return $name !== '' ? ($sku . ' | ' . $name) : $sku;
        }

        return $name !== '' ? $name : '-';
    }

    private function authorizeAbility(string $ability): void
    {
        abort_unless(RoleAccess::can(Session::get('user'), 'Pengiriman', $ability), 403, 'Akses ditolak.');
    }

    private function userId(): ?int
    {
        $id = Session::get('user')->staff_id ?? null;

        return $id ? (int) $id : null;
    }
}
