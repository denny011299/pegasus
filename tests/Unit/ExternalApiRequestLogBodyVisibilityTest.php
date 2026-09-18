<?php

namespace Tests\Unit;

use App\Models\ExternalApiRequestLog;
use Tests\TestCase;

/**
 * getExternalApiRequestLog() (feeds the Log API Eksternal DataTable) must never return
 * request_body/response_body outside the local environment — a defense-in-depth check alongside
 * RequestLogger only ever writing them locally (RequestLoggerLocalBodyCaptureTest), in case a row
 * written while local carries over after an environment change.
 */
class ExternalApiRequestLogBodyVisibilityTest extends TestCase
{
    private function seedLogWithBody(): void
    {
        $log = new ExternalApiRequestLog();
        $log->method = 'POST';
        $log->endpoint = '/api/external/v1/shipments/scheduled';
        $log->status_code = 422;
        $log->request_body = '{"ref_shipment_id":"X"}';
        $log->response_body = '{"success":false}';
        $log->requested_at = now();
        $log->save();
    }

    public function test_body_columns_are_hidden_outside_local(): void
    {
        app()->detectEnvironment(fn () => 'testing');
        $this->seedLogWithBody();

        $rows = (new ExternalApiRequestLog())->getExternalApiRequestLog();

        $this->assertNotEmpty($rows);
        $this->assertArrayNotHasKey('request_body', $rows->first()->getAttributes());
        $this->assertArrayNotHasKey('response_body', $rows->first()->getAttributes());
    }

    public function test_body_columns_are_visible_in_local(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $this->seedLogWithBody();

        $rows = (new ExternalApiRequestLog())->getExternalApiRequestLog();

        $this->assertNotEmpty($rows);
        $this->assertSame('{"ref_shipment_id":"X"}', $rows->first()->request_body);
        $this->assertSame('{"success":false}', $rows->first()->response_body);
    }
}
