<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ExternalApi\V1\Concerns\ChecksStockAvailability;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ShipmentShortageDocument;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Modul Shipment untuk sistem eksternal (API Contract v1 — lihat "private docs/Open API/
 * API_Integration_Specification_PMO_IPM_v1.md": /shipments/scheduled, /shipments/shipped,
 * /shipments/status, /shipments/cancel, GET /shipments/{ref_shipment_id}). Baru
 * /shipments/scheduled yang dibangun di sini — endpoint lain menyusul terpisah.
 *
 * Tabel yang dipakai TETAP sales_orders/sales_order_details — sama seperti menu admin
 * "Pengiriman" (lihat catatan migrasi retail_warehouse_id: "Menu UI = Pengiriman, tabel =
 * sales_orders"). Shipment yang dijadwalkan lewat API ini akan langsung muncul di halaman admin
 * itu juga, berstatus "Dijadwalkan" (status = 4, lihat migrasi
 * 2026_08_11_130000_add_ref_shipment_id_and_scheduled_status_to_sales_orders) — bukan tabel
 * terpisah.
 */
class ShipmentController extends Controller
{
    use ChecksStockAvailability;

    /** sales_orders.status = 4, lihat migrasi 2026_08_11_130000_*. */
    private const STATUS_SCHEDULED = 4;

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
     * Endpoint ini tidak menerima gudang_id — cek stok maupun sales_order_details.warehouse_id
     * selalu memakai gudang utama lewat ProductStock::resolveWarehouseId(null), sama seperti
     * default POST /stock/check ketika gudang_id tidak dikirim.
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
            'auto_create_shortage_doc' => ['nullable', 'boolean'],
        ], $this->stockItemValidationRules()));

        if (SalesOrder::where('ref_shipment_id', $data['ref_shipment_id'])->exists()) {
            return $this->duplicateRefShipmentError($data['ref_shipment_id']);
        }

        $warehouseId = ProductStock::resolveWarehouseId(null);
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

        return ApiResponse::success([
            'shipment_internal_id' => (int) $result['so']->so_id,
            'ref_shipment_id' => (string) $data['ref_shipment_id'],
            'status' => 'scheduled',
            'shortage_doc_created' => $result['shortage_doc_number'] !== null,
            'shortage_doc_number' => $result['shortage_doc_number'],
        ], [], 201);
    }

    private function duplicateRefShipmentError(string $refShipmentId): JsonResponse
    {
        return ApiResponse::error(
            'duplicate_ref_id',
            'ref_shipment_id '.$refShipmentId.' sudah dipakai shipment lain.',
            422,
        );
    }
}
