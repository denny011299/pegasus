<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpnameDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #78: reported live by an admin — created a Stock Opname for a product with two units
 * (DOS/pcs), filled in only DOS, left pcs blank (never physically counted). After creating and
 * printing, pcs suddenly showed a fabricated ~27-unit selisih.
 *
 * Root cause: a blank real-stock input didn't mean "not counted" anywhere in the stack — JS
 * defaulted real_qty = system_qty AT THAT INSTANT and saved it as if it were a genuine physical
 * count ("touched" was tracked per product ROW, not per unit). Any ordinary stock movement
 * (sale/shipment/production) on that untouched unit between creation and PDF print/approval then
 * read as a phantom selisih (refreshLiveSystemQty()), and approving the document would write that
 * stale frozen value straight into ps_stock, inflating real stock.
 *
 * PM decision (relayed by the user, 2026-08-26): the old "blank input defaults to system value"
 * fallback is NOT a must-have behaviour — remove it rather than trying to track it more precisely.
 *
 * Fix: an untouched unit is now represented by a literal "-" token in stod_real/stod_selisih
 * (StockController::getQty()/buildQtyString() treat "-" <=> null, never 0) instead of a fabricated
 * number. refreshLiveSystemQty() leaves an untouched unit's selisih "-" forever (never compares it
 * against a moving live target). accStockOpname()/accStockOpnameBahan() skip writing ps_stock/
 * ss_stock entirely for a unit whose real_qty is null — an uncounted unit's live stock is never
 * touched by approval, no matter how much it moved in between.
 */
class StockOpnameUntouchedUnitBugTest extends TestCase
{
    use ActingAsStaff;

    /** @return array{0: ProductVariant, 1: ProductStock, 2: ProductStock} [variant, dosStock, pcsStock] */
    private function pickFixture(): array
    {
        $units = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $units->count(), 'fixture needs at least 2 active units');
        [$dosUnit, $pcsUnit] = $units->all();

        $category = new Category();
        $category->category_name = 'Opname Untouched Unit Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Opname Untouched Unit Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$dosUnit->unit_id, $pcsUnit->unit_id]);
        $product->unit_id = $dosUnit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Opname Untouched Unit Test Variant';
        $variant->product_variant_sku = 'WF-OPNAME-UNTOUCHED-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $dosStock = new ProductStock();
        $dosStock->product_id = $product->product_id;
        $dosStock->product_variant_id = $variant->product_variant_id;
        $dosStock->unit_id = $dosUnit->unit_id;
        $dosStock->warehouse_id = 1;
        $dosStock->ps_stock = 12;
        $dosStock->status = 1;
        $dosStock->save();

        $pcsStock = new ProductStock();
        $pcsStock->product_id = $product->product_id;
        $pcsStock->product_variant_id = $variant->product_variant_id;
        $pcsStock->unit_id = $pcsUnit->unit_id;
        $pcsStock->warehouse_id = 1;
        $pcsStock->ps_stock = 42;
        $pcsStock->status = 1;
        $pcsStock->save();

