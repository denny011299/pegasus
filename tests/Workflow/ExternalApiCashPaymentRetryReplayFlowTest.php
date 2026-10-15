<?php

namespace Tests\Workflow;

use App\Http\Controllers\ExternalApi\V1\CashPaymentController;
use App\Http\Controllers\ReportController;
use App\Models\CashArmada;
use App\Models\CashArmadaDetail;
use App\Models\CashSales;
use App\Models\Customer;
use App\Models\CustomerSupplyReturn;
use App\Models\Staff;
use App\Models\Supplies;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Tests\Support\ActingAsExternalApiClient;
use Tests\TestCase;

/**
 * POST /payments/cash exactly-once untuk retry PMO: Approve & Print yang responsnya hilang lalu
 * di-Print ulang berkali-kali dengan payload yang SAMA PERSIS dan ref yang sama.
 *
 *   belum ada            -> 201
 *   sudah ada, isi sama  -> 200 replay (data + meta.returns sama dengan saat dibuat)
 *   sudah ada, isi beda  -> 409 PAYMENT_CONTENT_MISMATCH
 *
 * Termasuk: replay setelah staf IPM ACC/tolak/hapus (tidak boleh 409 palsu), dua request
 * bersamaan (jalur unique index -> replay, bukan 500), dokumen Pengembalian tidak dobel, ref lama
 * berakhiran -K, dan kegagalan transaksi yang tidak meninggalkan sisa.
 */
class ExternalApiCashPaymentRetryReplayFlowTest extends TestCase
{
    use ActingAsExternalApiClient;

    private const URL = '/api/external/v1/payments/cash';

    /** PNG 1x1 yang sah. */
    private const PHOTO = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    /* ---------------------------------------------------------------- */
    /* b. replay setelah respons hilang                                  */
    /* ---------------------------------------------------------------- */

    public function test_identical_retry_after_lost_response_replays_without_creating_anything(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada, $this->createJerigen()) + ['photos' => [self::PHOTO]];

        $first = $this->postJson(self::URL, $payload, $headers)->assertStatus(201);
        $snapshot = $this->snapshot($payload['ref_payment_id']);

        foreach (range(1, 3) as $attempt) {
            $replay = $this->postJson(self::URL, $payload, $headers)
                ->assertStatus(200)
                ->assertJsonPath('meta.idempotent_replay', true);

            $this->assertSame($first->json('data'), $replay->json('data'), "data replay #$attempt");
            $this->assertSame($first->json('meta.returns'), $replay->json('meta.returns'), "meta.returns replay #$attempt");
        }

