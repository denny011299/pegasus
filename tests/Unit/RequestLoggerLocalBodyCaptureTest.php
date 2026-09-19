<?php

namespace Tests\Unit;

use App\ExternalApi\Logging\RequestLogger;
use App\Models\ExternalApiRequestLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * request_body/response_body on external_api_request_logs (debugging helper, added after a PMO
 * integration debugging session where a 422 gave no clue why): RequestLogger::write() must only
 * capture them when app()->environment('local') — never in production, since the body can contain
 * data the caller didn't expect to be persisted, and this table isn't redacted/encrypted.
 */
class RequestLoggerLocalBodyCaptureTest extends TestCase
{
    private function logger(): RequestLogger
    {
        return app(RequestLogger::class);
    }

    public function test_bodies_are_captured_in_the_local_environment(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $request = Request::create('/api/external/v1/shipments/scheduled', 'POST', [], [], [], [], json_encode(['ref_shipment_id' => 'X']));
        $response = new JsonResponse(['success' => false, 'error' => ['code' => 'validation_failed']], 422);

        $this->logger()->write($request, $response, microtime(true));

        $log = ExternalApiRequestLog::latest('external_api_request_log_id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString('ref_shipment_id', (string) $log->request_body);
        $this->assertStringContainsString('validation_failed', (string) $log->response_body);
    }

    public function test_bodies_are_not_captured_outside_local(): void
    {
        app()->detectEnvironment(fn () => 'testing');

        $request = Request::create('/api/external/v1/shipments/scheduled', 'POST', [], [], [], [], json_encode(['ref_shipment_id' => 'X']));
        $response = new JsonResponse(['success' => false], 422);

        $this->logger()->write($request, $response, microtime(true));

        $log = ExternalApiRequestLog::latest('external_api_request_log_id')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->request_body);
        $this->assertNull($log->response_body);
    }

    public function test_multipart_file_uploads_are_summarised_by_filename_not_raw_bytes(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $request = Request::create('/api/external/v1/shipments/shipped', 'POST', ['ref_shipment_id' => 'SHP-1']);
        $request->files->set('photos', [UploadedFile::fake()->image('bukti.jpg')]);

        $response = new JsonResponse(['success' => true], 201);

        $this->logger()->write($request, $response, microtime(true));

        $log = ExternalApiRequestLog::latest('external_api_request_log_id')->first();
        $this->assertStringContainsString('bukti.jpg', (string) $log->request_body);
        $this->assertStringContainsString('SHP-1', (string) $log->request_body);
    }
}
