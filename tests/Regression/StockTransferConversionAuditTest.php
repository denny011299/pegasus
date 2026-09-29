<?php

namespace Tests\Regression;

use App\Http\Controllers\StockTransferController;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\Warehouse;
use App\Support\ProductUnitStock;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/** Audit reproductions: assertions record current bugs, not desired behavior. */
class StockTransferConversionAuditTest extends TestCase
{
    use ActingAsStaff;

    private function auditFixture(bool $withPieceRow = true): array
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(1);
        ProductUnitStock::clearCache();
        $category = (new Category())->forceFill(['category_name' => 'ST Audit', 'status' => 1]);
        $category->save();
        $product = (new Product())->forceFill([
            'product_name' => 'ST Audit', 'category_id' => $category->category_id,
            'product_unit' => json_encode([9, 7]), 'unit_id' => 9, 'status' => 1,
        ]);
        $product->save();
        $variant = (new ProductVariant())->forceFill([
            'product_id' => $product->product_id, 'product_variant_name' => 'ST Audit',
            'product_variant_sku' => 'AUDIT-' . uniqid(), 'product_variant_price' => 0,
            'retail_unit' => 9, 'status' => 1,
        ]);
        $variant->save();
        (new ProductRelation())->forceFill([
            'product_variant_id' => $variant->product_variant_id,
            'pr_unit_id_1' => 7, 'pr_unit_value_1' => 1,
            'pr_unit_id_2' => 9, 'pr_unit_value_2' => 12, 'pr_default' => 0, 'status' => 1,
        ])->save();
        $wh = (new Warehouse())->forceFill([
            'warehouse_name' => 'ST Audit ' . uniqid(),
            'warehouse_type_id' => Warehouse::findOrFail(1)->warehouse_type_id, 'status' => 1,
        ]);
        $wh->save();
        $stock = function (int $unitId, int $qty) use ($product, $variant): ProductStock {
            $row = (new ProductStock())->forceFill([
                'product_id' => $product->product_id, 'product_variant_id' => $variant->product_variant_id,
                'warehouse_id' => 1, 'unit_id' => $unitId, 'ps_stock' => $qty, 'status' => 1,
            ]);
            $row->save();
            return $row;
        };
        $dos = $stock(7, 1);
        $piece = $withPieceRow
            ? $stock(9, 0)
            : null;
        $header = (new StockTransfer())->forceFill([
            'transfer_code' => 'AUDIT-' . uniqid(), 'transfer_date' => now()->toDateString(),
            'sender_id' => session('user')->staff_id, 'from_warehouse_id' => 1,
            'to_warehouse_id' => $wh->id, 'status' => 1,
        ]);
        $header->save();
        StockTransferDetail::create([
            'st_id' => $header->st_id, 'product_id' => $product->product_id,
            'product_variant_id' => $variant->product_variant_id, 'unit_id' => 9, 'qty' => 2, 'status' => 1,
        ]);
        $fx = compact('product', 'variant');

        return [$fx, $dos, $piece, $header];
    }

    private function controller(): StockTransferController
    {
        return new class extends StockTransferController {
            public function shipForAudit(StockTransfer $header): void
            {
                $this->shipLockedTransfer($header, (int) session('user')->staff_id);
            }

            public function restoreForAudit(StockTransfer $header): void
            {
                $this->restoreSourceStock($header, 'audit');
            }

            public function normalizeForAudit(array $items): array
            {
                return $this->normalizeItems($items);
            }
        };
    }

    public function test_audit_available_ancestor_cannot_ship_without_target_stock_row(): void
    {
        [$fx, $dos, , $header] = $this->auditFixture(false);
        $check = ProductUnitStock::checkItems(1, [[
            'product_variant_id' => $fx['variant']->product_variant_id,
            'unit_id' => 9, 'qty' => 2, 'allow_unpack' => true,
        ]]);
        $this->assertTrue($check['ok']);
        try {
            DB::transaction(fn () => $this->controller()->shipForAudit($header));
            $this->fail('Bug changed: shipping now succeeds without a Piece row.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Satuan stok tidak ditemukan di gudang', $e->getMessage());
        }
        $this->assertSame(1, (int) $header->fresh()->status);
        $this->assertSame(1.0, (float) $dos->fresh()->ps_stock);
    }

    public function test_audit_cancel_fails_after_unpacked_remainder_is_consumed(): void
    {
        [$fx, $dos, $piece, $header] = $this->auditFixture();
        DB::transaction(fn () => $this->controller()->shipForAudit($header));
        $this->assertSame(10.0, (float) $piece->fresh()->ps_stock);
        $cut = ProductUnitStock::deductQty(1, $fx['variant']->product_variant_id, 9, 10, 'AUDIT-OTHER', 'Other transaction', false, false);
        $this->assertTrue($cut['ok']);
        try {
            DB::transaction(fn () => $this->controller()->restoreForAudit($header));
            $this->fail('Bug changed: cancellation now restores the shipped equivalent.');
        } catch (\RuntimeException $e) {
            $this->assertSame('Stok satuan kirim tidak mencukupi', $e->getMessage());
        }
        $this->assertSame(2, (int) $header->fresh()->status);
        $this->assertSame(0.0, (float) $dos->fresh()->ps_stock);
        $this->assertSame(0.0, (float) $piece->fresh()->ps_stock);
    }

    public function test_audit_normalization_silently_discards_invalid_detail(): void
    {
        $items = $this->controller()->normalizeForAudit([
            ['product_variant_id' => 1, 'unit_id' => 9, 'qty' => 3, 'label' => 'Valid'],
            ['product_variant_id' => 2, 'unit_id' => 0, 'qty' => 4, 'label' => 'Missing unit'],
        ]);
        // Desired behavior: reject the entire payload, instead of saving only one item.
        $this->assertCount(1, $items);
        $this->assertSame(1, $items[0]['product_variant_id']);
    }
}
