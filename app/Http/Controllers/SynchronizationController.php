<?php

namespace App\Http\Controllers;

use App\Models\SyncExecution;
use App\Synchronization\Contracts\PaginatedStepHandler;
use App\Synchronization\Contracts\ReviewQueueStepHandler;
use App\Synchronization\PrerequisiteChecker;
use App\Synchronization\PrerequisiteNotMetException;
use App\Synchronization\Pmo\PmoClient;
use App\Synchronization\Pmo\PmoException;
use App\Synchronization\SyncExecutionRepository;
use App\Synchronization\SyncFlow;
use App\Synchronization\SyncFlowRegistry;
use App\Synchronization\SyncRunner;
use App\Synchronization\SyncStatus;
use App\Synchronization\SyncStep;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Pusat Sinkronisasi.
 *
 * Controller ini hanya merender definisi alur dan meneruskan permintaan
 * eksekusi ke SyncRunner. Tidak ada logika sinkronisasi di sini.
 */
class SynchronizationController extends Controller
{
    public function __construct(
        private readonly SyncFlowRegistry $registry,
        private readonly SyncExecutionRepository $executions,
        private readonly PrerequisiteChecker $prerequisites,
        private readonly PmoClient $pmo,
    ) {
    }

    /** Halaman daftar seluruh alur sinkronisasi. */
    public function center()
    {
        return view('Backoffice.Synchronization.Center', [
            'flows' => $this->registry->all(),
            'pmoConfigured' => $this->pmo->isConfigured(),
            'pmoBaseUrl' => $this->pmo->baseUrl(),
        ]);
    }

    /** Wizard satu alur: seluruh langkah berada dalam satu halaman. */
    public function wizard(string $flow)
    {
        $flow = $this->registry->findOrFail($flow);

        return view('Backoffice.Synchronization.Wizard', [
            'flow' => $flow,
            'steps' => $flow->steps(),
            'state' => $this->state($flow),
            'pmoConfigured' => $this->pmo->isConfigured(),
            'pmoBaseUrl' => $this->pmo->baseUrl(),
        ]);
    }

    /** Status terkini seluruh langkah pada satu alur. */
    public function status(string $flow): JsonResponse
    {
        return response()->json($this->state($this->registry->findOrFail($flow)));
    }

