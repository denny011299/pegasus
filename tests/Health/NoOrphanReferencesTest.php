<?php

namespace Tests\Health;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * This app has zero real foreign key constraints (see memory
 * pegasus-migrations-drift), so nothing at the database layer stops a
 * detail row from pointing at a header/master row that doesn't exist. These
 * tests check the relations that matter most across the sales/purchase/
 * production/stock chain, run against the seeded snapshot (real,
 * production-shaped data).
 *
 * Each assertion is a plain "does the referenced id actually exist" check —
 * intentionally not generalized into a data provider, so a failure's test
 * name alone tells you exactly which relation broke.
 */
class NoOrphanReferencesTest extends TestCase
{
    public function test_product_variants_reference_existing_products(): void
    {
        $orphans = DB::table('product_variants as pv')
            ->leftJoin('products as p', 'p.product_id', '=', 'pv.product_id')
            ->whereNull('p.product_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_supplies_variants_reference_existing_supplies(): void
    {
        $orphans = DB::table('supplies_variants as sv')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'sv.supplies_id')
            ->whereNull('s.supplies_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_product_stocks_reference_existing_products(): void
    {
        $orphans = DB::table('product_stocks as ps')
            ->leftJoin('products as p', 'p.product_id', '=', 'ps.product_id')
            ->whereNull('p.product_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_product_stocks_reference_existing_product_variants(): void
    {
        $orphans = DB::table('product_stocks as ps')
            ->leftJoin('product_variants as pv', 'pv.product_variant_id', '=', 'ps.product_variant_id')
            ->whereNull('pv.product_variant_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_product_stocks_reference_existing_warehouses(): void
    {
        $orphans = DB::table('product_stocks as ps')
            ->leftJoin('warehouses as w', 'w.id', '=', 'ps.warehouse_id')
            ->whereNotNull('ps.warehouse_id')
            ->whereNull('w.id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_supplies_stocks_reference_existing_supplies(): void
    {
        $orphans = DB::table('supplies_stocks as ss')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'ss.supplies_id')
            ->whereNull('s.supplies_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_supplies_stocks_reference_existing_warehouses(): void
    {
        $orphans = DB::table('supplies_stocks as ss')
            ->leftJoin('warehouses as w', 'w.id', '=', 'ss.warehouse_id')
            ->whereNotNull('ss.warehouse_id')
            ->whereNull('w.id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_sales_order_details_reference_existing_sales_orders(): void
    {
        $orphans = DB::table('sales_order_details as sod')
            ->leftJoin('sales_orders as so', 'so.so_id', '=', 'sod.so_id')
            ->whereNull('so.so_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_sales_order_details_reference_existing_product_variants(): void
    {
        $orphans = DB::table('sales_order_details as sod')
            ->leftJoin('product_variants as pv', 'pv.product_variant_id', '=', 'sod.product_variant_id')
            ->whereNull('pv.product_variant_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_purchase_order_details_reference_existing_purchase_orders(): void
    {
        $orphans = DB::table('purchase_orders_details as pod')
            ->leftJoin('purchase_orders as po', 'po.po_id', '=', 'pod.po_id')
            ->whereNull('po.po_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_purchase_order_details_reference_existing_supplies_variants(): void
    {
        $orphans = DB::table('purchase_orders_details as pod')
            ->leftJoin('supplies_variants as sv', 'sv.supplies_variant_id', '=', 'pod.supplies_variant_id')
            ->whereNull('sv.supplies_variant_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_bom_details_reference_existing_boms(): void
    {
        $orphans = DB::table('bom_details as bd')
            ->leftJoin('boms as b', 'b.bom_id', '=', 'bd.bom_id')
            ->whereNull('b.bom_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    public function test_bom_details_reference_existing_supplies(): void
    {
        $orphans = DB::table('bom_details as bd')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'bd.supplies_id')
            ->whereNull('s.supplies_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    /**
     * log_type = 1 rows store a product_variant_id in log_item_id (confirmed
     * in StockController::submitStockOpname and ProductionController's
     * product-side log inserts).
     */
    public function test_product_log_stocks_reference_existing_product_variants(): void
    {
        $orphans = DB::table('log_stocks as l')
            ->leftJoin('product_variants as pv', 'pv.product_variant_id', '=', 'l.log_item_id')
            ->where('l.log_type', 1)
            ->whereNull('pv.product_variant_id')
            ->count();

        $this->assertSame(0, $orphans);
    }

    /**
     * log_type = 2 rows store a supplies_id (not supplies_variant_id) in
     * log_item_id — confirmed via LogStock.php's own join to `supplies`.
     */
    public function test_supplies_log_stocks_reference_existing_supplies(): void
    {
        $orphans = DB::table('log_stocks as l')
            ->leftJoin('supplies as s', 's.supplies_id', '=', 'l.log_item_id')
            ->where('l.log_type', 2)
            ->whereNull('s.supplies_id')
            ->count();

        $this->assertSame(0, $orphans);
    }
}
