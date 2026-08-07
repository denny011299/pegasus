<?php

namespace App\Http\Controllers;

use App\Models\CustomerProductReturn;
use App\Models\CustomerProductReturnDetail;
use App\Models\LogStock;
use App\Models\ProductStock;
use App\Support\RoleAccess;
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

class CustomerProductReturnController extends Controller
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
        $columns = [
            'cpr.return_number',
            'cpr.return_date',
            'cpr.ref_number',
            'c.customer_notes',
            'cpr.status',
            'creator.staff_name',
            'approver.staff_name',
            'cpr.return_id',
        ];

        $base = DB::table('customer_product_returns as cpr')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'cpr.customer_id')
            ->leftJoin('staffs as creator', 'creator.staff_id', '=', 'cpr.created_by')
            ->leftJoin('staffs as approver', 'approver.staff_id', '=', 'cpr.acc_by')
            ->where('cpr.status', '>', 0);

        $recordsTotal = DB::table('customer_product_returns')->where('status', '>', 0)->count();
        if ($search !== '') {
            $like = '%' . $search . '%';
            $base->where(function ($query) use ($like) {
                $query->where('cpr.return_number', 'like', $like)
                    ->orWhere('cpr.ref_number', 'like', $like)
                    ->orWhere('c.customer_notes', 'like', $like)
                    ->orWhere('creator.staff_name', 'like', $like)
                    ->orWhere('approver.staff_name', 'like', $like);
            });
        }

        $recordsFiltered = (clone $base)->count('cpr.return_id');
        $rows = $base
            ->select([
                'cpr.return_id',
                'cpr.return_number',
                'cpr.return_date',
                'cpr.ref_number',
                'cpr.status',
                'c.customer_notes as customer_name',
                'creator.staff_name as created_by_name',
                'approver.staff_name as acc_by_name',
            ])
            ->orderBy($columns[$orderIndex] ?? 'cpr.return_date', $orderDirection)
            ->orderByDesc('cpr.return_id')
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

    public function context(): JsonResponse
    {
        $this->authorizeAbility('view');

        return response()->json($this->buildReturnContext());
    }

    public function show(int $returnId): JsonResponse
    {
        $this->authorizeAbility('view');

        $header = DB::table('customer_product_returns as cpr')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'cpr.customer_id')
            ->leftJoin('staffs as creator', 'creator.staff_id', '=', 'cpr.created_by')
            ->leftJoin('staffs as approver', 'approver.staff_id', '=', 'cpr.acc_by')
            ->where('cpr.return_id', $returnId)
            ->where('cpr.status', '>', 0)
            ->first([
                'cpr.*',
                'c.customer_notes as customer_name',
                'creator.staff_name as created_by_name',
                'approver.staff_name as acc_by_name',
            ]);

        if (! $header) {
            return response()->json(['message' => 'Pengembalian tidak ditemukan.'], 404);
        }

        $details = DB::table('customer_product_return_details as d')
            ->join('product_variants as pv', 'pv.product_variant_id', '=', 'd.product_variant_id')
            ->join('products as p', 'p.product_id', '=', 'pv.product_id')
            ->join('units as u', 'u.unit_id', '=', 'd.unit_id')
            ->join('warehouses as w', 'w.id', '=', 'd.warehouse_id')
            ->where('d.return_id', $returnId)
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

        foreach ($details as $detail) {
            $detail->product_label = $this->formatProductVariantLabel(
                $detail->product_name ?? '',
                $detail->product_variant_name ?? '',
                $detail->product_variant_sku ?? ''
            );
        }

        $header->proof_url = $header->proof_path ? asset($header->proof_path) : null;
        $header->details = $details;
        $header->context = $this->buildReturnContext();

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
                $this->validateDetails($details);
                $record = CustomerProductReturn::create([
                    'return_number' => (new CustomerProductReturn())->generateReturnNumber(),
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

            return response()->json(['success' => true, 'message' => 'Pengembalian produk berhasil disimpan.', 'data' => $record]);
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
                $record = CustomerProductReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
                if ((int) $record->status !== 1) {
                    throw ValidationException::withMessages(['status' => 'Hanya pengembalian Pending yang dapat diubah.']);
                }

                $this->validateDetails($details);
                $oldProofPath = $record->proof_path;
                $record->fill([
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

            return response()->json(['success' => true, 'message' => 'Pengembalian produk berhasil diperbarui.']);
        } catch (\Throwable $e) {
            $this->deleteProof($newProofPath);
            throw $e;
        }
    }

    public function destroy(int $returnId): JsonResponse
    {
        $this->authorizeAbility('delete');

        DB::transaction(function () use ($returnId) {
            $record = CustomerProductReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
            if ((int) $record->status !== 1) {
                throw ValidationException::withMessages(['status' => 'Hanya pengembalian Pending yang dapat dihapus.']);
            }
            $record->status = 0;
            $record->save();
            CustomerProductReturnDetail::where('return_id', $returnId)->update(['status' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Pengembalian produk berhasil dihapus.']);
    }

    public function accept(int $returnId): JsonResponse
    {
        $this->authorizeAbility('others');

        DB::transaction(function () use ($returnId) {
            $record = CustomerProductReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
            if ((int) $record->status !== 1) {
                throw ValidationException::withMessages(['status' => 'Pengembalian sudah diproses dan tidak dapat di-ACC ulang.']);
            }

            $customerName = DB::table('customers')
                ->where('customer_id', $record->customer_id)
                ->value('customer_notes') ?: '-';

            $details = CustomerProductReturnDetail::where('return_id', $returnId)
                ->where('status', 1)
                ->lockForUpdate()
                ->get()
                ->map(fn ($detail) => [
                    'product_variant_id' => (int) $detail->product_variant_id,
                    'unit_id' => (int) $detail->unit_id,
                    'warehouse_id' => (int) $detail->warehouse_id,
                    'qty' => (int) $detail->qty,
                ])->all();
            $this->validateDetails($details);

            foreach ($details as $detail) {
                $variant = DB::table('product_variants')
                    ->where('product_variant_id', $detail['product_variant_id'])
                    ->where('status', 1)
                    ->first(['product_variant_id', 'product_id']);
                if (! $variant) {
                    throw ValidationException::withMessages([
                        'details' => 'Varian produk tidak aktif: ' . $detail['product_variant_id'],
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
                    'log_kode' => $record->return_number,
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
        }, 3);

        return response()->json(['success' => true, 'message' => 'Pengembalian diterima dan stok produk telah ditambahkan.']);
    }

    public function decline(int $returnId): JsonResponse
    {
        $this->authorizeAbility('others');

        DB::transaction(function () use ($returnId) {
            $record = CustomerProductReturn::where('return_id', $returnId)->lockForUpdate()->firstOrFail();
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
            throw ValidationException::withMessages(['details' => 'Minimal satu produk harus ditambahkan.']);
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
                throw ValidationException::withMessages(["details.$index.qty" => 'Qty harus lebih dari 0.']);
            }
        }

        return [[
            'customer_id' => (int) $request->input('customer_id'),
            'return_date' => $request->input('return_date'),
            'ref_number' => trim((string) $request->input('ref_number', '')),
            'notes' => trim((string) $request->input('notes', '')),
        ], array_values($merged)];
    }

    private function validateDetails(array $details): void
    {
        $context = $this->buildReturnContext();
        $allowed = collect($context['products'])->keyBy('product_variant_id');
        $warehouseIds = collect($context['warehouses'])->pluck('id')->map(fn ($id) => (int) $id);

        $warehousesById = collect($context['warehouses'])->keyBy('id');

        foreach ($details as $index => $detail) {
            $product = $allowed->get((int) $detail['product_variant_id']);
            if (! $product) {
                throw ValidationException::withMessages(["details.$index.product_variant_id" => 'Produk tidak aktif atau tidak valid.']);
            }
            if (! collect($product['units'])->contains(fn ($unit) => (int) $unit['unit_id'] === (int) $detail['unit_id'])) {
                throw ValidationException::withMessages(["details.$index.unit_id" => 'Satuan tidak aktif untuk produk yang dipilih.']);
            }
            if (! $warehouseIds->contains((int) $detail['warehouse_id'])) {
                throw ValidationException::withMessages(["details.$index.warehouse_id" => 'Gudang tujuan harus gudang aktif.']);
            }
            $warehouse = $warehousesById->get((int) $detail['warehouse_id']);
            $isRetailWarehouse = $warehouse && (int) ($warehouse['is_main_warehouse'] ?? 1) === 0;
            if ($isRetailWarehouse) {
                $retailUnitId = (int) ($product['retail_unit'] ?? 0);
                if ($retailUnitId <= 0) {
                    throw ValidationException::withMessages([
                        "details.$index.unit_id" => 'Produk tidak punya satuan eceran; tidak bisa dikembalikan ke gudang eceran.',
                    ]);
                }
                if ((int) $detail['unit_id'] !== $retailUnitId) {
                    throw ValidationException::withMessages([
                        "details.$index.unit_id" => 'Gudang eceran wajib memakai satuan eceran produk (bukan DOS/jerigen).',
                    ]);
                }
            }
            if ((int) $detail['qty'] <= 0) {
                throw ValidationException::withMessages(["details.$index.qty" => 'Qty harus lebih dari 0.']);
            }
        }
    }

    private function buildReturnContext(): array
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

        $products = $variants->map(function ($variant) use ($relationUnits, $units, $hasRetailCol, $hasProductUnitId) {
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

            $label = $this->formatProductVariantLabel(
                $variant->product_name ?? '',
                $variant->product_variant_name ?? '',
                $variant->product_variant_sku ?? ''
            );

            $retailUnitId = $hasRetailCol ? (int) ($variant->retail_unit ?? 0) : 0;

            return [
                'product_variant_id' => (int) $variant->product_variant_id,
                'product_id' => (int) $variant->product_id,
                'product_variant_sku' => $variant->product_variant_sku,
                'product_label' => $label,
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

        $warehouses = DB::table('warehouses as w')
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

        return [
            'products' => $products,
            'warehouses' => $warehouses,
        ];
    }

    private function replaceDetails(int $returnId, array $details): void
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
        $directory = public_path('customer_product_returns');
        File::ensureDirectoryExists($directory);
        $filename = now()->format('YmdHis') . '_' . Str::random(24) . '.' . $extension;
        if (File::put($directory . DIRECTORY_SEPARATOR . $filename, $binary) === false) {
            throw ValidationException::withMessages(['proof' => 'Bukti gagal disimpan.']);
        }

        return 'customer_product_returns/' . $filename;
    }

    private function deleteProof(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'customer_product_returns/')) {
            return;
        }
        File::delete(public_path(str_replace('/', DIRECTORY_SEPARATOR, $path)));
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

    private function formatProductVariantLabel($productName, $variantName = '', $sku = ''): string
    {
        $name = trim(preg_replace(
            '/\s+/',
            ' ',
            trim((string) $productName).' '.trim((string) $variantName)
        ));
        $sku = trim((string) $sku);
        if ($sku !== '' && $sku !== '-') {
            return $name !== '' ? ($sku.' | '.$name) : $sku;
        }

        return $name !== '' ? $name : '-';
    }
}