        return [$variant, $dosStock, $pcsStock];
    }

    private function categoryId(): int
    {
        return (int) DB::table('categories')->where('status', 1)->value('category_id');
    }

    private function staffId(): int
    {
        return (int) DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    private function parseToken(?string $string, string $unit): ?string
    {
        if (!$string) return null;
        foreach (explode(',', $string) as $part) {
            [$qty, $u] = array_pad(explode(' ', trim($part), 2), 2, null);
            if ($u === $unit) return $qty;
        }
        return null;
    }

    public function test_leaving_a_unit_blank_stores_a_not_counted_token_not_a_fabricated_real_qty(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $dosStock, $pcsStock] = $this->pickFixture();
        $dosUnit = Unit::find($dosStock->unit_id)->unit_short_name;
        $pcsUnit = Unit::find($pcsStock->unit_id)->unit_short_name;

        // Mirrors what CreateStockOpname.js now sends for an admin who typed 11 into DOS and left
        // pcs blank: DOS gets a real number, pcs gets null/"-" -- never a fallback to system qty.
        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Untouched unit bug test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->product_variant_id,
                'stod_system' => $dosStock->ps_stock.' '.$dosUnit.', '.$pcsStock->ps_stock.' '.$pcsUnit,
                'stod_real' => '11 '.$dosUnit.', - '.$pcsUnit,
                'stod_selisih' => '-1 '.$dosUnit.', - '.$pcsUnit,
                'stod_notes' => null,
                'stod_touched' => 1,
            ]]),
        ]);
        $response->assertStatus(200);
        $stoId = (int) $response->json('sto_id');

        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $stoId,
            'product_variant_id' => $variant->product_variant_id,
            'stod_real' => '11 '.$dosUnit.', - '.$pcsUnit,
        ]);
    }

    public function test_printing_a_pending_document_never_fabricates_a_selisih_for_an_untouched_unit_even_after_live_stock_moves(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $dosStock, $pcsStock] = $this->pickFixture();
        $dosUnit = Unit::find($dosStock->unit_id)->unit_short_name;
        $pcsUnit = Unit::find($pcsStock->unit_id)->unit_short_name;

        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Untouched unit bug test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->product_variant_id,
                'stod_system' => $dosStock->ps_stock.' '.$dosUnit.', '.$pcsStock->ps_stock.' '.$pcsUnit,
                'stod_real' => '11 '.$dosUnit.', - '.$pcsUnit,
                'stod_selisih' => '-1 '.$dosUnit.', - '.$pcsUnit,
                'stod_notes' => null,
                'stod_touched' => 1,
            ]]),
        ]);
        $response->assertStatus(200);
        $stoId = (int) $response->json('sto_id');

        // Ordinary stock movement on the UNTOUCHED unit, unrelated to this opname (e.g. a Sales
        // Order shipment) -- exactly what happened to SP0069/SILP400ML in production.
        $pcsStock->ps_stock -= 27;
        $pcsStock->save();

        // Printing the still-pending doc's PDF must not turn that movement into a fake selisih.
        $this->get('/generateStockOpname/'.$stoId)->assertStatus(200);

        $row = StockOpnameDetail::where('sto_id', $stoId)
            ->where('product_variant_id', $variant->product_variant_id)
            ->firstOrFail();

        $this->assertSame('-', $this->parseToken($row->stod_real, $pcsUnit), 'an untouched unit must stay "-", never a fabricated real qty');
        $this->assertSame('-', $this->parseToken($row->stod_selisih, $pcsUnit), 'an untouched unit must never show a selisih, no matter how live stock moved');
        // The touched unit (DOS) still behaves exactly as before: a genuine selisih is preserved.
        $this->assertSame('-1', $this->parseToken($row->stod_selisih, $dosUnit));
    }

    public function test_approving_never_overwrites_live_stock_for_a_unit_the_staff_never_counted(): void
    {
        $this->actingAsSuperAdminStaff();
        [$variant, $dosStock, $pcsStock] = $this->pickFixture();
        $dosUnit = Unit::find($dosStock->unit_id)->unit_short_name;
        $pcsUnit = Unit::find($pcsStock->unit_id)->unit_short_name;
        $pcsLiveStockAtApproval = $pcsStock->ps_stock - 27; // moved by an unrelated flow meanwhile

        $response = $this->post('/insertStockOpname', [
            'sto_date' => now()->toDateString(),
            'staff_id' => $this->staffId(),
            'category_id' => $this->categoryId(),
            'sto_notes' => 'Untouched unit bug test',
            'is_draft' => 0,
            'item' => json_encode([[
                'product_id' => $variant->product_id,
                'product_variant_id' => $variant->product_variant_id,
                'stod_system' => $dosStock->ps_stock.' '.$dosUnit.', '.$pcsStock->ps_stock.' '.$pcsUnit,
                'stod_real' => '11 '.$dosUnit.', - '.$pcsUnit,
                'stod_selisih' => '-1 '.$dosUnit.', - '.$pcsUnit,
                'stod_notes' => null,
                'stod_touched' => 1,
            ]]),
        ]);
        $response->assertStatus(200);
        $stoId = (int) $response->json('sto_id');

        $pcsStock->ps_stock = $pcsLiveStockAtApproval;
        $pcsStock->save();

        // Mirrors what the ACC screen now submits: DOS counted (real_qty=11), pcs never touched
        // (real_qty=null, since the input was never re-filled with a stale number on reload).
        $this->post('/accStockOpname', [
            'sto_id' => $stoId,
            'item' => json_encode([[
                'product_variant_id' => $variant->product_variant_id,
                'units' => [
                    ['unit_id' => $dosStock->unit_id, 'real_qty' => 11],
                    ['unit_id' => $pcsStock->unit_id, 'real_qty' => null],
                ],
            ]]),
        ])->assertStatus(200);

        $dosStock->refresh();
        $pcsStock->refresh();
        $this->assertSame(11, $dosStock->ps_stock, 'the counted unit is still overwritten with the real count, unaffected by this fix');
        $this->assertSame($pcsLiveStockAtApproval, $pcsStock->ps_stock, 'the never-counted unit must be left completely untouched by approval');

        // No opname log entry should exist for the untouched unit -- nothing was actually opnamed.
        $this->assertDatabaseMissing('log_stocks', [
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => $pcsStock->unit_id,
            'log_notes' => 'Stock Opname Produk',
        ]);
        $this->assertDatabaseHas('log_stocks', [
            'log_item_id' => $variant->product_variant_id,
            'unit_id' => $dosStock->unit_id,
            'log_notes' => 'Stock Opname Produk',
        ]);

        $row = StockOpnameDetail::where('sto_id', $stoId)
            ->where('product_variant_id', $variant->product_variant_id)
            ->firstOrFail();
        $this->assertSame('-', $this->parseToken($row->stod_selisih, $pcsUnit), 'the frozen approval-time record must also show "-" for the never-counted unit');
    }
}
