<?php

namespace Tests\Unit;

use App\Support\UnitRollUp;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic tests for `UnitRollUp::plan()` and `UnitRollUp::collapse()` — no DB, so this lives
 * in tests/Unit (the chain and the allowed-unit list are both passed in explicitly).
 *
 * Ladder used throughout: 1 Sak = 2 DOS, 1 DOS = 12 Piece (so 1 Sak = 24 Piece).
 *
 * collapse() is the primitive behind Stock Opname's "isi satuan kecil, satuan besar ikut naik"
 * behaviour (PM, 2026-08-27) — see App\Support\StockOpname\OpnameLifecycle::rollUpUnits().
 */
class UnitRollUpTest extends TestCase
{
    private const PIECE = 9;
    private const DOS = 7;
    private const SAK = 25;

    /** @return array<int, array{small:int, big:int, ratio:int}> */
    private function ladder(): array
    {
        return [
            ['small' => self::PIECE, 'big' => self::DOS, 'ratio' => 12],
            ['small' => self::DOS, 'big' => self::SAK, 'ratio' => 2],
        ];
    }

    private function allUnits(): array
    {
        return [self::PIECE, self::DOS, self::SAK];
    }

    public function test_an_exact_multiple_rolls_all_the_way_to_the_top(): void
    {
        // 24 Piece = 2 DOS = 1 Sak exactly, nothing left behind at any level.
        $plan = UnitRollUp::plan($this->ladder(), self::PIECE, 24, $this->allUnits());

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 0],
            ['unit_id' => self::DOS, 'qty' => 0],
            ['unit_id' => self::SAK, 'qty' => 1],
        ], $plan);
    }

    public function test_remainders_are_left_at_each_level(): void
    {
        // 31 Piece = 2 DOS + 7 Piece; 2 DOS = 1 Sak + 0 DOS.
        $plan = UnitRollUp::plan($this->ladder(), self::PIECE, 31, $this->allUnits());

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 7],
            ['unit_id' => self::DOS, 'qty' => 0],
            ['unit_id' => self::SAK, 'qty' => 1],
        ], $plan);
    }

    public function test_a_quantity_below_the_first_ratio_stays_put(): void
    {
        $plan = UnitRollUp::plan($this->ladder(), self::PIECE, 11, $this->allUnits());

        $this->assertSame([['unit_id' => self::PIECE, 'qty' => 11]], $plan);
    }

    public function test_rolling_can_start_partway_up_the_ladder(): void
    {
        // Receiving in DOS: 5 DOS = 2 Sak + 1 DOS. Piece is never involved.
        $plan = UnitRollUp::plan($this->ladder(), self::DOS, 5, $this->allUnits());

        $this->assertSame([
            ['unit_id' => self::DOS, 'qty' => 1],
            ['unit_id' => self::SAK, 'qty' => 2],
        ], $plan);
    }

    public function test_rolling_stops_at_a_unit_the_caller_disallows(): void
    {
        // Sak has no stock row → not in the allowed list. 24 Piece becomes 2 DOS and stops there
        // rather than silently provisioning a Sak row nobody asked for.
        $plan = UnitRollUp::plan($this->ladder(), self::PIECE, 24, [self::PIECE, self::DOS]);

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 0],
            ['unit_id' => self::DOS, 'qty' => 2],
        ], $plan);
    }

    public function test_a_missing_intermediate_unit_blocks_the_whole_roll_up(): void
    {
        // DOS row missing: Piece cannot skip straight to Sak (the ratio is defined per hop), so
        // everything stays at Piece. Leaving it flat is the only correct outcome here.
        $plan = UnitRollUp::plan($this->ladder(), self::PIECE, 24, [self::PIECE, self::SAK]);

        $this->assertSame([['unit_id' => self::PIECE, 'qty' => 24]], $plan);
    }

    public function test_a_product_with_no_ladder_at_all_is_left_untouched(): void
    {
        $plan = UnitRollUp::plan([], self::PIECE, 500, [self::PIECE]);

        $this->assertSame([['unit_id' => self::PIECE, 'qty' => 500]], $plan);
    }

    public function test_a_zero_or_negative_ratio_does_not_divide_by_zero(): void
    {
        $broken = [['small' => self::PIECE, 'big' => self::DOS, 'ratio' => 0]];

        $plan = UnitRollUp::plan($broken, self::PIECE, 24, $this->allUnits());

        $this->assertSame([['unit_id' => self::PIECE, 'qty' => 24]], $plan);
    }

    public function test_a_circular_chain_terminates_instead_of_looping_forever(): void
    {
        // Corrupt data: A -> B -> A. The 20-hop guard must stop this.
        $circular = [
            ['small' => self::PIECE, 'big' => self::DOS, 'ratio' => 2],
            ['small' => self::DOS, 'big' => self::PIECE, 'ratio' => 2],
        ];

        $plan = UnitRollUp::plan($circular, self::PIECE, 1048576, $this->allUnits());

        // Exactly one entry per hop, plus the final resting level — bounded, not infinite.
        $this->assertLessThanOrEqual(21, count($plan));
    }

    // ================================================================== collapse()

    /**
     * Rantai 2-tingkat terpisah dari ladder() (yang punya 3 tingkat) supaya contoh literal PM ini
     * teruji byte-per-byte tanpa kena gulungan tambahan ke SAK -- 2 DOS pada ladder() penuh
     * kebetulan sama persis dengan 1 SAK (ratio DOS->SAK = 2), jadi tetap "benar secara rekursif"
     * tapi bukan lagi angka yang PM sebutkan. Lihat test_collapse_rolls_recursively_through_...
     * untuk pembuktian bahwa gulungan memang lanjut menembus SAK saat itu memang tingkat yang ada.
     */
    private function twoLevelLadder(): array
    {
        return [['small' => self::PIECE, 'big' => self::DOS, 'ratio' => 12]];
    }

    /** Contoh persis dari PM: 1 DOS = 12 pcs, isi 30 pcs saja -> 2 DOS + 6 pcs. */
    public function test_collapse_matches_the_pm_reported_example_exactly(): void
    {
        $result = UnitRollUp::collapse($this->twoLevelLadder(), [self::PIECE => 30, self::DOS => null], [self::PIECE, self::DOS]);

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 6],
            ['unit_id' => self::DOS, 'qty' => 2],
        ], $result);
    }

    /** Satuan kecil yang diisi rolls ke satuan besar yang belum disentuh, dan BERHENTI di situ
     * kalau hasilnya belum cukup untuk naik lagi (1 DOS < ratio SAK=2). */
    public function test_collapse_rolls_a_single_filled_small_unit_up_into_an_untouched_big_one(): void
    {
        $result = UnitRollUp::collapse($this->ladder(), [self::PIECE => 18, self::DOS => null], $this->allUnits());

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 6],
            ['unit_id' => self::DOS, 'qty' => 1],
        ], $result);
    }

    /** "Rekursif": pcs -> DOS -> SAK, tiga tingkat sekaligus dari satu input di dasar tangga. */
    public function test_collapse_rolls_recursively_through_every_level_of_the_ladder(): void
    {
        // 55 pcs = 4 DOS + 7 pcs; 4 DOS < 2/SAK ratio? ratio SAK=2, jadi 4 DOS -> 2 SAK + 0 DOS.
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 55, self::DOS => null, self::SAK => null],
            $this->allUnits()
        );

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 7],
            ['unit_id' => self::DOS, 'qty' => 0],
            ['unit_id' => self::SAK, 'qty' => 2],
        ], $result);
    }

    /**
     * Setara GitHub #78: satuan BESAR yang diisi (bahkan diisi 0) TIDAK BOLEH membuat satuan
     * KECIL yang tidak pernah disentuh ikut "disimpulkan" 0 -- itu mengarang data yang tidak
     * pernah diperiksa staf. Titik mulai gulung harus satuan TERKECIL yang diisi, bukan dasar
     * tangga.
     */
    public function test_collapse_never_infers_a_value_for_a_smaller_unit_than_the_one_actually_filled(): void
    {
        $result = UnitRollUp::collapse($this->ladder(), [self::DOS => 0, self::PIECE => null], $this->allUnits());

        $this->assertSame([['unit_id' => self::DOS, 'qty' => 0]], $result);
    }

    /** Satuan yang tidak pernah diisi sama sekali tidak boleh muncul di hasil -- tetap NULL. */
    public function test_collapse_returns_nothing_when_nothing_was_ever_filled(): void
    {
        $result = UnitRollUp::collapse($this->ladder(), [self::PIECE => null, self::DOS => null], $this->allUnits());

        $this->assertSame([], $result);
    }

    /** Dua satuan diisi sekaligus (bukan cuma satu) harus digabung, bukan ditimpa. */
    public function test_collapse_combines_two_independently_filled_units_correctly(): void
    {
        // 15 pcs + 1 DOS yang sudah diisi terpisah = 27 pcs setara -> 2 DOS + 3 pcs.
        $result = UnitRollUp::collapse($this->twoLevelLadder(), [self::PIECE => 15, self::DOS => 1], [self::PIECE, self::DOS]);

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 3],
            ['unit_id' => self::DOS, 'qty' => 2],
        ], $result);
    }

    /**
     * Kasus yang butuh gulung DUA KALI (carry dari tingkat bawah + isian sendiri di tingkat atas
     * sama-sama meluap ke tingkat berikutnya) -- 48 pcs (4 DOS persis) + 3 DOS yang diisi terpisah
     * = 7 DOS setara, dan 7 DOS >= ratio SAK(2), jadi ikut naik lagi jadi 3 SAK + 1 DOS.
     */
    public function test_collapse_keeps_carrying_up_when_a_combined_total_overflows_again(): void
    {
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 48, self::DOS => 3, self::SAK => null],
            $this->allUnits()
        );

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 0],
            ['unit_id' => self::DOS, 'qty' => 1],
            ['unit_id' => self::SAK, 'qty' => 3],
        ], $result);
    }

    /**
     * Menjalankan collapse() pada hasil collapse() sebelumnya tidak boleh mengubah apa pun lagi --
     * penting karena rollUpUnits() dipanggil di SETIAP simpan (draft maupun menunggu), jadi
     * dokumen yang sama bisa lewat sini berkali-kali.
     */
    public function test_collapse_is_idempotent_on_its_own_output(): void
    {
        $first = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 55, self::DOS => null, self::SAK => null],
            $this->allUnits()
        );
        $asMap = collect($first)->pluck('qty', 'unit_id')->all();

        $second = UnitRollUp::collapse($this->ladder(), $asMap, $this->allUnits());

        $this->assertSame($first, $second);
    }

    /** Satuan yang tidak dilarang (tidak punya baris stok aktif) menghentikan gulungan di situ. */
    public function test_collapse_stops_rolling_at_a_unit_the_caller_disallows(): void
    {
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 30, self::DOS => null],
            [self::PIECE] // DOS tidak diizinkan (tidak punya baris stok)
        );

        $this->assertSame([['unit_id' => self::PIECE, 'qty' => 30]], $result);
    }

    /** Satuan yang diisi tapi tidak dikenal rantainya sama sekali dibiarkan seperti aslinya. */
    public function test_collapse_ignores_a_filled_unit_that_is_not_part_of_the_chain_at_all(): void
    {
        $unrelated = 999;
        $result = UnitRollUp::collapse($this->ladder(), [$unrelated => 5], $this->allUnits());

        $this->assertSame([], $result, 'satuan di luar rantai tidak boleh ikut ditulis collapse()');
    }

    public function test_collapse_on_a_product_with_no_ladder_returns_nothing(): void
    {
        $result = UnitRollUp::collapse([], [self::PIECE => 30], $this->allUnits());

        $this->assertSame([], $result);
    }

    /** Properti yang sebenarnya penting: total fisiknya tidak boleh berubah sedikit pun. */
    public function test_collapse_conserves_the_original_physical_total(): void
    {
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 55, self::DOS => null, self::SAK => null],
            $this->allUnits()
        );

        $pieceEquivalent = [self::PIECE => 1, self::DOS => 12, self::SAK => 24];
        $total = 0;
        foreach ($result as $credit) {
            $total += $credit['qty'] * $pieceEquivalent[$credit['unit_id']];
        }

        $this->assertSame(55, $total);
    }

    public function test_the_planned_quantities_conserve_the_original_amount(): void
    {
        // Whatever the split, converting every planned level back down to Piece must equal the
        // input — the property that actually matters for "no stock lost".
        $plan = UnitRollUp::plan($this->ladder(), self::PIECE, 100, $this->allUnits());

        $pieceEquivalent = [self::PIECE => 1, self::DOS => 12, self::SAK => 24];
        $total = 0;
        foreach ($plan as $credit) {
            $total += $credit['qty'] * $pieceEquivalent[$credit['unit_id']];
        }

        $this->assertSame(100, $total);
    }

    // ---------------------------------------------------------- collapse() $existingByUnitId

    /**
     * Bug dilaporkan user (multi-gudang, 2026-08-31): stok live sudah 84 DOS + 0 Piece (sudah
     * kanonik, 1 DOS = 12 Piece). Staf isi HANYA Piece = 1000, DOS dibiarkan kosong. Tanpa melipat
     * stok live yang sudah ada di DOS ke dalam carry, hasilnya 83 DOS + 4 Piece -- 84 DOS yang
     * sudah ada lenyap diam-diam DIGANTIKAN angka yang cuma berasal dari perhitungan Piece
     * sendirian, dan angka itu ikut tertulis ke ps_stock saat ACC karena tidak lagi NULL.
     */
    public function test_collapse_folds_existing_live_stock_of_an_untouched_unit_into_the_carry(): void
    {
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 1000, self::DOS => null, self::SAK => null],
            $this->allUnits(),
            [self::DOS => 84] // stok live yang sudah ada, DOS tidak disentuh dokumen ini
        );

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 4],
            // 84 lama + 83 hasil gulungan 1000 pcs = 167, lalu ikut naik lagi ke SAK (1 Sak = 2 DOS)
            ['unit_id' => self::DOS, 'qty' => 1],
            ['unit_id' => self::SAK, 'qty' => 83],
        ], $result);

        // Kekekalan fisik: 84 DOS lama (1008 pcs) + 1000 pcs baru = 2008, harus sama dengan hasil.
        $pieceEquivalent = [self::PIECE => 1, self::DOS => 12, self::SAK => 24];
        $total = 0;
        foreach ($result as $credit) {
            $total += $credit['qty'] * $pieceEquivalent[$credit['unit_id']];
        }
        $this->assertSame(84 * 12 + 1000, $total);
    }

    /** Default [] (pemanggil lama yang tidak memberi existingByUnitId) tidak boleh berubah perilakunya. */
    public function test_collapse_without_existing_by_unit_id_reproduces_the_old_behaviour(): void
    {
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 1000, self::DOS => null, self::SAK => null],
            $this->allUnits()
        );

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 4],
            ['unit_id' => self::DOS, 'qty' => 1],
            ['unit_id' => self::SAK, 'qty' => 41],
        ], $result);
    }

    /** Satuan yang DIISI EKSPLISIT oleh staf tidak boleh ikut dilipat dengan stok lamanya -- itu koreksi baru, bukan tambahan. */
    public function test_collapse_ignores_existing_stock_for_a_unit_the_staff_explicitly_filled(): void
    {
        $result = UnitRollUp::collapse(
            $this->ladder(),
            [self::PIECE => 15, self::DOS => 1, self::SAK => null],
            $this->allUnits(),
            [self::DOS => 84] // harus diabaikan -- DOS diisi eksplisit oleh staf jadi 1
        );

        $this->assertSame([
            ['unit_id' => self::PIECE, 'qty' => 3],
            ['unit_id' => self::DOS, 'qty' => 0],
            ['unit_id' => self::SAK, 'qty' => 1], // (15 pcs -> 1 DOS + 3 pcs) + DOS diisi 1 = 2 DOS = 1 SAK, BUKAN + 84
        ], $result);
    }
}
