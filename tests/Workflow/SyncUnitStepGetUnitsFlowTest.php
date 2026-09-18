<?php

namespace Tests\Workflow;

use App\Models\SyncSnapshot;
use App\Synchronization\Steps\ProductFlow\SyncUnitStep;
use App\Synchronization\SyncStatus;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SyncUnitStep (GitHub #184) — PMO's own /getUnits is now the primary source for "Sinkronisasi
 * Satuan", with the pre-existing items[].units[] aggregation from /getProducts kept as a fallback
 * for whenever /getUnits fails (4xx/5xx, unreachable, malformed payload). See the class docblock
 * and cdocs/integrations/202607260130-product-sync-flow.design.md §8.1.
 */
class SyncUnitStepGetUnitsFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'synchronization.pmo.base_url' => 'https://pmo.test',
            'synchronization.pmo.api_key' => 'test-key',
        ]);
    }

    private function fakeProductsResponse(): array
    {
        return [
            'pagination' => ['total' => 1, 'page' => 1, 'limit' => 10, 'total_pages' => 1],
            'items' => [
                [
                    'ref_product_id' => 111,
                    'product_name' => 'Fallback Source Product',
                    'category_name' => 'Kategori Test',
                    'default_unit_id' => 501,
                    'units' => [
                        ['unit_id' => 501, 'unit_name' => 'Dari GetProducts'],
                    ],
                    'variant' => [],
                    'is_active' => 1,
                ],
            ],
        ];
    }

    public function test_getunits_is_used_as_the_primary_source_when_it_succeeds(): void
    {
        Http::fake([
            'pmo.test/getUnits*' => Http::response([
                'pagination' => ['total' => 1, 'page' => 1, 'limit' => 10, 'total_pages' => 1],
                'data' => [
                    ['unit_id' => 501, 'unit_name' => 'Dari GetUnits', 'unit_short_name' => 'DGU', 'is_active' => 1],
                ],
            ], 200),
            'pmo.test/getProducts*' => Http::response($this->fakeProductsResponse(), 200),
        ]);

        $result = app(SyncUnitStep::class)->handle();

        $this->assertSame(SyncStatus::SUCCESS, $result->status);
        $this->assertSame([], $result->notices, 'no fallback notice expected when /getUnits succeeds');

        $unit = DB::table('units')->where('ref_unit_id', 501)->first();
        $this->assertNotNull($unit);
        $this->assertSame('Dari GetUnits', $unit->unit_name);
        $this->assertSame('DGU', $unit->unit_short_name, 'unit_short_name must come from /getUnits when it succeeds, not be left blank');

        Http::assertNotSent(function ($request) {
            return str_contains((string) $request->url(), 'getProducts');
        });
    }

    public function test_falls_back_to_getproducts_derived_units_when_getunits_fails(): void
    {
        Http::fake([
            'pmo.test/getUnits*' => Http::response(['error' => 'server error'], 500),
            'pmo.test/getProducts*' => Http::response($this->fakeProductsResponse(), 200),
        ]);

        $result = app(SyncUnitStep::class)->handle();

        $this->assertSame(SyncStatus::SUCCESS, $result->status);
        $this->assertNotEmpty($result->notices, 'a fallback must be reported as a notice, not happen silently');
        $this->assertStringContainsString('/getUnits gagal', $result->notices[0]);

        $unit = DB::table('units')->where('ref_unit_id', 501)->first();
        $this->assertNotNull($unit);
        $this->assertSame('Dari GetProducts', $unit->unit_name);
    }

    public function test_falls_back_when_getunits_is_unreachable(): void
    {
        Http::fake([
            'pmo.test/getUnits*' => function () {
                throw new ConnectionException('connection refused');
            },
            'pmo.test/getProducts*' => Http::response($this->fakeProductsResponse(), 200),
        ]);

        $result = app(SyncUnitStep::class)->handle();

        $this->assertSame(SyncStatus::SUCCESS, $result->status);
        $this->assertNotEmpty($result->notices);

        $this->assertNotNull(DB::table('units')->where('ref_unit_id', 501)->first());
    }

    public function test_fallback_notice_is_persisted_on_the_sync_execution_record(): void
    {
        Http::fake([
            'pmo.test/getUnits*' => Http::response([], 404),
            'pmo.test/getProducts*' => Http::response($this->fakeProductsResponse(), 200),
        ]);

        // Seed the shared snapshot exactly like the real "fetch" step would, so this step's own
        // fallback call to ProductFlowStep::units() finds it already there instead of refetching.
        $this->assertSame(0, SyncSnapshot::count());

        app(SyncUnitStep::class)->handle();

        $this->assertGreaterThan(0, SyncSnapshot::where('flow_key', 'product')->where('endpoint_key', 'products')->count());
    }
}
