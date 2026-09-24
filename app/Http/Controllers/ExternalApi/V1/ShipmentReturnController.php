<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\Unit;
use App\Support\CustomerReturnCreation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * POST /api/external/v1/shipments/returns (GitHub #58) — PMO memicu satu dokumen pengembalian
 * (bahan mentah/kemasan dan/atau produk jadi dari armada) langsung dari sistemnya sendiri, tanpa
 * lewat halaman admin. Fiturnya SUDAH ADA di alur admin (menu Pengiriman > Pengembalian,
 * App\Http\Controllers\CustomerReturnController) — controller ini cuma memetakan bentuk
 * permintaan PMO ke bentuk yang sudah dipakai App\Support\CustomerReturnCreation::create(),
 * bagian penyimpanan yang diekstrak dari CustomerReturnController::store() SUPAYA dipakai ulang
 * di sini (bukan disalin).
 *
 * Bentuk permintaan mengikuti chat WhatsApp pada issue #58 (belum ada di
 * "private docs/Open API/API_Integration_Specification_PMO_IPM_v1.md" — modul ini baru):
 *   - return_date (wajib, "tanggal")
 *   - armada_code (wajib, "armada_id" pada chat) — customers.customer_code, SAMA pola dengan
 *     armada_code pada /shipments/scheduled dan /shipments/shipped, bukan konsep baru di sini.
 *   - ref_number (opsional, "no_referensi")
 *   - notes (opsional)
 *   - proof / proof_base64 (SALAH SATU wajib, "foto") — file sungguhan (multipart/form-data)
 *     ATAU data URI base64 lewat JSON murni, PERSIS aturan yang sama dengan
 *     CustomerReturnController::storeProof()/App\Support\CustomerReturnCreation::
 *     storeProofFromInput() (bukan App\ExternalApi\Support\ShipmentPhotoStore milik
 *     /shipments/shipped — beda fitur, beda konvensi, disengaja tetap dipisah).
 *   - items[] (wajib minimal 1), tiap butir:
 *       - type: 1 (bahan mentah/kemasan) atau 2 (produk jadi)
 *       - ref_id: type=1 -> supplies.ref_supplies_id (integer, dikelola
 *         MasterSuppliesController — Data Bahan, modul baru dibangun bersamaan dengan endpoint
 *         ini). type=2 -> product_variant_sku (string, SAMA pola dengan items[].variant_sku pada
 *         POST /shipments/shipped, BUKAN products.ref_product_id — granularitasnya varian, ref
 *         itu granularitasnya produk).
 *       - qty
 *       - satuan_id: units.ref_unit_id, SAMA pola dipakai items[].unit_id di seluruh modul
 *         Shipment/Stok (BUKAN unit_id internal Pegasus).
 *       - gudang_id (opsional): warehouses.id LANGSUNG — SAMA seperti gudang_id opsional pada
 *         POST /stock/check dan {gudang_id} pada PUT/DELETE /master/warehouses/{gudang_id}, BUKAN
 *         kolom rujukan eksternal seperti ref_unit_id/ref_product_id (gudang tidak disinkronkan
 *         PMO — lihat catatan StockController::check()). Dipakai untuk SEMUA tipe baris (bahan
 *         maupun produk, eceran maupun bukan) sejak revisi GitHub #203 di bawah — PMO memang tidak
 *         pernah mengirimnya sama sekali untuk kasus pengembalian, jadi baris yang tidak
 *         menyertakannya dibiarkan warehouse_id NULL, bukan lagi di-auto-default.
 *
 * warehouse_id per baris — REVISI GitHub #203 (2026-09-25), MEMBALIK aturan auto-default gudang
 * utama yang tadinya dikonfirmasi pemilik produk 2026-08-17. Aturan LAMA (bahan mentah & produk
 * non-eceran SELALU otomatis ke gudang utama, gudang_id item diabaikan untuk keduanya) TIDAK
 * BERLAKU LAGI. Aturan BARU, berlaku untuk SEMUA baris tanpa kecuali (bahan maupun produk, eceran
 * maupun bukan):
 *   - pakai items[].gudang_id KALAU dikirim (divalidasi warehouses aktif di validatePayload());
 *   - kalau tidak dikirim, warehouse_id baris itu dibiarkan NULL — TIDAK PERNAH diisi otomatis ke
 *     gudang utama lagi.
 * Alasannya: endpoint ini SELALU dipanggil PMO (bukan admin), dan PMO memang tidak pernah mengirim
 * gudang_id sama sekali untuk kasus pengembalian — auto-default ke gudang utama untuk bahan/produk
 * non-eceran selama ini diam-diam MENYEMBUNYIKAN keputusan penempatan gudang dari staf gudang,
 * padahal barang retur fisiknya belum tentu benar-benar ada di gudang utama. Sekarang staf gudang
 * WAJIB menentukan sendiri gudang tujuan tiap baris lewat halaman admin Pengiriman > Pengembalian
 * (modal Edit, dropdown gudang per baris — lihat Customer_Return.js) sebelum dokumen bisa di-ACC.
 * Baris yang dibuat lewat endpoint ini BERSTATUS Pending (1) sama seperti dibuat lewat admin. Kalau
 * ADA baris yang warehouse_id-nya masih NULL (sekarang bisa baris tipe apa pun, bukan cuma produk
 * eceran seperti sebelum revisi ini), dokumennya TIDAK BISA langsung di-ACC sampai staf gudang
 * mengisi warehouse_id lewat halaman admin — CustomerReturnController::validateSupplyDetails()/
 * validateProductDetails() tetap menolak warehouse_id kosong sebelum accept() memotong stok, sudah
 * berlaku untuk kedua sisi sejak sebelum revisi ini (tidak berubah). Migrasi 2026_08_17_090100_*
 * yang mengizinkan NULL di kedua tabel detail masih relevan, sekarang malah jadi jalur utama bukan
 * kasus khusus. qc_staff_id masih selalu dikosongkan (kolom ini sudah nullable sejak awal, lihat
 * migrasi 2026_08_15_161200_*) — belum ada skema rujukan staf QC dari sisi PMO.
 *
 * GitHub #203 — retur per-nota dari PMO: saat shipment yang sudah "Berjalan" diedit dan
 * sebagian/semua notanya ditandai "Belum dikirim", PMO memanggil endpoint ini untuk memberi tahu
 * IPM barang dari nota-nota itu kembali, TERLEPAS dari sudah di tahap approval mana pun shipment
 * asalnya (lihat App\Support\ShipmentApproval) — beda dengan POST /shipments/shipped yang berhenti
 * bisa ditulis ulang begitu ada approval/reject. Endpoint ini TIDAK menyentuh status/stok shipment
 * asal sama sekali (di luar cakupan GitHub #203, lihat body issue) — ia murni mencatat dokumen
 * pengembalian yang tertaut ke shipment itu:
 *   - ref_shipment_id (opsional) — sales_orders.ref_shipment_id milik shipment asal, dipakai
 *     PEMANGGIL DARI PMO. Disimpan apa adanya ke customer_supply_returns/customer_product_returns
 *     (kolom baru, lihat migrasi 2026_09_24_090000_*) — TIDAK divalidasi harus ada di sales_orders
 *     (retur bisa merujuk shipment yang sudah lama, dan tidak ada alasan bisnis menolak retur
 *     hanya karena baris shipment-nya sendiri sudah tidak ada/berubah referensi).
 *   - items[].ref_nota_id (opsional) — pola SAMA dengan sales_order_details.ref_nota_id pada
 *     POST /shipments/shipped (GitHub #180): id nota (oms_order.id) PMO asal baris retur itu,
 *     disimpan ke customer_supply_return_details/customer_product_return_details.ref_nota_id,
 *     murni penelusuran, tidak divalidasi maupun memengaruhi logika lain. Ikut jadi bagian kunci
 *     penggabungan baris di resolveItems() (beda dari sebelum GitHub #203) supaya dua baris retur
 *     item+satuan yang sama TAPI dari nota PMO yang berbeda tidak tergabung jadi satu baris dan
 *     kehilangan keterlacakan per-nota.
 *
 * proof/proof_base64 jadi OPSIONAL ketika ref_shipment_id dikirim (lihat validatePayload()) —
 * form edit pengiriman PMO tidak punya field upload foto untuk kasus retur ini (beda dengan retur
 * yang dibuat manual dari halaman admin, fotonya tetap wajib kalau ref_shipment_id kosong).
 * customer_supply_returns.proof_path/customer_product_returns.proof_path dilonggarkan NULLABLE di
 * migrasi yang sama untuk menampung ini.
 *
 * IDEMPOTEN sejak GitHub #203 (BEDA dari sebelumnya) HANYA ketika ref_shipment_id dikirim — key-nya
 * dihitung idempotencyKey() dari ref_shipment_id + return_date + isi items[] (lihat method itu).
 * Permintaan yang sama persis dikirim ulang (mis. retry PMO setelah timeout jaringan) mengembalikan
 * dokumen yang SUDAH ada (200, bukan 201, 'idempotent_replay' => true pada meta), TIDAK membuat
 * dokumen kedua — dijamin sampai level constraint unique kolom idempotency_key (lihat
 * store()/QueryException di bawah), bukan cuma cek SELECT lebih dulu, supaya dua permintaan retry
 * yang nyaris bersamaan tetap tidak lolos berdua. Permintaan TANPA ref_shipment_id (retur manual
 * ala admin lewat integrasi lain) TETAP TIDAK idempoten seperti semula — setiap POST yang lolos
 * validasi selalu membuat dokumen baru, sama seperti /shipments/scheduled.
 */
class ShipmentReturnController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $customer = Customer::where('customer_code', $data['armada_code'])->where('status', 1)->first();

        [$supplyDetails, $productDetails] = $this->resolveItems($data['items']);
        // Sejak revisi GitHub #203 (2026-09-25), baris APA PUN (bahan maupun produk) bisa lolos
        // sampai sini dengan warehouse_id NULL -- lihat resolveSupplyWarehouses()/
        // resolveProductWarehouses(). Dihitung dari KEDUA sisi untuk ditampilkan balik ke pemanggil,
        // supaya kelihatan jelas berapa baris yang masih perlu diisi lewat halaman admin.
        $pendingWarehouseCount = collect($supplyDetails)->filter(fn ($d) => $d['warehouse_id'] === null)->count()
            + collect($productDetails)->filter(fn ($d) => $d['warehouse_id'] === null)->count();

        $refShipmentId = $data['ref_shipment_id'] ?? null;
        $idempotencyKey = $refShipmentId !== null
            ? $this->idempotencyKey($refShipmentId, $data['return_date'], $supplyDetails, $productDetails)
            : null;

        if ($idempotencyKey !== null) {
            $existing = CustomerReturnCreation::findByIdempotencyKey($idempotencyKey);
            if ($existing !== null) {
                return $this->presentResult($existing, $data['armada_code'], $pendingWarehouseCount, 200, ['idempotent_replay' => true]);
            }
        }

        $newProofPath = null;

        try {
            $newProofPath = CustomerReturnCreation::storeProofFromInput(
                $data['proof_base64'] ?? null,
                $request->hasFile('proof') ? $request->file('proof') : null,
                $refShipmentId === null,
            );

            $this->assertAgainstCatalog($supplyDetails, $productDetails);

            $result = CustomerReturnCreation::create([
                'customer_id' => (int) $customer->customer_id,
                'return_date' => $data['return_date'],
                'ref_number' => $data['ref_number'] ?? null,
                'notes' => $data['notes'] ?? null,
                'proof_path' => $newProofPath,
                'qc_staff_id' => null,
                'created_by' => null,
                'ref_shipment_id' => $refShipmentId,
                'idempotency_key' => $idempotencyKey,
            ], $supplyDetails, $productDetails);
        } catch (QueryException $e) {
            CustomerReturnCreation::deleteProof($newProofPath);

            // Dua permintaan dengan ref_shipment_id + items identik, nyaris bersamaan -- keduanya
            // sama-sama tidak menemukan baris di atas, lalu unique index idempotency_key menolak
            // yang kalah cepat. Perlakukan sebagai replay terhadap dokumen yang barusan dibuat
            // request lain, sama pola race yang sudah ditangani ShipmentController::scheduled()/
            // shipped().
            $raced = $idempotencyKey !== null ? CustomerReturnCreation::findByIdempotencyKey($idempotencyKey) : null;
            if ($raced === null) {
                throw $e;
            }

            return $this->presentResult($raced, $data['armada_code'], $pendingWarehouseCount, 200, ['idempotent_replay' => true]);
        } catch (\Throwable $e) {
            CustomerReturnCreation::deleteProof($newProofPath);
            throw $e;
        }

        return $this->presentResult($result, $data['armada_code'], $pendingWarehouseCount, 201);
    }

    /**
     * @param  array{doc_key:string, return_group:string, return_type:string, supply_return_id:?int, product_return_id:?int}  $result
     */
    private function presentResult(array $result, string $armadaCode, int $pendingWarehouseCount, int $httpStatus, array $meta = []): JsonResponse
    {
        return ApiResponse::success([
            'return_number' => $result['return_group'],
            'return_type' => $result['return_type'],
            'supply_return_id' => $result['supply_return_id'],
            'product_return_id' => $result['product_return_id'],
            'armada_code' => $armadaCode,
            'pending_warehouse_items' => $pendingWarehouseCount,
            'message' => $pendingWarehouseCount > 0
                ? 'Pengembalian berhasil disimpan. '.$pendingWarehouseCount.' baris belum punya gudang tujuan, menunggu diisi lewat halaman admin sebelum bisa diterima.'
                : 'Pengembalian berhasil disimpan, gudang tujuan tiap baris sudah terisi.',
        ], $meta, $httpStatus);
    }

    /**
     * Key idempotensi GitHub #203 — hanya dihitung ketika ref_shipment_id dikirim (lihat docblock
     * kelas ini). Dibangun dari ref_shipment_id + return_date + isi items[] SETELAH digabung
     * (supplyDetails/productDetails, sudah termasuk ref_nota_id di kuncinya lewat resolveItems())
     * supaya urutan baris pada payload atau penggabungan qty tidak mengubah key untuk payload yang
     * "sama" secara isi. gudang_id/satuan_id TIDAK ikut mempengaruhi key -- unit_id/warehouse_id
     * hasil resolusi sudah cukup mewakili baris yang sama, tidak perlu membawa representasi mentah
     * dari body permintaan.
     *
     * @param  array<int, array<string, mixed>>  $supplyDetails
     * @param  array<int, array<string, mixed>>  $productDetails
     */
    private function idempotencyKey(string $refShipmentId, string $returnDate, array $supplyDetails, array $productDetails): string
    {
        $normalize = static function (array $details, array $keys): array {
            return collect($details)
                ->map(static fn ($detail) => collect($keys)->map(fn ($key) => $detail[$key] ?? null)->implode('|'))
                ->sort()->values()->all();
        };

        $payload = [
            'ref_shipment_id' => $refShipmentId,
            'return_date' => $returnDate,
            'supplies' => $normalize($supplyDetails, ['supplies_id', 'unit_id', 'warehouse_id', 'ref_nota_id', 'qty']),
            'products' => $normalize($productDetails, ['product_variant_id', 'unit_id', 'warehouse_id', 'ref_nota_id', 'qty']),
        ];

        return hash('sha256', json_encode($payload));
    }

    /* ------------------------------------------------------------------ */
    /* Validasi & resolusi                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'return_date' => ['required', 'date'],
            'armada_code' => [
                'required', 'string',
                Rule::exists('customers', 'customer_code')->where('status', 1),
            ],
            'ref_shipment_id' => ['nullable', 'string', 'max:100'],
            'ref_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            // proof/proof_base64 jadi opsional (GitHub #203) begitu ref_shipment_id dikirim -- lihat
            // docblock kelas ini. required_without_all HANYA mewajibkan field ini kalau KEDUA field
            // lain yang disebut kosong, jadi retur ala admin (tanpa ref_shipment_id) tetap wajib
            // mengirim salah satu dari proof/proof_base64, persis perilaku sebelum GitHub #203.
            'proof' => ['required_without_all:proof_base64,ref_shipment_id', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'proof_base64' => ['required_without_all:proof,ref_shipment_id', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'integer', Rule::in([1, 2])],
            'items.*.ref_id' => ['required'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.satuan_id' => [
                'required', 'integer',
                Rule::exists('units', 'ref_unit_id')->where('status', 1),
            ],
            'items.*.gudang_id' => [
                'nullable', 'integer',
                Rule::exists('warehouses', 'id')->where('status', 1),
            ],
            'items.*.ref_nota_id' => ['nullable', 'integer'],
        ]);
    }

    /**
     * Petakan items[] (type/ref_id/satuan_id/gudang_id) ke baris siap-simpan
     * App\Support\CustomerReturnCreation::replaceSupplyDetails()/replaceProductDetails(),
     * TERMASUK menentukan warehouse_id per baris (lihat aturan lengkap di docblock kelas ini).
     * Baris dengan supplies_id/unit_id (atau product_variant_id/unit_id) yang sama digabung, qty
     * dijumlah — sama pola dengan CustomerReturnController::parseSupplyDetails()/
     * parseProductDetails(). Kalau baris yang sama muncul lebih dari sekali dengan gudang_id
     * berbeda-beda, yang dipakai adalah gudang_id dari kemunculan PERTAMA — bukan error, karena
     * kasus ini di luar cakupan kontrak WhatsApp issue #58 dan tidak ada alasan bisnis untuk
     * menolaknya keras.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function resolveItems(array $items): array
    {
        $refUnitIds = array_values(array_unique(array_map(fn ($item) => (int) $item['satuan_id'], $items)));
        $unitsByRef = Unit::whereIn('ref_unit_id', $refUnitIds)->where('status', 1)
            ->get(['unit_id', 'ref_unit_id'])->keyBy('ref_unit_id');

        $refSuppliesIds = array_values(array_unique(array_map(
            fn ($item) => (int) $item['ref_id'],
            array_filter($items, fn ($item) => (int) $item['type'] === 1),
        )));
        $suppliesByRef = $refSuppliesIds === []
            ? collect()
            : Supplies::whereIn('ref_supplies_id', $refSuppliesIds)->where('status', 1)
                ->get(['supplies_id', 'ref_supplies_id'])->keyBy('ref_supplies_id');

        $skus = array_values(array_unique(array_map(
            fn ($item) => (string) $item['ref_id'],
            array_filter($items, fn ($item) => (int) $item['type'] === 2),
        )));
        $variantsBySku = $skus === []
            ? collect()
            : ProductVariant::whereIn('product_variant_sku', $skus)->where('status', 1)
                ->get(['product_variant_id', 'product_variant_sku'])->keyBy('product_variant_sku');

        $supplyDetails = [];
        $productDetails = [];

        foreach ($items as $index => $item) {
            $type = (int) $item['type'];
            $qty = (int) $item['qty'];
            $unit = $unitsByRef->get((int) $item['satuan_id']);
            if ($unit === null) {
                // Sudah divalidasi ada di validatePayload() — null di sini cuma race condition.
                throw ValidationException::withMessages(["items.$index.satuan_id" => 'Satuan tidak lagi valid, coba ulang.']);
            }
            $itemGudangId = isset($item['gudang_id']) && $item['gudang_id'] !== null && $item['gudang_id'] !== ''
                ? (int) $item['gudang_id']
                : null;
            $itemRefNotaId = isset($item['ref_nota_id']) && $item['ref_nota_id'] !== null && $item['ref_nota_id'] !== ''
                ? (int) $item['ref_nota_id']
                : null;

            if ($type === 1) {
                $refSuppliesId = (int) $item['ref_id'];
                $supplies = $suppliesByRef->get($refSuppliesId);
                if ($supplies === null) {
                    throw ValidationException::withMessages([
                        "items.$index.ref_id" => 'Bahan dengan ref_supplies_id '.$refSuppliesId.' tidak ditemukan atau tidak aktif. Daftarkan lewat POST /bahan atau PATCH /bahan/connect terlebih dahulu.',
                    ]);
                }

                // ref_nota_id ikut jadi bagian kunci penggabungan (GitHub #203) -- dua baris bahan
                // yang sama tapi berasal dari nota PMO berbeda TIDAK digabung, supaya keterlacakan
                // per-nota tidak hilang.
                $key = $supplies->supplies_id.'|'.$unit->unit_id.'|'.($itemRefNotaId ?? '');
                if (isset($supplyDetails[$key])) {
                    $supplyDetails[$key]['qty'] += $qty;
                } else {
                    $supplyDetails[$key] = [
                        'supplies_id' => (int) $supplies->supplies_id,
                        'unit_id' => (int) $unit->unit_id,
                        'ref_nota_id' => $itemRefNotaId,
                        'qty' => $qty,
                        // Ditandai underscore -- flag internal untuk resolveSupplyWarehouses() di
                        // bawah, dibuang sebelum baris ini sampai ke CustomerReturnCreation.
                        '_gudang_id' => $itemGudangId,
                    ];
                }
            } else {
                $sku = (string) $item['ref_id'];
                $variant = $variantsBySku->get($sku);
                if ($variant === null) {
                    throw ValidationException::withMessages([
                        "items.$index.ref_id" => 'Produk dengan SKU "'.$sku.'" tidak ditemukan atau tidak aktif.',
                    ]);
                }

                // ref_nota_id ikut jadi bagian kunci penggabungan (GitHub #203), sama alasan seperti
                // baris bahan di atas.
                $key = $variant->product_variant_id.'|'.$unit->unit_id.'|'.($itemRefNotaId ?? '');
                if (isset($productDetails[$key])) {
                    $productDetails[$key]['qty'] += $qty;
                } else {
                    $productDetails[$key] = [
                        'product_variant_id' => (int) $variant->product_variant_id,
                        'unit_id' => (int) $unit->unit_id,
                        'ref_nota_id' => $itemRefNotaId,
                        'qty' => $qty,
                        // Ditandai underscore -- flag internal untuk resolveProductWarehouses() di
                        // bawah, dibuang sebelum baris ini sampai ke CustomerReturnCreation.
                        '_gudang_id' => $itemGudangId,
                    ];
                }
            }
        }

        $this->resolveSupplyWarehouses($supplyDetails);
        $this->resolveProductWarehouses($productDetails);

        return [array_values($supplyDetails), array_values($productDetails)];
    }

    /**
     * REVISI GitHub #203 (2026-09-25) — TIDAK PERNAH auto-default ke gudang utama lagi, lihat
     * docblock kelas ini. Pakai items[].gudang_id kalau dikirim, kalau tidak dibiarkan NULL supaya
     * staf gudang mengisinya manual lewat halaman admin sebelum dokumen bisa di-ACC.
     *
     * @param  array<string, array<string, mixed>>  $supplyDetails  diubah in-place (by reference).
     */
    private function resolveSupplyWarehouses(array &$supplyDetails): void
    {
        foreach ($supplyDetails as &$detail) {
            $detail['warehouse_id'] = $detail['_gudang_id'];
            unset($detail['_gudang_id']);
        }
        unset($detail);
    }

    /**
     * REVISI GitHub #203 (2026-09-25) — sama seperti resolveSupplyWarehouses(), TIDAK PERNAH
     * auto-default ke gudang utama lagi (dulu baris non-eceran selalu ke gudang utama, hanya baris
     * eceran yang boleh kosong). Sekarang SEMUA baris produk memakai items[].gudang_id kalau
     * dikirim, kalau tidak dibiarkan NULL, terlepas dari satuannya eceran atau bukan.
     *
     * @param  array<string, array<string, mixed>>  $productDetails  diubah in-place (by reference).
     */
    private function resolveProductWarehouses(array &$productDetails): void
    {
        foreach ($productDetails as &$detail) {
            $detail['warehouse_id'] = $detail['_gudang_id'];
            unset($detail['_gudang_id']);
        }
        unset($detail);
    }

    /**
     * Pastikan satuan yang dipakai tiap baris benar-benar terdaftar untuk bahan/produk itu (default
     * + satuan tambahan + relasi konversi) — katalog yang sama dipakai form admin
     * (CustomerReturnController::buildReturnContext()). Aturan gudang/eceran SUDAH diselesaikan
     * sebelum method ini dipanggil (lihat resolveItems()/resolveSupplyWarehouses()/
     * resolveProductWarehouses()) — ini murni validasi satuan, tidak menyentuh warehouse_id.
     *
     * Dicek SETELAH baris digabung (bukan per items[] asli), jadi galatnya menyebut supplies_id/
     * product_variant_id + unit_id yang bermasalah langsung — bukan "items.N.satuan_id", yang
     * indeksnya sudah tidak berarti apa-apa lagi pasca penggabungan qty.
     *
     * @param  array<int, array<string, mixed>>  $supplyDetails
     * @param  array<int, array<string, mixed>>  $productDetails
     */
    private function assertAgainstCatalog(array $supplyDetails, array $productDetails): void
    {
        if ($supplyDetails !== []) {
            $allowed = collect(CustomerReturnCreation::suppliesContext())->keyBy('supplies_id');
            foreach ($supplyDetails as $detail) {
                $supplies = $allowed->get($detail['supplies_id']);
                if (! $supplies || ! collect($supplies['units'])->contains(fn ($unit) => (int) $unit['unit_id'] === (int) $detail['unit_id'])) {
                    throw ValidationException::withMessages([
                        'items' => 'Satuan tidak terdaftar untuk bahan dengan supplies_id '.$detail['supplies_id'].'.',
                    ]);
                }
            }
        }

        if ($productDetails !== []) {
            $allowed = collect(CustomerReturnCreation::productsContext())->keyBy('product_variant_id');
            foreach ($productDetails as $detail) {
                $product = $allowed->get($detail['product_variant_id']);
                if (! $product || ! collect($product['units'])->contains(fn ($unit) => (int) $unit['unit_id'] === (int) $detail['unit_id'])) {
                    throw ValidationException::withMessages([
                        'items' => 'Satuan tidak terdaftar untuk produk dengan product_variant_id '.$detail['product_variant_id'].'.',
                    ]);
                }
            }
        }
    }
}
