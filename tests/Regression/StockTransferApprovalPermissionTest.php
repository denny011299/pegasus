<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Staff;
use App\Models\StaffWarehouse;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\Unit;
use App\Models\Warehouse;
use App\Models\WarehouseType;
use App\Support\RoleIds;
use App\Support\StockTransferApproval;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Retail request ST (main → eceran) gates QC/Ops approval on warehouse role assignment.
 * Direksi + Okejob (Developer) must also approve when designated staff unavailable.
 */
class StockTransferApprovalPermissionTest extends TestCase
{
    use ActingAsStaff;

    /** Bukti foto wajib saat approval yang auto-Kirim (GitHub #140). */
    private const PROOF_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';


    /** @var int */
    private int $pieceUnitId = 0;

    /** @var array{main:int,retail:int} */
    private array $warehouseIds = ['main' => 0, 'retail' => 0];

    protected function setUp(): void
    {
        parent::setUp();
        $unit = new Unit();
        $unit->unit_name = 'REG ST Piece ' . uniqid();
        $unit->unit_short_name = 'Pcs';
        $unit->status = 1;
        $unit->save();
        $this->pieceUnitId = (int) $unit->unit_id;
        $this->warehouseIds = $this->createWarehousePair();
        $this->ensureApprovalRolesAtMainWarehouse($this->warehouseIds['main']);
    }

    /** @return array{main:int,retail:int} */
    private function createWarehousePair(): array
    {
        $mainType = new WarehouseType();
        $mainType->warehouse_type_name = 'REG ST Main ' . uniqid();
        $mainType->is_main_warehouse = 1;
        $mainType->status = 1;
        $mainType->save();

        $retailType = new WarehouseType();
        $retailType->warehouse_type_name = 'REG ST Retail ' . uniqid();
        $retailType->is_main_warehouse = 0;
        $retailType->status = 1;
        $retailType->save();

        $mainWh = new Warehouse();
        $mainWh->warehouse_name = 'REG ST Main WH ' . uniqid();
        $mainWh->warehouse_type_id = (int) $mainType->id;
        $mainWh->status = 1;
        $mainWh->save();

        $retailWh = new Warehouse();
        $retailWh->warehouse_name = 'REG ST Retail WH ' . uniqid();
        $retailWh->warehouse_type_id = (int) $retailType->id;
        $retailWh->status = 1;
        $retailWh->save();

        return ['main' => (int) $mainWh->id, 'retail' => (int) $retailWh->id];
    }

    private function ensureApprovalRolesAtMainWarehouse(int $mainWarehouseId): void
    {
        $qc = new Staff();
        $qc->staff_name = 'REG ST QC ' . uniqid();
        $qc->role_id = RoleIds::QC_GUDANG;
        $qc->status = 1;
        $qc->save();

        $qcWh = new StaffWarehouse();
        $qcWh->staff_id = (int) $qc->staff_id;
        $qcWh->warehouse_id = $mainWarehouseId;
        $qcWh->is_kepala_cabang = 0;
        $qcWh->save();

        $ops = new Staff();
        $ops->staff_name = 'REG ST Ops ' . uniqid();
        $ops->role_id = 6;
        $ops->status = 1;
        $ops->save();

        $opsWh = new StaffWarehouse();
        $opsWh->staff_id = (int) $ops->staff_id;
        $opsWh->warehouse_id = $mainWarehouseId;
        $opsWh->is_kepala_cabang = 1;
        $opsWh->save();

        $this->assertTrue(StockTransferApproval::qcRequiredAtWarehouse($mainWarehouseId));
        $this->assertTrue(StockTransferApproval::opsRequiredAtWarehouse($mainWarehouseId));
    }

    /** @return array{header: StockTransfer, variant: ProductVariant} */
    private function createPendingRetailRequestWithStock(): array
    {
        $category = new Category();
        $category->category_name = 'REG ST Category ' . uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'REG ST Product ' . uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$this->pieceUnitId]);
        $product->unit_id = $this->pieceUnitId;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'REG ST Variant';
        $variant->product_variant_sku = 'REG-ST-' . uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $this->pieceUnitId;
        $variant->status = 1;
        $variant->save();

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = $this->pieceUnitId;
        $stock->warehouse_id = $this->warehouseIds['main'];
        $stock->ps_stock = 100;
        $stock->status = 1;
        $stock->save();

        $header = new StockTransfer();
        $header->transfer_code = 'REG-ST-' . uniqid();
        $header->transfer_date = now()->toDateString();
        $header->sender_id = (int) (session('user')->staff_id ?? 1);
        $header->from_warehouse_id = $this->warehouseIds['main'];
        $header->to_warehouse_id = $this->warehouseIds['retail'];
        $header->source_type = 'retail_request';
        $header->status = 1;
        $header->save();

        $detail = new StockTransferDetail();
        $detail->st_id = $header->st_id;
        $detail->product_id = $product->product_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->unit_id = $this->pieceUnitId;
        $detail->qty = 1;
        $detail->status = 1;
        $detail->save();

        return ['header' => $header, 'variant' => $variant];
    }

    private function actingAsElevatedApprover(int $roleId): object
    {
        $staff = $this->actingAsStaffWithOnlyPermission('Stock Transfer', ['view', 'others']);
        $staff->role_id = $roleId;
        $staff->role_name = $roleId === RoleIds::DIREKSI ? 'Direksi' : 'Okejob (Developer)';
        session(['user' => $staff]);

        return $staff;
    }

    public function test_direksi_can_approve_qc_then_ops_on_retail_request(): void
    {
        $this->actingAsElevatedApprover(RoleIds::DIREKSI);
        ['header' => $header] = $this->createPendingRetailRequestWithStock();
        $this->withActiveWarehouse($this->warehouseIds['main']);

        $this->get('/getStockTransferDetail?id=' . $header->st_id)
            ->assertOk()
            ->assertJsonPath('can_approve_qc', true)
            ->assertJsonPath('can_approve_ops', false);

        $this->post('/approveStockTransfer', ['id' => $header->st_id, 'type' => 'qc'])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $header->refresh();
        $this->assertGreaterThan(0, (int) $header->qc_approved_by);
        $this->assertNull($header->ops_approved_by);

        $this->get('/getStockTransferDetail?id=' . $header->st_id)
            ->assertOk()
            ->assertJsonPath('can_approve_qc', false)
            ->assertJsonPath('can_approve_ops', true);

        $this->post('/approveStockTransfer', [
            'id' => $header->st_id,
            'type' => 'ops',
            'proof_base64' => self::PROOF_BASE64,
        ])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $header->refresh();
        $this->assertGreaterThan(0, (int) $header->ops_approved_by);
        $this->assertSame(2, (int) $header->status, 'Final Ops approval auto-ships retail request.');
    }

    public function test_developer_can_approve_qc_on_retail_request(): void
    {
        $this->actingAsElevatedApprover(RoleIds::DEVELOPER);
        ['header' => $header] = $this->createPendingRetailRequestWithStock();
        $this->withActiveWarehouse($this->warehouseIds['main']);

        $this->post('/approveStockTransfer', ['id' => $header->st_id, 'type' => 'qc'])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $header->refresh();
        $this->assertGreaterThan(0, (int) $header->qc_approved_by);
    }
}
