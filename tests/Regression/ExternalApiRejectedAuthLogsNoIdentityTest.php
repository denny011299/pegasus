<?php

namespace Tests\Regression;

use App\ExternalApi\ApiKeyManager;
use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Logging\RequestLogger;
use App\Models\ExternalApiEndpointStatus;
use App\Models\ExternalApiKey;
use App\Models\ExternalApiRequestLog;
use App\Models\ExternalApplication;
use Tests\TestCase;

/**
 * Regression: failed External API auth attempts used to log NO identity at all.
 *
 * `ExternalApiAuthenticator::authenticate()` only called
 * `$request->attributes->set(...)` on its success path, so `RequestLogger::write()`
 * read null for `external_application_id`/`external_api_key_id`/`application_name`/
 * `key_name` on every rejected request — even for API_KEY_REVOKED / API_KEY_EXPIRED /
 * APPLICATION_DISABLED, where the key's identity had already been fully proven
 * (prefix found AND constant-time hash match) before the rejection.
 *
 * The practical damage was on the External API Log admin page: filtering by
 * application or key silently returned only SUCCESSFUL calls, which is the exact
 * opposite of what someone debugging a broken integration is looking for. The rows
 * existed, they just had nothing to filter on.
 *
 * Fixed 2026-08-31 by setting the attributes as soon as each identity is proven,
 * before the accept/reject branch. This is safe because AuthenticateExternalApi
 * returns its error response WITHOUT calling $next(), so no controller ever runs for
 * a rejected request — on the failure path RequestLogger is the only reader.
 *
 * The `invalidKey()` rejections (unknown prefix, hash mismatch) deliberately stay
 * identity-less, and that is asserted below too: nothing was proven there, and
 * keeping those rows blank preserves the uniform "API Key tidak valid." response
 * that stops a caller probing which keys exist.
 */
class ExternalApiRejectedAuthLogsNoIdentityTest extends TestCase
{
    private const ENDPOINT = '/api/external/v1/master/units';

    protected function setUp(): void
    {
        parent::setUp();

        // The switch this suite's other tests flip via ActingAsExternalApiClient;
        // a disabled endpoint answers 503 before authentication runs and would
        // never reach the logging path under test.
        ExternalApiEndpointStatus::query()->where('is_active', 0)->update(['is_active' => 1]);

        // Logging is an admin-toggleable setting — pin it on rather than
        // inheriting whatever the local DB happens to hold.
        app(RequestLogger::class)->setEnabled(true);
    }

    /**
     * @return array{app: ExternalApplication, key: ExternalApiKey, headers: array<string, string>}
     */
    private function makeClient(array $keyOverrides = [], array $appOverrides = []): array
    {
        $application = new ExternalApplication();
        $application->application_code = 'reglog-'.uniqid();
        $application->application_name = 'Regression Log App '.uniqid();
        $application->application_status = ExternalApplication::STATUS_ACTIVE;
        $application->status = 1;
        foreach ($appOverrides as $attr => $value) {
            $application->{$attr} = $value;
        }
        $application->save();

        /** @var ApiKeyManager $manager */
        $manager = app(ApiKeyManager::class);
        $generated = $manager->generate('development');

        $key = new ExternalApiKey();
        $key->external_application_id = $application->external_application_id;
        $key->key_name = 'Regression Log Key '.uniqid();
        $key->environment = 'development';
        $key->key_prefix = $generated['prefix'];
        $key->key_hash = $generated['hash'];
        $key->key_last_four = $generated['last_four'];
        $key->key_status = ExternalApiKey::STATUS_ACTIVE;
        $key->status = 1;
        foreach ($keyOverrides as $attr => $value) {
            $key->{$attr} = $value;
        }
        $key->save();

        return [
            'app' => $application,
            'key' => $key,
            'headers' => [$manager->header() => $generated['plain']],
        ];
    }

    private function lastLog(): ?ExternalApiRequestLog
    {
        return ExternalApiRequestLog::query()
            ->orderByDesc('external_api_request_log_id')
            ->first();
    }

