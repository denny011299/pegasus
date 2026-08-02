<?php

namespace Tests\Health;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * "No duplicate SKU" needs one nuance in this codebase: supplies_variants
 * has a supplier_id, so the SAME raw material legitimately gets one
 * supplies_variant row per supplier — and in the seeded data, those sibling
 * rows share the same supplies_variant_sku (e.g. "JRG30LTR" exists twice,
 * same supplies_id, two different supplier_id). That's expected, not a bug.
 * The real invariant is narrower: a SKU must not span more than one distinct
 * *base* item (product_id / supplies_id) — that would mean a barcode scan
 * can't tell which item it is.
 */
class DuplicateSkuTest extends TestCase
{
    /**
     * Confirmed 2026-08-01 against the seeded snapshot: two SKUs (MRP300P, SOHP) were shared
     * across genuinely different products. Fixed 2026-08-03 — MRP300P30 (product 66) and
     * SOHPPAIL (product 42) disambiguate them, and ProductController::validateVariantUniqueness()
     * now rejects any new collision at insert/update time (see
     * tests/Regression/ProductVariantUniquenessGuardTest.php). This allow-list stays empty going
     * forward — a non-empty result here means a NEW collision slipped past that guard.
     */
    private const KNOWN_DUPLICATE_PRODUCT_SKUS = [];

    /**
     * Since 2026-08-03 this checks true global uniqueness among active variants (any two
     * active variants sharing a SKU, whether on the same product or different ones) — not just
     * "spans multiple products" — matching what ProductController::validateVariantUniqueness()
     * now enforces going forward on every insert/update.
     */
    public function test_no_two_active_product_variants_share_a_sku(): void
    {
        $violations = DB::table('product_variants')
            ->where('status', 1)
            ->whereNotNull('product_variant_sku')
            ->where('product_variant_sku', '<>', '')
            ->select('product_variant_sku')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('product_variant_sku')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('product_variant_sku')
            ->all();

        $newViolations = array_values(array_diff($violations, self::KNOWN_DUPLICATE_PRODUCT_SKUS));

        $this->assertSame(
            [],
            $newViolations,
            'Product variant SKU(s) shared by more than one active variant: '.implode(', ', $newViolations)
        );
    }

    /**
     * Companion invariant added 2026-08-03 alongside the SKU fix: two active variants of the
     * SAME product should never share a name (different products reusing a name, e.g. "Merah",
     * is fine and not checked here — see cdocs/testing/KNOWN_ISSUES.md).
     */
    public function test_no_two_active_variants_of_the_same_product_share_a_name(): void
    {
        $violations = DB::table('product_variants')
            ->where('status', 1)
            ->whereNotNull('product_variant_name')
            ->where('product_variant_name', '<>', '')
            ->select('product_id', 'product_variant_name')
            ->selectRaw('COUNT(*) as cnt')
            ->groupBy('product_id', 'product_variant_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $this->assertCount(
            0,
            $violations,
            'Product variant name(s) shared by more than one active variant of the same product: '
                . $violations->map(fn ($v) => "product_id={$v->product_id} name={$v->product_variant_name}")->implode(', ')
        );
    }

    /**
     * Same-supplies_id duplicates (multi-supplier sourcing) are expected and
     * excluded by design — this only flags a SKU shared across *different*
     * base supplies_id, which has zero known occurrences today.
     */
    public function test_no_supplies_variant_sku_spans_multiple_supplies(): void
    {
        $violations = DB::table('supplies_variants')
            ->where('status', 1)
            ->whereNotNull('supplies_variant_sku')
            ->where('supplies_variant_sku', '<>', '')
            ->select('supplies_variant_sku')
            ->selectRaw('COUNT(DISTINCT supplies_id) as supplies_count')
            ->groupBy('supplies_variant_sku')
            ->havingRaw('COUNT(DISTINCT supplies_id) > 1')
            ->pluck('supplies_variant_sku')
            ->all();

        $this->assertSame(
            [],
            $violations,
            'Supplies SKU(s) span multiple distinct supplies_id: '.implode(', ', $violations)
        );
    }
}
