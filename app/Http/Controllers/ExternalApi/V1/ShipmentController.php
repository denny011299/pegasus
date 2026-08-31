<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\ShipmentPhotoStore;
use App\ExternalApi\Support\ShipmentStatusMap;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\ChecksStockAvailability;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ShipmentShortageDocument;
use App\Models\Unit;
use App\Support\SalesOrderApproval;
use App\Support\SalesOrderCancellation;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Modul Shipment untuk sistem eksternal (API Contract v1 — lihat "private docs/Open API/
 * API_Integration_Specification_PMO_IPM_v1.md": /shipments/scheduled, /shipments/shipped,
 * /shipments/{ref}/change-status, /shipments/{ref}/cancel, GET /shipments/{ref_shipment_id}).
 * scheduled(), shipped(), show(), changeStatus(), dan cancel() dibangun di sini — modul Shipment
 * lengkap sesuai API Contract v1.
 *
 * Tabel yang dipakai TETAP sales_orders/sales_order_details — sama seperti menu admin
 * "Pengiriman" (lihat catatan migrasi retail_warehouse_id: "Menu UI = Pengiriman, tabel =
 * sales_orders"). Shipment yang dijadwalkan/dikirim lewat API ini langsung muncul di halaman
 * admin itu juga — bukan tabel terpisah.
 *
 * ipm_status pada respons SELALU lewat App\ExternalApi\Support\ShipmentStatusMap — bukan
 * sales_orders.status apa adanya, kosakatanya beda (lihat docblock kelas itu).
 *
 * gudang_id (2026-08-31, konsisten dengan modul Gudang/Stock Transfer fase 2): scheduled() dan
 * shipped() menerima field opsional gudang_id, sama bentuk & aturannya dengan POST /stock/check
 * dan POST /shipments/returns — merujuk warehouses.id langsung, tidak dikirim -> gudang utama
 * (ProductStock::resolveWarehouseId(null)). Ditambahkan sebagai field OPSIONAL, bukan versi baru
 * — tidak dikirim berarti perilaku persis sama seperti sebelum multi-gudang ada, jadi pemanggil
 * (PMO/IPM) yang belum mengirimkannya tidak perlu berubah apa pun.
 *
 * gudang_id hanya benar-benar memindahkan gudang untuk item satuan ECERAN
 * (product_variants.retail_unit) — App\Support\SalesOrderStock, dipakai bersama halaman admin
 * Pengiriman, sengaja hanya pernah menyimpan stok BULK (satuan lain) di SATU gudang utama.
 * gudang_id non-utama pada item bulk tidak ditolak, tapi diam-diam tidak berpengaruh: cek stok
 * (checkStockAvailability(), lewat App\Support\SalesOrderStock::resolveLineWarehouseId()) MAUPUN
 * pemotongan sungguhan (buildPlan()) sama-sama tetap jatuh ke gudang utama. Ini bukan bug — cek
 * stok DULU (sebelum 2026-08-31) tidak menyadari perbedaan bulk/retail ini sama sekali, sehingga
 * angka yang dicek scheduled() bisa berbeda dari yang benar-benar dipotong shipped(); sudah
 * disamakan. Lihat KNOWN_ISSUES.md untuk detail penemuan & perbaikannya.
 */
class ShipmentController extends Controller
{
    use ChecksStockAvailability;

    /** sales_orders.status, lihat migrasi 2026_08_11_130000_* dan model SalesOrder. */
    private const STATUS_CONFIRMED = 2;

    private const STATUS_SCHEDULED = 4;

    /**
     * sales_orders.status, lihat migrasi 2026_08_12_110000_*. Belum dihasilkan endpoint manapun
     * saat ini — ALLOWED_TRANSITIONS di bawah belum membuka transisi apa pun menuju "Belum
     * terkirim", dipertahankan untuk kalau pemilik produk membukanya nanti.
     */
    private const STATUS_NOT_DELIVERED = 5;

    /** sales_orders.status untuk ipm_status "Sudah Terkirim" — lihat ALLOWED_TRANSITIONS. */
    private const STATUS_DELIVERED = 6;

    /**
     * Transisi ipm_status yang diizinkan changeStatus() saat ini — DIKONFIRMASI pemilik produk
     * 2026-08-13: HANYA Dijadwalkan -> Sudah Terkirim, kombinasi lain (termasuk mundur, maupun
     * target "Berjalan"/"Belum terkirim") ditolak INVALID_STATUS_TRANSITION. Bentuknya
     * ipm_status_saat_ini => daftar ipm_status_tujuan yang boleh, supaya gampang ditambah kalau
     * pemilik produk membuka transisi lain nanti — bukan berarti sengaja disiapkan buat itu
     * sekarang, cuma bentuk yang paling gampang diperluas tanpa nulis ulang guard-nya.
     */
    private const ALLOWED_TRANSITIONS = [
        ShipmentStatusMap::IPM_SCHEDULED => [ShipmentStatusMap::IPM_DELIVERED],
    ];

    /**
     * POST /api/external/v1/shipments/scheduled
     *
     * Menjadwalkan satu shipment: menjalankan cek stok yang SAMA PERSIS dengan
     * POST /stock/check (lewat Concerns\ChecksStockAvailability, dipakai bersama di sini),
     * lalu membuat satu baris sales_orders berstatus "Dijadwalkan" (4) beserta detailnya —
     * SELALU dibuat, baik ada shortage atau tidak (dikonfirmasi pemilik produk: penjadwalan
     * tetap jalan, pemotongan stok sungguhan baru terjadi nanti di /shipments/shipped).
     *
     * Kalau ADA item yang shortage-nya > 0 DAN auto_create_shortage_doc: true, satu dokumen
     * App\Models\ShipmentShortageDocument dibuat sebagai catatan untuk staf gudang/pembelian —
     * bukan syarat, tidak menghalangi shipment tetap dijadwalkan.
     *
     * armada_code merujuk customers.customer_code — sama seperti universal id pada modul Data
     * Armada (lihat MasterArmadaController), BUKAN kolom baru. Diterjemahkan ke
     * sales_orders.so_customer (disimpan sebagai string customer_id, sama seperti alur admin
     * insertSalesOrder()).
     *
     * ref_shipment_id UNIK di sales_orders — permintaan kedua dengan ref_shipment_id yang sama
     * ditolak duplicate_ref_id (dikonfirmasi pemilik produk: BUKAN idempotent replay seperti
     * /payments/cash — pemanggil wajib pakai ref_shipment_id baru per percobaan).
     *
     * gudang_id opsional, bentuk & aturan SAMA PERSIS dengan POST /stock/check (merujuk
     * warehouses.id langsung, bukan kolom rujukan eksternal seperti ref_unit_id/ref_product_id —
     * gudang tidak disinkronkan PMO). Tidak dikirim -> ProductStock::resolveWarehouseId(null):
     * gudang utama (is_main_warehouse = 1). Nilai yang sama disimpan ke sales_order_details.
     * warehouse_id setiap item — satu gudang untuk satu permintaan penjadwalan, tidak per-item
     * seperti POST /shipments/returns.
     *
     * Cek stok (checkStockAvailability()) MENGHORMATI gudang_id apa adanya HANYA untuk item
     * satuan eceran — untuk item satuan bulk, cek stok ikut aturan
     * App\Support\SalesOrderStock::resolveLineWarehouseId() (lihat docblock kelas), sehingga
     * gudang_id non-utama pada item bulk diam-diam dicek terhadap gudang utama, bukan gudang yang
     * diminta — supaya angka yang dicek di sini konsisten dengan yang benar-benar akan dipotong
     * shipped(). Lihat docblock kelas dan KNOWN_ISSUES.md.
     *
     * Tidak ada informasi harga pada kontrak permintaan ini (murni penjadwalan logistik) —
     * sod_harga/sod_subtotal/so_total seluruhnya disimpan 0, bukan mengarang harga.
     */
    public function scheduled(Request $request): JsonResponse
    {
        $data = $request->validate(array_merge([
            'ref_shipment_id' => ['required', 'string', 'max:100'],
            'scheduled_date' => ['required', 'date'],
            'armada_code' => [
                'required', 'string',
                Rule::exists('customers', 'customer_code')->where('status', 1),
            ],
            'gudang_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('status', 1)],
            'auto_create_shortage_doc' => ['nullable', 'boolean'],
        ], $this->stockItemValidationRules()));

        if (SalesOrder::where('ref_shipment_id', $data['ref_shipment_id'])->exists()) {
            return $this->duplicateRefShipmentError($data['ref_shipment_id']);
        }

        $warehouseId = ProductStock::resolveWarehouseId($data['gudang_id'] ?? null);
        $check = $this->checkStockAvailability($warehouseId, $data['items']);

        $customer = Customer::where('customer_code', $data['armada_code'])->where('status', 1)->first();
        $autoCreateShortageDoc = (bool) ($data['auto_create_shortage_doc'] ?? false);

        $productNames = Product::whereIn('product_id', array_values(array_unique(array_filter(
            array_column($check['items'], 'product_id')
        ))))->pluck('product_name', 'product_id');

        try {
            $result = DB::transaction(function () use ($data, $check, $customer, $warehouseId, $autoCreateShortageDoc, $productNames) {
                $so = (new SalesOrder())->insertSalesOrder([
                    'so_customer' => (string) $customer->customer_id,
                    'so_date' => $data['scheduled_date'],
                    'so_total' => 0,
                    'so_img' => json_encode([]),
                ]);
                $so->ref_shipment_id = $data['ref_shipment_id'];
                $so->status = self::STATUS_SCHEDULED;
                $so->save();

                foreach ($check['items'] as $item) {
                    (new SalesOrderDetail())->insertSalesOrderDetail([
                        'so_id' => $so->so_id,
                        'product_variant_id' => $item['product_variant_id'],
                        'product_name' => $productNames->get($item['product_id']) ?? '-',
                        'product_variant_name' => $item['product_variant_name'] ?? '',
                        'product_variant_sku' => $item['sku'],
                        'unit_id' => $item['internal_unit_id'],
                        'warehouse_id' => $warehouseId,
                        'product_variant_price' => 0,
                        'so_qty' => $item['requested'],
                        'so_subtotal' => 0,
                    ]);
                }

                $shortageDocNumber = null;
                if ($autoCreateShortageDoc && $check['has_shortage']) {
                    $shortageItems = array_values(array_map(
                        static fn (array $item) => [
                            'sku' => $item['sku'],
                            'unit_id' => $item['unit_id'],
                            'requested' => $item['requested'],
                            'available' => $item['available'],
                            'shortage' => $item['shortage'],
                        ],
                        array_filter($check['items'], static fn (array $item) => $item['shortage'] > 0),
                    ));

                    $doc = ShipmentShortageDocument::createForShortage(
                        $so->so_id,
                        $data['ref_shipment_id'],
                        $shortageItems,
                        null,
                    );
                    $shortageDocNumber = $doc->doc_number;
                }

                return ['so' => $so, 'shortage_doc_number' => $shortageDocNumber];
            });
        } catch (QueryException $e) {
            // Dua permintaan dengan ref_shipment_id baru yang sama, nyaris bersamaan: keduanya
            // sama-sama tidak menemukan baris di atas, lalu unique index menolak yang kalah cepat.
            if (SalesOrder::where('ref_shipment_id', $data['ref_shipment_id'])->exists()) {
                return $this->duplicateRefShipmentError($data['ref_shipment_id']);
            }

            throw $e;
        }

        $ipm = $this->ipmStatusFields($result['so']);

        return ApiResponse::success(array_merge([
            'shipment_internal_id' => (int) $result['so']->so_id,
            'ref_shipment_id' => (string) $data['ref_shipment_id'],
        ], $ipm, [
            'shortage_doc_created' => $result['shortage_doc_number'] !== null,
            'shortage_doc_number' => $result['shortage_doc_number'],
        ]), [], 201);
    }

    /**
     * POST /api/external/v1/shipments/shipped
     *
     * Idempoten lewat ref_shipment_id (satu-satunya endpoint Shipment yang begitu — beda dengan
     * /shipments/scheduled yang menolak duplikat):
     *   - ref_shipment_id BELUM ada -> buat baris sales_orders + sales_order_details baru
     *     (reuse SalesOrder::insertSalesOrder()/SalesOrderDetail::insertSalesOrderDetail(), sama
     *     seperti scheduled()), lalu langsung dikonfirmasi (lihat di bawah).
     *   - ref_shipment_id SUDAH ada, sales_orders.status belum Confirmed (1 Created / 4
     *     Dijadwalkan) -> baris yang sama diperbarui (bukan bikin baru), lalu dikonfirmasi.
     *     Beda antara data tersimpan vs permintaan ini ditangani lewat detail_handler:
     *       - "force" (bawaan): timpa (armada_code/shipment_date/notes/photos/items).
     *       - "validate": TOLAK dengan galat informatif shipment_detail_mismatch, tidak ada yang
     *         berubah — tidak ada beda sama sekali (data sudah identik) selalu lanjut ke
     *         konfirmasi apa pun nilai detail_handler-nya.
     *   - ref_shipment_id SUDAH ada, sales_orders.status = Confirmed (2, sudah pernah di-"ship"
     *     lewat panggilan sebelumnya) -> idempotent replay MURNI: isi permintaan ini TIDAK
     *     dibandingkan sama sekali (sama seperti /payments/cash), tidak ada stok yang dipotong
     *     dua kali, tidak ada detail yang diubah — status yang sudah tersimpan dikembalikan apa
     *     adanya lewat meta.idempotent_replay.
     *
     * Konfirmasi (potong stok + status -> Confirmed) memakai ulang App\Support\
     * SalesOrderApproval::confirm() — PERSIS logika accSO() yang dipakai halaman admin Pengiriman
     * (buildPlan -> executeDeduct -> set status), diekstrak supaya bisa dipakai di sini juga.
     * Kalau stok tidak cukup, baris sales_orders TETAP ada (status tidak maju ke Confirmed) —
     * sama seperti kalau admin gagal ACC lewat halaman admin, bukan galat 500.
     *
     * variant_sku (BUKAN "sku" seperti /stock/check dan /shipments/scheduled) di-resolve ke
     * product_variant_id lewat Concerns\ChecksStockAvailability::resolveVariantsAndUnits() —
     * resolusinya sama, cuma nama field bodinya beda di endpoint ini. items[].product_name/
     * variant_name TIDAK di-lookup dari database — dipakai apa adanya dari body permintaan untuk
     * sales_order_details.sod_nama/sod_variant, sama seperti field yang sudah diterima
     * SalesOrderDetail::insertSalesOrderDetail() dari form admin.
     *
     * photos menerima file sungguhan lewat multipart/form-data (photos[] sebagai upload) ATAU
     * data URI base64 lewat JSON murni — lihat App\ExternalApi\Support\ShipmentPhotoStore.
     * Disimpan ke sales_orders.so_img (kolom yang sama dipakai halaman admin), folder
     * public/issue/ (sama persis dengan CustomerController::insertSalesOrder()).
     *
     * gudang_id opsional, bentuk & aturan SAMA dengan POST /stock/check dan
     * POST /shipments/scheduled: merujuk warehouses.id langsung, tidak dikirim -> gudang utama
     * (ProductStock::resolveWarehouseId(null)). Satu gudang berlaku untuk SELURUH item permintaan
     * ini (bukan per-item), disimpan apa adanya ke sales_order_details.warehouse_id. Dianggap
     * field substantif sama seperti items — mengirim ulang ref_shipment_id yang sama dengan
     * gudang_id berbeda pada dokumen yang belum Confirmed ditolak shipment_detail_mismatch (lihat
     * diffAgainstExisting()), bukan diam-diam dipindah.
     *
     * Pemotongan stok sesungguhnya (SalesOrderApproval::confirm() -> SalesOrderStock::buildPlan())
     * HANYA menghormati gudang_id untuk item satuan eceran — item satuan bulk selalu dipotong dari
     * gudang utama, tidak peduli gudang_id yang tersimpan di detail. Lihat docblock kelas ini dan
     * KNOWN_ISSUES.md.
     */
    public function shipped(Request $request): JsonResponse
    {
        $data = $this->validateShippedPayload($request);
        $detailHandler = $data['detail_handler'] ?? 'force';
        $warehouseId = ProductStock::resolveWarehouseId($data['gudang_id'] ?? null);

        [$variantsBySku, $unitsByRef] = $this->resolveVariantsAndUnits(
            array_column($data['items'], 'variant_sku'),
            array_column($data['items'], 'unit_id'),
        );

        $resolvedItems = [];
        foreach ($data['items'] as $item) {
            $variant = $variantsBySku->get($item['variant_sku']);
            $unit = $unitsByRef->get((int) $item['unit_id']);

            // Sudah divalidasi ada di validateShippedPayload() — null di sini cuma kemungkinan
            // race condition (baris dinonaktifkan tepat setelah validate()), sangat jarang.
            if ($variant === null || $unit === null) {
                return ApiResponse::error(
                    ErrorCatalog::VALIDATION_FAILED,
                    'items.variant_sku atau items.unit_id tidak lagi valid, coba ulang.',
                    422,
                );
            }

            $resolvedItems[] = [
                'variant_sku' => (string) $item['variant_sku'],
                'unit_id' => (int) $item['unit_id'],
                'internal_unit_id' => (int) $unit->unit_id,
                'qty' => (int) $item['qty'],
                'product_variant_id' => (int) $variant->product_variant_id,
                'product_name' => (string) $item['product_name'],
                'variant_name' => (string) ($item['variant_name'] ?? ''),
            ];
        }

        $customer = Customer::where('customer_code', $data['armada_code'])->where('status', 1)->first();
        $photos = new ShipmentPhotoStore();
        $httpStatus = 200;

        try {
            $so = SalesOrder::where('ref_shipment_id', $data['ref_shipment_id'])->first();

            if ($so !== null && (int) $so->status === self::STATUS_CONFIRMED) {
                return $this->presentShipped($so, ['idempotent_replay' => true]);
            }

            if ($so === null) {
                $so = DB::transaction(fn () => $this->createShippedSo($data, $customer, $resolvedItems, $photos, $warehouseId));
                $httpStatus = 201;
            } else {
                $mismatch = $this->diffAgainstExisting($so, $data, $customer, $resolvedItems, $warehouseId);
                if ($mismatch !== []) {
                    if ($detailHandler === 'validate') {
                        $photos->cleanup();

                        return $this->mismatchError($mismatch);
                    }

                    $so = DB::transaction(fn () => $this->upsertShippedSo($so, $data, $customer, $resolvedItems, $photos, $warehouseId));
                }
            }
        } catch (\InvalidArgumentException $e) {
            $photos->cleanup();

            throw ValidationException::withMessages(['photos' => [$e->getMessage()]]);
        } catch (QueryException $e) {
            $photos->cleanup();

            // Dua permintaan dengan ref_shipment_id baru yang sama, nyaris bersamaan.
            $raced = SalesOrder::where('ref_shipment_id', $data['ref_shipment_id'])->first();
            if ($raced !== null) {
                return $this->presentShipped($raced, ['idempotent_replay' => (int) $raced->status === self::STATUS_CONFIRMED]);
            }

            throw $e;
        } catch (\Throwable $e) {
            $photos->cleanup();

            throw $e;
        }

        $result = SalesOrderApproval::confirm($so->fresh(), null);
        if (! ($result['ok'] ?? false)) {
            return ApiResponse::error(
                ErrorCatalog::INSUFFICIENT_STOCK,
                $result['message'] ?? 'Stok tidak mencukupi.',
                409,
                [
                    'products' => $result['products'] ?? [],
                    'recommendations' => $result['recommendations'] ?? [],
                ],
            );
        }

        return $this->presentShipped($so->fresh(), [], $httpStatus);
    }

    /**
     * GET /api/external/v1/shipments/{ref_shipment_id}
     *
     * Detail satu shipment (baik yang baru dijadwalkan lewat /shipments/scheduled maupun yang
     * sudah dikonfirmasi lewat /shipments/shipped) — ref_shipment_id yang sama sekali tidak
     * pernah dipakai dijawab SHIPMENT_NOT_FOUND (404). Bukan cuma status Confirmed — baris
     * apa pun yang benar-benar ada (berapa pun status internalnya) tetap ditemukan; existensi
     * baris dan status "aktif" adalah dua hal berbeda di sini.
     *
     * armada_code, variant_sku, unit_id (ref_unit_id), product_name, variant_name dikembalikan
     * apa adanya dari yang tersimpan — bentuknya SAMA PERSIS dengan yang diterima
     * POST /shipments/shipped (bukan format /shipments/scheduled).
     *
     * ?show_unit=true menambahkan field "unit": {id, unit_name, unit_short_name} hasil resolusi
     * items[].unit_id (ref_unit_id) di tiap item — satu objek per item (bukan daftar), karena
     * satu item memang cuma punya satu satuan. Satuan diambil sekali per permintaan (dikelompokkan
     * lewat whereIn), bukan sekali per item.
     */
    public function show(Request $request, string $refShipmentId): JsonResponse
    {
        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->first();

        if ($so === null) {
            return ApiResponse::error(
                ErrorCatalog::SHIPMENT_NOT_FOUND,
                ErrorCatalog::message(ErrorCatalog::SHIPMENT_NOT_FOUND, ['ref_shipment_id' => $refShipmentId]),
                404,
            );
        }

        $showUnit = $request->boolean('show_unit');
        $customer = Customer::find((int) $so->so_customer);

        $details = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)
            ->orderBy('sod_id', 'asc')->get();

        $unitIds = $details->pluck('unit_id')->filter()->unique()->values()->all();
        $unitsByInternalId = $unitIds !== []
            ? Unit::whereIn('unit_id', $unitIds)->get(['unit_id', 'ref_unit_id', 'unit_name', 'unit_short_name'])->keyBy('unit_id')
            : collect();

        $photoNames = json_decode($so->so_img ?? '[]', true) ?: [];

        return ApiResponse::success(array_merge([
            'shipment_internal_id' => (int) $so->so_id,
            'ref_shipment_id' => (string) $so->ref_shipment_id,
        ], $this->ipmStatusFields($so), [
            'shipment_date' => (string) $so->so_date,
            'armada_code' => $customer->customer_code ?? null,
            'notes' => $so->notes,
            'photos' => array_map(static fn ($name) => ShipmentPhotoStore::url($name), $photoNames),
            'created_at' => optional($so->created_at)->toIso8601String(),
            'items' => $details->map(function (SalesOrderDetail $detail) use ($unitsByInternalId, $showUnit) {
                $unit = $unitsByInternalId->get((int) $detail->unit_id);

                $item = [
                    'variant_sku' => (string) $detail->sod_sku,
                    'qty' => (int) $detail->sod_qty,
                    'unit_id' => $unit?->ref_unit_id !== null ? (int) $unit->ref_unit_id : null,
                    'product_name' => (string) $detail->sod_nama,
                    'variant_name' => (string) ($detail->sod_variant ?? ''),
                ];

                if ($showUnit) {
                    $item['unit'] = $unit !== null ? [
                        'id' => (int) $unit->unit_id,
                        'unit_name' => (string) $unit->unit_name,
                        'unit_short_name' => (string) $unit->unit_short_name,
                    ] : null;
                }

                return $item;
            })->values()->all(),
        ]));
    }

    /**
     * PATCH /api/external/v1/shipments/{ref_shipment_id}/change-status
     *
     * body.status adalah LABEL (bukan angka), salah satu dari 4 label yang disepakati kontrak
     * (App\ExternalApi\Support\ShipmentStatusMap::validLabels()): "Dijadwalkan", "Berjalan",
     * "Belum terkirim", "Sudah terkirim". Label yang bukan salah satu dari situ dijawab galat
     * INVALID_STATUS (422) berisi daftar label yang sah.
     *
     * HANYA SATU transisi yang diizinkan saat ini (DIKONFIRMASI pemilik produk 2026-08-13, lihat
     * ALLOWED_TRANSITIONS di atas): Dijadwalkan -> Sudah Terkirim. Kombinasi status-saat-ini/
     * label-tujuan lain (termasuk target "Berjalan"/"Belum terkirim", atau mundur dari status
     * mana pun) ditolak INVALID_STATUS_TRANSITION (409) — beda dari INVALID_STATUS: labelnya
     * SAH secara kontrak, cuma transisinya yang belum dibuka untuk status baris saat ini.
     *
     * Transisi Dijadwalkan -> Sudah Terkirim ini MEMOTONG STOK sungguhan — memakai ulang
     * App\Support\SalesOrderApproval::confirm() PERSIS seperti POST /shipments/shipped, cuma
     * target status akhirnya beda (6 "Sudah Terkirim (API)", bukan 2 "Confirmed/Berjalan").
     * Kalau stok tidak cukup, permintaan gagal INSUFFICIENT_STOCK (409) dan baris TETAP di
     * "Dijadwalkan" — sama seperti kegagalan konfirmasi di /shipments/shipped.
     */
    public function changeStatus(Request $request, string $refShipmentId): JsonResponse
    {
        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->first();

        if ($so === null) {
            return ApiResponse::error(
                ErrorCatalog::SHIPMENT_NOT_FOUND,
                ErrorCatalog::message(ErrorCatalog::SHIPMENT_NOT_FOUND, ['ref_shipment_id' => $refShipmentId]),
                404,
            );
        }

        $data = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $targetIpm = ShipmentStatusMap::ipmForLabel($data['status']);
        if ($targetIpm === null) {
            return ApiResponse::error(
                ErrorCatalog::INVALID_STATUS,
                ErrorCatalog::message(ErrorCatalog::INVALID_STATUS, [
                    'valid_statuses' => implode(', ', ShipmentStatusMap::validLabels()),
                ]),
                422,
            );
        }

        $currentIpm = ShipmentStatusMap::fromInternal((int) $so->status);
        $allowedTargets = self::ALLOWED_TRANSITIONS[$currentIpm] ?? [];
        if (! in_array($targetIpm, $allowedTargets, true)) {
            return $this->invalidTransitionError($currentIpm, $targetIpm);
        }

        // Satu-satunya transisi yang bisa lolos guard di atas saat ini: Dijadwalkan -> Sudah
        // Terkirim — memotong stok sungguhan lewat proses yang sama dengan /shipments/shipped.
        $result = SalesOrderApproval::confirm($so, null, self::STATUS_DELIVERED);
        if (! ($result['ok'] ?? false)) {
            return ApiResponse::error(
                ErrorCatalog::INSUFFICIENT_STOCK,
                $result['message'] ?? 'Stok tidak mencukupi.',
                409,
                [
                    'products' => $result['products'] ?? [],
                    'recommendations' => $result['recommendations'] ?? [],
                ],
            );
        }

        $label = ShipmentStatusMap::label($targetIpm);

        return ApiResponse::success([
            'ref_shipment_id' => (string) $so->ref_shipment_id,
            'status' => $label,
            'message' => 'Status Pengiriman dengan referensi '.$so->ref_shipment_id.' menjadi '.$label,
        ]);
    }

    private function invalidTransitionError(?int $currentIpm, int $targetIpm): JsonResponse
    {
        $allowedDescriptions = [];
        foreach (self::ALLOWED_TRANSITIONS as $fromIpm => $toIpmList) {
            foreach ($toIpmList as $toIpm) {
                $allowedDescriptions[] = ShipmentStatusMap::label($fromIpm).' -> '.ShipmentStatusMap::label($toIpm);
            }
        }

        return ApiResponse::error(
            ErrorCatalog::INVALID_STATUS_TRANSITION,
            ErrorCatalog::message(ErrorCatalog::INVALID_STATUS_TRANSITION, [
                'current_status' => $currentIpm !== null ? ShipmentStatusMap::label($currentIpm) : 'Tidak diketahui',
                'target_status' => ShipmentStatusMap::label($targetIpm),
                'allowed_transitions' => implode(', ', $allowedDescriptions),
            ]),
            409,
        );
    }

    /**
     * PUT /api/external/v1/shipments/{ref_shipment_id}/cancel
     *
     * Batalkan (pembatalan) satu shipment — SELALU menjalankan proses pembatalan yang sama
     * seperti alur normal, bukan sekadar menulis status seperti changeStatus(): kalau stok
     * shipment ini sebelumnya sudah dipotong (ipm_status "Berjalan" lewat /shipments/shipped,
     * ATAU "Sudah Terkirim" lewat transisi change-status Dijadwalkan -> Sudah Terkirim yang MEMANG
     * memotong stok — DIKONFIRMASI pemilik produk 2026-08-13), stoknya DIKEMBALIKAN lebih dulu.
     * Dari "Dijadwalkan" (stok belum pernah dipotong), cancel cuma mengubah status. Lihat
     * App\Support\SalesOrderCancellation::cancel() (BARU, diekstrak jadi reusable karena TIDAK
     * ADA alur admin yang setara untuk dicontek: CustomerController::declineSO()/
     * deleteSalesOrder() cuma berlaku sebelum Confirmed, jadi tidak pernah perlu mengembalikan
     * stok).
     *
     * IDEMPOTEN: shipment yang statusnya SUDAH Dibatalkan dijawab sukses apa adanya, TIDAK
     * mengembalikan stok lagi (mencegah stok gudang tergelembung kalau permintaan yang sama
     * dikirim ulang) — meta.already_cancelled menandainya.
     *
     * ipm_status -1 "Dibatalkan" HANYA bisa dihasilkan lewat endpoint ini — beda dengan
     * changeStatus() yang cuma menerima 4 label lain (Dijadwalkan/Berjalan/Belum terkirim/Sudah
     * terkirim), "Dibatalkan" sengaja TIDAK ada di ShipmentStatusMap::validLabels().
     */
    public function cancel(Request $request, string $refShipmentId): JsonResponse
    {
        $so = SalesOrder::where('ref_shipment_id', $refShipmentId)->first();

        if ($so === null) {
            return ApiResponse::error(
                ErrorCatalog::SHIPMENT_NOT_FOUND,
                ErrorCatalog::message(ErrorCatalog::SHIPMENT_NOT_FOUND, ['ref_shipment_id' => $refShipmentId]),
                404,
            );
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = SalesOrderCancellation::cancel($so, $data['reason'] ?? null);

        if (! ($result['ok'] ?? false)) {
            return ApiResponse::error(
                ErrorCatalog::SERVER_ERROR,
                $result['message'] ?? 'Gagal membatalkan shipment.',
                500,
            );
        }

        $message = ($result['stock_restored'] ?? false)
            ? 'Dokumen berhasil dibatalkan dan stok telah dikembalikan.'
            : 'Dokumen berhasil dibatalkan.';

        return ApiResponse::success(array_merge([
            'ref_shipment_id' => (string) $so->ref_shipment_id,
            'shipment_internal_id' => (int) $so->so_id,
        ], $this->ipmStatusFields($so->fresh()), [
            'message' => $message,
        ]), ['already_cancelled' => $result['already_cancelled'] ?? false]);
    }

    /* ------------------------------------------------------------------ */
    /* shipped() — validasi & penyimpanan                                 */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validateShippedPayload(Request $request): array
    {
        // photos[] bisa berupa berkas sungguhan (multipart/form-data) atau data URI base64
        // (JSON murni) — lihat docblock ShipmentPhotoStore. Aturan validasinya beda per bentuk,
        // jadi ditentukan dulu sebelum validate() dipanggil.
        $photoRules = $request->hasFile('photos')
            ? ['file', 'mimes:png,jpg,jpeg', 'max:5120']
            : ['string'];

        return $request->validate([
            'ref_shipment_id' => ['required', 'string', 'max:100'],
            'shipment_date' => ['required', 'date'],
            'armada_code' => [
                'required', 'string',
                Rule::exists('customers', 'customer_code')->where('status', 1),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'gudang_id' => ['nullable', 'integer', Rule::exists('warehouses', 'id')->where('status', 1)],
            'detail_handler' => ['nullable', 'string', Rule::in(['force', 'validate'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_sku' => [
                'required', 'string',
                Rule::exists('product_variants', 'product_variant_sku')->where('status', 1),
            ],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_id' => [
                'required', 'integer',
                Rule::exists('units', 'ref_unit_id')->where('status', 1),
            ],
            'items.*.product_name' => ['required', 'string', 'max:250'],
            'items.*.variant_name' => ['nullable', 'string', 'max:250'],
            'photos' => ['nullable', 'array'],
            'photos.*' => $photoRules,
        ]);
    }

    private function createShippedSo(array $data, Customer $customer, array $resolvedItems, ShipmentPhotoStore $photos, int $warehouseId): SalesOrder
    {
        $photoNames = ! empty($data['photos']) ? $photos->store($data['photos']) : [];

        $so = (new SalesOrder())->insertSalesOrder([
            'so_customer' => (string) $customer->customer_id,
            'so_date' => $data['shipment_date'],
            'so_total' => 0,
            'so_img' => json_encode($photoNames),
        ]);
        $so->ref_shipment_id = $data['ref_shipment_id'];
        $so->notes = $data['notes'] ?? null;
        $so->status = self::STATUS_SCHEDULED;
        $so->save();

        $this->replaceDetails($so, $resolvedItems, $warehouseId);

        return $so;
    }

    private function upsertShippedSo(SalesOrder $so, array $data, Customer $customer, array $resolvedItems, ShipmentPhotoStore $photos, int $warehouseId): SalesOrder
    {
        if (array_key_exists('photos', $data) && $data['photos'] !== null) {
            $so->so_img = json_encode($photos->store($data['photos']));
        }

        $so->so_customer = (string) $customer->customer_id;
        $so->so_date = $data['shipment_date'];
        $so->notes = $data['notes'] ?? null;
        $so->save();

        $this->replaceDetails($so, $resolvedItems, $warehouseId);

        return $so->fresh();
    }

    /**
     * Ganti seluruh sales_order_details milik $so dengan $resolvedItems — baris lama yang tidak
     * ada lagi di daftar baru dinonaktifkan (status = 0), sama persis pola
     * CustomerController::updateSalesOrder(). $warehouseId sudah diresolusi pemanggil (gudang_id
     * permintaan, atau gudang utama bila tidak dikirim — lihat docblock shipped()) dan berlaku
     * untuk SELURUH baris, sama seperti scheduled().
     *
     * @param  array<int, array<string, mixed>>  $resolvedItems
     */
    private function replaceDetails(SalesOrder $so, array $resolvedItems, int $warehouseId): void
    {
        $keptIds = [];

        foreach ($resolvedItems as $item) {
            $keptIds[] = (new SalesOrderDetail())->insertSalesOrderDetail([
                'so_id' => $so->so_id,
                'product_variant_id' => $item['product_variant_id'],
                'product_name' => $item['product_name'],
                'product_variant_name' => $item['variant_name'],
                'product_variant_sku' => $item['variant_sku'],
                'unit_id' => $item['internal_unit_id'],
                'warehouse_id' => $warehouseId,
                'product_variant_price' => 0,
                'so_qty' => $item['qty'],
                'so_subtotal' => 0,
            ]);
        }

        SalesOrderDetail::where('so_id', $so->so_id)->whereNotIn('sod_id', $keptIds)->update(['status' => 0]);
    }

    /**
     * Bandingkan data tersimpan vs permintaan ini — dipanggil hanya saat SO sudah ada dan BELUM
     * Confirmed (lihat shipped()). Hanya field substantif yang dibandingkan (armada_code/
     * shipment_date/notes/items/gudang_id — identitas + qty + unit_id + warehouse_id);
     * product_name/variant_name (label dekoratif) TIDAK dibandingkan, supaya perbedaan penulisan
     * nama produk saja tidak memicu shipment_detail_mismatch. photos juga tidak dibandingkan
     * (tidak ada cara membandingkan berkas baru vs nama berkas tersimpan secara berarti) — force
     * selalu menimpanya kalau dikirim, validate mengabaikannya sama sekali.
     *
     * gudang_id diikutkan sejak fase 2 (multi-gudang): warehouse_id menentukan stok mana yang
     * akhirnya dipotong saat dikonfirmasi, jadi permintaan kedua yang diam-diam pindah gudang
     * (tanpa mengubah item apa pun) HARUS tetap terlihat sebagai mismatch, bukan lolos begitu
     * saja karena hanya items yang dibandingkan.
     *
     * @param  array<int, array<string, mixed>>  $resolvedItems
     * @return array<int, string> nama field yang berbeda, kosong kalau sama persis
     */
    private function diffAgainstExisting(SalesOrder $so, array $data, Customer $customer, array $resolvedItems, int $warehouseId): array
    {
        $diffs = [];

        if ((string) $so->so_customer !== (string) $customer->customer_id) {
            $diffs[] = 'armada_code';
        }
        if ((string) $so->so_date !== (string) $data['shipment_date']) {
            $diffs[] = 'shipment_date';
        }
        if ((string) ($so->notes ?? '') !== (string) ($data['notes'] ?? '')) {
            $diffs[] = 'notes';
        }

        $existingItems = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)
            ->get(['product_variant_id', 'unit_id', 'sod_qty'])
            ->map(static fn ($row) => ((int) $row->product_variant_id).':'.((int) $row->unit_id).':'.((int) $row->sod_qty))
            ->sort()->values()->all();

        $incomingItems = collect($resolvedItems)
            ->map(static fn (array $item) => $item['product_variant_id'].':'.$item['internal_unit_id'].':'.$item['qty'])
            ->sort()->values()->all();

        if ($existingItems !== $incomingItems) {
            $diffs[] = 'items';
        }

        // Semua baris satu SO berbagi satu warehouse_id (lihat replaceDetails()) — baris lama
        // dengan status=0 sengaja tidak ikut dibandingkan, sudah bukan bagian dari dokumen ini.
        $existingWarehouseIds = SalesOrderDetail::where('so_id', $so->so_id)->where('status', 1)
            ->distinct()->pluck('warehouse_id')->map(fn ($id) => (int) $id)->values()->all();

        if ($existingWarehouseIds !== [] && $existingWarehouseIds !== [$warehouseId]) {
            $diffs[] = 'gudang_id';
        }

        return $diffs;
    }

    private function mismatchError(array $mismatchedFields): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::SHIPMENT_DETAIL_MISMATCH,
            'Data shipment untuk ref_shipment_id ini sudah tersimpan dan berbeda dari permintaan ini ('
                .implode(', ', $mismatchedFields).'). Kirim detail_handler: "force" untuk menimpa, atau '
                .'samakan data permintaan dengan yang sudah tersimpan.',
            409,
            ['mismatched_fields' => $mismatchedFields],
        );
    }

    /* ------------------------------------------------------------------ */
    /* Respons                                                             */
    /* ------------------------------------------------------------------ */

    private function presentShipped(SalesOrder $so, array $meta = [], int $httpStatus = 200): JsonResponse
    {
        return ApiResponse::success(array_merge([
            'ref_shipment_id' => (string) $so->ref_shipment_id,
            'shipment_internal_id' => (int) $so->so_id,
        ], $this->ipmStatusFields($so)), $meta, $httpStatus);
    }

    /**
     * @return array{ipm_status: ?int, ipm_status_label: ?string}
     */
    private function ipmStatusFields(SalesOrder $so): array
    {
        $ipmStatus = ShipmentStatusMap::fromInternal((int) $so->status);

        return [
            'ipm_status' => $ipmStatus,
            'ipm_status_label' => $ipmStatus !== null ? ShipmentStatusMap::label($ipmStatus) : null,
        ];
    }

    private function duplicateRefShipmentError(string $refShipmentId): JsonResponse
    {
        return ApiResponse::error(
            ErrorCatalog::DUPLICATE_REF_ID,
            'ref_shipment_id '.$refShipmentId.' sudah dipakai shipment lain.',
            422,
        );
    }
}
