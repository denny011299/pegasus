<?php

namespace App\ExternalApi\Support;

use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\Unit;
use App\Support\CustomerReturnCreation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Pemetaan items[] retur External API (type/ref_id/qty/satuan_id/gudang_id/ref_nota_id) ke baris
 * siap-simpan App\Support\CustomerReturnCreation. Dipakai POST /shipments/returns dan potongan
 * barang di POST /payments/cash, supaya kedua endpoint memakai aturan resolusi yang sama persis.
 * Aturan bisnis lengkap (gudang per baris, penggabungan, idempotensi) ada di docblock
 * App\Http\Controllers\ExternalApi\V1\ShipmentReturnController.
 */
class ReturnItemResolver
{
    /**
     * Key idempotensi GitHub #203 — hanya dihitung ketika ref_shipment_id dikirim (lihat docblock
     * ShipmentReturnController). Dibangun dari ref_shipment_id + return_date + isi items[] SETELAH digabung
     * (supplyDetails/productDetails, sudah termasuk ref_nota_id di kuncinya lewat resolveItems())
     * supaya urutan baris pada payload atau penggabungan qty tidak mengubah key untuk payload yang
     * "sama" secara isi. gudang_id/satuan_id TIDAK ikut mempengaruhi key -- unit_id/warehouse_id
     * hasil resolusi sudah cukup mewakili baris yang sama, tidak perlu membawa representasi mentah
     * dari body permintaan.
     *
     * @param  array<int, array<string, mixed>>  $supplyDetails
     * @param  array<int, array<string, mixed>>  $productDetails
     */
    public function idempotencyKey(string $refShipmentId, string $returnDate, array $supplyDetails, array $productDetails): string
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

    /**
     * Petakan items[] (type/ref_id/satuan_id/gudang_id) ke baris siap-simpan
     * App\Support\CustomerReturnCreation::replaceSupplyDetails()/replaceProductDetails(),
     * TERMASUK menentukan warehouse_id per baris (lihat aturan lengkap di docblock ShipmentReturnController).
     * Baris dengan supplies_id/unit_id (atau product_variant_id/unit_id) yang sama digabung, qty
     * dijumlah — sama pola dengan CustomerReturnController::parseSupplyDetails()/
     * parseProductDetails(). Kalau baris yang sama muncul lebih dari sekali dengan gudang_id
     * berbeda-beda, yang dipakai adalah gudang_id dari kemunculan PERTAMA — bukan error, karena
     * kasus ini di luar cakupan kontrak WhatsApp issue #58 dan tidak ada alasan bisnis untuk
     * menolaknya keras.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     *
     * $pathPrefix menentukan awalan path galat per baris: 'items' untuk POST /shipments/returns,
     * 'items.%d.goods' (dengan indeks item asli) untuk potongan barang di POST /payments/cash.
     */
    public function resolveItems(array $items, string $pathPrefix = 'items'): array
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
        // SKU case-insensitive (sama pola ChecksStockAvailability): MySQL collation
        // sering CI, tapi Collection::keyBy di PHP case-sensitive — "Aahk1500ml" vs
        // "AAHK1500ML" lolos query lalu gagal di get() (log #96).
        $variantsBySku = $skus === []
            ? collect()
            : ProductVariant::whereIn('product_variant_sku', $skus)->where('status', 1)
                ->get($variantCols)
                ->keyBy(static fn ($v) => mb_strtoupper(trim((string) $v->product_variant_sku)));

        $supplyDetails = [];
        $productDetails = [];

        foreach ($items as $index => $item) {
            $type = (int) $item['type'];
            $qty = (int) $item['qty'];
            $unit = $unitsByRef->get((int) $item['satuan_id']);
            if ($unit === null) {
                // Sudah divalidasi ada di validatePayload() — null di sini cuma race condition.
                throw ValidationException::withMessages([$this->path($pathPrefix, $index, 'satuan_id') => 'Satuan tidak lagi valid, coba ulang.']);
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
                        $this->path($pathPrefix, $index, 'ref_id') => 'Bahan dengan ref_supplies_id '.$refSuppliesId.' tidak ditemukan atau tidak aktif. Daftarkan lewat POST /bahan atau PATCH /bahan/connect terlebih dahulu.',
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
                $variant = $variantsBySku->get(mb_strtoupper(trim($sku)));
                if ($variant === null) {
                    throw ValidationException::withMessages([
                        $this->path($pathPrefix, $index, 'ref_id') => 'Produk dengan SKU "'.$sku.'" tidak ditemukan atau tidak aktif.',
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

        $this->resolveWarehouses($supplyDetails);
        $this->resolveWarehouses($productDetails);

        return [array_values($supplyDetails), array_values($productDetails)];
    }

    /**
     * 2026-10-12: retur dari PMO TIDAK lagi auto-default ke gudang utama (bahan maupun produk) —
     * warehouse_id NULL kecuali items[].gudang_id dikirim. Staf wajib memilih gudang di modal
     * Edit/Konfirmasi Pengembalian; accept() menolak selama masih ada baris tanpa gudang.
     *
     * @param  array<string, array<string, mixed>>  $details  diubah in-place (by reference).
     */
    private function resolveWarehouses(array &$details): void
    {
        foreach ($details as &$detail) {
            $detail['warehouse_id'] = $detail['_gudang_id'] ?? null;
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
    public function assertAgainstCatalog(array $supplyDetails, array $productDetails): void
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

    private function path(string $pathPrefix, int|string $index, string $field): string
    {
        return str_contains($pathPrefix, '%d')
            ? sprintf($pathPrefix, $index).'.'.$field
            : $pathPrefix.'.'.$index.'.'.$field;
    }
}
