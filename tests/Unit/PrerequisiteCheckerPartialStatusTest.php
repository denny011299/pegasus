<?php

namespace Tests\Unit;

use App\Models\SyncExecution;
use App\Synchronization\Flows\ProductSyncFlow;
use App\Synchronization\PrerequisiteChecker;
use App\Synchronization\SyncStatus;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic tests for PrerequisiteChecker (GitHub #184): a PARTIAL execution (some rows failed,
 * some succeeded — see SyncStepResultPartialStatusTest) must satisfy a dependent step's
 * prerequisite the same way SUCCESS does, so the operator can proceed to e.g. Sinkronisasi Varian
 * Produk using whatever products/units did sync, instead of being blocked until every single row
 * of "Sinkronisasi Satuan"/"Sinkronisasi Produk" is fixed.
 *
 * Uses the real ProductSyncFlow ('product' step requires 'unit' + 'category') rather than a fake
 * flow, since SyncFlow/SyncStep have no DB dependency and building a throwaway flow class would
 * just re-describe the same prerequisite wiring already defined there.
 */
class PrerequisiteCheckerPartialStatusTest extends TestCase
{
    private function execution(string $status): SyncExecution
    {
        return new SyncExecution(['status' => $status]);
    }

    public function test_partial_satisfies_a_dependents_prerequisite(): void
    {
        $flow = new ProductSyncFlow();
        $productStep = $flow->findStep('product');

        $result = (new PrerequisiteChecker())->evaluate($flow, $productStep, [
            'unit' => $this->execution(SyncStatus::PARTIAL),
            'category' => $this->execution(SyncStatus::SUCCESS),
        ]);

        $unit = collect($result)->firstWhere('key', 'unit');
        $this->assertTrue($unit['satisfied'], 'a PARTIAL prerequisite must satisfy the dependent step');
    }

    public function test_success_still_satisfies_a_dependents_prerequisite(): void
    {
        $flow = new ProductSyncFlow();
        $productStep = $flow->findStep('product');

        $result = (new PrerequisiteChecker())->evaluate($flow, $productStep, [
            'unit' => $this->execution(SyncStatus::SUCCESS),
            'category' => $this->execution(SyncStatus::SUCCESS),
        ]);

        $unit = collect($result)->firstWhere('key', 'unit');
        $this->assertTrue($unit['satisfied']);
    }

    public function test_failed_does_not_satisfy_a_dependents_prerequisite(): void
    {
        $flow = new ProductSyncFlow();
        $productStep = $flow->findStep('product');

        $result = (new PrerequisiteChecker())->evaluate($flow, $productStep, [
            'unit' => $this->execution(SyncStatus::FAILED),
            'category' => $this->execution(SyncStatus::SUCCESS),
        ]);

        $unit = collect($result)->firstWhere('key', 'unit');
        $this->assertFalse($unit['satisfied']);
    }

    public function test_unmet_excludes_partial_prerequisites(): void
    {
        $flow = new ProductSyncFlow();
        $productStep = $flow->findStep('product');

        $unmet = (new PrerequisiteChecker())->unmet($flow, $productStep, [
            'unit' => $this->execution(SyncStatus::PARTIAL),
            'category' => $this->execution(SyncStatus::SUCCESS),
        ]);

        $this->assertSame([], $unmet);
    }
}
