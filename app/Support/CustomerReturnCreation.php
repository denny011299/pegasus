<?php

namespace App\Support;

use App\Models\CustomerProductReturn;
use App\Models\CustomerProductReturnDetail;
use App\Models\CustomerSupplyReturn;
use App\Models\CustomerSupplyReturnDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Bagian "tulis" dari alur Pengembalian (retur armada) yang TIDAK bergantung pada sesi/gudang
 * aktif — diekstrak dari App\Http\Controllers\CustomerReturnController supaya bisa dipakai ulang
 * persis oleh App\Http\Controllers\ExternalApi\V1\ShipmentReturnController::store() (endpoint
 * baru POST /shipments/returns, GitHub #58), sama pola dengan SalesOrderApproval/
 * SalesOrderCancellation yang diekstrak dari CustomerController untuk dipakai ShipmentController.
 *
 * CustomerReturnController tetap satu-satunya pemilik ATURAN BISNIS gudang (validateSupplyDetails/
 * validateProductDetails, keduanya baca active_warehouse_id sesi admin) — method di sini murni
 * penyimpanan baris + katalog bahan/produk yang aktif, tidak pernah menolak berdasarkan gudang.
 * Pemanggil dari External API sengaja TIDAK memanggil validateSupplyDetails/validateProductDetails
 * sama sekali (tidak ada sesi, dan warehouse_id di sana boleh kosong — DIKONFIRMASI pemilik produk
 * lewat WhatsApp pada issue #58: "diperbolehkan skip auto insert ke gudang/warehouse").
 */
class CustomerReturnCreation
{
    /**
     * Simpan satu dokumen pengembalian (sisi bahan dan/atau sisi produk, minimal salah satu wajib
     * berisi baris) — badan transaksi PERSIS yang dulu ada di dalam DB::transaction() milik
     * CustomerReturnController::store(), dipindah apa adanya (termasuk urutan create bahan lalu
     * produk) supaya perilaku admin tidak berubah sedikit pun.
     *
     * @param  array{customer_id:int, return_date:string, ref_number:?string, notes:?string, proof_path:?string, qc_staff_id:?int, created_by:?int}  $header
     * @param  array<int, array<string, mixed>>  $supplyDetails  baris siap-simpan (lihat replaceSupplyDetails()), boleh kosong.
     * @param  array<int, array<string, mixed>>  $productDetails  baris siap-simpan (lihat replaceProductDetails()), boleh kosong.
     * @return array{doc_key:string, return_group:string, return_type:string, supply_return_id:?int, product_return_id:?int}
     */
    public static function create(array $header, array $supplyDetails, array $productDetails): array
    {
        return DB::transaction(function () use ($header, $supplyDetails, $productDetails) {
            $group = self::generateReturnGroup();
            $supply = null;
            $product = null;

            if ($supplyDetails !== []) {
                $supply = CustomerSupplyReturn::create([
                    'return_number' => (new CustomerSupplyReturn())->generateReturnNumber(),
                    'return_group' => $group,
                    'so_id' => null,
                    'customer_id' => $header['customer_id'],
                    'return_date' => $header['return_date'],
                    'ref_number' => $header['ref_number'] ?: null,
                    'notes' => $header['notes'] ?: null,
                    'proof_path' => $header['proof_path'],
                    'status' => 1,
                    'created_by' => $header['created_by'],
                    'qc_staff_id' => $header['qc_staff_id'],
                ]);
                self::replaceSupplyDetails($supply->return_id, $supplyDetails);
            }

            if ($productDetails !== []) {
                $product = CustomerProductReturn::create([
                    'return_number' => (new CustomerProductReturn())->generateReturnNumber(),
                    'return_group' => $group,
                    'customer_id' => $header['customer_id'],
                    'return_date' => $header['return_date'],
                    'ref_number' => $header['ref_number'] ?: null,
                    'notes' => $header['notes'] ?: null,
                    'proof_path' => $header['proof_path'],
                    'status' => 1,
                    'created_by' => $header['created_by'],
                    'qc_staff_id' => $header['qc_staff_id'],
                ]);
                self::replaceProductDetails($product->return_id, $productDetails);
            }

            return [
                'doc_key' => $group,
                'return_group' => $group,
                'return_type' => self::resolveType($supply !== null, $product !== null),
                'supply_return_id' => $supply?->return_id,
                'product_return_id' => $product?->return_id,
            ];
        });
    }

    public static function resolveType(bool $hasSupply, bool $hasProduct): string
    {
        if ($hasSupply && $hasProduct) {
            return 'mixed';
        }
        if ($hasProduct) {
            return 'product';
        }

        return 'supply';
    }

    /**
     * return_group berbentuk PKR#### — nomor gabungan dipakai baris bahan maupun produk yang
     * datang dari dokumen pengembalian yang sama, diambil dari nomor terbesar YANG SUDAH DIPAKAI
     * salah satu dari dua tabel supaya keduanya tidak pernah tabrakan.
     */
    public static function generateReturnGroup(): string
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

    /**
     * Ganti seluruh baris customer_supply_return_details milik $returnId dengan $details.
     * warehouse_id boleh tidak ada di baris ($detail['warehouse_id'] ?? null) — dipakai
     * ShipmentReturnController yang sengaja tidak pernah mengisinya (lihat docblock kelas ini).
     *
     * @param  array<int, array<string, mixed>>  $details
     */
    public static function replaceSupplyDetails(int $returnId, array $details): void
    {
        CustomerSupplyReturnDetail::where('return_id', $returnId)->delete();
        $now = now();
        CustomerSupplyReturnDetail::insert(array_map(fn ($detail) => [
            'return_id' => $returnId,
            'supplies_id' => $detail['supplies_id'],
            'unit_id' => $detail['unit_id'],
            'warehouse_id' => $detail['warehouse_id'] ?? null,
            'qty' => $detail['qty'],
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ], $details));
    }

    /**
     * Ganti seluruh baris customer_product_return_details milik $returnId dengan $details.
     * warehouse_id boleh tidak ada di baris — destination_warehouse_id ikut dikosongkan kalau
     * warehouse_id-nya sendiri kosong (beda dengan alur admin yang selalu mengisi warehouse_id,
     * sehingga destination_warehouse_id di sana selalu punya nilai bawaan).
     *
     * @param  array<int, array<string, mixed>>  $details
     */
    public static function replaceProductDetails(int $returnId, array $details): void
    {
        CustomerProductReturnDetail::where('return_id', $returnId)->delete();
        $now = now();
        $hasDest = Schema::hasColumn('customer_product_return_details', 'destination_warehouse_id');
        CustomerProductReturnDetail::insert(array_map(function ($detail) use ($returnId, $now, $hasDest) {
            $row = [
                'return_id' => $returnId,
                'product_variant_id' => $detail['product_variant_id'],
                'unit_id' => $detail['unit_id'],
                'warehouse_id' => $detail['warehouse_id'] ?? null,
                'qty' => $detail['qty'],
                'status' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if ($hasDest) {
                $dest = (int) ($detail['destination_warehouse_id'] ?? 0);
                $sourceWarehouse = (int) ($detail['warehouse_id'] ?? 0);
                $row['destination_warehouse_id'] = $dest > 0 ? $dest : ($sourceWarehouse > 0 ? $sourceWarehouse : null);
            }

            return $row;
        }, $details));
    }

    /**
     * Katalog bahan mentah/kemasan aktif beserta satuan yang boleh dipakai tiap bahan (default +
     * supplies_unit + supplies_relations) — dipakai CustomerReturnController::buildReturnContext()
     * (form admin) maupun ShipmentReturnController (validasi item type=1 sebelum simpan).
     *
     * @return array<int, array{supplies_id:int, supplies_name:string, default_unit_id:int, units:array<int, array{unit_id:int, unit_name:string, unit_short_name:string}>}>
     */
    public static function suppliesContext(): array
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

    /**
     * Katalog varian produk aktif beserta satuan yang boleh dipakai tiap varian (default +
     * product_unit + product_relations + satuan eceran) — pasangan produk dari suppliesContext().
     *
     * @return array<int, array{product_variant_id:int, product_id:int, product_variant_sku:?string, product_label:string, default_unit_id:int, retail_unit:?int, units:array<int, array{unit_id:int, unit_name:string, unit_short_name:string}>}>
     */
    public static function productsContext(): array
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
                'product_label' => self::formatProductVariantLabel(
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

    public static function formatProductVariantLabel($productName, $variantName = '', $sku = ''): string
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

    /**
     * Simpan berkas bukti foto pengembalian — file sungguhan (multipart) ATAU data URI base64,
     * sama persis validasi/penamaan yang dipakai form admin (folder public/customer_returns/).
     * Dipakai kedua alur (admin lewat CustomerReturnController, External API lewat
     * ShipmentReturnController) supaya nama berkas dan aturan ukuran/format selalu satu sumber.
     *
     * @param  string|null  $base64  data URI "data:image/...;base64,..." (mis. dari JSON murni).
     * @param  \Illuminate\Http\UploadedFile|null  $file  berkas upload sungguhan (multipart/form-data).
     */
    public static function storeProofFromInput(?string $base64, $file, bool $required): ?string
    {
        $binary = null;
        if ($file !== null) {
            $binary = File::get($file->getRealPath());
        } elseif ($base64) {
            if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,([A-Za-z0-9+\/=\r\n]+)$/', $base64, $matches)) {
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

    /**
     * Hapus berkas bukti — hanya kalau path-nya berada di folder yang memang dipakai fitur
     * pengembalian, supaya path sembarangan (hasil bug di tempat lain) tidak pernah terhapus.
     */
    public static function deleteProof(?string $path): void
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
}
