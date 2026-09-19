<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockTransfer;
use App\Models\StockTransferDetail;
use App\Models\Warehouse;
use App\Support\PendingStockSoftBlock;
use App\Support\StockOpname\OpenOpnameGuard;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Soft-block mutasi saat opname open (tanpa antrian).
 */
class PendingStockQueueWorkflowTest extends TestCase
{
    use ActingAsStaff;

    private const MAIN_WAREHOUSE_ID = 1;
    private const OTHER_WAREHOUSE_ID = 2;
    private const PIECE_UNIT_ID = 9;
    private const PROOF_BASE64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

    private function openProductOpname(int $warehouseId): StockOpname
    {
        $sto = new StockOpname();
        $staffId = (int) (session('user')->staff_id ?? 0);
        $sto->sto_code = 'SP' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $sto->sto_date = now()->toDateString();
        $sto->warehouse_id = $warehouseId;
        $sto->staff_id = $staffId;
        $sto->category_id = 0;
        $sto->status = 1;
        $sto->is_draft = false;
        if (Schema::hasColumn('stock_opnames', 'is_old_version')) {
            $sto->is_old_version = false;
        }
        $sto->created_by = $staffId;
        $sto->save();

        return $sto;
    }

    /** @return array{product: Product, variant: ProductVariant, stock: ProductStock} */
    private function createStockedProduct(int $warehouseId, float $qty = 50): array
    {
        $category = new Category();
        $category->category_name = 'PSO WF Cat';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'PSO WF Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([self::PIECE_UNIT_ID]);
        $product->unit_id = self::PIECE_UNIT_ID;
        $product->status = 1;
        if (Schema::hasColumn('products', 'product_kind')) {
            $product->product_kind = Product::KIND_PRODUCT;
        }
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'PSO WF Variant';
        $variant->product_variant_sku = 'PSO-WF-' . uniqid();
        $variant->product_variant_price = 0;
        $variant->retail_unit = self::PIECE_UNIT_ID;
        $variant->status = 1;
        $variant->save();

        $stock = new ProductStock();
        $stock->product_id = $product->product_id;
        $stock->product_variant_id = $variant->product_variant_id;
        $stock->unit_id = self::PIECE_UNIT_ID;
        $stock->warehouse_id = $warehouseId;
        $stock->ps_stock = $qty;
        $stock->status = 1;
        $stock->save();

        return compact('product', 'variant', 'stock');
    }

    public function test_open_opname_guard_is_warehouse_scoped(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);

