<?php

namespace Tests\Workflow;

use App\Models\Staff;
use App\Support\PendingStockSoftBlock;
use App\Support\StockOpname\OpnamePageLock;
use App\Support\StockOpname\OpenOpnameGuard;
use Illuminate\Support\Facades\Schema;
use Tests\Support\ActingAsStaff;
use Tests\TestCase;

/**
 * Exclusive lock Input Stok Opname (-1) per gudang + soft-block via page lock.
 */
class OpnamePageLockWorkflowTest extends TestCase
{
    use ActingAsStaff;

    private const WH_A = 1;

    private const WH_B = 2;

    protected function setUp(): void
    {
        parent::setUp();
        if (! Schema::hasTable('stock_opname_page_locks')) {
            $this->markTestSkipped('stock_opname_page_locks belum di-migrate (testing DB).');
        }
    }

    private function twoStaff(): array
    {
        $rows = Staff::query()->where('status', 1)->orderBy('staff_id')->limit(2)->get();
        $this->assertGreaterThanOrEqual(2, $rows->count(), 'Butuh minimal 2 staff aktif di seed');

        return [$rows[0], $rows[1]];
    }

    public function test_second_user_cannot_acquire_same_warehouse_product_lock(): void
    {
        [$a, $b] = $this->twoStaff();

        $r1 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);
        $this->assertTrue($r1['ok']);
        $this->assertNotEmpty($r1['token']);

        $r2 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $b->staff_id, (string) $b->staff_name);
        $this->assertFalse($r2['ok']);
        $this->assertStringContainsString((string) $a->staff_name, (string) ($r2['held_by'] ?? ''));
    }

    public function test_other_warehouse_can_acquire_independently(): void
    {
        [$a, $b] = $this->twoStaff();

        $r1 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);
        $this->assertTrue($r1['ok']);

        $r2 = OpnamePageLock::acquire(self::WH_B, OpnamePageLock::DOMAIN_PRODUCT, (int) $b->staff_id, (string) $b->staff_name);
        $this->assertTrue($r2['ok'], 'Gudang B tidak boleh terganggu lock gudang A');
    }

    public function test_product_and_supplies_locks_are_independent(): void
    {
        [$a, $b] = $this->twoStaff();

        $r1 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);
        $this->assertTrue($r1['ok']);

        $r2 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_SUPPLIES, (int) $b->staff_id, (string) $b->staff_name);
        $this->assertTrue($r2['ok']);
    }

    public function test_same_staff_takeover_replaces_token(): void
    {
        [$a] = $this->twoStaff();

        $r1 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);
        $this->assertTrue($r1['ok']);
        $oldToken = $r1['token'];

        $r2 = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);
        $this->assertTrue($r2['ok']);
        $this->assertNotSame($oldToken, $r2['token']);

        $beat = OpnamePageLock::heartbeat($oldToken);
        $this->assertFalse($beat['ok']);
    }

    public function test_page_lock_triggers_soft_block_without_document(): void
    {
        [$a] = $this->twoStaff();
        OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);

        $this->assertTrue(app(OpenOpnameGuard::class)->isBlocked(self::WH_A, OpenOpnameGuard::DOMAIN_PRODUCT));
        $msg = PendingStockSoftBlock::messageIfBlocked(self::WH_A, OpenOpnameGuard::DOMAIN_PRODUCT);
        $this->assertNotNull($msg);
        $this->assertStringContainsString('Stock Opname', $msg);
    }

    public function test_assert_writable_blocks_other_staff(): void
    {
        [$a, $b] = $this->twoStaff();
        OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);

        $this->assertNull(OpnamePageLock::messageIfNotWritable(
            self::WH_A,
            OpnamePageLock::DOMAIN_PRODUCT,
            (int) $a->staff_id
        ));

        $msg = OpnamePageLock::messageIfNotWritable(
            self::WH_A,
            OpnamePageLock::DOMAIN_PRODUCT,
            (int) $b->staff_id
        );
        $this->assertNotNull($msg);
        $this->assertStringContainsString((string) $a->staff_name, $msg);
    }

    public function test_detail_minus_one_redirects_when_locked_by_other(): void
    {
        [$a, $b] = $this->twoStaff();
        OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);

        $this->actingAsSuperAdminStaff([
            'staff_id' => (int) $b->staff_id,
            'staff_name' => (string) $b->staff_name,
        ]);
        $this->withActiveWarehouse(self::WH_A);

        $this->get('/detailStockOpname/-1')
            ->assertRedirect(route('stockOpname'));
    }

    public function test_detail_minus_one_ok_for_holder_warehouse(): void
    {
        [$a] = $this->twoStaff();
        $this->actingAsSuperAdminStaff([
            'staff_id' => (int) $a->staff_id,
            'staff_name' => (string) $a->staff_name,
        ]);
        $this->withActiveWarehouse(self::WH_A);

        $this->get('/detailStockOpname/-1')->assertOk();
        $this->assertTrue(OpnamePageLock::isLive(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT));
    }

    public function test_release_clears_lock(): void
    {
        [$a] = $this->twoStaff();
        $r = OpnamePageLock::acquire(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT, (int) $a->staff_id, (string) $a->staff_name);
        $this->assertTrue(OpnamePageLock::release($r['token']));
        $this->assertFalse(OpnamePageLock::isLive(self::WH_A, OpnamePageLock::DOMAIN_PRODUCT));
    }
}
