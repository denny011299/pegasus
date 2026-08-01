<?php

namespace Tests\Health;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Stock quantities are mutated in bare loops with no DB::transaction() in
 * most flows (Purchase Order/Tt, Production, Stock Opname — see
 * cdocs/testing/guides/DATABASE_TRANSACTION_GUIDE.md), so a negative
 * quantity slipping through is a real risk, not a theoretical one.
 */
class StockNeverNegativeTest extends TestCase
{
    public function test_no_product_stock_row_is_negative(): void
    {
        $negative = DB::table('product_stocks')->where('ps_stock', '<', 0)->count();

        $this->assertSame(0, $negative);
    }

    public function test_no_supplies_stock_row_is_negative(): void
    {
        $negative = DB::table('supplies_stocks')->where('ss_stock', '<', 0)->count();

        $this->assertSame(0, $negative);
    }
}
