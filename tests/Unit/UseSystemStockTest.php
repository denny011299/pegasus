<?php

namespace Tests\Unit;

use App\Support\StockOpname\UseSystemStock;
use PHPUnit\Framework\TestCase;

class UseSystemStockTest extends TestCase
{
    public function test_autofill_sets_real_qty_from_system_when_flagged(): void
    {
        $items = UseSystemStock::autofillPayload([
            [
                'product_variant_id' => 1,
                'units' => [
                    ['unit_id' => 10, 'system_qty' => 5, 'real_qty' => null, 'use_system_stock' => 1],
                    ['unit_id' => 11, 'system_qty' => 3, 'real_qty' => 15, 'use_system_stock' => 0],
                    ['unit_id' => 12, 'system_qty' => 7, 'real_qty' => null, 'use_system_stock' => 0],
                ],
            ],
        ], 'units');

        $this->assertSame(5, $items[0]['units'][0]['real_qty']);
        $this->assertSame(15, $items[0]['units'][1]['real_qty']);
        $this->assertNull($items[0]['units'][2]['real_qty']);
    }

    public function test_autofill_works_for_sp_units_key(): void
    {
        $items = UseSystemStock::autofillPayload([
            [
                'supplies_id' => 9,
                'sp_units' => [
                    ['unit_id' => 1, 'system_qty' => 4, 'real_qty' => null, 'use_system_stock' => true],
                ],
            ],
        ], 'sp_units');

        $this->assertSame(4, $items[0]['sp_units'][0]['real_qty']);
    }
}
