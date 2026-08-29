<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Supplies;
use App\Models\SuppliesRelation;
use App\Models\SuppliesStock;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * `stock:audit-rollup` (born from a direct question, 2026-08-29, after GitHub #87 shipped: "if the
 * DB is never touched by this fix, does the bug recur?" — answer: no NEW instances, but any row
 * already stuck over-ratio from BEFORE the fix stays wrong until something transacts against it
 * again). This locks in that the command finds a stuck row, leaves a healthy one alone, and — since
 * it may run directly against production — never writes anything.
 */
class AuditStuckUnitRollUpCommandTest extends TestCase
{
    private const PIECE_UNIT_ID = 9;
    private const DOS_UNIT_ID = 7;
    private const WAREHOUSE_ID = 1;

    public function test_finds_a_stuck_product_row_and_leaves_a_healthy_one_out_of_the_report(): void
    {
        $category = new Category();
        $category->category_name = 'Audit Command Regression Category';
        $category->status = 1;
        $category->save();

        // --- STUCK fixture: 24 Piece sitting there, 1 DOS = 24 Piece, DOS row already exists at 11.
        $stuckProduct = new Product();
        $stuckProduct->product_name = 'Audit Command Stuck Product';
        $stuckProduct->category_id = $category->category_id;
        $stuckProduct->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $stuckProduct->unit_id = self::PIECE_UNIT_ID;
        $stuckProduct->status = 1;
        $stuckProduct->save();

        $stuckVariant = new ProductVariant();
        $stuckVariant->product_id = $stuckProduct->product_id;
        $stuckVariant->product_variant_name = 'Audit Command Stuck Variant';
        $stuckVariant->product_variant_sku = 'WF-AUDIT-STUCK-'.uniqid();
        $stuckVariant->product_variant_price = 0;
        $stuckVariant->status = 1;
        $stuckVariant->save();

        $stuckPiece = new ProductStock();
        $stuckPiece->product_id = $stuckProduct->product_id;
        $stuckPiece->product_variant_id = $stuckVariant->product_variant_id;
        $stuckPiece->unit_id = self::PIECE_UNIT_ID;
        $stuckPiece->warehouse_id = self::WAREHOUSE_ID;
        $stuckPiece->ps_stock = 24; // stuck: should have rolled into 1 more DOS
        $stuckPiece->status = 1;
        $stuckPiece->save();

        $stuckDos = new ProductStock();
        $stuckDos->product_id = $stuckProduct->product_id;
        $stuckDos->product_variant_id = $stuckVariant->product_variant_id;
        $stuckDos->unit_id = self::DOS_UNIT_ID;
        $stuckDos->warehouse_id = self::WAREHOUSE_ID;
        $stuckDos->ps_stock = 11;
        $stuckDos->status = 1;
        $stuckDos->save();

        $stuckRelation = new ProductRelation();
        $stuckRelation->product_variant_id = $stuckVariant->product_variant_id;
        $stuckRelation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $stuckRelation->pr_unit_value_1 = 1;
        $stuckRelation->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $stuckRelation->pr_unit_value_2 = 24;
        $stuckRelation->pr_default = 0;
        $stuckRelation->status = 1;
        $stuckRelation->save();

        // --- HEALTHY control fixture: already correctly rolled up, must NOT be reported.
        $healthyProduct = new Product();
        $healthyProduct->product_name = 'Audit Command Healthy Product';
        $healthyProduct->category_id = $category->category_id;
        $healthyProduct->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $healthyProduct->unit_id = self::PIECE_UNIT_ID;
        $healthyProduct->status = 1;
        $healthyProduct->save();

        $healthyVariant = new ProductVariant();
        $healthyVariant->product_id = $healthyProduct->product_id;
        $healthyVariant->product_variant_name = 'Audit Command Healthy Variant';
        $healthyVariant->product_variant_sku = 'WF-AUDIT-HEALTHY-'.uniqid();
        $healthyVariant->product_variant_price = 0;
        $healthyVariant->status = 1;
        $healthyVariant->save();

        $healthyPiece = new ProductStock();
        $healthyPiece->product_id = $healthyProduct->product_id;
        $healthyPiece->product_variant_id = $healthyVariant->product_variant_id;
        $healthyPiece->unit_id = self::PIECE_UNIT_ID;
        $healthyPiece->warehouse_id = self::WAREHOUSE_ID;
        $healthyPiece->ps_stock = 5; // below the ratio, correctly left at Piece
        $healthyPiece->status = 1;
        $healthyPiece->save();

        $healthyDos = new ProductStock();
        $healthyDos->product_id = $healthyProduct->product_id;
        $healthyDos->product_variant_id = $healthyVariant->product_variant_id;
        $healthyDos->unit_id = self::DOS_UNIT_ID;
        $healthyDos->warehouse_id = self::WAREHOUSE_ID;
        $healthyDos->ps_stock = 3;
        $healthyDos->status = 1;
        $healthyDos->save();

        $healthyRelation = new ProductRelation();
        $healthyRelation->product_variant_id = $healthyVariant->product_variant_id;
        $healthyRelation->pr_unit_id_1 = self::DOS_UNIT_ID;
        $healthyRelation->pr_unit_value_1 = 1;
        $healthyRelation->pr_unit_id_2 = self::PIECE_UNIT_ID;
        $healthyRelation->pr_unit_value_2 = 24;
        $healthyRelation->pr_default = 0;
        $healthyRelation->status = 1;
        $healthyRelation->save();

        Artisan::call('stock:audit-rollup', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);

        $stuckIds = array_column($output['products'], 'id');
        $this->assertContains($stuckVariant->product_variant_id, $stuckIds, 'the stuck product must be reported');
        $this->assertNotContains($healthyVariant->product_variant_id, $stuckIds, 'the already-correct product must NOT be reported');

        $stuckEntry = collect($output['products'])->firstWhere('id', $stuckVariant->product_variant_id);
        $this->assertSame(24, $stuckEntry['before']['Piece']);
        $this->assertSame(11, $stuckEntry['before']['DOS']);
        $this->assertSame(0, $stuckEntry['after']['Piece']);
        $this->assertSame(12, $stuckEntry['after']['DOS']);

        // The whole point: this is a diagnostic. It must never write anything, anywhere.
        $stuckPiece->refresh();
        $stuckDos->refresh();
        $healthyPiece->refresh();
        $healthyDos->refresh();
        $this->assertSame(24, $stuckPiece->ps_stock, 'read-only command must not touch the stuck row');
        $this->assertSame(11, $stuckDos->ps_stock, 'read-only command must not touch the stuck row');
        $this->assertSame(5, $healthyPiece->ps_stock);
        $this->assertSame(3, $healthyDos->ps_stock);
    }

