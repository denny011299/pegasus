<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Added 2026-08-25.
 *
 * The staff's physical count is frozen into `stod_real` when the opname is created, but
 * `accStockOpname()` overwrites live stock with that figure verbatim at approval time. So any stock
 * movement BETWEEN the count and the approval (goods received, SO shipped, production finished) is
 * silently destroyed — stock snaps back to a count that no longer describes reality.
 *
 * The GitHub #53 live-refresh made this *visible* (a pending doc's selisih grows when stock moves)
 * but did nothing to prevent it. This guard makes it an explicit decision: `accStockOpname()` now
 * returns `{status: -3}` naming the affected products, and only proceeds once the caller resends
 * with `confirm_stale=1` — the same confirm-then-retry pattern `accProduction()` already uses for
 * provisioning new stock rows.
 *
 * Detection deliberately uses `log_stocks` rather than comparing `stod_system` against live stock:
 * `stod_system` is re-synced every time the PDF is downloaded (see `refreshLiveSystemQty()`), so a
 * stod_system-based check would "heal" itself just because someone opened the document, even though
 * the physical count is still stale. The reference timestamp is the `stock_opnames` HEADER's
 * `updated_at`, which the PDF refresh does not touch (it only saves detail rows).
 */
class StockOpnameStaleCountGuardTest extends TestCase
{
    use ActingAsStaff;

    /** @return array{0: ProductStock, 1: ProductVariant, 2: Category} */
    private function createFixture(): array
    {
        $unit = Unit::where('status', 1)->firstOrFail();

        $category = new Category();
        $category->category_name = 'Stale Opname Guard Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Stale Opname Guard Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Stale Opname Guard Variant';
        $variant->product_variant_sku = 'WF-STALE-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = $unit->unit_id;
        $stock->warehouse_id = 1;
        $stock->ps_stock = 100;
        $stock->status = 1;
        $stock->save();

        return [$stock, $variant, $category];
    }

    private function createOpname(ProductStock $stock, ProductVariant $variant, Category $category, int $realQty): int
    {
        $unitName = (string) (Unit::find($stock->unit_id)->unit_short_name ?? 'pcs');

        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => (int) DB::table('staffs')->where('status', 1)->value('staff_id'),
            'category_id' => $category->category_id,
            'sto_notes' => 'Stale guard test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $stock->product_id,
                'product_variant_id' => $variant->product_variant_id,
                'stod_system' => $stock->ps_stock.' '.$unitName,
                'stod_real' => $realQty.' '.$unitName,
                'stod_selisih' => ($realQty - $stock->ps_stock).' '.$unitName,
                'stod_notes' => null,
                'stod_touched' => 1,
            ]]),
        ]);
        $response->assertStatus(200);

        return (int) $response->json('sto_id');
    }

    private function approve(int $stoId, ProductStock $stock, int $realQty, bool $confirmStale = false)
    {
        $payload = [
            'sto_id' => $stoId,
            'item' => json_encode([[
                'product_variant_id' => $stock->product_variant_id,
                'units' => [['unit_id' => $stock->unit_id, 'real_qty' => $realQty]],
            ]]),
        ];
        if ($confirmStale) {
            $payload['confirm_stale'] = 1;
        }

        return $this->post('/accStockOpname', $payload);
    }

    public function test_approving_is_blocked_for_confirmation_when_stock_moved_after_the_count(): void
    {
        $this->actingAsSuperAdminStaff();
        [$stock, $variant, $category] = $this->createFixture();

        $stoId = $this->createOpname($stock, $variant, $category, realQty: 95);

        // Some other event moves this product's stock AFTER the count was submitted.
        sleep(1); // ensure log_date lands strictly after the header's updated_at
        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode' => 'TEST-MOVE',
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'log_notes' => 'Barang masuk setelah opname dihitung',
            'log_jumlah' => 20,
            'unit_id' => $stock->unit_id,
        ]);
        $stock->ps_stock = 120;
        $stock->save();

        $response = $this->approve($stoId, $stock, 95);
        $response->assertStatus(200);

        $this->assertSame(-3, $response->json('status'), 'approval must ask for confirmation, not proceed silently');
        $this->assertStringContainsString(
            'Stale Opname Guard Product',
            $response->json('message'),
            'the affected product must be named so the approver knows what changed'
        );

        // Nothing may have been mutated by the refused attempt.
        $stock->refresh();
        $this->assertSame(120, $stock->ps_stock, 'the intervening movement must still be intact');
        $this->assertSame(1, (int) StockOpname::find($stoId)->status, 'the opname stays pending');
    }

    public function test_resending_with_confirmation_goes_through_and_overwrites_as_before(): void
    {
        $this->actingAsSuperAdminStaff();
        [$stock, $variant, $category] = $this->createFixture();

        $stoId = $this->createOpname($stock, $variant, $category, realQty: 95);

        sleep(1);
        (new LogStock())->insertLog([
            'log_date' => now(),
            'log_kode' => 'TEST-MOVE',
            'log_type' => 1,
            'log_category' => 1,
            'log_item_id' => $variant->product_variant_id,
            'log_notes' => 'Barang masuk setelah opname dihitung',
            'log_jumlah' => 20,
            'unit_id' => $stock->unit_id,
        ]);
        $stock->ps_stock = 120;
        $stock->save();

        $this->assertSame(-3, $this->approve($stoId, $stock, 95)->json('status'));

        // Deliberate override — the approver accepted that the movement will be discarded.
        $confirmed = $this->approve($stoId, $stock, 95, confirmStale: true);
        $confirmed->assertStatus(200);

        $stock->refresh();
        $this->assertSame(95, $stock->ps_stock, 'confirming still overwrites with the counted figure');
        $this->assertSame(2, (int) StockOpname::find($stoId)->status, 'the opname is approved');
    }

    public function test_an_opname_with_no_intervening_movement_approves_without_any_extra_prompt(): void
    {
        $this->actingAsSuperAdminStaff();
        [$stock, $variant, $category] = $this->createFixture();

        $stoId = $this->createOpname($stock, $variant, $category, realQty: 95);

        // No stock movement at all between count and approval — the common case must be unchanged.
        $response = $this->approve($stoId, $stock, 95);
        $response->assertStatus(200);
        $this->assertSame('1', trim($response->getContent()), 'a fresh opname approves in one step as before');

        $stock->refresh();
        $this->assertSame(95, $stock->ps_stock);
        $this->assertSame(2, (int) StockOpname::find($stoId)->status);
    }
}
