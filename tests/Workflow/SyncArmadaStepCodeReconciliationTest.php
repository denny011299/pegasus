<?php

namespace Tests\Workflow;

use App\Models\Customer;
use App\Models\SalesOrder;
use App\Support\ArmadaUpsert;
use App\Synchronization\Pmo\PmoSnapshotStore;
use App\Synchronization\Steps\ArmadaFlow\SyncArmadaStep;
use App\Synchronization\SyncStatus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * SyncArmadaStep's customer_code reconciliation (PMO#16 follow-up to GitHub #187): PMO's
 * /getArmada never sent the vehicle's real code (oms_vehicle.kode), only its numeric armada_id —
 * confirmed by querying PMO's own database directly. That same code IS what shipments send as
 * armada_code, and App\Support\ArmadaUpsert creates a bare customers row keyed on it whenever a
 * shipment arrives before the armada is synced. Once PMO's /getArmada starts including that code
 * (requested in PMO#16), SyncArmadaStep must recognize it's the SAME armada instead of creating a
 * second row via the usual No Pol + PIC matching.
 */
class SyncArmadaStepCodeReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'synchronization.pmo.base_url' => 'https://pmo.test',
            'synchronization.pmo.api_key' => 'test-key',
        ]);
    }

    /** @var array<int, array<string, mixed>>|null */
    private ?array $fakeArmadaRows = null;

    private bool $armadaFakeRegistered = false;

    /**
     * Http::fake() keeps the FIRST stub registered for a matching URL pattern for the rest of the
     * test — calling Http::fake() again with an overlapping pattern does NOT override it (Laravel
     * resolves stubs via Collection::first(), and fake() appends rather than replaces). So a test
     * that needs /getArmada to answer differently across two sync runs must register ONE fake
     * whose closure reads mutable state, not call Http::fake() a second time.
     */
    private function fakeArmadaResponse(int $armadaId, string $picName, string $nomerPol, ?string $code = null): void
    {
        $row = [
            'armada_id' => $armadaId,
            'pic_name' => $picName,
            'nomer_pol' => $nomerPol,
            'nomer_telp' => null,
            'saldo_armada' => 0,
        ];
        if ($code !== null) {
            $row['code'] = $code;
        }

        $this->fakeArmadaRows = [$row];

        if (! $this->armadaFakeRegistered) {
            $this->armadaFakeRegistered = true;

            Http::fake([
                'pmo.test/getArmada*' => function () {
                    return Http::response([
                        'pagination' => ['total' => 1, 'page' => 1, 'limit' => 10, 'total_pages' => 1],
                        'data' => $this->fakeArmadaRows,
                    ], 200);
                },
            ]);
        }
    }

    public function test_new_armada_uses_the_pmo_code_as_customer_code_instead_of_generating_one(): void
    {
        $armadaId = random_int(1000000000, 9999999999);
        $this->fakeArmadaResponse($armadaId, 'Honda Freed Edited', 'L1111XA', 'PMOT-003');

        $result = app(SyncArmadaStep::class)->handle();

        $this->assertSame(SyncStatus::SUCCESS, $result->status, (string) $result->message);
        $customer = Customer::where('ref_armada_id', $armadaId)->first();
        $this->assertNotNull($customer);
        $this->assertSame('PMOT-003', $customer->customer_code);
    }

    public function test_merges_a_bare_armada_upsert_row_into_the_ref_linked_row_once_the_code_arrives(): void
    {
        $armadaId = random_int(1000000000, 9999999999);

        // Step 1: a shipment arrives for this armada BEFORE it's synced — ArmadaUpsert creates a
        // bare row keyed on the PMO code, with no PIC/No Pol/ref_armada_id at all.
        $bareArmada = ArmadaUpsert::resolveOrCreate('PMOT-003');
        $so = (new SalesOrder())->insertSalesOrder([
            'so_customer' => (string) $bareArmada->customer_id,
            'so_date' => '2026-09-19',
            'so_total' => 0,
            'so_img' => json_encode([]),
        ]);

        // Step 2: Sinkronisasi Armada runs BEFORE PMO's /getArmada fix — no code in the payload,
        // so it can't recognize the bare row and (correctly, given what it's told) creates a
        // second row via the normal ref_armada_id/No Pol+PIC path.
        $this->fakeArmadaResponse($armadaId, 'Honda Freed Edited', 'L1111XA', null);
        app(PmoSnapshotStore::class)->forget('armada', 'armada');
        app(SyncArmadaStep::class)->handle();

        $linkedArmada = Customer::where('ref_armada_id', $armadaId)->first();
        $this->assertNotNull($linkedArmada);
        $this->assertNotSame($bareArmada->customer_id, $linkedArmada->customer_id, 'without a code, this really is a second row — the bug this test exists to catch');

        // Step 3: PMO ships the code (PMO#16) — the next sync run must recognize both rows are the
        // same armada, merge the bare one into the ref-linked one, and adopt the real code.
        $this->fakeArmadaResponse($armadaId, 'Honda Freed Edited', 'L1111XA', 'PMOT-003');
        app(PmoSnapshotStore::class)->forget('armada', 'armada');
        $result = app(SyncArmadaStep::class)->handle();

        $this->assertSame(SyncStatus::SUCCESS, $result->status, (string) $result->message);

        $linkedArmada->refresh();
        $this->assertSame('PMOT-003', $linkedArmada->customer_code, 'the surviving row must adopt the real PMO code');

        $bareArmada->refresh();
        $this->assertSame(0, (int) $bareArmada->status, 'the duplicate bare row must be deactivated, not left dangling');

        $so->refresh();
        $this->assertSame((string) $linkedArmada->customer_id, $so->so_customer, 'the sales_order must be reassigned to the surviving row');
    }

    public function test_does_not_reassign_a_code_already_claimed_by_a_different_armada(): void
    {
        $armadaIdA = random_int(1000000000, 1999999999);
        $armadaIdB = random_int(2000000000, 2999999999);

        $this->fakeArmadaResponse($armadaIdA, 'Truk A', 'A 1111 A', 'SHARED-CODE');
        app(SyncArmadaStep::class)->handle();
        $customerA = Customer::where('ref_armada_id', $armadaIdA)->first();
        $this->assertSame('SHARED-CODE', $customerA->customer_code);

        // A different armada_id claims the SAME code — a genuine PMO data inconsistency, not
        // something safe to auto-resolve by stealing the code from armada A.
        $this->fakeArmadaResponse($armadaIdB, 'Truk B', 'B 2222 B', 'SHARED-CODE');
        app(PmoSnapshotStore::class)->forget('armada', 'armada');
        $result = app(SyncArmadaStep::class)->handle();

        $customerB = Customer::where('ref_armada_id', $armadaIdB)->first();
        $this->assertNotNull($customerB);
        $this->assertNotSame('SHARED-CODE', $customerB->customer_code, 'must not steal a code already claimed by a different armada');
        $this->assertNotEmpty($result->notices);

        $customerA->refresh();
        $this->assertSame('SHARED-CODE', $customerA->customer_code, 'the original holder must be untouched');
    }
}
