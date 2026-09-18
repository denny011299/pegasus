<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Unit;
use Tests\TestCase;

/**
 * SalesOrder::getSalesOrderDataTable() feeds the "Pengiriman" admin list (Sales_Order.blade.php /
 * Sales_Order.js). Its "Dibuat Oleh" column uses the shared renderCreatedBySync() helper
 * (footer-scripts.blade.php) to show a "PMO" badge for rows that came from the External API
 * rather than the admin form — for shipments, the tell is sales_orders.ref_shipment_id, which is
 * ALWAYS set by ShipmentController::scheduled()/shipped() and never by an admin-created shipment.
 * This must be selected and returned by the DataTable query for that badge to have anything to
 * key off of.
 */
class SalesOrderDataTableRefShipmentIdTest extends TestCase
{
    private const MAIN_WAREHOUSE_ID = 1;

    private function createFixtureSo(?string $refShipmentId): int
    {
        $unit = new Unit();
        $unit->unit_name = 'DT Test Unit '.uniqid();
        $unit->unit_short_name = 'DTU-'.random_int(1000, 9999);
        $unit->status = 1;
        $unit->save();

        $category = new Category();
        $category->category_name = 'DT Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'DT Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$unit->unit_id]);
        $product->unit_id = $unit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'DT Test Variant';
        $variant->product_variant_sku = 'DT-TEST-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $so = (new SalesOrder())->insertSalesOrder([
            'so_customer' => '1',
            'so_date' => '2026-09-19',
            'so_total' => 0,
            'so_img' => json_encode([]),
        ]);
        $so->ref_shipment_id = $refShipmentId;
        $so->status = 4;
        $so->save();

        (new SalesOrderDetail())->insertSalesOrderDetail([
            'so_id' => $so->so_id,
            'product_variant_id' => $variant->product_variant_id,
            'product_name' => $product->product_name,
            'product_variant_name' => $variant->product_variant_name,
            'product_variant_sku' => $variant->product_variant_sku,
            'unit_id' => $unit->unit_id,
            'warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'product_variant_price' => 0,
            'so_qty' => 1,
            'so_subtotal' => 0,
        ]);

        return $so->so_id;
    }

    /**
     * pegasus_testing carries real multi-warehouse seed data (hundreds of existing sales_orders —
     * see memory "pegasus-testing-db-multiwarehouse-drift"), so the fixture row created here isn't
     * guaranteed to land on the DataTable's default page/sort. Order explicitly by so_id desc
     * (column index 7, see getSalesOrderDataTable()'s $columns map) and ask for the max page
     * length so the just-inserted (highest so_id) row is always included.
     */
    private function fetchFixtureRow(int $soId): ?array
    {
        $result = (new SalesOrder())->getSalesOrderDataTable([
            'active_warehouse_id' => self::MAIN_WAREHOUSE_ID,
            'length' => 100,
            'order' => [['column' => 7, 'dir' => 'desc']],
        ]);

        return collect($result['data'])->firstWhere('so_id', $soId);
    }

    public function test_datatable_returns_ref_shipment_id_for_a_shipment_created_via_the_api(): void
    {
        $refShipmentId = 'SHP-DT-'.uniqid();
        $soId = $this->createFixtureSo($refShipmentId);

        $row = $this->fetchFixtureRow($soId);

        $this->assertNotNull($row, 'the fixture shipment must appear in the DataTable output');
        $this->assertSame($refShipmentId, $row['ref_shipment_id']);
    }

    public function test_datatable_returns_null_ref_shipment_id_for_an_admin_created_shipment(): void
    {
        $soId = $this->createFixtureSo(null);

        $row = $this->fetchFixtureRow($soId);

        $this->assertNotNull($row);
        $this->assertNull($row['ref_shipment_id']);
    }
}
