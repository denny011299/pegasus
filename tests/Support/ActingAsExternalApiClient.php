<?php

namespace Tests\Support;

use App\ExternalApi\ApiKeyManager;
use App\Models\ExternalApiKey;
use App\Models\ExternalApplication;

/**
 * The External API (routes/external-api/v1.php) authenticates via an API Key header, never a
 * session — see App\ExternalApi\Auth\ExternalApiAuthenticator. This trait builds a real
 * application + key pair and returns the headers to attach to a test request, mirroring how
 * Tests\Support\ActingAsStaff builds a session for the internal admin routes.
 */
trait ActingAsExternalApiClient
{
    /** @return array<string, string> */
    protected function externalApiHeaders(array $overrides = []): array
    {
        $application = new ExternalApplication();
        $application->application_code = 'test-app-'.uniqid();
        $application->application_name = 'Test External Application';
        $application->application_status = ExternalApplication::STATUS_ACTIVE;
        $application->status = 1;
        $application->save();

        /** @var ApiKeyManager $manager */
        $manager = app(ApiKeyManager::class);
        $generated = $manager->generate('development');

        $key = new ExternalApiKey();
        $key->external_application_id = $application->external_application_id;
        $key->key_name = 'Test Key';
        $key->environment = 'development';
        $key->key_prefix = $generated['prefix'];
        $key->key_hash = $generated['hash'];
        $key->key_last_four = $generated['last_four'];
        $key->key_status = 'active';
        $key->status = 1;
        foreach ($overrides as $attr => $value) {
            $key->{$attr} = $value;
        }
        $key->save();

        return [$manager->header() => $generated['plain']];
    }
}
