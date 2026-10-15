<?php

namespace Tests\Health;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exactly-once POST /payments/cash bergantung pada unique index ref_payment_id + penerima +
 * ref_direction di database, bukan hanya pemeriksaan di kode (dua request bersamaan lolos
 * pemeriksaan kode). Baris API yang ref_direction-nya NULL lolos dari index itu (MySQL tidak
 * mencegah NULL ganda), jadi backfill migration harus mengisi semuanya.
 *
 * Query yang sama dipakai untuk verifikasi DB live setelah deploy — lihat PR #221.
 */
class CashPaymentIdempotencyIndexTest extends TestCase
{
    private const TABLES = [
        'cash_armadas' => ['cash_armadas_ref_payment_recipient_direction_unique', ['ref_payment_id', 'customer_id', 'ref_direction']],
        'cash_sales' => ['cash_sales_ref_payment_recipient_direction_unique', ['ref_payment_id', 'staff_id', 'ref_direction']],
    ];

    public function test_unique_index_covers_ref_recipient_and_direction(): void
    {
        foreach (self::TABLES as $table => [$index, $columns]) {
            $rows = collect(DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$index]));

            $this->assertTrue($rows->isNotEmpty(), "$table: index $index tidak ada");
            $this->assertSame(0, (int) $rows->first()->Non_unique, "$table: index $index tidak unik");
            $this->assertSame($columns, $rows->sortBy('Seq_in_index')->pluck('Column_name')->values()->all(), "$table: kolom index");
        }
    }

    public function test_no_api_payment_row_has_a_null_direction(): void
    {
        foreach (array_keys(self::TABLES) as $table) {
            $this->assertSame(
                0,
                DB::table($table)->whereNotNull('ref_payment_id')->whereNull('ref_direction')->count(),
                "$table: baris External API tanpa ref_direction",
            );
        }
    }
}
