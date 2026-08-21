<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Follow-up to GitHub #53's PDF highlight (see 2026_08_14_010000_add_touched_to_stock_opname_...
 * -tables): that migration added stod_touched/stobd_touched with a blanket default(false) but
 * never backfilled it, so EVERY row written before 2026-08-14 is stuck at "untouched" -- the PDF
 * (Backoffice/PDF/Opname.blade.php + OpnameBahan.blade.php) now silently shows NO highlight at
 * all for those documents, whether the row genuinely had a selisih (should be yellow) or matched
 * exactly (should be green), because both states render identically once touched=0.
 *
 * Confirmed against the live `pegasus` DB 2026-08-21: 227 stock_opname_details rows and 820
 * stock_opname_detail_bahans rows have a real genuine selisih (stod_real != stod_system) but are
 * still touched=0.
 *
 * Backfill is one-directional and only handles the deterministic half of the bug: if stod_real
 * differs from stod_system, that difference can ONLY exist because a staff member typed it --
 * CreateStockOpname.js/CreateStockOpnameSupplies.js always default real = system when the input
 * is left blank (see insertData()). So any mismatch is unambiguous proof of human input, safe to
 * mark touched=1.
 *
 * NOT fixed here (and not fixable): pre-2026-08-14 rows where real == system. Whether that was a
 * staff member typing the exact matching value (should be green) or leaving the field blank
 * (should stay unhighlighted) is genuinely unrecoverable -- both produce the identical stored
 * string, and the distinguishing signal (the client-side "was this input non-blank" flag) was
 * simply never captured before that migration existed. Left as untouched (the conservative
 * default); documents created/edited since 2026-08-14 already track this correctly going forward.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('stock_opname_details', 'stod_touched')) {
            DB::table('stock_opname_details')
                ->where('stod_touched', 0)
                ->whereColumn('stod_real', '<>', 'stod_system')
                ->update(['stod_touched' => 1]);
        }

        if (Schema::hasColumn('stock_opname_detail_bahans', 'stobd_touched')) {
            DB::table('stock_opname_detail_bahans')
                ->where('stobd_touched', 0)
                ->whereColumn('stobd_real', '<>', 'stobd_system')
                ->update(['stobd_touched' => 1]);
        }
    }

    public function down(): void
    {
        // Deliberately irreversible: we have no record of which touched=1 rows this backfill set
        // vs. which were already genuinely touched=1 before it ran, so there is nothing safe to
        // revert to. Rolling back would either no-op or destroy real data -- neither is correct.
    }
};