    /**
     * Jalankan tepat satu langkah (jalur sekali-jalan). Tidak pernah otomatis
     * melanjutkan ke langkah berikutnya.
     */
    public function execute(string $flow, string $step): JsonResponse
    {
        [$flow, $syncStep, $notFound] = $this->resolveStep($flow, $step);
        if ($notFound) {
            return $notFound;
        }

        try {
            $execution = app(SyncRunner::class)->run($flow, $syncStep);
        } catch (PrerequisiteNotMetException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'state' => $this->state($flow),
            ], 422);
        }

        return response()->json([
            'ok' => $execution->status === SyncStatus::SUCCESS,
            'message' => $execution->message,
            'step' => $syncStep->key,
            'execution' => $execution->toWizardArray(),
            'state' => $this->state($flow),
        ]);
    }

    /**
     * Ambil TEPAT SATU halaman dari PMO untuk langkah berbasis halaman
     * (SyncStep::$paginated) — dipanggil berulang oleh wizard, satu request
     * per halaman, supaya progresnya terlihat berjalan. Belum menulis
     * riwayat eksekusi apa pun; itu baru terjadi di finalize().
     */
    public function fetchPage(string $flow, string $step, Request $request): JsonResponse
    {
        [$flow, $syncStep, $notFound] = $this->resolveStep($flow, $step);
        if ($notFound) {
            return $notFound;
        }

        $handler = $syncStep->makeHandler();
        if (! $syncStep->paginated || ! $handler instanceof PaginatedStepHandler) {
            return response()->json([
                'ok' => false,
                'message' => 'Langkah "'.$syncStep->key.'" bukan langkah berbasis halaman.',
            ], 422);
        }

        $page = max(1, (int) $request->input('page', 1));
        $query = (array) $request->input('query', []);

        try {
            $progress = $handler->fetchPage($page, $query);
        } catch (PmoException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true] + $progress);
    }

    /**
     * Selesaikan langkah berbasis halaman setelah wizard memanggil
     * fetchPage() sampai halaman terakhir — memindahkan buffer sesi ke
     * potret sungguhan, menjalankan validasi, dan mencatat riwayat eksekusi
     * seperti execute() pada langkah biasa.
     */
    public function finalize(string $flow, string $step): JsonResponse
    {
        [$flow, $syncStep, $notFound] = $this->resolveStep($flow, $step);
        if ($notFound) {
            return $notFound;
        }

        try {
            $execution = app(SyncRunner::class)->runFinalize($flow, $syncStep);
        } catch (PrerequisiteNotMetException $e) {
            return response()->json([
                'ok' => false,
                'message' => $e->getMessage(),
                'state' => $this->state($flow),
            ], 422);
        }

        return response()->json([
            'ok' => $execution->status === SyncStatus::SUCCESS,
            'message' => $execution->message,
            'step' => $syncStep->key,
            'execution' => $execution->toWizardArray(),
            'state' => $this->state($flow),
        ]);
    }

    /**
     * Daftar baris yang menunggu konfirmasi manual untuk langkah berbasis
     * antrean (SyncStep::$reviewQueue) — dipanggil saat pane langkah itu
     * ditampilkan, dan sesudah setiap aksi resolve().
     */
    public function pendingReviews(string $flow, string $step): JsonResponse
    {
        [, $syncStep, $notFound] = $this->resolveStep($flow, $step);
        if ($notFound) {
            return $notFound;
        }

        $handler = $syncStep->makeHandler();
        if (! $syncStep->reviewQueue || ! $handler instanceof ReviewQueueStepHandler) {
            return response()->json([
                'ok' => false,
                'message' => 'Langkah "'.$syncStep->key.'" bukan langkah konfirmasi manual.',
            ], 422);
        }

        return response()->json(['ok' => true, 'reviews' => $handler->pendingReviews()]);
    }

    /**
     * Selesaikan satu baris pada langkah berbasis antrean — "connect" (perlu
     * customer_id, harus salah satu kandidat baris itu) atau "discard".
     */
    public function resolveReview(string $flow, string $step, Request $request): JsonResponse
    {
        [, $syncStep, $notFound] = $this->resolveStep($flow, $step);
        if ($notFound) {
            return $notFound;
        }

        $handler = $syncStep->makeHandler();
        if (! $syncStep->reviewQueue || ! $handler instanceof ReviewQueueStepHandler) {
            return response()->json([
                'ok' => false,
                'message' => 'Langkah "'.$syncStep->key.'" bukan langkah konfirmasi manual.',
            ], 422);
        }

        $reviewId = (int) $request->input('review_id');
        $action = (string) $request->input('action');
        $customerId = $request->filled('customer_id') ? (int) $request->input('customer_id') : null;

        try {
            $reviews = $handler->resolveReview($reviewId, $action, $customerId);
        } catch (InvalidArgumentException|PmoException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'reviews' => $reviews]);
    }

    /**
     * @return array{0: SyncFlow, 1: ?SyncStep, 2: ?JsonResponse}
     */
    private function resolveStep(string $flow, string $step): array
    {
        $flowObj = $this->registry->findOrFail($flow);
        $syncStep = $flowObj->findStep($step);

        if (! $syncStep) {
            return [$flowObj, null, response()->json([
                'ok' => false,
                'message' => 'Langkah sinkronisasi "'.$step.'" tidak ditemukan.',
            ], 404)];
        }

        return [$flowObj, $syncStep, null];
    }

    /**
     * Status setiap langkah + evaluasi prasyarat, dipakai Blade maupun JS.
     *
     * @return array<string, mixed>
     */
    private function state(SyncFlow $flow): array
    {
        $latest = $this->executions->latestForFlow($flow);
        $steps = [];
        $completed = 0;

        foreach ($flow->steps() as $step) {
            /** @var SyncExecution|null $execution */
            $execution = $latest[$step->key] ?? null;
            $prerequisites = $this->prerequisites->evaluate($flow, $step, $latest);
            $unmet = collect($prerequisites)->reject(fn ($row) => $row['satisfied'])->pluck('title')->all();
            $status = $execution?->status ?? SyncStatus::NOT_EXECUTED;

            if ($status === SyncStatus::SUCCESS) {
                $completed++;
            }

            $steps[$step->key] = [
                'key' => $step->key,
                'status' => $status,
                'status_label' => SyncStatus::label($status),
                'badge_class' => SyncStatus::badgeClass($status),
                'prerequisites' => $prerequisites,
                'unmet' => $unmet,
                'can_execute' => $unmet === [] && $this->pmo->isConfigured(),
                'blocked_reason' => $this->blockedReason($unmet),
                'execution' => $execution?->toWizardArray(),
            ];
        }

        $total = count($steps);

        return [
            'flow' => $flow->key(),
            'steps' => $steps,
            'completed' => $completed,
            'total' => $total,
            'progress' => $total > 0 ? (int) round($completed / $total * 100) : 0,
            'pmo_configured' => $this->pmo->isConfigured(),
        ];
    }

    /**
     * @param  array<int, string>  $unmet
     */
    private function blockedReason(array $unmet): ?string
    {
        if (! $this->pmo->isConfigured()) {
            return 'Alamat server PMO belum dikonfigurasi. Isi PMO_BASE_URL dan PMO_API_KEY pada file .env terlebih dahulu.';
        }

        if ($unmet !== []) {
            return 'Selesaikan langkah berikut lebih dulu: '.implode(', ', $unmet).'.';
        }

        return null;
    }
}