    public function test_a_revoked_key_attempt_logs_the_application_and_key_identity(): void
    {
        $client = $this->makeClient(['key_status' => ExternalApiKey::STATUS_REVOKED]);

        $this->getJson(self::ENDPOINT, $client['headers'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', ErrorCatalog::API_KEY_REVOKED);

        $log = $this->lastLog();
        $this->assertNotNull($log, 'A rejected request must still be logged.');
        $this->assertSame(
            (int) $client['key']->external_api_key_id,
            (int) $log->external_api_key_id,
            'The revoked key was fully identified before rejection; the log must say which key it was.'
        );
        $this->assertSame((int) $client['app']->external_application_id, (int) $log->external_application_id);
        $this->assertSame($client['key']->key_name, $log->key_name);
        $this->assertSame($client['app']->application_name, $log->application_name);
    }

    public function test_an_expired_key_attempt_logs_the_application_and_key_identity(): void
    {
        $client = $this->makeClient(['expires_at' => now()->subDay()]);

        $this->getJson(self::ENDPOINT, $client['headers'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', ErrorCatalog::API_KEY_EXPIRED);

        $log = $this->lastLog();
        $this->assertNotNull($log);
        $this->assertSame((int) $client['key']->external_api_key_id, (int) $log->external_api_key_id);
        $this->assertSame((int) $client['app']->external_application_id, (int) $log->external_application_id);
        $this->assertSame($client['key']->key_name, $log->key_name);
        $this->assertSame($client['app']->application_name, $log->application_name);
    }

    public function test_a_disabled_application_attempt_logs_the_application_and_key_identity(): void
    {
        $client = $this->makeClient([], ['application_status' => ExternalApplication::STATUS_DISABLED]);

        $this->getJson(self::ENDPOINT, $client['headers'])
            ->assertStatus(403)
            ->assertJsonPath('error.code', ErrorCatalog::APPLICATION_DISABLED);

        $log = $this->lastLog();
        $this->assertNotNull($log);
        $this->assertSame((int) $client['key']->external_api_key_id, (int) $log->external_api_key_id);
        $this->assertSame((int) $client['app']->external_application_id, (int) $log->external_application_id);
        $this->assertSame($client['key']->key_name, $log->key_name);
        $this->assertSame($client['app']->application_name, $log->application_name);
    }

    /**
     * A proven key whose application row is soft-deleted is answered as a plain
     * invalid key (deliberate — from the client's side the key really is dead),
     * but the KEY identity was still proven, so the log keeps it. The application
     * columns stay null because that row genuinely no longer exists.
     */
    public function test_a_deleted_application_still_logs_the_key_identity(): void
    {
        $client = $this->makeClient([], ['status' => 0]);

        $this->getJson(self::ENDPOINT, $client['headers'])
            ->assertStatus(401)
            ->assertJsonPath('error.code', ErrorCatalog::INVALID_API_KEY);

        $log = $this->lastLog();
        $this->assertNotNull($log);
        $this->assertSame((int) $client['key']->external_api_key_id, (int) $log->external_api_key_id);
        $this->assertSame($client['key']->key_name, $log->key_name);
        $this->assertNull($log->external_application_id);
        $this->assertNull($log->application_name);
    }

    /**
     * The other half of the fix: an unproven caller must leave NO identity behind.
     * A wrong secret against a real key's prefix must not be attributed to that
     * key — possession was never demonstrated.
     */
    public function test_a_wrong_secret_on_a_real_prefix_logs_no_identity(): void
    {
        $client = $this->makeClient();
        /** @var ApiKeyManager $manager */
        $manager = app(ApiKeyManager::class);

        $realKey = $client['headers'][$manager->header()];
        $tampered = substr($realKey, 0, -4).'0000';

        $this->getJson(self::ENDPOINT, [$manager->header() => $tampered])
            ->assertStatus(401)
            ->assertJsonPath('error.code', ErrorCatalog::INVALID_API_KEY);

        $log = $this->lastLog();
        $this->assertNotNull($log, 'The attempt is still logged — just without an identity.');
        $this->assertNull($log->external_api_key_id);
        $this->assertNull($log->external_application_id);
        $this->assertNull($log->key_name);
        $this->assertNull($log->application_name);
    }

    public function test_a_request_with_no_key_at_all_logs_no_identity(): void
    {
        $this->getJson(self::ENDPOINT)
            ->assertStatus(401)
            ->assertJsonPath('error.code', ErrorCatalog::UNAUTHENTICATED);

        $log = $this->lastLog();
        $this->assertNotNull($log);
        $this->assertNull($log->external_api_key_id);
        $this->assertNull($log->external_application_id);
    }

    /** The success path must keep logging identity exactly as it always did. */
    public function test_a_successful_request_still_logs_the_identity(): void
    {
        $client = $this->makeClient();

        $this->getJson(self::ENDPOINT, $client['headers'])->assertStatus(200);

        $log = $this->lastLog();
        $this->assertNotNull($log);
        $this->assertSame((int) $client['key']->external_api_key_id, (int) $log->external_api_key_id);
        $this->assertSame((int) $client['app']->external_application_id, (int) $log->external_application_id);
        $this->assertSame($client['key']->key_name, $log->key_name);
        $this->assertSame($client['app']->application_name, $log->application_name);
    }
}
