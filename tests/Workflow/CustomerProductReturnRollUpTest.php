<?php

namespace Tests\Workflow;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerProductReturn;
use App\Models\CustomerProductReturnDetail;
use App\Models\Product;
use App\Models\ProductRelation;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\Warehouse;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * GitHub #132 ("Bug report yang perlu diperhatikan"): Pengembalian produk jadi pcs di gudang
 * besar dulu ditulis flat (`ps_stock += qty` di satuan yang dikembalikan saja) tanpa pernah
 * menggulung ke satuan atas -- 100 pcs (1 DOS = 12 pcs) tersimpan sebagai 100 Piece, bukan 8 DOS +
 * 4 Piece, dan data yang tidak pernah dikonversi ini yang belakangan membuat Stock Opname gudang
 * itu "rusak" (baris DOS tidak pernah ada/ikut naik). Sekarang lewat App\Support\ProductUnitStock::
 * addQty() dengan roll-up khusus gudang UTAMA -- lihat CustomerProductReturnController::accept().
 */
class CustomerProductReturnRollUpTest extends TestCase
{
    use ActingAsStaff;

    private array $units = [];

    protected function setUp(): void
    {
        parent::setUp();
        $rows = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $rows->count(), 'fixture butuh minimal 2 satuan aktif');
        $this->units = ['dos' => $rows[0], 'pcs' => $rows[1]];
    }

    private function mainWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 1))
            ->orderBy('warehouses.id')
            ->value('id');
    }

    private function retailWarehouseId(): int
    {
        return (int) Warehouse::query()
            ->where('warehouses.status', 1)
            ->whereHas('type', fn ($q) => $q->where('status', 1)->where('is_main_warehouse', 0))
            ->orderBy('warehouses.id')
            ->value('id');
    }

    /** 1 DOS = 12 pcs, kedua satuan sudah punya baris ProductStock aktif di $warehouseId. */
    private function makeLadderedVariant(int $warehouseId, int $dosStock, int $pcsStock, int $ratio = 12): ProductVariant
    {
        $category = new Category();
        $category->category_name = 'Return RollUp Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Return RollUp Test Product';
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$this->units['dos']->unit_id, $this->units['pcs']->unit_id]);
        $product->unit_id = $this->units['dos']->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Return RollUp Test Variant';
        $variant->product_variant_sku = 'RTNRU-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $relation = new ProductRelation();
        $relation->product_variant_id = $variant->product_variant_id;
        $relation->pr_unit_id_1 = $this->units['dos']->unit_id; // besar
        $relation->pr_unit_value_1 = 1;
        $relation->pr_unit_id_2 = $this->units['pcs']->unit_id; // kecil
        $relation->pr_unit_value_2 = $ratio;
        $relation->status = 1;
        $relation->save();

        foreach ([['dos', $dosStock], ['pcs', $pcsStock]] as [$key, $qty]) {
            $s = new ProductStock();
            $s->product_id = $product->product_id;
            $s->product_variant_id = $variant->product_variant_id;
            $s->unit_id = $this->units[$key]->unit_id;
            $s->warehouse_id = $warehouseId;
            $s->ps_stock = $qty;
            $s->status = 1;
            $s->save();
        }

        return $variant;
    }

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Return RollUp Test Armada';
        $customer->customer_code = 'RRU'.random_int(1000, 9999);
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    private function currentStock(ProductVariant $v, string $unitKey, int $warehouseId): int
    {
        return (int) ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $v->product_variant_id)
            ->where('unit_id', $this->units[$unitKey]->unit_id)
            ->where('warehouse_id', $warehouseId)
            ->value('ps_stock');
    }

    private function makeReturn(Customer $customer, ProductVariant $v, int $warehouseId, string $unitKey, int $qty): CustomerProductReturn
    {
        $record = new CustomerProductReturn();
        $record->return_number = 'PBJ-TEST-'.uniqid();
        $record->customer_id = $customer->customer_id;
        $record->return_date = now()->toDateString();
        $record->proof_path = 'return-rollup-test.png';
        $record->status = 1;
        $record->created_by = $this->staffId();
        $record->save();

        $detail = new CustomerProductReturnDetail();
        $detail->return_id = $record->return_id;
        $detail->product_variant_id = $v->product_variant_id;
        $detail->unit_id = $this->units[$unitKey]->unit_id;
        $detail->warehouse_id = $warehouseId;
        $detail->qty = $qty;
        $detail->status = 1;
        $detail->save();

        return $record;
    }

    private function staffId(): int
    {
        return (int) \Illuminate\Support\Facades\DB::table('staffs')->where('status', 1)->value('staff_id');
    }

    public function test_returning_pcs_at_main_warehouse_rolls_up_into_the_bigger_unit(): void
    {
        $this->actingAsSuperAdminStaff();

        $warehouseId = $this->mainWarehouseId();
        $v = $this->makeLadderedVariant($warehouseId, dosStock: 0, pcsStock: 0, ratio: 12);
        $customer = $this->createArmada();

        // Dikembalikan 100 pcs (1 DOS = 12 pcs) -> harus tergulung jadi 8 DOS + 4 Piece, bukan
        // menumpuk sebagai 100 Piece polos.
        $record = $this->makeReturn($customer, $v, $warehouseId, 'pcs', 100);

        $response = $this->post("/customerProductReturns/{$record->return_id}/accept");
        $response->assertOk();

        $this->assertSame(8, $this->currentStock($v, 'dos', $warehouseId), 'pengembalian di gudang utama harus ikut menggulung ke DOS');
        $this->assertSame(4, $this->currentStock($v, 'pcs', $warehouseId), 'sisa yang tidak genap satu DOS harus tetap di Piece');
    }

    public function test_returning_pcs_at_retail_warehouse_stays_flat_no_roll_up(): void
    {
        $this->actingAsSuperAdminStaff();

        $retailId = $this->retailWarehouseId();
        if ($retailId <= 0) {
            $this->markTestSkipped('Tidak ada gudang eceran aktif di seed data.');
        }

        // Gudang eceran hanya boleh punya baris retail_unit (pcs) -- DOS sengaja tidak dibuat di
        // sini, meniru OpnameLifecycle::isRetailWarehouse()'s aturan yang sama.
        $v = $this->makeLadderedVariant($retailId, dosStock: 0, pcsStock: 0, ratio: 12);
        $v->retail_unit = $this->units['pcs']->unit_id;
        $v->save();
        ProductStock::withoutGlobalScope('active_warehouse')
            ->where('product_variant_id', $v->product_variant_id)
            ->where('warehouse_id', $retailId)
            ->where('unit_id', $this->units['dos']->unit_id)
            ->delete();

        $customer = $this->createArmada();
        $record = $this->makeReturn($customer, $v, $retailId, 'pcs', 100);

        $response = $this->post("/customerProductReturns/{$record->return_id}/accept");
        $response->assertOk();

        $this->assertSame(100, $this->currentStock($v, 'pcs', $retailId), 'gudang eceran tidak boleh menggulung sama sekali');
        $this->assertDatabaseMissing('product_stocks', [
            'product_variant_id' => $v->product_variant_id,
            'warehouse_id' => $retailId,
            'unit_id' => $this->units['dos']->unit_id,
            'status' => 1,
        ]);
    }
}
