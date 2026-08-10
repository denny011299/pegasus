<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Data master produk untuk sistem eksternal (Data Produk).
 *
 * Polanya sama persis dengan MasterUnitController (bukan
 * MasterArmadaController): produk punya endpoint connect, karena
 * products.ref_product_id — sama seperti units.ref_unit_id — nullable dan
 * sering kosong untuk produk yang dibuat lewat halaman admin, bukan selalu
 * terisi seperti customers.customer_code pada armada.
 *
 *   - products.product_id     : id asli Pegasus, auto-increment, tidak
 *                                pernah ditentukan pemanggil. Disebut "id"
 *                                pada respons.
 *   - products.ref_product_id : id produk yang sama pada sistem PMO
 *                                (nullable, unik lewat
 *                                products_ref_product_id_unique — lihat
 *                                migrasi tabel products). Inilah yang
 *                                dipakai sebagai BODY create dan PATH
 *                                ubah/hapus.
 *
 * CATATAN PENTING: kolom ref_product_id ini juga ditulis Pusat Sinkronisasi
 * (SyncProductStep, menarik data dari PMO — lihat
 * cdocs/integrations/202607260130-product-sync-flow.design.md). Endpoint di
 * controller ini adalah jalur tulis KEDUA ke kolom yang sama — disengaja,
 * pola yang sama dan sudah dikonfirmasi untuk units.ref_unit_id: keduanya
 * melayani sistem yang sama (PMO), siapa yang menulis terakhir yang
 * berlaku, tidak ada penguncian tambahan di antara keduanya.
 *
 * Tidak ada konsep "peran" untuk produk (beda dengan sales) — satu-satunya
 * syarat "dikelola" endpoint ini adalah status aktif, sama seperti
 * getProduct()/getProductForExternalApi().
 *
 * Field body (product_name, category_id, unit_id, product_unit) mengikuti
 * persis field yang sudah dikelola Product::insertProduct()/updateProduct()
 * milik halaman admin. Beda dengan Product::insertProduct(), endpoint ini
 * MEMVALIDASI category_id/unit_id/product_unit benar-benar menunjuk
 * kategori/satuan aktif sebelum menyimpan — pemanggil eksternal tidak
 * boleh membuat produk dengan rujukan yang menggantung.
 */
class MasterProductController extends Controller
{
    use HandlesListQueryParams;

    /**
     * GET /api/external/v1/produk
     *
     * Paginasi, urutan (?sort=), dan pencarian (?search=) semuanya opsional
     * — lihat HandlesListQueryParams. Kunci ?sort= yang sah: id,
     * ref_product_id, product_name, category_id, unit_id, created_at,
     * updated_at. ?search= mencari di product_name.
     *
     * Tiga relasi opsional, masing-masing hanya ikut kalau parameternya
     * dikirim bernilai true:
     *   ?show_units=true     tambahkan field "units": daftar Unit (id,
     *                        unit_name, unit_short_name) hasil resolusi
     *                        product_unit DIGABUNG unit_id (default), tanpa
     *                        duplikat. Diambil sekali per permintaan
     *                        (tabel units kecil), bukan sekali per produk.
     *   ?show_category=true  tambahkan field "category": {id, category_name}
     *                        hasil resolusi category_id, atau null kalau
     *                        kategorinya sudah tidak ada.
     *   ?show_variants=true  tambahkan field "variants": daftar varian aktif
     *                        produk itu (id, product_variant_name,
     *                        product_variant_sku, product_variant_barcode,
     *                        product_variant_price, product_variant_alert,
     *                        unit_id) — tanpa konversi satuannya sendiri,
     *                        supaya tidak lebih dari satu tingkat relasi
     *                        bersarang.
     */
    public function index(Request $request): JsonResponse
    {
        $showUnits = $request->boolean('show_units');
        $showCategory = $request->boolean('show_category');
        $showVariants = $request->boolean('show_variants');

        $query = (new Product())->getProductForExternalApi();

        if ($showCategory) {
            $query->with('category');
        }

        if ($showVariants) {
            $query->with(['variants' => fn ($q) => $q->where('status', 1)->orderBy('created_at', 'asc')]);
        }

        // Tabel units kecil (belasan baris) — diambil sekali per permintaan,
        // bukan sekali per produk, supaya ?show_units= tidak jadi N+1.
        $unitsMap = $showUnits ? Unit::where('status', 1)->get()->keyBy('unit_id') : null;

        return $this->respondList(
            $query,
            $request,
            fn ($product) => $this->present($product, $unitsMap, $showCategory, $showVariants),
            sortable: [
                'id' => 'product_id',
                'ref_product_id' => 'ref_product_id',
                'product_name' => 'product_name',
                'category_id' => 'category_id',
                'unit_id' => 'unit_id',
                'created_at' => 'created_at',
                'updated_at' => 'updated_at',
            ],
            searchable: ['product_name'],
            tieBreaker: 'product_id',
        );
    }

