<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Browser-triggerable Artisan runner for shared hosting without SSH (#34, App\Http\Controllers\
 * DeployController). Shipped without any test. Deliberately outside checkLogin/check.access —
 * security is entirely DEPLOY_TOKEN (env, checked with hash_equals) plus a typed confirm phrase
 * for the two destructive routes.
 *
 * This file NEVER lets a real migrate:fresh/db:seed execute against pegasus_testing: those would
 * drop/reload the whole schema outside DatabaseTransactions' rollback safety net, corrupting the
 * shared seed baseline for every other test in the run (the same category of risk the testing
 * skill already flags for dd()/die() code paths). The Artisan facade is mocked for the two
 * destructive commands instead, so only "was the guard passed and was the right command
 * requested" is asserted, never the command's actual effect. migrate/migrate:status/
 * config-clear etc. ARE safe and idempotent against the real test DB, so those run for real.
 */
class DeployControllerTest extends TestCase
{
    private const VALID_TOKEN = 'test-deploy-token-1234567890-abcdef';

    protected function tearDown(): void
    {
        putenv('DEPLOY_TOKEN');
        unset($_ENV['DEPLOY_TOKEN'], $_SERVER['DEPLOY_TOKEN']);
        parent::tearDown();
    }

    private function setDeployToken(string $token): void
    {
        putenv('DEPLOY_TOKEN='.$token);
        $_ENV['DEPLOY_TOKEN'] = $token;
        $_SERVER['DEPLOY_TOKEN'] = $token;
    }

    public static function safeRouteProvider(): array
    {
        return [
            'migrate' => ['/deploy/migrate'],
            'migrate-status' => ['/deploy/migrate-status'],
            'optimize-clear' => ['/deploy/optimize-clear'],
            'console' => ['/deploy/console'],
            'snapshots' => ['/deploy/snapshots'],
        ];
    }

    #[DataProvider('safeRouteProvider')]
    public function test_a_safe_route_403s_when_no_token_is_configured(string $uri): void
    {
        putenv('DEPLOY_TOKEN');
        unset($_ENV['DEPLOY_TOKEN'], $_SERVER['DEPLOY_TOKEN']);

        $this->get($uri.'?token=anything')
            ->assertStatus(403)
            ->assertSee('DEPLOY_TOKEN belum diset', false);
    }

    #[DataProvider('safeRouteProvider')]
    public function test_a_safe_route_403s_with_the_wrong_token(string $uri): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        // The message must say the token was WRONG, not the generic "Forbidden." fallback —
        // a human staring at this page needs to know what to fix.
        $this->get($uri.'?token=wrong-token')
            ->assertStatus(403)
            ->assertSee('Token deploy salah', false);
    }

    #[DataProvider('safeRouteProvider')]
    public function test_a_safe_route_403s_with_no_token_query_param_at_all(string $uri): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        // Distinct message from the wrong-token case: nothing was sent at all.
        $this->get($uri)
            ->assertStatus(403)
            ->assertSee('Token deploy tidak dikirim', false);
    }

    #[DataProvider('safeRouteProvider')]
    public function test_a_safe_route_succeeds_with_the_correct_token(string $uri): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get($uri.'?token='.self::VALID_TOKEN)->assertStatus(200);
    }

    public function test_a_deploy_token_shorter_than_24_characters_is_treated_as_unset(): void
    {
        $this->setDeployToken('short-token-15c');

        $this->get('/deploy/migrate?token=short-token-15c')->assertStatus(403);
    }

    public function test_seed_and_fresh_are_post_only_and_reject_a_plain_get(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        $this->get('/deploy/seed?token='.self::VALID_TOKEN)->assertStatus(405);
        $this->get('/deploy/fresh-empty?token='.self::VALID_TOKEN)->assertStatus(405);
    }

    public function test_seed_is_blocked_without_the_token_even_with_the_correct_confirm_phrase(): void
    {
        Artisan::shouldReceive('call')->never();

        $this->post('/deploy/seed', ['confirm' => 'SEED'])->assertStatus(403);
    }

    public function test_seed_is_blocked_without_the_confirm_phrase_even_with_a_valid_token(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->never();

        $this->post('/deploy/seed?token='.self::VALID_TOKEN)
            ->assertStatus(403);
    }

    public function test_seed_is_blocked_by_a_wrong_confirm_phrase(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->never();

        $this->post('/deploy/seed?token='.self::VALID_TOKEN, ['confirm' => 'seed'])
            ->assertStatus(403);
    }

    public function test_seed_calls_snapshot_restore_default_only_once_the_token_and_confirm_phrase_both_match(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->once()->with('snapshot:restore', ['name' => 'default'])->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('mocked output');

        $this->post('/deploy/seed?token='.self::VALID_TOKEN, ['confirm' => 'SEED'])
            ->assertStatus(200);
    }

    public function test_seed_restores_the_named_snapshot_the_form_selected(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->once()->with('snapshot:restore', ['name' => 'default'])->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('mocked output');

        // "default" is the only snapshot guaranteed to exist in every checkout (database/seeders/data);
        // this proves the `snapshot` field is read and passed through, without depending on a second
        // named snapshot existing in this checkout.
        $this->post('/deploy/seed?token='.self::VALID_TOKEN, ['confirm' => 'SEED', 'snapshot' => 'default'])
            ->assertStatus(200);
    }

    public function test_seed_rejects_an_unknown_snapshot_name_without_calling_artisan(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->never();

        $this->post('/deploy/seed?token='.self::VALID_TOKEN, ['confirm' => 'SEED', 'snapshot' => '../../etc/passwd'])
            ->assertStatus(403)
            ->assertSee('Snapshot tidak dikenal', false);
    }

    public function test_fresh_empty_is_blocked_by_a_wrong_confirm_phrase(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->never();

        $this->post('/deploy/fresh-empty?token='.self::VALID_TOKEN, ['confirm' => 'FRESH!!'])
            ->assertStatus(403);
    }

    public function test_fresh_empty_calls_migrate_fresh_only_once_the_token_and_confirm_phrase_both_match(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);
        Artisan::shouldReceive('call')->once()->with('migrate:fresh', ['--force' => true])->andReturn(0);
        Artisan::shouldReceive('output')->andReturn('mocked output');

        $this->post('/deploy/fresh-empty?token='.self::VALID_TOKEN, ['confirm' => 'FRESH'])
            ->assertStatus(200);
    }

    /**
     * migrate/migrate-status/optimize-clear are throttle:10,1 (routes/web.php); this only proves
     * the middleware is actually attached, not the exact limiter window.
     */
    public function test_safe_routes_are_rate_limited(): void
    {
        $this->setDeployToken(self::VALID_TOKEN);

        for ($i = 0; $i < 10; $i++) {
            $this->get('/deploy/migrate-status?token='.self::VALID_TOKEN)->assertStatus(200);
        }

        $this->get('/deploy/migrate-status?token='.self::VALID_TOKEN)->assertStatus(429);
    }
}
