<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesStock;
use App\Models\Unit;
use App\Support\ArmadaUpsert;
use App\Support\CustomerReturnCreation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
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
 *   - armada_code ("armada_id" pada chat) — customers.customer_code, SAMA pola dengan armada_code
 *     pada /shipments/scheduled dan /shipments/shipped. Sejak revisi GitHub #203 (2026-09-25) TIDAK
 *     lagi selalu wajib sendirian — lihat resolveArmada() untuk kontrak lengkapnya (armada_code vs
 *     body.armada, keduanya opsional tapi salah satu WAJIB dikirim).
 *   - armada (opsional, GitHub #203) — objek profil armada untuk upsert, ALTERNATIF dari
 *     armada_code (lihat resolveArmada()/App\Support\ArmadaUpsert::upsertProfile()).
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
 *         PMO — lihat catatan StockController::check()). Dihormati untuk SEMUA tipe baris (bahan
 *         maupun produk, eceran maupun bukan) kalau item itu mengirimnya. Kalau TIDAK dikirim,
 *         lihat aturan fallback per tipe baris di resolveSupplyWarehouses()/
 *         resolveProductWarehouses() — bahan mentah & produk non-eceran default ke gudang utama,
 *         produk satuan eceran dibiarkan kosong (wajib diisi manual lewat modal approval admin).
 *
 * warehouse_id per baris — keputusan FINAL 2026-09-25 setelah beberapa kali dibalik hari yang sama
 * (lihat riwayat commit): KEMBALI ke aturan yang dikonfirmasi pemilik produk 2026-08-17, dengan
 * satu tambahan dari revisi GitHub #203 yang DIPERTAHANKAN (gudang_id kini dihormati untuk SEMUA
 * tipe baris, bukan cuma produk eceran). Per ITEM, bukan per dokumen (beda dari /shipments/shipped
 * yang satu $warehouseId untuk seluruh dokumen — di sini items[].gudang_id sudah ada sejak GitHub
 * #58 dan tetap berguna untuk baris satuan eceran):
 *   - pakai items[].gudang_id KALAU item itu mengirimnya — SEMUA tipe baris, tidak dibatasi eceran;
 *   - kalau TIDAK dikirim DAN baris itu bahan mentah ATAU produk satuan BUKAN eceran -> default ke
 *     gudang utama (SuppliesStock::resolveWarehouseId(null)/ProductStock::resolveWarehouseId(null));
 *   - kalau TIDAK dikirim DAN baris itu produk satuan eceran (product_variants.retail_unit) ->
 *     warehouse_id dibiarkan NULL — SATU-SATUNYA kasus yang masih bisa kosong, staf gudang WAJIB
 *     mengisinya manual lewat halaman admin Pengiriman > Pengembalian (modal Edit, dropdown gudang
 *     per baris) sebelum dokumen bisa di-ACC. Alasan bahan mentah/non-eceran boleh auto-default
 *     tapi eceran tidak: barang retur satuan eceran belum tentu balik ke gudang utama (bisa balik
 *     ke gudang eceran mana saja), sedangkan bahan mentah/produk non-eceran memang selalu ke gudang
 *     utama secara bisnis. pending_warehouse_items pada respons menghitung baris eceran yang masih
 *     kosong ini. qc_staff_id masih selalu dikosongkan (kolom ini sudah nullable sejak awal, lihat
 *     migrasi 2026_08_15_161200_*) — belum ada skema rujukan staf QC dari sisi PMO.
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
        $customer = $this->resolveArmada($data);

        [$supplyDetails, $productDetails] = $this->resolveItems($data['items']);
        // Cuma baris produk satuan eceran tanpa gudang_id yang bisa lolos sampai sini dengan
        // warehouse_id NULL -- lihat resolveProductWarehouses() (bahan mentah selalu terisi, lihat
        // resolveSupplyWarehouses()). Dihitung dari kedua sisi untuk ditampilkan balik ke
        // pemanggil, supaya kelihatan jelas berapa baris yang masih perlu diisi lewat halaman admin.
        $pendingWarehouseCount = collect($supplyDetails)->filter(fn ($d) => $d['warehouse_id'] === null)->count()
            + collect($productDetails)->filter(fn ($d) => $d['warehouse_id'] === null)->count();

        $refShipmentId = $data['ref_shipment_id'] ?? null;
        $idempotencyKey = $refShipmentId !== null
            ? $this->idempotencyKey($refShipmentId, $data['return_date'], $supplyDetails, $productDetails)
            : null;

        if ($idempotencyKey !== null) {
            $existing = CustomerReturnCreation::findByIdempotencyKey($idempotencyKey);
            if ($existing !== null) {
                return $this->presentResult($existing, $customer->customer_code, $pendingWarehouseCount, 200, ['idempotent_replay' => true]);
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

            return $this->presentResult($raced, $customer->customer_code, $pendingWarehouseCount, 200, ['idempotent_replay' => true]);
        } catch (\Throwable $e) {
            CustomerReturnCreation::deleteProof($newProofPath);
            throw $e;
        }

        return $this->presentResult($result, $customer->customer_code, $pendingWarehouseCount, 201);
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
                ? 'Pengembalian berhasil disimpan. '.$pendingWarehouseCount.' baris produk satuan eceran belum punya gudang tujuan, menunggu diisi lewat halaman admin sebelum bisa diterima.'
                : 'Pengembalian berhasil disimpan, gudang tujuan tiap baris sudah ditentukan otomatis.',
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
     * Resolusi armada (GitHub #203 follow-up, 2026-09-25) — SEKARANG DUA MODE, beda dari sebelum
     * revisi ini (armada_code selalu wajib & harus sudah ada):
     *   - body.armada dikirim (objek profil, lihat App\Support\ArmadaUpsert::upsertProfile()) ->
     *     UPSERT: armada dibuat otomatis kalau belum ada, atau diperbarui (field yang dikirim saja,
     *     lihat docblock upsertProfile()) kalau sudah ada — TIDAK PERNAH ditolak karena "tidak
     *     ditemukan". Pola sama dengan Sinkronisasi Armada/PUT /armada/{code}, cuma ditumpangkan di
     *     endpoint ini supaya PMO tidak perlu memanggil PUT /armada/{code} lebih dulu sebelum bisa
     *     membuat retur untuk armada yang belum tersinkron.
     *   - body.armada TIDAK dikirim (hanya armada_code) -> perilaku LAMA, tidak berubah: armada
     *     WAJIB sudah ada dan aktif, kalau tidak ditolak VALIDATION_FAILED. Ini tetap jalur utama
     *     untuk pemanggil yang memang sudah tahu armada itu ada (kirim body.armada cuma untuk
     *     armada yang BELUM TENTU tersinkron).
     * Mengirim KEDUA field sekaligus dengan code yang BEDA ditolak (ambigu, bukan galat diam-diam
     * pakai salah satu). Mengirim keduanya dengan code yang SAMA diizinkan (armada_code jadi
     * mubazir tapi tidak menyesatkan) -- armada.code yang dipakai.
     *
     * @param  array<string, mixed>  $data
     */
    private function resolveArmada(array $data): Customer
    {
        $armadaCode = $data['armada_code'] ?? null;
        $armadaProfile = $data['armada'] ?? null;

        if ($armadaCode === null && $armadaProfile === null) {
            throw ValidationException::withMessages([
                'armada_code' => 'armada_code atau armada wajib dikirim salah satu.',
            ]);
        }

        if ($armadaCode !== null && $armadaProfile !== null
            && mb_strtoupper($armadaCode) !== mb_strtoupper((string) $armadaProfile['code'])) {
            throw ValidationException::withMessages([
                'armada' => 'armada_code dan armada.code tidak boleh berbeda kalau dikirim bersamaan.',
            ]);
        }

        if ($armadaProfile !== null) {
            return ArmadaUpsert::upsertProfile($armadaProfile);
        }

        $customer = Customer::where('customer_code', $armadaCode)->where('status', 1)->first();
        if ($customer === null) {
            throw ValidationException::withMessages([
                'armada_code' => 'armada_code tidak ditemukan atau tidak aktif. Kirim body.armada (lihat dokumentasi) untuk membuat/memperbarui armada ini otomatis, tanpa perlu PUT /armada/{code} lebih dulu.',
            ]);
        }

        return $customer;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $rules = [
            'return_date' => ['required', 'date'],
            // armada_code TIDAK lagi selalu required -- lihat resolveArmada()/docblock kelas ini
            // (GitHub #203 follow-up, 2026-09-25). Wajib salah satu dari armada_code ATAU armada
            // dikirim, ditegakkan manual di resolveArmada() (bukan lewat required_without di sini)
            // supaya galat armada_code-tidak-ditemukan tetap spesifik menyebut armada_code, bukan
            // tercampur pesan generik "salah satu wajib diisi".
            'armada_code' => ['nullable', 'string', 'max:64'],
            'armada' => ['nullable', 'array'],
            'armada.code' => ['required_with:armada', 'string', 'max:64'],
            'armada.pic' => ['nullable', 'string', 'max:255'],
            'armada.pic_phone' => ['nullable', 'string', 'max:50'],
            'armada.nomor_polisi' => ['nullable', 'string'],
            'armada.category' => ['nullable', 'string', 'max:100'],
            'armada.merk_model' => ['nullable', 'string', 'max:255'],
            'armada.tahun_kendaraan' => ['nullable', 'string', 'max:20'],
            'armada.lokasi' => ['nullable', 'string', 'max:255'],
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
        ];

        return $request->validate($rules);
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
        // retail_unit ikut diambil di sini supaya isEceran() bisa dihitung sekali per baris tanpa
        // query tambahan -- dipakai resolveProductWarehouses() di bawah untuk menentukan baris
        // mana yang boleh default ke gudang utama vs yang wajib diisi manual (lihat docblock
        // method itu).
        $hasRetailCol = Schema::hasColumn('product_variants', 'retail_unit');
        $variantCols = ['product_variant_id', 'product_variant_sku'];
        if ($hasRetailCol) {
            $variantCols[] = 'retail_unit';
        }
        $variantsBySku = $skus === []
            ? collect()
            : ProductVariant::whereIn('product_variant_sku', $skus)->where('status', 1)
                ->get($variantCols)->keyBy('product_variant_sku');

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

                $retailUnitId = $hasRetailCol ? (int) ($variant->retail_unit ?? 0) : 0;
                $isEceran = $retailUnitId > 0 && $retailUnitId === (int) $unit->unit_id;

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
                        '_is_eceran' => $isEceran,
                    ];
                }
            }
        }

        $this->resolveSupplyWarehouses($supplyDetails);
        $this->resolveProductWarehouses($productDetails);

        return [array_values($supplyDetails), array_values($productDetails)];
    }

    /**
     * Bahan mentah/kemasan tidak punya konsep satuan eceran (beda dari produk jadi, lihat
     * resolveProductWarehouses()) — jadi aturannya lebih sederhana: pakai items[].gudang_id kalau
     * dikirim, kalau TIDAK selalu default ke gudang utama (SuppliesStock::resolveWarehouseId(null)),
     * TIDAK PERNAH dibiarkan NULL. Keputusan final 2026-09-25 (sempat dibalik dua kali hari yang
     * sama — lihat riwayat commit): percobaan "biarkan semua baris NULL, staf isi manual lewat
     * admin" dibatalkan karena bahan mentah memang selalu bisa diasumsikan balik ke gudang utama,
     * tidak ada padanan "gudang eceran" untuknya.
     *
     * @param  array<string, array<string, mixed>>  $supplyDetails  diubah in-place (by reference).
     */
    private function resolveSupplyWarehouses(array &$supplyDetails): void
    {
        $mainWarehouseId = SuppliesStock::resolveWarehouseId(null);
        foreach ($supplyDetails as &$detail) {
            $detail['warehouse_id'] = $detail['_gudang_id'] ?? $mainWarehouseId;
            unset($detail['_gudang_id']);
        }
        unset($detail);
    }

    /**
     * DIPUTUSKAN ULANG 2026-09-25 (setelah dua kali dibalik hari yang sama) — kembali ke aturan
     * yang sama dengan yang dikonfirmasi pemilik produk 2026-08-17, PLUS gudang_id sekarang
     * dihormati untuk baris non-eceran juga (tidak diabaikan seperti versi 2026-08-17):
     *   - pakai items[].gudang_id KALAU baris itu mengirimnya (baik eceran maupun bukan);
     *   - kalau tidak dikirim DAN satuannya BUKAN satuan eceran produk itu
     *     (product_variants.retail_unit) -> default ke gudang utama
     *     (ProductStock::resolveWarehouseId(null)), SAMA seperti bahan mentah;
     *   - kalau tidak dikirim DAN satuannya ADALAH satuan eceran -> warehouse_id dibiarkan NULL.
     *     Baris ini WAJIB diisi manual oleh staf gudang lewat modal approval Pengembalian sebelum
     *     dokumen bisa di-ACC — TIDAK di-auto-default ke gudang utama, karena barang retur satuan
     *     eceran belum tentu balik ke gudang utama (bisa balik ke gudang eceran mana saja).
     * Ini SATU-SATUNYA kasus yang masih bisa menyisakan warehouse_id kosong pada endpoint ini.
     *
     * @param  array<string, array<string, mixed>>  $productDetails  diubah in-place (by reference).
     */
    private function resolveProductWarehouses(array &$productDetails): void
    {
        $mainWarehouseId = ProductStock::resolveWarehouseId(null);
        foreach ($productDetails as &$detail) {
            $detail['warehouse_id'] = $detail['_gudang_id'] ?? ($detail['_is_eceran'] ? null : $mainWarehouseId);
            unset($detail['_gudang_id'], $detail['_is_eceran']);
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