    public function test_finds_a_stuck_supplies_row(): void
    {
        $liter = 3;
        $drum = 5;

        $supplies = new Supplies();
        $supplies->supplies_name = 'Audit Command Stuck Supplies';
        $supplies->supplies_unit = json_encode([$liter, $drum]);
        $supplies->supplies_default_unit = $liter;
        $supplies->status = 1;
        $supplies->save();

        $literStock = new SuppliesStock();
        $literStock->supplies_id = $supplies->supplies_id;
        $literStock->unit_id = $liter;
        $literStock->warehouse_id = self::WAREHOUSE_ID;
        $literStock->ss_stock = 250; // stuck: 1 Drum = 200 Liter, this should be 1 Drum + 50 Liter
        $literStock->status = 1;
        $literStock->save();

        $drumStock = new SuppliesStock();
        $drumStock->supplies_id = $supplies->supplies_id;
        $drumStock->unit_id = $drum;
        $drumStock->warehouse_id = self::WAREHOUSE_ID;
        $drumStock->ss_stock = 2;
        $drumStock->status = 1;
        $drumStock->save();

        $relation = new SuppliesRelation();
        $relation->supplies_id = $supplies->supplies_id;
        $relation->su_id_1 = $drum;
        $relation->su_id_2 = $liter;
        $relation->sr_value_1 = 1;
        $relation->sr_value_2 = 200;
        $relation->status = 1;
        $relation->save();

        Artisan::call('stock:audit-rollup', ['--json' => true]);
        $output = json_decode(Artisan::output(), true);

        $entry = collect($output['supplies'])->firstWhere('id', $supplies->supplies_id);
        $this->assertNotNull($entry, 'the stuck supplies row must be reported');
        $this->assertSame(250, $entry['before']['Liter']);
        $this->assertSame(2, $entry['before']['Drum']);
        $this->assertSame(50, $entry['after']['Liter']);
        $this->assertSame(3, $entry['after']['Drum']);

        $literStock->refresh();
        $drumStock->refresh();
        $this->assertSame(250, $literStock->ss_stock, 'read-only command must not touch the stuck row');
        $this->assertSame(2, $drumStock->ss_stock, 'read-only command must not touch the stuck row');
    }
}