        $guard = app(OpenOpnameGuard::class);
        $this->assertTrue($guard->isBlocked(self::MAIN_WAREHOUSE_ID, OpenOpnameGuard::DOMAIN_PRODUCT));
        $this->assertFalse($guard->isBlocked(self::OTHER_WAREHOUSE_ID, OpenOpnameGuard::DOMAIN_PRODUCT));
    }

    public function test_ship_soft_blocks_when_origin_opname_open(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);
        $fx = $this->createStockedProduct(self::MAIN_WAREHOUSE_ID, 40);

        $mainTypeId = (int) Warehouse::query()->findOrFail(self::MAIN_WAREHOUSE_ID)->warehouse_type_id;
        $dest = new Warehouse();
        $dest->warehouse_name = 'PSO Dest Main ' . uniqid();
        $dest->warehouse_type_id = $mainTypeId;
        $dest->status = 1;
        $dest->save();

        $header = new StockTransfer();
        $header->transfer_code = 'PSO-ST-' . uniqid();
        $header->transfer_date = now()->toDateString();
        $header->sender_id = (int) (session('user')->staff_id ?? 0);
        $header->from_warehouse_id = self::MAIN_WAREHOUSE_ID;
        $header->to_warehouse_id = (int) $dest->id;
        $header->status = 1;
        $header->save();

        $detail = new StockTransferDetail();
        $detail->st_id = $header->st_id;
        $detail->product_id = $fx['product']->product_id;
        $detail->product_variant_id = $fx['variant']->product_variant_id;
        $detail->unit_id = self::PIECE_UNIT_ID;
        $detail->qty = 10;
        $detail->status = 1;
        $detail->save();

        $this->post('/shipStockTransfer', [
            'id' => $header->st_id,
            'proof_base64' => self::PROOF_BASE64,
        ])
            ->assertStatus(200)
            ->assertJson([
                'status' => -1,
                'header' => 'Stock Opname',
            ]);

        $header->refresh();
        $fx['stock']->refresh();
        $this->assertSame(1, (int) $header->status);
        $this->assertSame(40.0, (float) $fx['stock']->ps_stock);
    }

    public function test_soft_block_message_when_opname_open(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);

        $msg = PendingStockSoftBlock::messageIfBlocked(
            self::MAIN_WAREHOUSE_ID,
            OpenOpnameGuard::DOMAIN_PRODUCT
        );
        $this->assertNotNull($msg);
        $this->assertStringContainsString('Stock Opname', $msg);

        $this->assertNull(PendingStockSoftBlock::messageIfBlocked(
            self::OTHER_WAREHOUSE_ID,
            OpenOpnameGuard::DOMAIN_PRODUCT
        ));
    }

    public function test_product_issues_soft_blocks_when_product_opname_open(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);

        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);
        $fx = $this->createStockedProduct(self::MAIN_WAREHOUSE_ID, 20);
        $qty = 4;

        $this->post('/insertProductIssues', [
            'tipe_return' => 2,
            'pi_type' => 1,
            'pi_date' => now()->format('d-m-Y'),
            'pi_notes' => 'PSO soft-block product issues',
            'items' => json_encode([[
                'product_variant_id' => $fx['variant']->product_variant_id,
                'pr_name' => 'PSO PI Product',
                'unit_id' => self::PIECE_UNIT_ID,
                'pid_qty' => $qty,
            ]]),
        ])->assertStatus(200);

        $piId = (int) \App\Models\ProductIssues::orderByDesc('pi_id')->value('pi_id');
        $this->assertGreaterThan(0, $piId);

        $this->post('/accProductIssues', ['pi_id' => $piId])
            ->assertStatus(200)
            ->assertJson([
                'status' => -1,
                'header' => 'Stock Opname',
            ]);

        $pi = \App\Models\ProductIssues::findOrFail($piId);
        $fx['stock']->refresh();
        $this->assertSame(1, (int) $pi->status);
        $this->assertSame(20.0, (float) $fx['stock']->ps_stock);
    }

    /** QC12: ACC bahan rusak (tipe_return=1) tidak diblok opname produk. */
    public function test_product_issues_bahan_not_blocked_by_product_opname(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);

        $msg = PendingStockSoftBlock::messageIfBlocked(
            self::MAIN_WAREHOUSE_ID,
            OpenOpnameGuard::DOMAIN_SUPPLIES
        );
        $this->assertNull($msg, 'opname produk tidak boleh memblok domain bahan');
    }

    /** QC14: cancel kirim ST diblok saat opname produk open di gudang asal. */
    public function test_cancel_kirim_soft_blocks_when_product_opname_open(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->withActiveWarehouse(self::MAIN_WAREHOUSE_ID);
        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);

        $header = new StockTransfer();
        $header->transfer_code = 'ST-QC14-' . uniqid();
        $header->transfer_date = now()->toDateString();
        $header->from_warehouse_id = self::MAIN_WAREHOUSE_ID;
        $header->to_warehouse_id = self::OTHER_WAREHOUSE_ID;
        $header->sender_id = (int) (session('user')->staff_id ?? 0);
        $header->status = 2;
        $header->save();

        $this->post('/cancelKirimStockTransfer', ['id' => $header->st_id])
            ->assertStatus(200)
            ->assertJson([
                'status' => -1,
                'header' => 'Stock Opname',
            ]);

        $header->refresh();
        $this->assertSame(2, (int) $header->status, 'status harus tetap Kirim saat soft-block');
    }

    public function test_soft_block_any_domain_helper(): void
    {
        $this->actingAsSuperAdminStaff();
        $this->openProductOpname(self::MAIN_WAREHOUSE_ID);

        $msg = PendingStockSoftBlock::messageIfAnyDomainBlocked(self::MAIN_WAREHOUSE_ID);
        $this->assertNotNull($msg);
        $this->assertStringContainsString('Stock Opname', $msg);
    }
}
