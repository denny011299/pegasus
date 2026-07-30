<?php

namespace App\Http\Controllers;

use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use App\Models\LogStock;
use App\Models\SuppliesStock;
use App\Support\RoleAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerSupplyReturnController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility('view');

        $draw = (int) $request->input('draw', 1);
        $start = max(0, (int) $request->input('start', 0));
        $length = min(100, max(1, (int) $request->input('length', 10)));
        $search = trim((string) data_get($request->all(), 'search.value', ''));
        $orderIndex = (int) data_get($request->all(), 'order.0.column', 4);
        $orderDirection = strtolower((string) data_get($request->all(), 'order.0.dir', 'desc')) === 'asc'
            ? 'asc'
            : 'desc';
        $columns = [
            'csr.return_number',
            'csr.return_date',
            'so.so_invoice_no',
            'c.customer_notes',
            'csr.status',
            'creator.staff_name',
            'approver.staff_name',
            'csr.return_id',
        ];

        $base = DB::table('customer_supply_returns as csr')
            ->leftJoin('sales_orders as so', 'so.so_id', '=', 'csr.so_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'csr.customer_id')
            ->leftJoin('staffs as creator', 'creator.staff_id', '=', 'csr.created_by')
            ->leftJoin('staffs as approver', 'approver.staff_id', '=', 'csr.acc_by')
            ->where('csr.status', '>', 0);

        $recordsTotal = DB::table('customer_supply_returns')->where('status', '>', 0)->count();
        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($query) use ($like) {
                $query->where('csr.return_number', 'like', $like)
                    ->orWhere('csr.ref_number', 'like', $like)
                    ->orWhere('so.so_invoice_no', 'like', $like)
                    ->orWhere('so.so_number', 'like', $like)
                    ->orWhere('c.customer_notes', 'like', $like)
                    ->orWhere('creator.staff_name', 'like', $like)
                    ->orWhere('approver.staff_name', 'like', $like);
            });
        }

        $recordsFiltered = (clone $base)->count('csr.return_id');
        $rows = $base
            ->select([
                'csr.return_id',
                'csr.return_number',
                'csr.return_date',
                'csr.ref_number',
                'csr.status',
                'so.so_number',
                'so.so_invoice_no',
                'c.customer_notes as customer_name',
                'creator.staff_name as created_by_name',
                'approver.staff_name as acc_by_name',
            ])
            ->orderBy($columns[$orderIndex] ?? 'csr.return_date', $orderDirection)
            ->orderByDesc('csr.return_id')
            ->offset($start)
            ->limit($length)
            ->get();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $rows,
        ]);
    }

    public function salesOrders(Request $request): JsonResponse
    {
        $this->authorizeAbility('view');
        $search = trim((string) $request->input('q', ''));

        $rows = DB::table('sales_orders as so')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'so.so_customer')
            ->where('so.status', 2)
            ->when($search !== '', function ($query) use ($search) {
                $like = '%' . $search . '%';
                $query->where(function ($inner) use ($like) {
                    $inner->where('so.so_number', 'like', $like)
                        ->orWhere('so.so_invoice_no', 'like', $like)
                        ->orWhere('c.customer_notes', 'like', $like);
                });
            })
            ->orderByDesc('so.so_date')
            ->limit(30)
            ->get([
                'so.so_id',
                'so.so_number',
                'so.so_invoice_no',
                'so.so_date',
                'so.so_customer as customer_id',
                'c.customer_notes as customer_name',
            ]);

        return response()->json([
            'results' => $rows->map(fn ($row) => [
                'id' => (int) $row->so_id,
                'text' => trim(($row->so_invoice_no ?: $row->so_number) . ' — ' . ($row->customer_name ?: '-')),
                'so_number' => $row->so_number,
                'so_invoice_no' => $row->so_invoice_no,
                'so_date' => $row->so_date,
                'customer_id' => (int) $row->customer_id,
                'customer_name' => $row->customer_name ?: '-',
            ])->values(),
        ]);
    }

    public function salesOrderContext(int $soId): JsonResponse
    {
        $this->authorizeAbility('view');
        $context = $this->buildSalesOrderContext($soId);

        if (! $context) {
            return response()->json(['message' => 'Pengiriman diterima tidak ditemukan.'], 404);
        }

        return response()->json($context);
    }

    public function show(int $returnId): JsonResponse
    {
        $this->authorizeAbility('view');

        $header = DB::table('customer_supply_returns as csr')
            ->leftJoin('sales_orders as so', 'so.so_id', '=', 'csr.so_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'csr.customer_id')
            ->leftJoin('staffs as creator', 'creator.staff_id', '=', 'csr.created_by')
            ->leftJoin('staffs as approver', 'approver.staff_id', '=', 'csr.acc_by')
            ->where('csr.return_id', $returnId)
            ->where('csr.status', '>', 0)
            ->first([
                'csr.*',
                'so.so_number',
                'so.so_invoice_no',
                'so.so_date',
                'c.customer_notes as customer_name',
                'creator.staff_name as created_by_name',
                'approver.staff_name as acc_by_name',
            ]);

        if (! $header) {
            return response()->json(['message' => 'Pengembalian tidak ditemukan.'], 404);
        }

        $details = DB::table('customer_supply_return_details as d')
            ->join('supplies as s', 's.supplies_id', '=', 'd.supplies_id')
            ->join('units as u', 'u.unit_id', '=', 'd.unit_id')
            ->join('warehouses as w', 'w.id', '=', 'd.warehouse_id')
            ->where('d.return_id', $returnId)
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
            ]);

        $context = $this->buildSalesOrderContext((int) $header->so_id);
        $originBySupply = collect($context['supplies'] ?? [])->keyBy('supplies_id');
        foreach ($details as $detail) {
            $detail->origin_text = $originBySupply->get((int) $detail->supplies_id)['origin_text'] ?? '-';
        }

        $header->proof_url = $header->proof_path ? asset($header->proof_path) : null;
        $header->details = $details;
        $header->context = $context;

        return response()->json($header);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility('create');

        [$data, $details] = $this->validatedPayload($request, true);
        $newProofPath = null;

        try {
            $newProofPath = $this->storeProof($request, true);
            $record = DB::transaction(function () use ($data, $details, $newProofPath) {
                $so = DB::table('sales_orders')
                    ->where('so_id', $data['so_id'])
                    ->where('status', 2)
                    ->lockForUpdate()
                    ->first();
                if (! $so) {
                    throw ValidationException::withMessages(['so_id' => 'Pengiriman harus berstatus Diterima.']);
                }

                $this->validateDetailsAgainstSalesOrder((int) $so->so_id, $details);
                $record = CustomerSupplyReturn::create([
                    'return_number' => 'PCR' . now()->format('ymdHis') . strtoupper(Str::random(4)),
                    'so_id' => $so->so_id,
                    'customer_id' => $data['customer_id'],
                    'return_date' => $data['return_date'],
                    'ref_number' => $data['ref_number'] ?: null,
                    'notes' => $data['notes'] ?: null,
                    'proof_path' => $newProofPath,
                    'status' => 1,
                    'created_by' => $this->userId(),
                ]);
                $this->replaceDetails($record->return_id, $details);

                return $record;
            });

            return response()->json(['success' => true, 'message' => 'Pengembalian berhasil disimpan.', 'data' => $record]);
        } catch (\Throwable $e) {
            $this->deleteProof($newProofPath);
            throw $e;
        }
    }

    public function update(Request $request, int $returnId): JsonResponse
    {
        $this->authorizeAbility('edit');
        [$data, $details] = $this->validatedPayload($request, false);
        $newProofPath = null;
        $oldProofPath = null;

        try {
            if ($request->hasFile('proof') || $request->filled('proof_base64')) {
                $newProofPath = $this->storeProof($request, false);
            }

            DB::transaction(function () use ($returnId, $data, $details, $newProofPath, &$oldProofPath) {
                $record = CustomerSupplyReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
                if ((int) $record->status !== 1) {
                    throw ValidationException::withMessages(['status' => 'Hanya pengembalian Pending yang dapat diubah.']);
                }
                $so = DB::table('sales_orders')
                    ->where('so_id', $data['so_id'])
                    ->where('status', 2)
                    ->lockForUpdate()
                    ->first();
                if (! $so) {
                    throw ValidationException::withMessages(['so_id' => 'Pengiriman harus berstatus Diterima.']);
                }

                $this->validateDetailsAgainstSalesOrder((int) $so->so_id, $details);
                $oldProofPath = $record->proof_path;
                $record->fill([
                    'so_id' => $so->so_id,
                    'customer_id' => $data['customer_id'],
                    'return_date' => $data['return_date'],
                    'ref_number' => $data['ref_number'] ?: null,
                    'notes' => $data['notes'] ?: null,
                ]);
                if ($newProofPath) {
                    $record->proof_path = $newProofPath;
                }
                $record->save();
                $this->replaceDetails($record->return_id, $details);
            });

            if ($newProofPath && $oldProofPath !== $newProofPath) {
                $this->deleteProof($oldProofPath);
            }

            return response()->json(['success' => true, 'message' => 'Pengembalian berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $this->deleteProof($newProofPath);
            throw $e;
        }
    }

    public function destroy(int $returnId): JsonResponse
    {
        $this->authorizeAbility('delete');

        DB::transaction(function () use ($returnId) {
            $record = CustomerSupplyReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
            if ((int) $record->status !== 1) {
                throw ValidationException::withMessages(['status' => 'Hanya pengembalian Pending yang dapat dihapus.']);
            }
            $record->status = 0;
            $record->save();
            CustomerSupplyReturnDetail::where('return_id', $returnId)->update(['status' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Pengembalian berhasil dihapus.']);
    }

    public function accept(int $returnId): JsonResponse
    {
        $this->authorizeAbility('others');

        DB::transaction(function () use ($returnId) {
            $record = CustomerSupplyReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
            if ((int) $record->status !== 1) {
                throw ValidationException::withMessages(['status' => 'Pengembalian sudah diproses dan tidak dapat di-ACC ulang.']);
            }
            $so = DB::table('sales_orders')->where('so_id', $record->so_id)->where('status', 2)->lockForUpdate()->first();
            if (! $so) {
                throw ValidationException::withMessages(['so_id' => 'Referensi pengiriman tidak lagi valid atau belum Diterima.']);
            }

            $details = CustomerSupplyReturnDetail::where('return_id', $returnId)
                ->where('status', 1)
                ->lockForUpdate()
                ->get()
                ->map(fn ($detail) => [
                    'supplies_id' => (int) $detail->supplies_id,
                    'unit_id' => (int) $detail->unit_id,
                    'warehouse_id' => (int) $detail->warehouse_id,
                    'qty' => (int) $detail->qty,
                ])->all();
            $this->validateDetailsAgainstSalesOrder((int) $record->so_id, $details);

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
                    'log_kode' => $record->return_number,
                    'log_type' => 2,
                    'log_category' => 1,
                    'log_item_id' => $detail['supplies_id'],
                    'log_notes' => 'Pengembalian bahan/kemasan customer dari ' . ($so->so_invoice_no ?: $so->so_number),
                    'log_jumlah' => $detail['qty'],
                    'unit_id' => $detail['unit_id'],
                    'warehouse_id' => $detail['warehouse_id'],
                ]);
            }

            $record->status = 2;
            $record->acc_by = $this->userId();
            $record->save();
        }, 3);

        return response()->json(['success' => true, 'message' => 'Pengembalian diterima dan stok bahan telah ditambahkan.']);
    }

    public function decline(int $returnId): JsonResponse
    {
        $this->authorizeAbility('others');

        DB::transaction(function () use ($returnId) {
            $record = CustomerSupplyReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
            if ((int) $record->status !== 1) {
                throw ValidationException::withMessages(['status' => 'Pengembalian sudah diproses.']);
            }
            $record->status = 3;
            $record->acc_by = $this->userId();
            $record->save();
        });

        return response()->json(['success' => true, 'message' => 'Pengembalian ditolak tanpa perubahan stok.']);
    }

    private function validatedPayload(Request $request, bool $proofRequired): array
    {
        $validator = Validator::make($request->all(), [
            'so_id' => ['required', 'integer'],
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'customer_id')->where(fn ($query) => $query->where('status', 1)),
            ],
            'return_date' => ['required', 'date'],
            'ref_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'details' => ['required'],
            'proof' => [$proofRequired ? 'required_without:proof_base64' : 'nullable', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'proof_base64' => [$proofRequired ? 'required_without:proof' : 'nullable', 'string'],
        ]);
        $validator->validate();

        $rawDetails = $request->input('details');
        $details = is_string($rawDetails) ? json_decode($rawDetails, true) : $rawDetails;
        if (! is_array($details) || $details === []) {
            throw ValidationException::withMessages(['details' => 'Minimal satu bahan harus ditambahkan.']);
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
                throw ValidationException::withMessages(["details.$index.qty" => 'Qty harus lebih dari 0.']);
            }
        }

        return [[
            'so_id' => (int) $request->input('so_id'),
            'customer_id' => (int) $request->input('customer_id'),
            'return_date' => $request->input('return_date'),
            'ref_number' => trim((string) $request->input('ref_number', '')),
            'notes' => trim((string) $request->input('notes', '')),
        ], array_values($merged)];
    }

    private function validateDetailsAgainstSalesOrder(int $soId, array $details): void
    {
        $context = $this->buildSalesOrderContext($soId);
        if (! $context) {
            throw ValidationException::withMessages(['so_id' => 'Referensi pengiriman tidak valid atau belum Diterima.']);
        }
        $allowed = collect($context['supplies'])->keyBy('supplies_id');
        $mainWarehouseIds = collect($context['warehouses'])->pluck('id')->map(fn ($id) => (int) $id);

        foreach ($details as $index => $detail) {
            $supply = $allowed->get((int) $detail['supplies_id']);
            if (! $supply) {
                throw ValidationException::withMessages(["details.$index.supplies_id" => 'Bahan tidak berasal dari BOM pengiriman ini.']);
            }
            if (! collect($supply['units'])->contains(fn ($unit) => (int) $unit['unit_id'] === (int) $detail['unit_id'])) {
                throw ValidationException::withMessages(["details.$index.unit_id" => 'Satuan tidak aktif untuk bahan yang dipilih.']);
            }
            if (! $mainWarehouseIds->contains((int) $detail['warehouse_id'])) {
                throw ValidationException::withMessages(["details.$index.warehouse_id" => 'Gudang tujuan harus gudang utama aktif.']);
            }
            if ((int) $detail['qty'] <= 0) {
                throw ValidationException::withMessages(["details.$index.qty" => 'Qty harus lebih dari 0.']);
            }
        }
    }

    private function buildSalesOrderContext(int $soId): ?array
    {
        $so = DB::table('sales_orders as so')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'so.so_customer')
            ->where('so.so_id', $soId)
            ->where('so.status', 2)
            ->first([
                'so.so_id',
                'so.so_number',
                'so.so_invoice_no',
                'so.so_date',
                'so.so_customer as customer_id',
                'c.customer_notes as customer_name',
            ]);
        if (! $so) {
            return null;
        }

        $products = DB::table('sales_order_details as sod')
            ->leftJoin('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
            ->leftJoin('products as p', 'p.product_id', '=', 'pv.product_id')
            ->where('sod.so_id', $soId)
            ->where('sod.status', 1)
            ->get([
                'sod.product_variant_id',
                'sod.sod_nama',
                'sod.sod_variant',
                'sod.sod_sku',
                'sod.sod_qty',
                'p.product_name',
                'pv.product_variant_name',
            ]);
        $variantIds = $products->pluck('product_variant_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

        $bomRows = $variantIds->isEmpty()
            ? collect()
            : DB::table('boms as b')
                ->join('bom_details as bd', function ($join) {
                    $join->on('bd.bom_id', '=', 'b.bom_id')->where('bd.status', 1);
                })
                ->join('supplies as s', function ($join) {
                    $join->on('s.supplies_id', '=', 'bd.supplies_id')->where('s.status', 1);
                })
                ->where('b.status', 1)
                ->whereIn('b.product_id', $variantIds)
                ->get([
                    'b.product_id as product_variant_id',
                    'bd.supplies_id',
                    's.supplies_name',
                    's.supplies_unit',
                    's.supplies_default_unit',
                ]);

        $productsWithBom = $bomRows->pluck('product_variant_id')->map(fn ($id) => (int) $id)->unique();
        $missingBom = $products
            ->filter(fn ($product) => ! $productsWithBom->contains((int) $product->product_variant_id))
            ->map(fn ($product) => $this->productLabel($product))
            ->unique()
            ->values();

        $supplyIds = $bomRows->pluck('supplies_id')->map(fn ($id) => (int) $id)->unique()->values();
        $relationUnits = $supplyIds->isEmpty()
            ? collect()
            : DB::table('supplies_relations')
                ->where('status', 1)
                ->whereIn('supplies_id', $supplyIds)
                ->get(['supplies_id', 'su_id_1', 'su_id_2'])
                ->groupBy('supplies_id');

        $allUnitIds = [];
        foreach ($bomRows->groupBy('supplies_id') as $suppliesId => $rows) {
            if ((int) ($rows->first()->supplies_default_unit ?? 0) > 0) {
                $allUnitIds[(int) $rows->first()->supplies_default_unit] = true;
            }
            foreach ((array) (json_decode($rows->first()->supplies_unit ?? '[]', true) ?: []) as $unitId) {
                $allUnitIds[(int) $unitId] = true;
            }
            foreach ($relationUnits->get($suppliesId, collect()) as $relation) {
                $allUnitIds[(int) $relation->su_id_1] = true;
                $allUnitIds[(int) $relation->su_id_2] = true;
            }
        }
        $units = $allUnitIds === []
            ? collect()
            : DB::table('units')->where('status', 1)->whereIn('unit_id', array_keys($allUnitIds))
                ->get(['unit_id', 'unit_name', 'unit_short_name'])->keyBy('unit_id');
        $productByVariant = $products->groupBy('product_variant_id');

        $supplies = $bomRows->groupBy('supplies_id')->map(function ($rows, $suppliesId) use ($relationUnits, $units, $productByVariant) {
            $unitIds = collect(json_decode($rows->first()->supplies_unit ?? '[]', true) ?: []);
            $defaultUnitId = (int) ($rows->first()->supplies_default_unit ?? 0);
            if ($defaultUnitId > 0) {
                $unitIds->prepend($defaultUnitId);
            }
            foreach ($relationUnits->get($suppliesId, collect()) as $relation) {
                $unitIds->push($relation->su_id_1)->push($relation->su_id_2);
            }
            $originLabels = $rows->pluck('product_variant_id')->unique()
                ->flatMap(fn ($variantId) => $productByVariant->get($variantId, collect())->map(fn ($product) => $this->productLabel($product)))
                ->unique()->values();

            return [
                'supplies_id' => (int) $suppliesId,
                'supplies_name' => $rows->first()->supplies_name,
                'origin_text' => 'Sesuai produk: ' . $originLabels->implode(', '),
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

        $warehouses = DB::table('warehouses as w')
            ->join('warehouse_types as wt', 'wt.id', '=', 'w.warehouse_type_id')
            ->where('w.status', 1)
            ->where('wt.status', 1)
            ->where('wt.is_main_warehouse', 1)
            ->orderBy('w.warehouse_name')
            ->get(['w.id', 'w.warehouse_name'])
            ->map(fn ($warehouse) => ['id' => (int) $warehouse->id, 'warehouse_name' => $warehouse->warehouse_name])
            ->values()->all();

        return [
            'sales_order' => [
                'so_id' => (int) $so->so_id,
                'so_number' => $so->so_number,
                'so_invoice_no' => $so->so_invoice_no,
                'so_date' => $so->so_date,
                'customer_id' => (int) $so->customer_id,
                'customer_name' => $so->customer_name ?: '-',
            ],
            'products' => $products,
            'missing_bom_products' => $missingBom,
            'supplies' => $supplies,
            'warehouses' => $warehouses,
        ];
    }

    private function replaceDetails(int $returnId, array $details): void
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

    private function storeProof(Request $request, bool $required): ?string
    {
        $binary = null;
        $extension = null;
        if ($request->hasFile('proof')) {
            $file = $request->file('proof');
            $binary = File::get($file->getRealPath());
            $extension = strtolower((string) $file->guessExtension());
        } elseif ($request->filled('proof_base64')) {
            $value = (string) $request->input('proof_base64');
            if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $value, $matches)) {
                throw ValidationException::withMessages(['proof_base64' => 'Format bukti tidak valid.']);
            }
            $binary = base64_decode($matches[2], true);
            $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
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
        $directory = public_path('customer_supply_returns');
        File::ensureDirectoryExists($directory);
        $filename = now()->format('YmdHis') . '_' . Str::random(24) . '.' . $extension;
        if (File::put($directory . DIRECTORY_SEPARATOR . $filename, $binary) === false) {
            throw ValidationException::withMessages(['proof' => 'Bukti gagal disimpan.']);
        }

        return 'customer_supply_returns/' . $filename;
    }

    private function deleteProof(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'customer_supply_returns/')) {
            return;
        }
        File::delete(public_path(str_replace('/', DIRECTORY_SEPARATOR, $path)));
    }

    private function productLabel(object $product): string
    {
        $name = $product->product_name ?: $product->sod_nama ?: '-';
        $variant = $product->product_variant_name ?: $product->sod_variant ?: '';

        return trim($name . ($variant ? ' varian ' . $variant : ''));
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
