<?php

namespace Tests\Workflow;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * GitHub #53 follow-up, reported by the user after the PDF highlight fix shipped: reprinting an
 * OLD Stock Opname (created before 2026-08-14) showed no highlight at all, even for rows with a
 * genuine selisih. Root cause: stod_touched/stobd_touched (added by
 * 2026_08_14_010000_add_touched_to_stock_opname_details_tables) were added with a blanket
 * default(false) and never backfilled -- every pre-existing row is stuck "untouched", and the PDF
 * (Backoffice/PDF/Opname.blade.php + OpnameBahan.blade.php) renders untouched exactly like an
 * exact match: no color either way.
 *
 * Confirmed empirically against the live `pegasus` DB 2026-08-21: 227 stock_opname_details rows
 * and 820 stock_opname_detail_bahans rows had stod_real/stobd_real != stod_system/stobd_system
 * (proof of real human input -- CreateStockOpname.js/CreateStockOpnameSupplies.js always default
 * real = system when the field is left blank) but were still touched=0.
 *
 * See database/migrations/2026_08_21_010000_backfill_stod_touched_from_real_vs_system.php for the
 * one-directional backfill and why the real==system case (staff typed a matching value vs. left
 * it blank) is deliberately NOT touched -- that distinction is unrecoverable for pre-existing rows.
 */
class StockOpnameTouchedBackfillTest extends TestCase
{
    private function runBackfill(): void
    {
        $migration = require database_path('migrations/2026_08_21_010000_backfill_stod_touched_from_real_vs_system.php');
        $migration->up();
    }

    public function test_mismatched_legacy_row_gets_backfilled_to_touched(): void
    {
        $id = DB::table('stock_opname_details')->insertGetId([
            'sto_id' => 1,
            'product_id' => 1,
            'product_variant_id' => 1,
            'stod_system' => '210 pcs',
            'stod_real' => '214 pcs',
            'stod_selisih' => '4 pcs',
            'stod_touched' => 0,
            'status' => 1,
        ]);

        $this->runBackfill();

        $this->assertSame(1, (int) DB::table('stock_opname_details')->where('stod_id', $id)->value('stod_touched'));
    }

    public function test_exact_match_legacy_row_stays_untouched_because_it_is_unrecoverably_ambiguous(): void
    {
        $id = DB::table('stock_opname_details')->insertGetId([
            'sto_id' => 1,
            'product_id' => 1,
            'product_variant_id' => 1,
            'stod_system' => '210 pcs',
            'stod_real' => '210 pcs',
            'stod_selisih' => '0 pcs',
            'stod_touched' => 0,
            'status' => 1,
        ]);

        $this->runBackfill();

        $this->assertSame(0, (int) DB::table('stock_opname_details')->where('stod_id', $id)->value('stod_touched'));
    }

    public function test_already_touched_row_is_left_alone(): void
    {
        $id = DB::table('stock_opname_details')->insertGetId([
            'sto_id' => 1,
            'product_id' => 1,
            'product_variant_id' => 1,
            'stod_system' => '210 pcs',
            'stod_real' => '214 pcs',
            'stod_selisih' => '4 pcs',
            'stod_touched' => 1,
            'status' => 1,
        ]);

        $this->runBackfill();

        $this->assertSame(1, (int) DB::table('stock_opname_details')->where('stod_id', $id)->value('stod_touched'));
    }

    public function test_bahan_mismatched_legacy_row_gets_backfilled_to_touched(): void
    {
        $id = DB::table('stock_opname_detail_bahans')->insertGetId([
            'stob_id' => 1,
            'supplies_id' => 1,
            'stobd_system' => '210 kg',
            'stobd_real' => '214 kg',
            'stobd_selisih' => '4 kg',
            'stobd_touched' => 0,
            'status' => 1,
        ]);

        $this->runBackfill();

        $this->assertSame(1, (int) DB::table('stock_opname_detail_bahans')->where('stobd_id', $id)->value('stobd_touched'));
    }

    public function test_bahan_exact_match_legacy_row_stays_untouched(): void
    {
        $id = DB::table('stock_opname_detail_bahans')->insertGetId([
            'stob_id' => 1,
            'supplies_id' => 1,
            'stobd_system' => '210 kg',
            'stobd_real' => '210 kg',
            'stobd_selisih' => '0 kg',
            'stobd_touched' => 0,
            'status' => 1,
        ]);

        $this->runBackfill();

        $this->assertSame(0, (int) DB::table('stock_opname_detail_bahans')->where('stobd_id', $id)->value('stobd_touched'));
    }
}
