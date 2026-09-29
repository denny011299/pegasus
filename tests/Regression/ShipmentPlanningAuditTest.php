<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\ShipmentShortageDocument;
use App\Models\Unit;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * Audit reproduction fixtures, adapted from the scheduled workflow test.
 * External API v1 shipment scheduling â€” App\Http\Controllers\ExternalApi\V1\ShipmentController::
 * scheduled(). Real warehouse id from the committed seed snapshot: 1 = Gudang Pusat (main), see
 * SalesOrderRetailAndUnitConversionFlowTest's docblock.
 */
class ShipmentPlanningAuditTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const MAIN_WAREHOUSE_ID = 1;

    /** sales_orders.status = 4 ("Dijadwalkan"), see migration 2026_08_11_130000_*. */
    private const STATUS_SCHEDULED = 4;

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Shipment Test Armada';
        $customer->customer_code = 'SC'.random_int(1000, 9999);
        $customer->customer_notes = 'Armada Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function createUnit(?int $refUnitId): Unit
    {
        $unit = new Unit();
        $unit->unit_name = 'Shipment Test Unit '.uniqid();
        $unit->unit_short_name = 'SU-'.random_int(1000, 9999);
        $unit->ref_unit_id = $refUnitId;
        $unit->status = 1;
        $unit->save();

        return $unit;
    }

    /** @return array{variant: ProductVariant, sku: string} */
    private function createProductFixture(Unit $unit): array
    {
        $category = new Category();
        $category->category_name = 'Shipment Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Shipment Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $sku = 'SHP-TEST-'.uniqid();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Shipment Test Variant';
        $variant->product_variant_sku = $sku;
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        return ['variant' => $variant, 'sku' => $sku];
    }

    private function createStock(ProductVariant $variant, int $unitId, int $qty): void
    {
        \App\Models\ProductStock::withoutGlobalScope('active_warehouse')->create([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->product_variant_id,
            'unit_id' => $unitId,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'ps_stock' => $qty,
            'status' => 1,
        ]);
    }

    private function auditRequest(): array
    {
        \App\Support\ProductUnitStock::clearCache();
        $unit = $this->createUnit(random_int(900000, 999999));
        $fx = $this->createProductFixture($unit);
        $this->createStock($fx['variant'], $unit->unit_id, 5);
        $armada = $this->createArmada();
        return [[
            'ref_shipment_id' => 'AUDIT-'.uniqid(),
            'scheduled_date' => '2026-09-23',
            'armada_code' => $armada->customer_code,
            'auto_create_shortage_doc' => true,
            'items' => [['sku' => $fx['sku'], 'qty' => 24, 'unit_id' => $unit->ref_unit_id]],
        ], $fx, $unit];
    }

    private function schedule(array $payload, array $headers)
    {
        return $this->putJson(\App\ExternalApi\Support\ExternalApiPath::endpoint('v1', '/shipments/scheduled'), $payload, $headers);
    }

    // These assertions record current bugs; flip them when each bug is fixed.
    public function test_audit_missing_flag_skips_shortage_and_planning(): void
    {
        [$payload] = $this->auditRequest();
        unset($payload['auto_create_shortage_doc']);
        $res = $this->schedule($payload, $this->externalApiHeaders())->assertStatus(201);
        $this->assertFalse($res->json('data.shortage_doc_created'));
        $this->assertSame(0, ShipmentShortageDocument::where('so_id', $res->json('data.shipment_internal_id'))->count());
    }

    public function test_audit_repeated_shipment_creates_duplicate_planning(): void
    {
        [$payload] = $this->auditRequest();
        $headers = $this->externalApiHeaders();
        $res = $this->schedule($payload, $headers)->assertStatus(201);
        $this->schedule($payload, $headers)->assertStatus(200);
        $soId = $res->json('data.shipment_internal_id');
        $this->assertSame(2, ShipmentShortageDocument::where('so_id', $soId)->count());
        $this->assertSame(2, \App\Models\ProductionPlanning::where('so_id', $soId)->where('status', 1)->count());
        $ids = \App\Models\ProductionPlanning::where('so_id', $soId)->pluck('production_planning_id');
        $this->assertSame(38.0, (float) \App\Models\ProductionPlanningItem::whereIn('production_planning_id', $ids)->sum('qty'));
    }

    public function test_audit_duplicate_sku_lines_reuse_available_stock(): void
    {
        [$payload] = $this->auditRequest();
        $payload['items'][0]['qty'] = 4;
        $payload['items'][] = $payload['items'][0];
        $res = $this->schedule($payload, $this->externalApiHeaders())->assertStatus(201);
        // 8 requested against 5 available should create shortage 3, but creates none.
        $this->assertFalse($res->json('data.shortage_doc_created'));
        $this->assertSame(0, ShipmentShortageDocument::where('so_id', $res->json('data.shipment_internal_id'))->count());
    }

    public function test_audit_failed_planning_item_still_returns_api_success(): void
    {
        [$payload] = $this->auditRequest();
        $headers = $this->externalApiHeaders();
        $original = \App\Models\ProductionPlanningItem::getEventDispatcher();
        $dispatcher = clone $original;
        \App\Models\ProductionPlanningItem::setEventDispatcher($dispatcher);
        $dispatcher->listen('eloquent.creating: '.\App\Models\ProductionPlanningItem::class, function () {
            throw new \RuntimeException('Audit: simulated planning item write failure');
        });
        try {
            $res = $this->schedule($payload, $headers)->assertStatus(201);
        } finally {
            \App\Models\ProductionPlanningItem::setEventDispatcher($original);
        }
        $this->assertTrue($res->json('data.shortage_doc_created'));
        $pp = \App\Models\ProductionPlanning::where('so_id', $res->json('data.shipment_internal_id'))->firstOrFail();
        $this->assertSame(0, \App\Models\ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)->count());
    }

    public function test_audit_lowercase_sku_loses_variant_in_planning(): void
    {
        [$payload, $fx, $unit] = $this->auditRequest();
        $payload['items'][0]['sku'] = strtolower($fx['sku']);
        $res = $this->schedule($payload, $this->externalApiHeaders())->assertStatus(201);
        $pp = \App\Models\ProductionPlanning::where('so_id', $res->json('data.shipment_internal_id'))->firstOrFail();
        $item = \App\Models\ProductionPlanningItem::where('production_planning_id', $pp->production_planning_id)->firstOrFail();
        $this->assertNull($item->product_variant_id);
        $this->assertSame(19.0, (float) $item->qty);
        $this->assertSame((int) $unit->unit_id, (int) $item->unit_id);
    }
}
