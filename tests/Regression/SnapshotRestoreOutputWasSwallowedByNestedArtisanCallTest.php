<?php

namespace Tests\Regression;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Confirmed and fixed 2026-08-11 (found by the user after the multi-snapshot deploy console PR):
 * `/deploy/seed` went from showing a full seed log to a blank white page.
 *
 * Root cause: App\Console\Commands\SnapshotRestoreCommand::handle() called the target seed command
 * via the `Artisan::call()` FACADE (Illuminate\Console\Application::call()), which stashes its
 * output in a single shared `$lastOutput` BufferedOutput on the console Application instance and
 * whose `Artisan::output()` companion reads it via fetch() — which drains the buffer. DeployController
 * itself reaches SnapshotRestoreCommand through `Artisan::call('snapshot:restore', ...)` too, so the
 * nested facade call inside handle() overwrote `$lastOutput` to point at its OWN buffer and then
 * immediately drained it via `Artisan::output()` to relay it — leaving `$lastOutput` referencing an
 * already-empty buffer by the time DeployController::seedSnapshot() read `Artisan::output()` after
 * the outer call returned. The restore itself ran fine; only the displayed log was lost.
 *
 * Fix: SnapshotRestoreCommand now uses `$this->call()` (Illuminate\Console\Command::call(), which
 * runs the target command straight against $this->output, never touching Application::$lastOutput)
 * instead of the Artisan facade. See SnapshotRestoreCommand's own docblock for the full trace.
 *
 * This test intentionally does NOT use a mocked Artisan facade (unlike DeployControllerTest, which
 * mocks Artisan::call for exactly this route to avoid a real destructive db:seed touching
 * pegasus_testing outside DatabaseTransactions' rollback safety net — TRUNCATE causes an implicit
 * commit, so a transaction rollback would not undo it anyway). A mock would prove the right command
 * was *requested*, not that its output survives the nested-call round trip, which is the actual bug.
 * So this restores the "default" snapshot for real, then explicitly restores pegasus_testing back to
 * ITS OWN standing baseline in tearDown() regardless of pass/fail.
 *
 * That baseline is the `okeh8644` snapshot (real production-shaped data), not the bare `db:seed`
 * default — see memory pegasus-testing-db-okeh8644-switch. Restoring plain `db:seed --force` here
 * used to leave `pegasus_testing` sitting on the old, much smaller default snapshot for every test
 * that ran later in the same process (this test has no filter pinning it last), silently
 * reintroducing everything the okeh8644 switch fixed — e.g. the MRP300P/SOHP duplicate-SKU
 * collision the default snapshot's source data still has (see pegasus-duplicate-sku-data-issue) —
 * for the rest of that run. Confirmed as the actual root cause 2026-08-29 after that exact
 * regression recurred three times in one session.
 */
class SnapshotRestoreOutputWasSwallowedByNestedArtisanCallTest extends TestCase
{
    protected function tearDown(): void
    {
        Artisan::call('snapshot:restore', ['name' => 'okeh8644']);
        parent::tearDown();
    }

    public function test_snapshot_restore_output_survives_a_nested_artisan_call_the_way_deploy_controller_makes_it(): void
    {
        // Mirrors exactly how DeployController::seedSnapshot() invokes this: Artisan::call() from
        // outside, which is what exposed the shared-buffer bug (a direct $this->call() from a test,
        // or calling SnapshotRestoreCommand directly, would not reproduce it).
        Artisan::call('snapshot:restore', ['name' => 'default']);
        $output = Artisan::output();

        $this->assertNotSame('', $output, 'Artisan::output() was empty — the nested-call bug is back.');
        $this->assertStringContainsString('roles', $output);
        $this->assertMatchesRegularExpression('/\brows\b/', $output);
    }
}
