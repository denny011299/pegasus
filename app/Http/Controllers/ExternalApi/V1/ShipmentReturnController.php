<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\Unit;
use App\Support\CustomerReturnCreation;
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
 *
 * warehouse_id SENGAJA selalu dikosongkan pada setiap baris detail yang dibuat — DIKONFIRMASI
 * pemilik produk lewat WhatsApp pada issue #58: "Terkait auto insert gudang (warehouse adalah new
 * feature juga di fase 2): masih belum tahap final, sehingga diperbolehkan skip auto insert ke
 * gudang/warehouse ... jadi pas acc baru isi gudang". Baris yang dibuat lewat endpoint ini
 * BERSTATUS Pending (1) sama seperti dibuat lewat admin, tapi TIDAK BISA langsung di-ACC sampai
 * staf gudang mengisi warehouse_id lewat halaman admin Pengiriman > Pengembalian —
 * CustomerReturnController::validateSupplyDetails()/validateProductDetails() tetap menolak
 * warehouse_id kosong sebelum accept() memotong stok, jadi tidak ada risiko stok masuk ke gudang
 * yang salah/tidak ditentukan. Migrasi 2026_08_17_090100_* yang mengizinkan NULL di kedua tabel
 * detail hanya berlaku untuk baris yang datang dari sini — alur admin selalu tetap mengisi
 * warehouse_id seperti sebelumnya. qc_staff_id juga selalu dikosongkan dengan alasan yang sama
 * (kolom ini sudah nullable sejak awal, lihat migrasi 2026_08_16_161200_*).
 *
 * TIDAK idempoten (beda dengan /shipments/shipped dan /payments/cash) — tidak ada field acuan
 * unik seperti ref_shipment_id/ref_payment_id pada kontrak WhatsApp ini, ref_number di sini murni
 * catatan bebas (sama seperti pada alur admin), bukan kunci dedup. Setiap POST yang lolos validasi
 * selalu membuat dokumen pengembalian baru, sama seperti /shipments/scheduled.
 */
class ShipmentReturnController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $customer = Customer::where('customer_code', $data['armada_code'])->where('status', 1)->first();

        [$supplyDetails, $productDetails] = $this->resolveItems($data['items']);

        $newProofPath = null;

        try {
            $newProofPath = CustomerReturnCreation::storeProofFromInput(
                $data['proof_base64'] ?? null,
                $request->hasFile('proof') ? $request->file('proof') : null,
                true,
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
            ], $supplyDetails, $productDetails);
        } catch (\Throwable $e) {
            CustomerReturnCreation::deleteProof($newProofPath);
            throw $e;
        }

        return ApiResponse::success([
            'return_number' => $result['return_group'],
            'return_type' => $result['return_type'],
            'supply_return_id' => $result['supply_return_id'],
            'product_return_id' => $result['product_return_id'],
            'armada_code' => $data['armada_code'],
            'message' => 'Pengembalian berhasil disimpan, menunggu gudang tujuan diisi sebelum diterima.',
        ], [], 201);
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
            'ref_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'proof' => ['required_without:proof_base64', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'proof_base64' => ['required_without:proof', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'integer', Rule::in([1, 2])],
            'items.*.ref_id' => ['required'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.satuan_id' => [
                'required', 'integer',
                Rule::exists('units', 'ref_unit_id')->where('status', 1),
            ],
        ]);
    }

    /**
     * Petakan items[] (type/ref_id/satuan_id) ke baris siap-simpan
     * App\Support\CustomerReturnCreation::replaceSupplyDetails()/replaceProductDetails() —
     * warehouse_id SENGAJA tidak pernah diisi (lihat docblock kelas ini). Baris dengan
     * supplies_id/unit_id (atau product_variant_id/unit_id) yang sama digabung, qty dijumlah —
     * sama pola dengan CustomerReturnController::parseSupplyDetails()/parseProductDetails().
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

            if ($type === 1) {
                $refSuppliesId = (int) $item['ref_id'];
                $supplies = $suppliesByRef->get($refSuppliesId);
                if ($supplies === null) {
                    throw ValidationException::withMessages([
                        "items.$index.ref_id" => 'Bahan dengan ref_supplies_id '.$refSuppliesId.' tidak ditemukan atau tidak aktif. Daftarkan lewat POST /bahan atau PATCH /bahan/connect terlebih dahulu.',
                    ]);
                }

                $key = $supplies->supplies_id.'|'.$unit->unit_id;
                if (isset($supplyDetails[$key])) {
                    $supplyDetails[$key]['qty'] += $qty;
                } else {
                    $supplyDetails[$key] = [
                        'supplies_id' => (int) $supplies->supplies_id,
                        'unit_id' => (int) $unit->unit_id,
                        'qty' => $qty,
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

                $key = $variant->product_variant_id.'|'.$unit->unit_id;
                if (isset($productDetails[$key])) {
                    $productDetails[$key]['qty'] += $qty;
                } else {
                    $productDetails[$key] = [
                        'product_variant_id' => (int) $variant->product_variant_id,
                        'unit_id' => (int) $unit->unit_id,
                        'qty' => $qty,
                    ];
                }
            }
        }

        return [array_values($supplyDetails), array_values($productDetails)];
    }

    /**
     * Pastikan satuan yang dipakai tiap baris benar-benar terdaftar untuk bahan/produk itu (default
     * + satuan tambahan + relasi konversi) — katalog yang sama dipakai form admin
     * (CustomerReturnController::buildReturnContext()), TANPA aturan gudang/eceran (endpoint ini
     * tidak pernah menentukan gudang, lihat docblock kelas ini).
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
