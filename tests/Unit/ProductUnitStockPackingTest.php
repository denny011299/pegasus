<?php

namespace Tests\Unit;

use App\Support\ProductUnitStock;
use PHPUnit\Framework\TestCase;

class ProductUnitStockPackingTest extends TestCase
{
    public function test_fifty_retail_units_can_pack_one_box_and_keep_two(): void
    {
        $plan = ProductUnitStock::packingPlanFromMultipliers(
            [10 => 0, 20 => 50],
            [10 => 48, 20 => 1],
            10,
            1
        );

        $this->assertTrue($plan['ok']);
        $this->assertSame(1, $plan['available']);
        $this->assertSame(0.0, $plan['after'][10]);
        $this->assertSame(2.0, $plan['after'][20]);
    }

    public function test_incomplete_retail_stock_cannot_form_requested_box(): void
    {
        $plan = ProductUnitStock::packingPlanFromMultipliers(
            [10 => 0, 20 => 47],
            [10 => 48, 20 => 1],
            10,
            1
        );

        $this->assertFalse($plan['ok']);
        $this->assertSame(0, $plan['available']);
    }

    public function test_stock_across_chain_is_counted_once(): void
    {
        $plan = ProductUnitStock::packingPlanFromMultipliers(
            [10 => 1, 20 => 50],
            [10 => 48, 20 => 1],
            10,
            2
        );

        $this->assertTrue($plan['ok']);
        $this->assertSame(2, $plan['available']);
        $this->assertSame(0.0, $plan['after'][10]);
        $this->assertSame(2.0, $plan['after'][20]);
    }

    /** Produk tanpa product_relations: default/retail unit tetap chain valid (ST main→eceran 1 Piece). */
    public function test_connected_unit_ids_falls_back_to_anchor_when_no_relations(): void
    {
        ProductUnitStock::clearCache();

        $ref = new \ReflectionClass(ProductUnitStock::class);
        $cache = $ref->getProperty('relationsCache');
        $cache->setAccessible(true);
        $cache->setValue(null, [301 => collect()]);

        $method = $ref->getMethod('connectedUnitIds');
        $method->setAccessible(true);

        $this->assertSame([9], $method->invoke(null, 301, 9));

        ProductUnitStock::clearCache();
    }
}