        $this->assertSame($snapshot, $this->snapshot($payload['ref_payment_id']));
    }

    /* ---------------------------------------------------------------- */
    /* c/d. replay setelah ACC / tolak / hapus oleh staf IPM             */
    /* ---------------------------------------------------------------- */

    public function test_retry_after_staff_accepts_declines_or_deletes_is_still_a_replay(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $reports = app(ReportController::class);

        foreach (['accepted', 'declined', 'deleted'] as $action) {
            $payload = $this->mixedPayload($armada);
            $this->postJson(self::URL, $payload, $headers)->assertStatus(201);
            $rows = CashArmada::where('ref_payment_id', $payload['ref_payment_id'])->get();

            foreach ($rows as $row) {
                $request = new Request(['cr_id' => $row->cr_id]);
                match ($action) {
                    'accepted' => $reports->acceptCashArmada($request),
                    'declined' => $reports->declineCashArmada($request),
                    'deleted' => $reports->deleteCashArmada($request),
                };
            }

            $this->postJson(self::URL, $payload, $headers)
                ->assertStatus(200)
                ->assertJsonPath('meta.idempotent_replay', true)
                ->assertJsonPath('data.0.status', $action)
                ->assertJsonPath('data.1.status', $action);

            $this->assertSame(2, CashArmada::where('ref_payment_id', $payload['ref_payment_id'])->count(), $action);
        }
    }

    public function test_legacy_row_without_fingerprint_still_replays_after_delete(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada);

        $this->postJson(self::URL, $payload, $headers)->assertStatus(201);
        // Baris dari sebelum ref_payload_hash ada: dibandingkan lewat rinciannya. Hapus
        // men-nonaktifkan rincian (status 0), dan itu tidak boleh membuat retry jadi 409.
        CashArmada::where('ref_payment_id', $payload['ref_payment_id'])->update(['ref_payload_hash' => null]);
        foreach (CashArmada::where('ref_payment_id', $payload['ref_payment_id'])->get() as $row) {
            app(ReportController::class)->deleteCashArmada(new Request(['cr_id' => $row->cr_id]));
        }

        $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true);
    }

    /* ---------------------------------------------------------------- */
    /* e. dua request identik bersamaan                                  */
    /* ---------------------------------------------------------------- */

    public function test_concurrent_identical_request_that_loses_the_race_gets_a_replay_not_500(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada, $this->createJerigen()) + ['photos' => [self::PHOTO]];

        // Diikat sebelum request pertama: Laravel menyimpan instance controller di route.
        $this->app->bind(CashPaymentController::class, RaceLosingCashPaymentController::class);

        $winner = $this->postJson(self::URL, $payload, $headers)->assertStatus(201);
        $snapshot = $this->snapshot($payload['ref_payment_id']);

        // Request kedua: pemeriksaan awalnya "belum melihat" baris pemenang, jadi benar-benar
        // mencoba insert, ditolak unique index, lalu harus pulih jadi replay.
        RaceLosingCashPaymentController::$blindNextLookup = true;

        $loser = $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true);

        $this->assertSame($winner->json('data'), $loser->json('data'));
        $this->assertSame($winner->json('meta.returns'), $loser->json('meta.returns'));
        $this->assertFalse(RaceLosingCashPaymentController::$blindNextLookup, 'jalur balapan benar-benar dilewati');
        $this->assertSame($snapshot, $this->snapshot($payload['ref_payment_id']), 'tidak ada baris, retur, atau berkas foto sisa');
    }

    public function test_concurrent_request_with_different_content_gets_409_not_500(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada);

        $this->app->bind(CashPaymentController::class, RaceLosingCashPaymentController::class);
        $this->postJson(self::URL, $payload, $headers)->assertStatus(201);

        $changed = $payload;
        $changed['items'][0]['amount'] += 1;
        RaceLosingCashPaymentController::$blindNextLookup = true;

        $this->postJson(self::URL, $changed, $headers)
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'PAYMENT_CONTENT_MISMATCH')
            ->assertJsonPath('error.details.type', 1);
        $this->assertFalse(RaceLosingCashPaymentController::$blindNextLookup, 'jalur balapan benar-benar dilewati');
    }

    /* ---------------------------------------------------------------- */
    /* f. normalisasi pembanding                                         */
    /* ---------------------------------------------------------------- */

    public function test_comparison_normalisation_is_stable_and_255_char_notes_survive(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $longNotes = str_repeat('é', 255);
        $ref = 'PB-NORM-'.uniqid();

        $base = ['ref_payment_id' => $ref, 'payment_type' => 1, 'armada_code' => $armada->customer_code, 'payment_date' => '2026-10-16'];

        $this->postJson(self::URL, $base + ['items' => [
            ['amount' => 1000, 'type' => 1, 'notes' => $longNotes, 'ref_nota_id' => 'N-1'],
            ['amount' => 2000, 'type' => 2],
        ]], $headers)->assertStatus(201);

        $this->assertSame($longNotes, CashArmadaDetail::where('crd_notes', $longNotes)->value('crd_notes'));

        // Bentuk lain dari isi yang sama: amount string, kind eksplisit cash, notes/ref_nota_id
        // kosong vs tidak dikirim, spasi di tepi.
        $this->postJson(self::URL, $base + ['items' => [
            ['amount' => '1000', 'type' => '1', 'kind' => 'cash', 'notes' => $longNotes, 'ref_nota_id' => ' N-1 '],
            ['amount' => 2000, 'type' => 2, 'kind' => '', 'notes' => '', 'ref_nota_id' => null],
        ]], $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true);
    }

    /* ---------------------------------------------------------------- */
    /* g. dokumen Pengembalian tidak pernah dobel                        */
    /* ---------------------------------------------------------------- */

    public function test_return_documents_are_never_duplicated_on_replay_including_legacy_key(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada, $this->createJerigen());

        $first = $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(201)
            ->assertJsonCount(1, 'meta.returns');
        $returnId = $first->json('meta.returns.0.supply_return_id');

        $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.returns.0.supply_return_id', $returnId)
            ->assertJsonPath('meta.returns.0.return_number', $first->json('meta.returns.0.return_number'));

        // Dokumen yang dibuat sebelum kunci retur memuat arah: replay tetap menemukannya.
        CustomerSupplyReturn::whereKey($returnId)->update(['idempotency_key' => hash('sha256', json_encode([
            'source' => 'payments/cash',
            'ref_payment_id' => $payload['ref_payment_id'],
            'recipient' => 'armada:'.$armada->customer_id,
            'armada_code' => mb_strtoupper($armada->customer_code),
            'ref_shipment_id' => 'SHP-RETRY',
        ]))]);

        $this->postJson(self::URL, $payload, $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.returns.0.supply_return_id', $returnId);

        $this->assertSame(1, CustomerSupplyReturn::where('ref_number', $payload['ref_payment_id'])->count());
    }

    /* ---------------------------------------------------------------- */
    /* h. field header tidak ikut dibandingkan                           */
    /* ---------------------------------------------------------------- */

    public function test_header_fields_are_not_compared_on_replay(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada) + ['ref_payment_number' => 'PB/X/001'];

        $this->postJson(self::URL, $payload, $headers)->assertStatus(201);

        $this->postJson(self::URL, array_merge($payload, [
            'ref_payment_number' => 'PB/X/002',
            'payment_date' => '2026-12-31',
            'photos' => [self::PHOTO],
        ]), $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true)
            ->assertJsonPath('data.0.ref_payment_number', 'PB/X/001')
            ->assertJsonPath('data.0.photos', []);
    }

    /* ---------------------------------------------------------------- */
    /* i. ref lama berakhiran -K                                         */
    /* ---------------------------------------------------------------- */

    public function test_old_document_resent_without_k_suffix_does_not_record_expenses_twice(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $group = 'G'.random_int(1000000000, 9999999999);
        $base = ['payment_type' => 1, 'armada_code' => $armada->customer_code, 'payment_date' => '2026-10-01'];
        $masuk = ['amount' => 50000, 'type' => 1, 'notes' => 'Setoran', 'ref_nota_id' => 'N-9'];
        $keluar = ['amount' => 7000, 'type' => 2, 'notes' => 'Solar'];

        // Cara lama PMO: Masuk di ref group, Keluar di ref group-K, tanpa sidik jari.
        $this->postJson(self::URL, $base + ['ref_payment_id' => $group, 'items' => [$masuk]], $headers)->assertStatus(201);
        $this->postJson(self::URL, $base + ['ref_payment_id' => $group.'-K', 'items' => [$keluar]], $headers)->assertStatus(201);
        CashArmada::whereIn('ref_payment_id', [$group, $group.'-K'])->update(['ref_payload_hash' => null]);

        // Cara baru: satu request, ref tanpa akhiran, item campuran.
        $this->postJson(self::URL, $base + ['ref_payment_id' => $group, 'items' => [$masuk, $keluar]], $headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.idempotent_replay', true)
            ->assertJsonCount(2, 'data');

        $this->assertSame(2, CashArmada::whereIn('ref_payment_id', [$group, $group.'-K'])->count());
    }

    /* ---------------------------------------------------------------- */
    /* j. transaksi gagal tidak meninggalkan sisa                        */
    /* ---------------------------------------------------------------- */

    public function test_failed_transaction_leaves_nothing_and_the_retry_creates_cleanly(): void
    {
        $headers = $this->externalApiHeaders();
        $armada = $this->createArmada();
        $payload = $this->mixedPayload($armada, $this->createJerigen());
        $before = $this->fileCounts();

        // Foto pertama sah (sudah tertulis ke disk), foto kedua rusak -> seluruh transaksi batal.
        $this->postJson(self::URL, $payload + ['photos' => [self::PHOTO, 'data:image/png;base64,bukan-gambar']], $headers)
            ->assertStatus(422);

        $this->assertSame(0, CashArmada::where('ref_payment_id', $payload['ref_payment_id'])->count());
        $this->assertSame(0, CustomerSupplyReturn::where('ref_number', $payload['ref_payment_id'])->count());
        $this->assertSame($before, $this->fileCounts());

        $this->postJson(self::URL, $payload, $headers)->assertStatus(201)->assertJsonCount(2, 'data');
    }

    /* ---------------------------------------------------------------- */
    /* Helper                                                            */
    /* ---------------------------------------------------------------- */

    /** Masuk (tunai + potongan barang bila $jerigen diberikan) dan Keluar, satu ref. */
    private function mixedPayload(Customer $armada, ?array $jerigen = null): array
    {
        $items = [
            ['kind' => 'cash', 'type' => 1, 'amount' => 40000, 'notes' => 'Pelunasan', 'ref_nota_id' => 'NOTA-1'],
            ['kind' => 'cash', 'type' => 2, 'amount' => 7000, 'notes' => 'Solar'],
        ];
        if ($jerigen !== null) {
            $items[] = ['kind' => 'potongan_barang', 'type' => 1, 'amount' => 5000, 'notes' => 'Jerigen', 'ref_nota_id' => 'NOTA-1',
                'goods' => ['item_type' => 1, 'ref_id' => (string) $jerigen['ref_supplies_id'], 'qty' => 2, 'satuan_id' => $jerigen['ref_unit_id']]];
        }

        return [
            'ref_payment_id' => 'PB-RETRY-'.uniqid(),
            'payment_type' => 1,
            'armada_code' => $armada->customer_code,
            'payment_date' => '2026-10-16',
            'ref_shipment_id' => 'SHP-RETRY',
            'items' => $items,
        ];
    }

    /** Semua yang bisa tercipta dari satu pembayaran: baris kas, rincian, retur, berkas. */
    private function snapshot(string $ref): array
    {
        $ids = CashArmada::where('ref_payment_id', $ref)->pluck('cr_id');

        return [
            'cash' => $ids->all(),
            'details' => CashArmadaDetail::whereIn('cr_id', $ids)->count(),
            'returns' => CustomerSupplyReturn::where('ref_number', $ref)->pluck((new CustomerSupplyReturn())->getKeyName())->all(),
            'files' => $this->fileCounts(),
        ];
    }

    private function fileCounts(): array
    {
        return [
            'photos' => count(glob(public_path('kas_admin/armada').'/*') ?: []),
            'proofs' => count(glob(public_path('customer_returns').'/*') ?: []),
        ];
    }

    private function createArmada(): Customer
    {
        $customer = new Customer();
        $customer->customer_name = 'Retry Test Armada';
        $customer->customer_code = 'RTY'.random_int(100000, 999999);
        $customer->customer_notes = 'Armada Retry Test';
        $customer->status = 1;
        $customer->save();

        return $customer;
    }

    /** @return array{ref_supplies_id:int, ref_unit_id:int} */
    private function createJerigen(): array
    {
        $unit = new Unit();
        $unit->unit_name = 'Jerigen Retry '.uniqid();
        $unit->unit_short_name = 'JR-'.random_int(1000, 9999);
        $unit->ref_unit_id = random_int(950000, 989999);
        $unit->status = 1;
        $unit->save();

        $supplies = new Supplies();
        $supplies->ref_supplies_id = random_int(950000, 989999);
        $supplies->supplies_name = 'Jerigen Retry '.uniqid();
        $supplies->supplies_default_unit = $unit->unit_id;
        $supplies->supplies_unit = json_encode([(string) $unit->unit_id]);
        $supplies->supplies_alert = 0;
        $supplies->status = 1;
        $supplies->save();

        return ['ref_supplies_id' => (int) $supplies->ref_supplies_id, 'ref_unit_id' => (int) $unit->ref_unit_id];
    }
}

/**
 * Mensimulasikan request yang kalah balapan: pemeriksaan idempotensi pertama belum melihat baris
 * pemenang (seperti dua request yang lolos pemeriksaan bersamaan), pemeriksaan berikutnya normal.
 */
class RaceLosingCashPaymentController extends CashPaymentController
{
    /** Dinyalakan test tepat sebelum request yang kalah; dipakai sekali lalu mati sendiri. */
    public static bool $blindNextLookup = false;

    protected function findExisting(array $data): Collection
    {
        if (self::$blindNextLookup) {
            self::$blindNextLookup = false;

            return collect();
        }

        return parent::findExisting($data);
    }
}
