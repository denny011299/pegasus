<?php

namespace Tests\Unit;

use App\Synchronization\SyncStatus;
use App\Synchronization\SyncStepResult;
use PHPUnit\Framework\TestCase;

/**
 * Pure-logic tests for SyncStepResult::finish()'s three-way outcome (GitHub #184): a sync step
 * that processes many independent rows (e.g. one row per PMO product) should let the operator
 * proceed with whatever succeeded instead of an all-or-nothing pass/fail — see
 * App\Synchronization\PrerequisiteChecker, which treats PARTIAL the same as SUCCESS.
 */
class SyncStepResultPartialStatusTest extends TestCase
{
    public function test_finish_succeeds_when_nothing_failed(): void
    {
        $result = SyncStepResult::start();
        $result->processed = 5;
        $result->inserted = 5;

        $result->finish('selesai');

        $this->assertSame(SyncStatus::SUCCESS, $result->status);
    }

    public function test_finish_is_partial_when_some_rows_succeeded_and_some_failed(): void
    {
        $result = SyncStepResult::start();
        $result->processed = 292;
        $result->inserted = 90;
        $result->failed = 202;

        $result->finish();

        $this->assertSame(SyncStatus::PARTIAL, $result->status);
        $this->assertStringContainsString('202 dari 292', $result->message);
    }

    public function test_finish_is_partial_when_failures_mix_with_updates_too(): void
    {
        $result = SyncStepResult::start();
        $result->processed = 10;
        $result->updated = 3;
        $result->failed = 7;

        $result->finish();

        $this->assertSame(SyncStatus::PARTIAL, $result->status);
    }

    public function test_finish_is_failed_when_nothing_succeeded_at_all(): void
    {
        $result = SyncStepResult::start();
        $result->processed = 4;
        $result->failed = 4;

        $result->finish();

        $this->assertSame(SyncStatus::FAILED, $result->status);
    }
}
