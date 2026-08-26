<?php

namespace Tests\Regression;

use App\Models\Category;
use App\Models\LogStock;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\ProductVariant;
use App\Models\StockOpname;
use App\Models\StockOpnameDetail;
use App\Models\Unit;
use App\Support\StockOpnameUntouchedUnitHealer;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GitHub #78 follow-up: documents created BEFORE the fix shipped can still carry the old
 * defaulted-to-system-qty numbers baked into stod_real/stod_selisih as plain numbers, not the "-"
 * token. StockOpnameUntouchedUnitHealer is the one-time repair tool for that (see its docblock for
 * the full two-tier rationale). This locks down its classification logic against a controlled
 * fixture covering all four outcomes, independently of the messy real production/local data it was
 * validated against manually.
 */
class StockOpnameUntouchedUnitHealerTest extends TestCase
{
    private function makeFixture(): array
    {
        $units = Unit::where('status', 1)->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $units->count());
        [$dosUnit, $pcsUnit] = $units->all();

        $category = new Category();
        $category->category_name = 'Healer Test Category';
        $category->status = 1;
        $category->save();

        $product = new Product();
        $product->product_name = 'Healer Test Product '.uniqid();
        $product->category_id = $category->category_id;
        $product->product_unit = json_encode([$dosUnit->unit_id, $pcsUnit->unit_id]);
        $product->unit_id = $dosUnit->unit_id;
        $product->status = 1;
        $product->save();

        $variant = new ProductVariant();
        $variant->product_id = $product->product_id;
        $variant->product_variant_name = 'Healer Test Variant';
        $variant->product_variant_sku = 'WF-HEALER-'.uniqid();
        $variant->product_variant_price = 0;
        $variant->status = 1;
        $variant->save();

        foreach ([$dosUnit->unit_id => 10, $pcsUnit->unit_id => 5] as $unitId => $stock) {
            $ps = new ProductStock();
            $ps->product_id = $product->product_id;
            $ps->product_variant_id = $variant->product_variant_id;
            $ps->unit_id = $unitId;
            $ps->warehouse_id = 1;
            $ps->ps_stock = $stock;
            $ps->status = 1;
            $ps->save();
        }

