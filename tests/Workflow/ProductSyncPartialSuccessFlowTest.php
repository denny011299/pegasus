<?php

namespace Tests\Workflow;

use App\Synchronization\Flows\ProductSyncFlow;
use App\Synchronization\SyncRunner;
use App\Synchronization\SyncStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * End-to-end coverage for GitHub #184's partial-sync behavior: a PMO catalog where SOME products
 * reference a unit that doesn't exist anywhere (the real-world case that triggered this — see
 * cdocs/integrations/202607260130-product-sync-flow.design.md §8.1's addendum, unit
 * 9506012026014611 referenced by 202/292 products but present in no product's own units[] list)
 * must let the operator continue to "Sinkronisasi Varian Produk" using the products that DID sync,
 * instead of the whole flow being stuck until every single failing product is fixed on PMO's side.
 */
class ProductSyncPartialSuccessFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'synchronization.pmo.base_url' => 'https://pmo.test',
            'synchronization.pmo.api_key' => 'test-key',
        ]);

        Http::fake([
            'pmo.test/getUnits*' => Http::response([
                'pagination' => ['total' => 1, 'page' => 1, 'limit' => 10, 'total_pages' => 1],
                'data' => [
                    ['unit_id' => 501, 'unit_name' => 'Pcs Test', 'unit_short_name' => 'Pcs', 'is_active' => 1],
                ],
            ], 200),
            'pmo.test/getProducts*' => Http::response([
                'pagination' => ['total' => 2, 'page' => 1, 'limit' => 10, 'total_pages' => 1],
                'items' => [
                    [
                        'ref_product_id' => 111,
                        'product_name' => 'Produk Sukses',
                        'category_name' => 'Kategori Test',
                        'default_unit_id' => 501,
                        'units' => [['unit_id' => 501, 'unit_name' => 'Pcs Test']],
                        'variant' => [],
                        'is_active' => 1,
                    ],
                    [
                        'ref_product_id' => 222,
                        'product_name' => 'Produk Satuan Hantu',
                        'category_name' => 'Kategori Test',
                        // References a unit id that never appears in ANY product's units[] and
                        // that /getUnits doesn't have either — mirrors the real PMO data gap.
                        'default_unit_id' => 999999,
                        'units' => [['unit_id' => 501, 'unit_name' => 'Pcs Test']],
                        'variant' => [],
                        'is_active' => 1,
                    ],
                ],
            ], 200),
        ]);
    }

    public function test_a_partially_failed_product_step_unblocks_the_next_step(): void
    {
        $flow = new ProductSyncFlow();
        $runner = app(SyncRunner::class);

        $fetchExecution = $runner->run($flow, $flow->findStep('fetch'));
        $this->assertSame(SyncStatus::SUCCESS, $fetchExecution->status, (string) $fetchExecution->message);

        $unitExecution = $runner->run($flow, $flow->findStep('unit'));
        $this->assertSame(SyncStatus::SUCCESS, $unitExecution->status, (string) $unitExecution->message);

        $categoryExecution = $runner->run($flow, $flow->findStep('category'));
        $this->assertSame(SyncStatus::SUCCESS, $categoryExecution->status, (string) $categoryExecution->message);

        $productExecution = $runner->run($flow, $flow->findStep('product'));
        $this->assertSame(
            SyncStatus::PARTIAL,
            $productExecution->status,
            'one product references a unit that exists nowhere, the other is fine — this must be PARTIAL, not FAILED'
        );
        $this->assertSame(1, $productExecution->total_inserted);
        $this->assertSame(1, $productExecution->total_failed);

        $this->assertNotNull(DB::table('products')->where('ref_product_id', 111)->first(), 'the succeeding product must actually be written');
        $this->assertNull(DB::table('products')->where('ref_product_id', 222)->first(), 'the failing product must NOT be written');

        // The real point of GitHub #184: this must NOT throw PrerequisiteNotMetException even
        // though 'product' is PARTIAL rather than SUCCESS.
        $variantExecution = $runner->run($flow, $flow->findStep('product_variant'));
        $this->assertNotSame(SyncStatus::NOT_EXECUTED, $variantExecution->status);
    }
}
