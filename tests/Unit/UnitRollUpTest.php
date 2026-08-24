<?php

namespace Tests\Unit;

use App\Support\UnitRollUp;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic tests for `UnitRollUp::plan()` — no DB, so this lives in tests/Unit (the chain and
 * the allowed-unit list are both passed in explicitly, which is exactly why plan() takes them as
 * arrays rather than querying).
 *
 * Ladder used throughout: 1 Sak = 2 DOS, 1 DOS = 12 Piece (so 1 Sak = 24 Piece).
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
}