        return [$product, $variant, $dosUnit, $pcsUnit];
    }

    /** Simulasikan riwayat log_stocks: satu entry log_category=1 ("setelah") di waktu tertentu. */
    private function logSystemAt(int $productVariantId, int $unitId, int $qty, string $at): void
    {
        $log = new LogStock();
        $log->log_date = $at;
        $log->log_kode = 'TESTFIX';
        $log->log_type = 1;
        $log->log_category = 1;
        $log->log_item_id = $productVariantId;
        $log->log_notes = 'Stock Opname Produk';
        $log->log_jumlah = $qty;
        $log->unit_id = $unitId;
        $log->status = 1;
        $log->save();
        $log->created_at = $at;
        $log->updated_at = $at;
        $log->timestamps = false;
        $log->save();
    }

    private function insertDetail(int $stoId, int $productId, int $variantId, string $real, string $selisih, bool $touched): StockOpnameDetail
    {
        $d = new StockOpnameDetail();
        $d->sto_id = $stoId;
        $d->product_id = $productId;
        $d->product_variant_id = $variantId;
        $d->stod_system = 'placeholder';
        $d->stod_real = $real;
        $d->stod_selisih = $selisih;
        $d->stod_touched = $touched;
        $d->status = 1;
        $d->save();
        return $d;
    }

    public function test_classifies_all_four_outcomes_correctly_in_dry_run_and_apply(): void
    {
        [$product, $variant, $dosUnit, $pcsUnit] = $this->makeFixture();
        $dosName = $dosUnit->unit_short_name;
        $pcsName = $pcsUnit->unit_short_name;

        $sto = new StockOpname();
        $sto->sto_date = now()->toDateString();
        $sto->sto_code = 'TF'.str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $sto->staff_id = DB::table('staffs')->where('status', 1)->value('staff_id');
        $sto->category_id = -1;
        $sto->sto_notes = 'Healer test';
        $sto->status = 1;
        $sto->save();
        $createdAt = '2026-01-10 10:00:00';
        $sto->created_at = $createdAt;
        $sto->timestamps = false;
        $sto->save();

        // Row A: fully untouched -- must ALWAYS convert (TIER1), no log history needed at all.
        $rowA = $this->insertDetail($sto->sto_id, $product->product_id, $variant->product_variant_id, '10 '.$dosName.', 5 '.$pcsName, '10 '.$dosName.', 5 '.$pcsName, touched: false);

        // Row B: touched, DOS genuinely counted (differs from reconstructed system-at-creation),
        // pcs left blank and silently defaulted (matches reconstructed system-at-creation exactly).
        $this->logSystemAt($variant->product_variant_id, $dosUnit->unit_id, 10, '2026-01-09 08:00:00');
        $this->logSystemAt($variant->product_variant_id, $pcsUnit->unit_id, 5, '2026-01-09 08:00:00');
        $rowB = $this->insertDetail($sto->sto_id, $product->product_id, $variant->product_variant_id, '8 '.$dosName.', 5 '.$pcsName, '-2 '.$dosName.', 0 '.$pcsName, touched: true);

        // Row C: touched, but there's zero log history before creation for either unit -- must be
        // left completely alone (UNRESOLVED), never guessed.
        $variant2 = new ProductVariant();
        $variant2->product_id = $product->product_id;
        $variant2->product_variant_name = 'Healer Test Variant No History';
        $variant2->product_variant_sku = 'WF-HEALER-NOHIST-'.uniqid();
        $variant2->product_variant_price = 0;
        $variant2->status = 1;
        $variant2->save();
        $ps = new ProductStock();
        $ps->product_id = $product->product_id;
        $ps->product_variant_id = $variant2->product_variant_id;
        $ps->unit_id = $dosUnit->unit_id;
        $ps->warehouse_id = 1;
        $ps->ps_stock = 7;
        $ps->status = 1;
        $ps->save();
        $rowC = $this->insertDetail($sto->sto_id, $product->product_id, $variant2->product_variant_id, '7 '.$dosName, '0 '.$dosName, touched: true);

        // Row D: already-fixed unit (literal "-") must simply be skipped, not touched again.
        $rowD = $this->insertDetail($sto->sto_id, $product->product_id, $variant->product_variant_id, '- '.$dosName, '- '.$dosName, touched: true);

        $healer = new StockOpnameUntouchedUnitHealer();

        // --- Dry run: nothing written, but the report classifies everything correctly.
        $dryResult = $healer->healProduct($sto->sto_id, apply: false);
        $byRow = collect($dryResult['report'])->groupBy('detail_id');

        $this->assertTrue($byRow[$rowA->stod_id]->every(fn ($r) => $r['status'] === 'TIER1_UNTOUCHED_ROW'));

        $rowBByUnit = $byRow[$rowB->stod_id]->keyBy('unit');
        $this->assertSame('KEPT_GENUINE', $rowBByUnit[$dosName]['status']);
        $this->assertSame(10, $rowBByUnit[$dosName]['reconstructed_system_at_creation']);
        $this->assertSame('TIER2_CONVERTED', $rowBByUnit[$pcsName]['status']);
        $this->assertSame(5, $rowBByUnit[$pcsName]['reconstructed_system_at_creation']);

        $this->assertTrue($byRow[$rowC->stod_id]->every(fn ($r) => $r['status'] === 'UNRESOLVED_NO_HISTORY'));

        $this->assertArrayNotHasKey($rowD->stod_id, $byRow->toArray(), 'an already-"-" unit must not appear in the report at all');

        // Dry run must not have written anything.
        $this->assertDatabaseHas('stock_opname_details', ['stod_id' => $rowA->stod_id, 'stod_real' => '10 '.$dosName.', 5 '.$pcsName]);
        $this->assertDatabaseHas('stock_opname_details', ['stod_id' => $rowB->stod_id, 'stod_real' => '8 '.$dosName.', 5 '.$pcsName]);

        // --- --sql mode: for a host with no artisan/DB access from the app, the same dry-run
        // result must yield copy-pasteable UPDATE statements for exactly the changed rows.
        $sqlStatements = $healer->toSql($dryResult['updates']);
        $sqlByRow = [];
        foreach ($sqlStatements as $stmt) {
            if (str_contains($stmt, 'stod_id = '.$rowA->stod_id.';')) $sqlByRow['A'] = $stmt;
            if (str_contains($stmt, 'stod_id = '.$rowB->stod_id.';')) $sqlByRow['B'] = $stmt;
            if (str_contains($stmt, 'stod_id = '.$rowC->stod_id.';')) $sqlByRow['C'] = $stmt;
        }
        $this->assertArrayHasKey('A', $sqlByRow, 'row A (fully untouched) must produce an UPDATE');
        $this->assertStringContainsString('stock_opname_details', $sqlByRow['A']);
        $this->assertStringContainsString("stod_real = '- {$dosName}, - {$pcsName}'", $sqlByRow['A']);
        $this->assertArrayHasKey('B', $sqlByRow, 'row B (partially phantom) must produce an UPDATE');
        $this->assertStringContainsString("stod_real = '8 {$dosName}, - {$pcsName}'", $sqlByRow['B']);
        $this->assertArrayNotHasKey('C', $sqlByRow, 'row C (unresolved) must NOT produce an UPDATE');

        // --sql analysis itself must not have written anything either.
        $this->assertDatabaseHas('stock_opname_details', ['stod_id' => $rowA->stod_id, 'stod_real' => '10 '.$dosName.', 5 '.$pcsName]);

        // --- Apply: only TIER1/TIER2 units actually change.
        $healer->healProduct($sto->sto_id, apply: true);

        $this->assertDatabaseHas('stock_opname_details', [
            'stod_id' => $rowA->stod_id,
            'stod_real' => '- '.$dosName.', - '.$pcsName,
            'stod_selisih' => '- '.$dosName.', - '.$pcsName,
        ]);
        $this->assertDatabaseHas('stock_opname_details', [
            'stod_id' => $rowB->stod_id,
            'stod_real' => '8 '.$dosName.', - '.$pcsName,
        ]);
        $this->assertDatabaseHas('stock_opname_details', [
            'stod_id' => $rowC->stod_id,
            'stod_real' => '7 '.$dosName,
            'stod_selisih' => '0 '.$dosName,
        ]);
        $this->assertDatabaseHas('stock_opname_details', [
            'stod_id' => $rowD->stod_id,
            'stod_real' => '- '.$dosName,
        ]);

        // Never touches live stock or the header.
        $this->assertDatabaseHas('product_stocks', ['product_variant_id' => $variant->product_variant_id, 'unit_id' => $dosUnit->unit_id, 'ps_stock' => 10]);
        $this->assertDatabaseHas('product_stocks', ['product_variant_id' => $variant->product_variant_id, 'unit_id' => $pcsUnit->unit_id, 'ps_stock' => 5]);
        $this->assertDatabaseHas('stock_opnames', ['sto_id' => $sto->sto_id, 'status' => 1]);
    }
}
