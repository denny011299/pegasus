<?php

namespace Tests\Unit;

use App\Http\Controllers\AiChatController;
use App\Services\AiAssistant\AiChatService;
use App\Services\AiAssistant\OpenRouterClient;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Image questions: model switching and upload validation — no DB needed.
 */
class AiAssistantVisionTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'ai_assistant.openrouter.api_key' => 'test-key',
            'ai_assistant.openrouter.model' => 'text/model',
            'ai_assistant.openrouter.fallback_models' => [],
            'ai_assistant.vision.model' => 'vision/model',
            'ai_assistant.vision.fallback_models' => [],
        ]);
    }

    private function sentModel(bool $vision): string
    {
        Http::fake(['*' => Http::response(['choices' => [['message' => ['content' => 'ok']]]])]);

        (new OpenRouterClient())->chat([['role' => 'user', 'content' => 'hi']], [], $vision);

        $model = null;
        Http::assertSent(function ($request) use (&$model) {
            $model = $request['model'];

            return true;
        });

        return (string) $model;
    }

    public function test_text_question_uses_main_model(): void
    {
        $this->assertSame('text/model', $this->sentModel(false));
    }

    public function test_image_question_switches_to_vision_model(): void
    {
        $this->assertSame('vision/model', $this->sentModel(true));
    }

    public function test_vision_model_defaults_to_free_router(): void
    {
        $config = require base_path('config/ai_assistant.php');
        if (getenv('OPENROUTER_VISION_MODEL') === false) {
            $this->assertSame('openrouter/free', $config['vision']['model']);
        } else {
            $this->assertNotEmpty($config['vision']['model']);
        }
    }

    private function dataUrl(UploadedFile $file): ?string
    {
        $method = new \ReflectionMethod(AiChatController::class, 'imageDataUrl');

        return $method->invoke(new AiChatController(), $file);
    }

    public function test_valid_image_becomes_data_url(): void
    {
        $file = UploadedFile::fake()->image('nota.jpg', 40, 40);

        $this->assertStringStartsWith('data:image/jpeg;base64,', (string) $this->dataUrl($file));
    }

    public function test_non_image_bytes_are_rejected_even_with_image_name(): void
    {
        $file = UploadedFile::fake()->createWithContent('nota.jpg', '<?php echo "x";');

        $this->assertNull($this->dataUrl($file));
    }

    public function test_oversized_image_is_rejected(): void
    {
        config(['ai_assistant.vision.max_kb' => 1]);
        $file = UploadedFile::fake()->image('besar.png', 800, 800)->size(50);

        $this->assertNull($this->dataUrl($file));
    }

    public function test_persist_image_writes_under_ai_chat_dir(): void
    {
        Storage::fake('local');
        config(['ai_assistant.vision.storage_dir' => 'ai_chat']);

        $dataUrl = 'data:image/jpeg;base64,'.base64_encode('fakejpegbytes');
        // Reflection on real class without hitting constructor deps.
        $method = new \ReflectionMethod(AiChatService::class, 'persistImage');
        $path = $method->invoke(
            $method->getDeclaringClass()->newInstanceWithoutConstructor(),
            $dataUrl,
            42,
            'conv-uuid-test'
        );

        $this->assertNotNull($path);
        $this->assertStringStartsWith('ai_chat/42/conv-uuid-test/', $path);
        $this->assertStringEndsWith('.jpg', $path);
        Storage::disk('local')->assertExists($path);
    }
}
