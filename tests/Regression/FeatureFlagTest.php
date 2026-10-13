<?php

namespace Tests\Regression;

use App\Http\Controllers\ProductController;
use App\Models\Supplies;
use App\Support\Feature;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Flag off: route fitur 404, bahan baru tidak bisa jadi Trading walau FE mengirim kind=trading. */
class FeatureFlagTest extends TestCase
{
    public function test_route_behind_flag_is_404_when_off_and_ok_when_on(): void
    {
        Route::get('/__feature-probe', fn () => 'ok')->middleware('feature:production_planning');

        config(['features.production_planning' => false]);
        $this->get('/__feature-probe')->assertNotFound();

        config(['features.production_planning' => true]);
        $this->get('/__feature-probe')->assertOk();
    }

    public function test_unknown_flag_is_off_and_all_returns_booleans(): void
    {
        $this->assertFalse(Feature::on('flag_yang_tidak_ada'));
        config(['features.supplies_trading' => 1]);
        $this->assertSame(true, Feature::all()['supplies_trading']);
    }

    public function test_new_supplies_forced_to_supply_kind_when_trading_flag_off(): void
    {
        if (! Supplies::hasKindColumn()) {
            $this->markTestSkipped('kolom supplies_kind belum ada');
        }
        $sanitize = fn (array $d) => (fn () => $this->sanitizeSuppliesValues($d))->call(app(ProductController::class));
        $input = ['supplies_kind' => 'trading', 'trading_product_variant_id' => 99];

        config(['features.supplies_trading' => false]);
        $off = $sanitize($input);
        $this->assertSame(Supplies::KIND_SUPPLY, $off['supplies_kind']);
        $this->assertSame(0, (int) $off['trading_product_variant_id']);

        config(['features.supplies_trading' => true]);
        $this->assertSame(Supplies::KIND_TRADING, $sanitize($input)['supplies_kind']);
    }
}
