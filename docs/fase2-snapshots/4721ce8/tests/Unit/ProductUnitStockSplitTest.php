<?php

namespace Tests\Unit;

use App\Support\ProductUnitStock;
use PHPUnit\Framework\TestCase;

/**
 * Item #4 di docs/backlog-stock-multi-gudang.md: Terima ST ke gudang utama tidak boleh
 * menyimpan pecahan (mis. 4.1667 DOS) — pecah jadi bagian bulat + sisa di satuan kecil
 * (div/mod), bukan convertQty() polos.
 */
class ProductUnitStockSplitTest extends TestCase
{
    public function test_fifty_piece_received_as_dos_splits_into_four_dos_plus_two_piece(): void
    {
        $split = ProductUnitStock::splitWholeAndRemainderFromMultipliers(
            50.0,
            9, // Piece
            7, // DOS
            [7 => 12, 9 => 1]
        );

        $this->assertSame(7, $split['whole_unit_id']);
        $this->assertSame(4.0, $split['whole_qty']);
        $this->assertSame(9, $split['remainder_unit_id']);
        $this->assertSame(2.0, $split['remainder_qty']);
    }

    public function test_exact_multiple_has_no_remainder(): void
    {
        $split = ProductUnitStock::splitWholeAndRemainderFromMultipliers(
            48.0,
            9,
            7,
            [7 => 12, 9 => 1]
        );

        $this->assertSame(4.0, $split['whole_qty']);
        $this->assertNull($split['remainder_unit_id']);
        $this->assertSame(0.0, $split['remainder_qty']);
    }

    public function test_less_than_one_target_unit_is_all_remainder(): void
    {
        $split = ProductUnitStock::splitWholeAndRemainderFromMultipliers(
            5.0,
            9,
            7,
            [7 => 12, 9 => 1]
        );

        $this->assertSame(0.0, $split['whole_qty']);
        $this->assertSame(9, $split['remainder_unit_id']);
        $this->assertSame(5.0, $split['remainder_qty']);
    }

    public function test_same_unit_is_a_no_op(): void
    {
        $split = ProductUnitStock::splitWholeAndRemainderFromMultipliers(
            30.0,
            7,
            7,
            [7 => 12, 9 => 1]
        );

        $this->assertSame(30.0, $split['whole_qty']);
        $this->assertNull($split['remainder_unit_id']);
    }

    public function test_converting_from_a_larger_unit_down_never_produces_a_remainder(): void
    {
        // 3 DOS -> Piece: qty * 12 selalu bulat, tidak butuh split.
        $split = ProductUnitStock::splitWholeAndRemainderFromMultipliers(
            3.0,
            7,
            9,
            [7 => 12, 9 => 1]
        );

        $this->assertSame(9, $split['whole_unit_id']);
        $this->assertSame(36.0, $split['whole_qty']);
        $this->assertNull($split['remainder_unit_id']);
    }
}
