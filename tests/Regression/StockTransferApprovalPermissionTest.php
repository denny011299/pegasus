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

    /** @return array{header: StockTransfer, variant: ProductVariant} */
    private function createShippedMainRequestWithStock(): array
    {
        $category = new Category();
        $category->category_name = 'REG ST MainReq Cat ' . uniqid();
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'REG ST MainReq Product ' . uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$this->pieceUnitId]);
        $product->unit_id = $this->pieceUnitId;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'REG ST MainReq Variant';
        $variant->product_variant_sku = 'REG-ST-MR-' . uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $this->pieceUnitId;
        $variant->status = 1;
        $variant->save();

        // Stok di eceran (asal kirim)
        $stockRetail = new ProductStock();
        $stockRetail->product_id = $product->product_id;
        $stockRetail->product_variant_id = $variant->product_variant_id;
        $stockRetail->unit_id = $this->pieceUnitId;
        $stockRetail->warehouse_id = $this->warehouseIds['retail'];
        $stockRetail->ps_stock = 50;
        $stockRetail->status = 1;
        $stockRetail->save();

        // Baris stok tujuan (utama) biar addQty tidak gagal provisioning
        $stockMain = new ProductStock();
        $stockMain->product_id = $product->product_id;
        $stockMain->product_variant_id = $variant->product_variant_id;
        $stockMain->unit_id = $this->pieceUnitId;
        $stockMain->warehouse_id = $this->warehouseIds['main'];
        $stockMain->ps_stock = 0;
        $stockMain->status = 1;
        $stockMain->save();

        $header = new StockTransfer();
        $header->transfer_code = 'REG-ST-MR-' . uniqid();
        $header->transfer_date = now()->toDateString();
        $header->sender_id = (int) (session('user')->staff_id ?? 1);
        $header->from_warehouse_id = $this->warehouseIds['retail'];
        $header->to_warehouse_id = $this->warehouseIds['main'];
        $header->source_type = 'main_request';
        $header->status = 2; // sudah Kirim dari eceran
        $header->acc_by = (int) (session('user')->staff_id ?? 1);
        $header->save();

        $detail = new StockTransferDetail();
        $detail->st_id = $header->st_id;
        $detail->product_id = $product->product_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->unit_id = $this->pieceUnitId;
        $detail->qty = 2;
        $detail->status = 1;
        $detail->save();

        return ['header' => $header, 'variant' => $variant];
    }

    public function test_main_can_create_main_request_to_retail(): void
    {
        $staff = $this->actingAsStaffWithOnlyPermission('Stock Transfer', ['view', 'create', 'others']);
        $staff->role_id = RoleIds::DIREKSI;
        $staff->role_name = 'Direksi';
        session(['user' => $staff]);

        $link = new StaffWarehouse();
        $link->staff_id = (int) $staff->staff_id;
        $link->warehouse_id = $this->warehouseIds['main'];
        $link->is_kepala_cabang = 0;
        $link->save();

        $this->withActiveWarehouse($this->warehouseIds['main']);

        $category = new Category();
        $category->category_name = 'REG ST Create Cat ' . uniqid();
        $category->status = 1;
        $category->save();
        $product = new Product();
        $product->product_name = 'REG ST Create Product ' . uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$this->pieceUnitId]);
        $product->unit_id = $this->pieceUnitId;
        $product->status = 1;
        $product->save();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'V';
        $variant->product_variant_sku = 'REG-ST-C-' . uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $this->pieceUnitId;
        $variant->status = 1;
        $variant->save();
        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = $this->pieceUnitId;
        $stock->warehouse_id = $this->warehouseIds['retail'];
        $stock->ps_stock = 20;
        $stock->status = 1;
        $stock->save();

        $response = $this->post('/insertStockTransfer', [
            'transfer_date' => now()->format('d-m-Y'),
            'sender_id' => (int) session('user')->staff_id,
            'from_warehouse_id' => $this->warehouseIds['retail'],
            'to_warehouse_id' => $this->warehouseIds['main'],
            'note' => 'main request test',
            'items' => [[
                'product_variant_id' => $variant->product_variant_id,
                'unit_id' => $this->pieceUnitId,
                'qty' => 1,
            ]],
        ]);
        $response->assertOk()->assertJson(['status' => 1]);
        $stId = (int) ($response->json('id') ?? 0);
        $this->assertGreaterThan(0, $stId);
        $header = StockTransfer::find($stId);
        $this->assertSame('main_request', $header->source_type);
        $this->assertSame(1, (int) $header->status);
    }

    public function test_direksi_can_approve_qc_then_ops_on_main_request_auto_accept(): void
    {
        $this->actingAsElevatedApprover(RoleIds::DIREKSI);
        ['header' => $header] = $this->createShippedMainRequestWithStock();
        $this->withActiveWarehouse($this->warehouseIds['main']);

        $this->get('/getStockTransferDetail?id=' . $header->st_id)
            ->assertOk()
            ->assertJsonPath('is_main_request', 1)
            ->assertJsonPath('can_approve_qc', true)
            ->assertJsonPath('can_approve_ops', false)
            ->assertJsonPath('can_acc', false);

        $this->post('/approveStockTransfer', ['id' => $header->st_id, 'type' => 'qc'])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $header->refresh();
        $this->assertGreaterThan(0, (int) $header->qc_approved_by);
        $this->assertSame(2, (int) $header->status);

        $this->get('/getStockTransferDetail?id=' . $header->st_id)
            ->assertOk()
            ->assertJsonPath('can_approve_ops', true);

        $this->post('/approveStockTransfer', ['id' => $header->st_id, 'type' => 'ops'])
            ->assertOk()
            ->assertJson(['status' => 1, 'auto_accepted' => 1]);

        $header->refresh();
        $this->assertGreaterThan(0, (int) $header->ops_approved_by);
        $this->assertSame(4, (int) $header->status, 'Final Ops approval auto-accepts main request.');
    }

    /**
     * Gudang utama tanpa Kepala: tahap Ops tetap wajib.
     * Developer/Direksi boleh ganti QC lalu Ops — tidak auto-accept setelah QC saja.
     */
    public function test_elevated_qc_on_main_without_kepala_still_requires_ops(): void
    {
        // Simulasikan bug live: gudang utama tanpa is_kepala_cabang.
        StaffWarehouse::query()
            ->where('warehouse_id', $this->warehouseIds['main'])
            ->update(['is_kepala_cabang' => 0]);

        $this->actingAsElevatedApprover(RoleIds::DEVELOPER);
        ['header' => $header] = $this->createShippedMainRequestWithStock();
        $this->withActiveWarehouse($this->warehouseIds['main']);

        $this->assertTrue(StockTransferApproval::opsRequiredAtWarehouse($this->warehouseIds['main']));
        $this->assertTrue(StockTransferApproval::qcRequiredAtWarehouse($this->warehouseIds['main']));

        $this->post('/approveStockTransfer', ['id' => $header->st_id, 'type' => 'qc'])
            ->assertOk()
            ->assertJson(['status' => 1, 'auto_accepted' => 0]);

        $header->refresh();
        $this->assertSame(2, (int) $header->status, 'Setelah QC masih Kirim — menunggu Ops');
        $this->assertNull($header->ops_approved_by);

        $this->get('/getStockTransferDetail?id=' . $header->st_id)
            ->assertOk()
            ->assertJsonPath('can_approve_ops', true)
            ->assertJsonPath('can_approve_qc', false);

        $this->post('/approveStockTransfer', ['id' => $header->st_id, 'type' => 'ops'])
            ->assertOk()
            ->assertJson(['status' => 1, 'auto_accepted' => 1]);

        $header->refresh();
        $this->assertSame(4, (int) $header->status);
    }

    /**
     * Bug: list flag can_cancel_kirim true for main_request di gudang tujuan,
     * tapi assertCanCancelKirim hanya mengizinkan retail_request → API gagal.
     * Cancel Kirim di tujuan harus restore stok ke eceran (asal).
     */
    public function test_main_request_cancel_kirim_at_destination_restores_origin_stock(): void
    {
        $staff = $this->actingAsStaffWithOnlyPermission('Stock Transfer', ['view', 'others']);
        $this->assignWarehousesToActingStaff(
            $this->warehouseIds['retail'],
            $this->warehouseIds['main']
        );
        session(['user' => $staff]);

        $category = new Category();
        $category->category_name = 'REG ST CancelMR Cat ' . uniqid();
        $category->status = 1;
        $category->save();
        $product = new Product();
        $product->product_name = 'REG ST CancelMR Product ' . uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$this->pieceUnitId]);
        $product->unit_id = $this->pieceUnitId;
        $product->status = 1;
        $product->save();
        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'V';
        $variant->product_variant_sku = 'REG-ST-CMR-' . uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = $this->pieceUnitId;
        $variant->status = 1;
        $variant->save();

        $stockRetail = new ProductStock();
        $stockRetail->product_id = $product->product_id;
        $stockRetail->product_variant_id = $variant->product_variant_id;
        $stockRetail->unit_id = $this->pieceUnitId;
        $stockRetail->warehouse_id = $this->warehouseIds['retail'];
        $stockRetail->ps_stock = 20;
        $stockRetail->status = 1;
        $stockRetail->save();

        $stockMain = new ProductStock();
        $stockMain->product_id = $product->product_id;
        $stockMain->product_variant_id = $variant->product_variant_id;
        $stockMain->unit_id = $this->pieceUnitId;
        $stockMain->warehouse_id = $this->warehouseIds['main'];
        $stockMain->ps_stock = 0;
        $stockMain->status = 1;
        $stockMain->save();

        $header = new StockTransfer();
        $header->transfer_code = 'REG-ST-CMR-' . uniqid();
        $header->transfer_date = now()->toDateString();
        $header->sender_id = (int) $staff->staff_id;
        $header->from_warehouse_id = $this->warehouseIds['retail'];
        $header->to_warehouse_id = $this->warehouseIds['main'];
        $header->source_type = 'main_request';
        $header->status = 1;
        $header->save();

        $detail = new StockTransferDetail();
        $detail->st_id = $header->st_id;
        $detail->product_id = $product->product_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->unit_id = $this->pieceUnitId;
        $detail->qty = 3;
        $detail->status = 1;
        $detail->save();

        $this->withActiveWarehouse($this->warehouseIds['retail']);
        $this->post('/shipStockTransfer', [
            'id' => $header->st_id,
            'proof_base64' => self::PROOF_BASE64,
        ])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $stockRetail->refresh();
        $this->assertSame(17.0, (float) $stockRetail->ps_stock, 'Kirim potong 3 dari eceran');

        $this->withActiveWarehouse($this->warehouseIds['main']);
        $this->get('/getStockTransferDetail?id=' . $header->st_id)
            ->assertOk()
            ->assertJsonPath('can_cancel_kirim', true);

        $this->post('/cancelKirimStockTransfer', ['id' => $header->st_id])
            ->assertOk()
            ->assertJson(['status' => 1]);

        $header->refresh();
        $stockRetail->refresh();
        $this->assertSame(5, (int) $header->status, 'Cancel Kirim');
        $this->assertSame(20.0, (float) $stockRetail->ps_stock, 'Stok kembali ke eceran');
    }
}
