<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Browser-triggerable runner for GitHub #78's heal tool (App\Http\Controllers\
 * StockOpnameHealController), for a host with neither artisan/SSH access nor direct DB access the
 * team is willing to use. Mirrors DeployControllerTest's guard/confirm coverage (fase2/main,
 * App\Http\Controllers\DeployController — that whole module isn't merged into main; this
 * controller reproduces just its security pattern) plus the healer's own classification/apply
 * behaviour, exercised through the real HTTP routes this time.
 */
class StockOpnameHealControllerTest extends TestCase
{
    private const VALID_TOKEN = 'test-deploy-token-1234567890-abcdef';

    protected function tearDown(): void
    {
        putenv('DEPLOY_TOKEN');
        unset($_ENV['DEPLOY_TOKEN'], $_SERVER['DEPLOY_TOKEN']);
        parent::tearDown();
    }

    private function setDeployToken(string $token): void
    {
        putenv('DEPLOY_TOKEN='.$token);
        $_ENV['DEPLOY_TOKEN'] = $token;
        $_SERVER['DEPLOY_TOKEN'] = $token;
    }

    public function test_console_403s_when_no_token_is_configured(): void
    {
        putenv('DEPLOY_TOKEN');
        unset($_ENV['DEPLOY_TOKEN'], $_SERVER['DEPLOY_TOKEN']);

        $this->get('/deploy/heal-stock-opname?token=anything')
            ->assertStatus(403)
            ->assertSee('DEPLOY_TOKEN belum diset', false);
    }

    public function test_console_403s_with_the_wrong_token(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get('/deploy/heal-stock-opname?token=wrong-token')
            ->assertStatus(403)
            ->assertSee('Token deploy salah', false);
    }

    public function test_console_403s_with_no_token_at_all(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get('/deploy/heal-stock-opname')
            ->assertStatus(403)
            ->assertSee('Token deploy tidak dikirim', false);
    }

    public function test_console_succeeds_with_the_correct_token(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get('/deploy/heal-stock-opname?token='.self::VALID_TOKEN)->assertStatus(200);
    }

    public function test_a_deploy_token_shorter_than_24_characters_is_treated_as_unset(): void
    {
        $this->setDeployToken('short-token-15c');

        $this->get('/deploy/heal-stock-opname?token=short-token-15c')->assertStatus(403);
    }

    public function test_apply_is_post_only_and_rejects_a_plain_get(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get('/deploy/heal-stock-opname/apply?token='.self::VALID_TOKEN.'&id=1')->assertStatus(405);
    }

    public function test_apply_is_blocked_without_the_token_even_with_the_correct_confirm_phrase(): void
    {
        $this->post('/deploy/heal-stock-opname/apply', ['id' => 1, 'confirm' => 'HEAL'])
            ->assertStatus(403);
    }

    public function test_apply_is_blocked_without_the_confirm_phrase_even_with_a_valid_token(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->post('/deploy/heal-stock-opname/apply?token='.self::VALID_TOKEN, ['id' => 1])
            ->assertStatus(403);
    }

    public function test_apply_is_blocked_by_a_wrong_confirm_phrase(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->post('/deploy/heal-stock-opname/apply?token='.self::VALID_TOKEN, ['id' => 1, 'confirm' => 'heal'])
            ->assertStatus(403);
    }

    /** @return array{0: StockOpname, 1: ProductVariant, 2: Unit, 3: Unit} [sto, variant, dosUnit, pcsUnit] */
    private function makeCorruptedFixture(): array
    {
        $units = Unit::where('status', 1)->limit(2)->get();
        [$dosUnit, $pcsUnit] = $units->all();

        $category = new Category();
        $category->category_name = 'Heal Controller Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Heal Controller Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$dosUnit->unit_id, $pcsUnit->unit_id]);
        $product->unit_id = $dosUnit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Heal Controller Test Variant';
        $variant->product_variant_sku = 'WF-HEALCTRL-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        $ps = new ProductStock();
        $ps->product_id = $product->product_id;
        $ps->product_variant_id = $variant->product_variant_id;
        $ps->unit_id = $dosUnit->unit_id;
        $ps->warehouse_id = 1;
        $ps->ps_stock = 10;
        $ps->status = 1;
        $ps->save();

        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'HC'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $sto->staff_id = DB::table('staffs')->where('status', 1)->value('staff_id');
        $sto->category_id = -1;
        $sto->sto_notes = 'Heal controller test';
        $sto->status = 1;
        $sto->save();

        // Fully untouched row (TIER 1) -- unambiguous, always converts.
        $detail = new StockOpnameDetail();
        $detail->sto_id = $sto->sto_id;
        $detail->product_id = $product->product_id;
        $detail->product_variant_id = $variant->product_variant_id;
        $detail->stod_system = '10 '.$dosUnit->unit_short_name;
        $detail->stod_real = '10 '.$dosUnit->unit_short_name;
        $detail->stod_selisih = '10 '.$dosUnit->unit_short_name;
        $detail->stod_touched = false;
        $detail->status = 1;
        $detail->save();

        return [$sto, $variant, $dosUnit, $pcsUnit];
    }

    public function test_preview_classifies_but_writes_nothing(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        [$sto, , $dosUnit] = $this->makeCorruptedFixture();

        $response = $this->get('/deploy/heal-stock-opname/preview?token='.self::VALID_TOKEN.'&id='.$sto->sto_id);
        $response->assertStatus(200);
        $response->assertSee('TIER1_UNTOUCHED_ROW');

        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $sto->sto_id,
            'stod_real' => '10 '.$dosUnit->unit_short_name,
        ]);
    }

    public function test_apply_actually_writes_once_token_and_confirm_both_match(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        [$sto, , $dosUnit] = $this->makeCorruptedFixture();

        $response = $this->post('/deploy/heal-stock-opname/apply?token='.self::VALID_TOKEN, [
            'id' => $sto->sto_id,
            'confirm' => 'HEAL',
        ]);
        $response->assertStatus(200);
        $response->assertSee('Selesai ditulis', false);

        $this->assertDatabaseHas('stock_opname_details', [
            'sto_id' => $sto->sto_id,
            'stod_real' => '- '.$dosUnit->unit_short_name,
            'stod_selisih' => '- '.$dosUnit->unit_short_name,
        ]);

        // Never touches live stock or the header.
        $this->assertDatabaseHas('stock_opnames', ['sto_id' => $sto->sto_id, 'status' => 1]);
    }

    public function test_unknown_id_is_reported_as_an_error_without_crashing(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get('/deploy/heal-stock-opname/preview?token='.self::VALID_TOKEN.'&id=999999999')
            ->assertStatus(200)
            ->assertSee('tidak ditemukan', false);
    }
}