    /**
     * POST /api/external/v1/produk
     *
     * Selalu membuat baris produk baru (id Pegasus-nya auto-increment,
     * tidak pernah ditentukan pemanggil). Bukan upsert: ref_product_id yang
     * sudah dipakai produk lain ditolak sebagai duplicate_ref_id — pakai
     * PUT untuk memperbarui produk yang rujukannya sudah ada, atau PATCH
     * /produk/connect untuk menghubungkan rujukan ke produk Pegasus yang
     * sudah ada.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validateCreatePayload($request);
        $refProductId = (int) $data['ref_product_id'];

        if (Product::where('ref_product_id', $refProductId)->exists()) {
            return $this->duplicateRefError($refProductId);
        }

        $product = new Product();
        $product->ref_product_id = $refProductId;
        $product->status = 1;
        $product->created_by = null;
        $this->applyPayload($product, $data);

        try {
            $product->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan dengan ref_product_id baru yang sama, nyaris
            // bersamaan: keduanya sama-sama tidak menemukan baris di atas,
            // lalu unique index menolak yang kalah cepat.
            if (Product::where('ref_product_id', $refProductId)->exists()) {
                return $this->duplicateRefError($refProductId);
            }

            throw $e;
        }

        return ApiResponse::success($this->present($product), [], 201);
    }

    /**
     * PUT /api/external/v1/produk/{ref_product_id}
     *
     * Tidak pernah membuat produk baru; ref_product_id yang tidak
     * ditemukan (atau ditemukan tapi statusnya nonaktif) selalu dijawab
     * not_found.
     */
    public function update(Request $request, int $ref_product_id): JsonResponse
    {
        $product = $this->findManagedByRef($ref_product_id);

        if ($product === null) {
            return $this->notFoundByRefError($ref_product_id);
        }

        $data = $this->validateProfilePayload($request);
        $this->applyPayload($product, $data);
        $product->save();

        return ApiResponse::success($this->present($product));
    }

    /**
     * DELETE /api/external/v1/produk/{ref_product_id}
     *
     * Soft delete (status = 0), memakai ulang Product::deleteProduct() —
     * sama persis dengan yang dipakai halaman admin, termasuk
     * menonaktifkan varian dan stok produk ini. ref_product_id TIDAK
     * dilepas oleh operasi ini, jadi id yang sudah dihapus lewat endpoint
     * ini tidak bisa dipakai ulang lewat POST (baris lama masih
     * memegangnya, hanya berstatus nonaktif).
     */
    public function destroy(int $ref_product_id): JsonResponse
    {
        $product = $this->findManagedByRef($ref_product_id);

        if ($product === null) {
            return $this->notFoundByRefError($ref_product_id);
        }

        (new Product())->deleteProduct(['product_id' => $product->product_id]);

        return ApiResponse::success(['ref_product_id' => $ref_product_id]);
    }

