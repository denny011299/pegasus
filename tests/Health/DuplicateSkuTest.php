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
     * Confirmed 2026-08-01 against the seeded snapshot: two SKUs are
     * currently shared across genuinely different products. This is a real
     * data-quality issue (not a code bug — nothing here decides which
     * product's SKU is "wrong"), documented in cdocs/testing/KNOWN_ISSUES.md
     * and deliberately kept in this allow-list rather than silently ignored.
     */
    private const KNOWN_DUPLICATE_PRODUCT_SKUS = ['MRP300P', 'SOHP'];

    public function test_no_new_product_variant_sku_spans_multiple_products(): void
    {
        $violations = DB::table('product_variants')
            ->where('status', 1)
            ->whereNotNull('product_variant_sku')
            ->where('product_variant_sku', '<>', '')
            ->select('product_variant_sku')
            ->selectRaw('COUNT(DISTINCT product_id) as product_count')
            ->groupBy('product_variant_sku')
            ->havingRaw('COUNT(DISTINCT product_id) > 1')
            ->pluck('product_variant_sku')
            ->all();

        $newViolations = array_values(array_diff($violations, self::KNOWN_DUPLICATE_PRODUCT_SKUS));

        $this->assertSame(
            [],
            $newViolations,
            'New product_variant_sku value(s) span multiple products: '.implode(', ', $newViolations)
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
