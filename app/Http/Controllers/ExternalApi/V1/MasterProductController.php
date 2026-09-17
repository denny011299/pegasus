<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\CategoryAutoSync;
use App\ExternalApi\Support\Exceptions\AmbiguousNameMatchException;
use App\ExternalApi\Support\UnitAutoSync;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\HandlesListQueryParams;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
 * unit_id/product_unit/category_id — SINKRONISASI OTOMATIS satuan & kategori:
 * PMO tidak selalu tahu id Pegasus untuk satuan/kategori yang dipakai sebuah
 * produk (mis. satuan/kategori itu baru, belum pernah disinkronkan lewat
 * jalur manapun). Karena itu unit_id dan setiap unsur product_unit menerima
 * DUA bentuk:
 *   - angka polos -> id satuan Pegasus yang SUDAH ADA & aktif (perilaku
 *     lama, lihat GET /master/units).
 *   - objek {ref_unit_id, unit_name, unit_short_name?} -> diresolusi lewat
 *     App\ExternalApi\Support\UnitAutoSync, LOGIKA SAMA PERSIS dengan
 *     PUT /master/units/{ref_unit_id} (dua lapis: ref_unit_id cocok -> pakai
 *     baris itu; tidak cocok -> coba adopsi lewat nama; tidak ada yang
 *     cocok -> satuan baru dibuat) — pada gilirannya sama dengan
 *     App\Synchronization\Steps\ProductFlow\SyncUnitStep (Pusat
 *     Sinkronisasi > Sinkronisasi Produk > langkah Satuan).
 *
 * category_id boleh dikosongkan kalau category_name dikirim (salah satu
 * wajib ada): category_id yang sudah ada & aktif dipakai apa adanya;
 * category_name diresolusi lewat App\ExternalApi\Support\CategoryAutoSync,
 * LOGIKA SAMA PERSIS dengan SyncCategoryStep — PMO tidak pernah menerbitkan
 * id kategori, jadi pencocokan MURNI lewat nama (tanpa kolom rujukan sama
 * sekali, beda dengan satuan/produk).
 *
 * Ambiguitas nama (baik satuan maupun kategori) dijawab AMBIGUOUS_NAME_MATCH
 * (422), sama seperti PUT /master/units/{ref_unit_id} dan SyncUnitStep/
 * SyncCategoryStep sendiri melaporkan gagal untuk kasus yang sama.
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

        try {
            $resolved = $this->resolvePayload($data);
        } catch (AmbiguousNameMatchException $e) {
            return $this->ambiguousNameError($e->entityLabel, $e->name, $e->candidateIds);
        }

        $product = new Product();
        $product->ref_product_id = $refProductId;
        $product->status = 1;
        $product->created_by = null;
        $this->applyPayload($product, $resolved);

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
     * Upsert: ref_product_id yang belum pernah ada membuat produk baru
     * (respons 201), sama seperti POST tapi dengan ref_product_id dari
     * path, bukan body — dipakai PMO untuk langsung mengirim data produk
     * yang belum pernah disinkronkan tanpa harus tahu lebih dulu apakah
     * produk itu sudah ada di Pegasus. ref_product_id yang sudah ada tapi
     * statusnya nonaktif DIAKTIFKAN KEMBALI sekaligus diperbarui (bukan
     * dijawab not_found) — upsert selalu berujung pada satu baris aktif
     * dengan data terbaru.
     */
    public function update(Request $request, int $ref_product_id): JsonResponse
    {
        $data = $this->validateProfilePayload($request);

        try {
            $resolved = $this->resolvePayload($data);
        } catch (AmbiguousNameMatchException $e) {
            return $this->ambiguousNameError($e->entityLabel, $e->name, $e->candidateIds);
        }

        $product = Product::where('ref_product_id', $ref_product_id)->first();

        if ($product === null) {
            return $this->createFromUpsert($ref_product_id, $resolved);
        }

        $product->status = 1;
        $this->applyPayload($product, $resolved);
        $product->save();

        return ApiResponse::success($this->present($product));
    }

    /**
     * @param  array<string, mixed>  $resolved  hasil resolvePayload() — id kategori/satuan sudah
     *                                          nyata ada di Pegasus, tidak perlu diresolusi lagi.
     */
    private function createFromUpsert(int $refProductId, array $resolved): JsonResponse
    {
        $product = new Product();
        $product->ref_product_id = $refProductId;
        $product->status = 1;
        $product->created_by = null;
        $this->applyPayload($product, $resolved);

        try {
            $product->save();
        } catch (\Illuminate\Database\QueryException $e) {
            // Dua permintaan PUT dengan ref_product_id baru yang sama,
            // nyaris bersamaan: keduanya sama-sama tidak menemukan baris di
            // atas, lalu unique index menolak yang kalah cepat. Perlakukan
            // sebagai upsert terhadap baris yang barusan dibuat request lain.
            $existing = Product::where('ref_product_id', $refProductId)->first();

            if ($existing === null) {
                throw $e;
            }

            $existing->status = 1;
            $this->applyPayload($existing, $resolved);
            $existing->save();

            return ApiResponse::success($this->present($existing));
        }

        return ApiResponse::success($this->present($product), [], 201);
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
                ErrorCatalog::NOT_FOUND,
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
     * Validasi BENTUK saja (tipe data, field wajib). Apakah category_id/
     * unit_id/product_unit benar-benar menunjuk baris yang ada & aktif di
     * Pegasus — atau perlu disinkronkan otomatis lebih dulu — diperiksa
     * belakangan oleh resolvePayload(), bukan di sini, karena unit_id dan
     * unsur product_unit boleh berupa objek {ref_unit_id, unit_name, ...}
     * yang sama sekali belum ada baris Pegasus-nya saat validasi ini
     * berjalan (lihat catatan kelas).
     *
     * @return array<string, array<int, mixed>>
     */
    private function profileRules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:250'],

            // Salah satu wajib ada — lihat resolveCategoryId().
            'category_id' => ['required_without:category_name', 'nullable', 'integer', 'min:1'],
            'category_name' => ['required_without:category_id', 'nullable', 'string', 'max:250'],

            'unit_id' => ['required', $this->unitFieldRule()],
            'product_unit' => ['required', 'array', 'min:1'],
            'product_unit.*' => [$this->unitFieldRule()],
        ];
    }

    /**
     * Aturan satu field unit_id/product_unit.*: boleh angka polos (id
     * Pegasus yang sudah ada) ATAU objek {ref_unit_id, unit_name,
     * unit_short_name?} untuk satuan yang belum pernah disinkronkan — lihat
     * catatan kelas dan resolveUnit().
     */
    private function unitFieldRule(): \Closure
    {
        return function (string $attribute, $value, \Closure $fail) {
            if ($this->isPlainId($value)) {
                return;
            }

            if (! is_array($value)) {
                $fail($attribute.' wajib berupa id satuan Pegasus (angka), atau objek '
                    .'{ref_unit_id, unit_name, unit_short_name?} untuk satuan yang belum disinkronkan.');

                return;
            }

            if (! isset($value['ref_unit_id']) || ! $this->isPlainId($value['ref_unit_id'])) {
                $fail($attribute.'.ref_unit_id wajib berupa angka.');

                return;
            }

            if (! isset($value['unit_name']) || ! is_string($value['unit_name']) || trim($value['unit_name']) === '') {
                $fail($attribute.'.unit_name wajib diisi kalau mengirim objek satuan.');

                return;
            }

            if (array_key_exists('unit_short_name', $value)
                && $value['unit_short_name'] !== null
                && ! is_string($value['unit_short_name'])) {
                $fail($attribute.'.unit_short_name wajib berupa teks.');
            }
        };
    }

    private function isPlainId(mixed $value): bool
    {
        return (is_int($value) && $value > 0)
            || (is_string($value) && ctype_digit($value) && (int) $value > 0);
    }

    /**
     * Menerjemahkan body tervalidasi-bentuk (profileRules()) menjadi id
     * Pegasus nyata — mengaktifkan sinkronisasi otomatis satuan & kategori
     * yang dikirim sebagai objek/nama, bukan id (lihat catatan kelas).
     * Dipanggil SEBELUM applyPayload(), sekali per request (store/update),
     * bukan berulang di createFromUpsert()'s retry path.
     *
     * @param  array<string, mixed>  $data
     * @return array{product_name: string, category_id: int, unit_id: int, product_unit: array<int, int>}
     */
    private function resolvePayload(array $data): array
    {
        $categoryId = $this->resolveCategoryId($data);
        $unitId = $this->resolveUnit($data['unit_id'])->unit_id;
        $productUnitIds = array_map(
            fn ($item) => (int) $this->resolveUnit($item)->unit_id,
            $data['product_unit'],
        );

        return [
            'product_name' => trim($data['product_name']),
            'category_id' => $categoryId,
            'unit_id' => (int) $unitId,
            'product_unit' => array_values($productUnitIds),
        ];
    }

    /**
     * category_id yang dikirim & aktif dipakai apa adanya. Kalau tidak
     * dikirim (category_name dikirim sebagai gantinya — profileRules()
     * mewajibkan salah satu), diresolusi lewat CategoryAutoSync — LOGIKA
     * SAMA PERSIS dengan SyncCategoryStep (murni lewat nama, PMO tidak
     * pernah menerbitkan id kategori).
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveCategoryId(array $data): int
    {
        if (! empty($data['category_id'])) {
            $categoryId = (int) $data['category_id'];

            if (! DB::table('categories')->where('category_id', $categoryId)->where('status', 1)->exists()) {
                $this->failValidation('category_id', 'category_id '.$categoryId.' tidak menunjuk kategori Pegasus yang aktif.');
            }

            return $categoryId;
        }

        return (new CategoryAutoSync())->resolve((string) $data['category_name']);
    }

    /**
     * Satu unsur unit_id/product_unit -> baris Unit Pegasus nyata. Angka
     * polos wajib sudah ada & aktif (perilaku lama). Objek
     * {ref_unit_id, unit_name, unit_short_name?} diresolusi lewat
     * UnitAutoSync — bisa melempar AmbiguousNameMatchException, ditangkap
     * pemanggil resolvePayload() lewat store()/update().
     */
    private function resolveUnit(mixed $value): Unit
    {
        if ($this->isPlainId($value)) {
            $unitId = (int) $value;
            $unit = Unit::where('unit_id', $unitId)->where('status', 1)->first();

            if ($unit === null) {
                $this->failValidation('unit_id', 'id satuan '.$unitId.' tidak menunjuk satuan Pegasus yang aktif.');
            }

            return $unit;
        }

        $refUnitId = (int) $value['ref_unit_id'];
        $unitName = (string) $value['unit_name'];
        $unitShortName = (string) ($value['unit_short_name'] ?? '');

        return (new UnitAutoSync())->resolve($refUnitId, $unitName, $unitShortName)->unit;
    }

    private function failValidation(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => [$message]]);
    }

    /**
     * product_unit disimpan sebagai JSON array of string ("[\"7\",\"9\"]"),
     * mengikuti konvensi yang sudah dipakai Product::insertProduct() dan
     * SyncProductStep — bukan keputusan baru di sini.
     *
     * @param  array{product_name: string, category_id: int, unit_id: int, product_unit: array<int, int>}  $resolved
     */
    private function applyPayload(Product $product, array $resolved): void
    {
        $product->product_name = $resolved['product_name'];
        $product->category_id = $resolved['category_id'];
        $product->unit_id = $resolved['unit_id'];
        $product->product_unit = json_encode(array_map('strval', $resolved['product_unit']));
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
            ErrorCatalog::NOT_FOUND,
            'Produk dengan ref_product_id '.$refProductId.' tidak ditemukan.',
            404,
        );
    }

    private function duplicateRefError(int $refProductId): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::DUPLICATE_REF_ID,
            'ref_product_id '.$refProductId.' sudah dipakai produk lain.',
            422,
        );
    }

    /**
     * @param  array<int, int>  $candidateIds
     */
    private function ambiguousNameError(string $entityLabel, string $name, array $candidateIds): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::AMBIGUOUS_NAME_MATCH,
            $entityLabel.' "'.$name.'" cocok dengan '.count($candidateIds).' baris Pegasus sekaligus yang belum '
                .'tersambung, jadi tidak bisa disinkronkan otomatis. Gabungkan/hubungkan duplikatnya lebih dulu '
                .'(mis. lewat PATCH /master/units/connect untuk satuan), lalu ulangi permintaan ini.',
            422,
            ['candidate_ids' => $candidateIds],
        );
    }
}