    /**
     * PATCH /api/external/v1/produk/connect
     *
     * Bentuk jamak, sama seperti PATCH /master/units/connect dan
     * /master/sales/connect: satu permintaan boleh menghubungkan banyak
     * produk sekaligus lewat body.connections (array). Setiap butir berisi
     * id (id internal Pegasus) dan ref_product_id (rujukan yang mau
     * dipasang), dipakai untuk menghubungkan produk yang sudah ada dengan
     * id PMO-nya tanpa perlu membuat baris baru.
     *
     * Setiap butir diproses independen: butir yang datanya salah tidak
     * menggagalkan butir lain dalam permintaan yang sama. Respons berupa
     * daftar hasil per butir, masing-masing menandai berhasil/gagal
     * sendiri-sendiri lewat success.
     *
     * Menimpa link yang sudah ada pada produk tujuan diperbolehkan. Kalau
     * ref_product_id yang dikirim sedang dipegang produk LAIN, rujukan itu
     * dilepas dulu dari produk itu (jadi null) sebelum dipasang ke produk
     * tujuan — "dipindah", bukan ditolak sebagai duplikat.
     */
    public function connect(Request $request): JsonResponse
    {
        $data = $request->validate([
            'connections' => ['required', 'array', 'min:1'],
            'connections.*.id' => ['required', 'integer', 'min:1'],
            'connections.*.ref_product_id' => ['required', 'integer', 'min:1'],
        ]);

        $results = array_map(
            fn (array $item) => $this->connectOne((int) $item['id'], (int) $item['ref_product_id']),
            $data['connections'],
        );

        $successCount = count(array_filter($results, static fn ($r) => $r['success']));

        return ApiResponse::success($results, [
            'total' => count($results),
            'success' => $successCount,
            'failed' => count($results) - $successCount,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function connectOne(int $productId, int $refProductId): array
    {
        $product = Product::find($productId);

        if ($product === null || ! $this->isManagedProduct($product)) {
            return $this->connectFailure(
                $productId,
                $refProductId,
                ApiResponse::ERROR_NOT_FOUND,
                'Produk dengan id '.$productId.' tidak ditemukan atau tidak aktif.',
            );
        }

        DB::transaction(function () use ($product, $refProductId) {
            Product::where('ref_product_id', $refProductId)
                ->where('product_id', '!=', $product->product_id)
                ->update(['ref_product_id' => null]);

            $product->ref_product_id = $refProductId;
            $product->save();
        });

        return [
            'id' => $productId,
            'ref_product_id' => $refProductId,
            'success' => true,
            'data' => $this->present($product->fresh()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function connectFailure(int $productId, int $refProductId, string $code, string $message): array
    {
        return [
            'id' => $productId,
            'ref_product_id' => $refProductId,
            'success' => false,
            'error' => ['code' => $code, 'message' => $message],
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Validasi & penyimpanan                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validateCreatePayload(Request $request): array
    {
        return $request->validate([
            'ref_product_id' => ['required', 'integer', 'min:1'],
        ] + $this->profileRules());
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProfilePayload(Request $request): array
    {
        return $request->validate($this->profileRules());
    }

    /**
     * category_id, unit_id, dan setiap unsur product_unit divalidasi harus
     * benar-benar menunjuk kategori/satuan yang AKTIF — beda dengan
     * Product::insertProduct()/updateProduct() milik halaman admin yang
     * tidak memeriksa ini sama sekali. Pemanggil eksternal tidak boleh
     * membuat produk dengan rujukan yang menggantung.
     *
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:250'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'category_id')->where('status', 1)],
            'unit_id' => ['required', 'integer', Rule::exists('units', 'unit_id')->where('status', 1)],
            'product_unit' => ['required', 'array', 'min:1'],
            'product_unit.*' => ['integer', Rule::exists('units', 'unit_id')->where('status', 1)],
        ];
    }

    /**
     * product_unit disimpan sebagai JSON array of string ("[\"7\",\"9\"]"),
     * mengikuti konvensi yang sudah dipakai Product::insertProduct() dan
     * SyncProductStep — bukan keputusan baru di sini.
     */
    private function applyPayload(Product $product, array $data): void
    {
        $product->product_name = trim($data['product_name']);
        $product->category_id = (int) $data['category_id'];
        $product->unit_id = (int) $data['unit_id'];
        $product->product_unit = json_encode(array_map('strval', $data['product_unit']));
    }

    /**
     * Produk dikelola endpoint ini kalau statusnya aktif — tidak ada
     * penyaringan lain seperti peran pada sales, karena tabel products
     * tidak punya kolom jenis yang membedakan produk yang boleh dikelola
     * External API dari yang tidak.
     */
    private function isManagedProduct(Product $product): bool
    {
        return (int) $product->status === 1;
    }

    private function findManagedByRef(int $refProductId): ?Product
    {
        $product = Product::where('ref_product_id', $refProductId)->first();

        return ($product !== null && $this->isManagedProduct($product)) ? $product : null;
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function present(Product $product, ?Collection $unitsMap = null, bool $showCategory = false, bool $showVariants = false): array
    {
        $productUnitIds = $this->decodeProductUnit($product->product_unit);

        $data = [
            'id' => (int) $product->product_id,
            'ref_product_id' => $product->ref_product_id !== null ? (int) $product->ref_product_id : null,
            'product_name' => (string) $product->product_name,
            'category_id' => (int) $product->category_id,
            'unit_id' => (int) $product->unit_id,
            'product_unit' => $productUnitIds,
        ];

        if ($unitsMap !== null) {
            $resolvedIds = array_values(array_unique([...$productUnitIds, (int) $product->unit_id]));

            $data['units'] = collect($resolvedIds)
                ->map(fn ($id) => $unitsMap->get($id))
                ->filter()
                ->map(static fn ($unit) => [
                    'id' => (int) $unit->unit_id,
                    'unit_name' => (string) $unit->unit_name,
                    'unit_short_name' => (string) $unit->unit_short_name,
                ])
                ->values()
                ->all();
        }

        if ($showCategory) {
            $category = $product->category;
            $data['category'] = $category ? [
                'id' => (int) $category->category_id,
                'category_name' => (string) $category->category_name,
            ] : null;
        }

        if ($showVariants) {
            $data['variants'] = $product->variants
                ->map(static fn ($variant) => [
                    'id' => (int) $variant->product_variant_id,
                    'product_variant_name' => $variant->product_variant_name,
                    'product_variant_sku' => $variant->product_variant_sku,
                    'product_variant_barcode' => $variant->product_variant_barcode,
                    'product_variant_price' => (int) $variant->product_variant_price,
                    'product_variant_alert' => $variant->product_variant_alert !== null ? (int) $variant->product_variant_alert : null,
                    'unit_id' => $variant->unit_id !== null ? (int) $variant->unit_id : null,
                ])
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<int, int>
     */
    private function decodeProductUnit($raw): array
    {
        $decoded = json_decode((string) $raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $decoded)));
    }

    private function notFoundByRefError(int $refProductId): JsonResponse
    {
        return ApiResponse::error(
            ApiResponse::ERROR_NOT_FOUND,
            'Produk dengan ref_product_id '.$refProductId.' tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(int $refProductId): JsonResponse
    {
        return ApiResponse::error(
            'duplicate_ref_id',
            'ref_product_id '.$refProductId.' sudah dipakai produk lain.',
            422,
        );
    }
}
