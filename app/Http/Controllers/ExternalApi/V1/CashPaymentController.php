<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\PaymentPhotoStore;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use App\Models\CashArmada;
use App\Models\CashArmadaDetail;
use App\Models\CashSales;
use App\Models\CashSalesDetail;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pembayaran kas untuk sistem eksternal (API-005).
 *
 * Dua jenis pembayaran, mengikuti dua tabel yang sudah ada:
 *   payment_type 1 = Armada  -> cash_armadas  + cash_armada_details
 *   payment_type 2 = Sales   -> cash_sales    + cash_sales_details
 *
 * CATATAN PENTING soal istilah: spesifikasi menyebut "armada_id", padahal di
 * basis data tidak ada entitas armada sama sekali. Kas armada tercatat atas
 * nama CUSTOMER (cash_armadas.customer_id) — kendaraan disimpan sebagai
 * pelanggan dengan nomor polisi pada customer_notes, misalnya
 * "W 9518 PG (Agus)". Jadi armada_id di kontrak API diterjemahkan ke
 * customers.customer_id, dan divalidasi ke tabel customers.
 *
 * Yang dipakai ulang dari implementasi yang sudah ada, bukan ditulis ulang:
 *   - urutan pembuatan kas operasional (ReportController::insertCashArmada /
 *     insertCashSales) beserta cara menurunkan catatan, jenis, dan nominalnya
 *   - alur persetujuan (ReportController::acceptCashArmada / acceptCashSales),
 *     termasuk perubahan saldo customer/staff yang menyertainya
 *   - penyimpanan foto base64 ke public/kas_admin/, disimpan sebagai JSON
 *     nama berkas di kolom cr_img / cs_img
 *
 * Endpoint ini hanya melayani transaksi "operasional" (pengeluaran/setoran
 * berbutir dengan rincian). Penambahan saldo ("saldo") tetap lewat halaman
 * admin karena bentuknya berbeda dan tidak ada dalam kontrak API.
 */
class CashPaymentController extends Controller
{
    /** Jenis pembayaran sesuai kontrak API. */
    private const TYPE_ARMADA = 1;
    private const TYPE_SALES = 2;

    /** Arah rincian kas, sesuai crd_type/csd_type yang sudah dipakai UI. */
    private const DIRECTION_MASUK = 1;

    /**
     * POST /api/external/v1/payments/cash
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);

        // --- Idempotensi -------------------------------------------------
        // Referensi yang sama tidak pernah melahirkan transaksi kedua. Kalau
        // referensinya sudah pernah dipakai, permintaan ini diperlakukan
        // sebagai pengiriman ulang dan pembayaran yang lama dikembalikan apa
        // adanya — termasuk bila jenisnya berbeda, karena satu referensi hanya
        // boleh menunjuk satu transaksi.
        $existing = $this->findByRef($data['ref_payment_id']);

        if ($existing !== null) {
            return ApiResponse::success(
                $this->present($existing['payment'], $existing['type']),
                ['idempotent_replay' => true],
            );
        }

        $photos = new PaymentPhotoStore();

        try {
            $payment = DB::transaction(function () use ($data, $photos) {
                return $data['payment_type'] === self::TYPE_ARMADA
                    ? $this->createArmada($data, $photos)
                    : $this->createSales($data, $photos);
            });
        } catch (\InvalidArgumentException $e) {
            // Foto yang formatnya tidak sah adalah kesalahan pemanggil, bukan
            // kegagalan server — jadi dijawab 422 seperti validasi lainnya,
            // bukan 500.
            $photos->cleanup();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'photos' => [$e->getMessage()],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $photos->cleanup();

            // Dua permintaan dengan referensi sama yang tiba nyaris bersamaan:
            // pemeriksaan di awal sama-sama belum melihat baris apa pun, lalu
            // unique index menolak yang kalah cepat. Perlakukan sebagai kiriman
            // ulang biasa — yang penting kasnya hanya satu, bukan dua.
            $raced = $this->findByRef($data['ref_payment_id']);

            if ($raced !== null) {
                return ApiResponse::success(
                    $this->present($raced['payment'], $raced['type']),
                    ['idempotent_replay' => true],
                );
            }

            throw $e;
        } catch (\Throwable $e) {
            // Berkas foto tidak ikut dibatalkan database, jadi dibereskan di
            // sini agar tidak tertinggal sebagai berkas yatim.
            $photos->cleanup();

            throw $e;
        }

        if (! empty($data['auto_accept'])) {
            $failure = $this->accept($payment, $data['payment_type']);

            if ($failure !== null) {
                return $failure;
            }

            $payment = $this->reload($payment, $data['payment_type']);
        }

        return ApiResponse::success($this->present($payment, $data['payment_type']), [], 201);
    }

    /**
     * GET /api/external/v1/payments/cash/{ref_payment_id}
     */
    public function show(string $refPaymentId): JsonResponse
    {
        $found = $this->findByRef($refPaymentId);

        if ($found === null) {
            return ApiResponse::error(
                ErrorCatalog::NOT_FOUND,
                'Pembayaran dengan ref_payment_id "'.$refPaymentId.'" tidak ditemukan.',
                404,
            );
        }

        return ApiResponse::success($this->present($found['payment'], $found['type']));
    }

    /* ------------------------------------------------------------------ */
    /* Validasi                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request): array
    {
        $data = $request->validate([
            'ref_payment_id' => ['required', 'string', 'max:100'],
            'payment_type' => ['required', 'integer', Rule::in([self::TYPE_ARMADA, self::TYPE_SALES])],

            // armada_id menunjuk ke customers (lihat catatan kelas).
            'armada_id' => ['required_if:payment_type,'.self::TYPE_ARMADA, 'integer'],
            'staff_id' => ['required_if:payment_type,'.self::TYPE_SALES, 'integer'],

            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'integer'],
            'auto_accept' => ['sometimes', 'boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.amount' => ['required', 'integer'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.type' => ['required', 'integer', Rule::in([1, 2, 3])],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['string'],
        ]);

        $this->assertReferencedRecordExists($data);
        $this->assertItemsShareOneDirection($data['items']);
        $this->assertAmountMatchesItems($data);

        return $data;
    }

    /** Armada dicari di customers, sales dicari di staff. */
    private function assertReferencedRecordExists(array $data): void
    {
        if ((int) $data['payment_type'] === self::TYPE_ARMADA) {
            $exists = Customer::where('customer_id', '=', $data['armada_id'])->exists();

            if (! $exists) {
                $this->fail('armada_id', 'Armada dengan id '.$data['armada_id'].' tidak ditemukan.');
            }

            return;
        }

        $exists = Staff::where('staff_id', '=', $data['staff_id'])->exists();

        if (! $exists) {
            $this->fail('staff_id', 'Sales dengan staff_id '.$data['staff_id'].' tidak ditemukan.');
        }
    }

    /**
     * Seluruh rincian harus searah.
     *
     * Bukan aturan baru: implementasi yang ada menentukan jenis dan catatan
     * SATU transaksi dari item pertama saja (`$item[0]['crd_type']`), sehingga
     * campuran masuk-keluar dalam satu pembayaran akan tercatat keliru. Di
     * halaman admin hal itu dicegah tampilan; di sini harus dinyatakan tegas.
     */
    private function assertItemsShareOneDirection(array $items): void
    {
        $types = array_unique(array_map(static fn ($item) => (int) $item['type'], $items));

        if (count($types) > 1) {
            $this->fail('items', 'Seluruh item harus memiliki type yang sama dalam satu pembayaran.');
        }
    }

    /** payment_amount wajib sama dengan jumlah seluruh item. */
    private function assertAmountMatchesItems(array $data): void
    {
        $total = array_sum(array_map(static fn ($item) => (int) $item['amount'], $data['items']));

        if ((int) $data['payment_amount'] !== $total) {
            $this->fail(
                'payment_amount',
                'payment_amount ('.$data['payment_amount'].') tidak sama dengan jumlah item ('.$total.').'
            );
        }
    }

    private function fail(string $field, string $message): void
    {
        throw \Illuminate\Validation\ValidationException::withMessages([$field => [$message]]);
    }

    /* ------------------------------------------------------------------ */
    /* Pembuatan transaksi                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Kas armada operasional.
     *
     * Nilai turunan (catatan, jenis, nominal, cr_aksi, cash_id) disusun persis
     * seperti ReportController::insertCashArmada pada cabang "operasional".
     */
    private function createArmada(array $data, PaymentPhotoStore $photos): CashArmada
    {
        $customer = Customer::find($data['armada_id']);
        $isMasuk = (int) $data['items'][0]['type'] === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'customer_id' => $data['armada_id'],
            'cash_id' => 0,
            'cr_date' => $data['payment_date'],
            'cr_nominal' => (int) $data['payment_amount'],
            'cr_notes' => ($isMasuk ? 'Setoran armada ' : 'Pengeluaran armada ').$customer->customer_notes,
            'cr_aksi' => 2,
            'status' => 1,
        ];

        if ($isMasuk) {
            $row['cr_type'] = 1;
        }

        if (! empty($data['photos'])) {
            $row['cr_img'] = json_encode($photos->store($data['photos'], 'armada'));
        }

        $crId = (new CashArmada())->insertCashArmada($row);

        foreach ($data['items'] as $item) {
            (new CashArmadaDetail())->insertCashArmadaDetail([
                'cr_id' => $crId,
                'crd_nominal' => (int) $item['amount'],
                'crd_notes' => $item['notes'] ?? null,
                'crd_type' => (int) $item['type'],
            ]);
        }

        return CashArmada::find($crId);
    }

    /**
     * Kas sales operasional.
     *
     * Mengikuti ReportController::insertCashSales pada cabang "operasional".
     */
    private function createSales(array $data, PaymentPhotoStore $photos): CashSales
    {
        $staff = Staff::find($data['staff_id']);
        $direction = (int) $data['items'][0]['type'];
        $isMasuk = $direction === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'staff_id' => $data['staff_id'],
            'cash_id' => 0,
            // bank_id tidak punya nilai bawaan di model dan tidak dipakai pada
            // transaksi operasional; kolomnya sendiri berdefault 0.
            'bank_id' => 0,
            'cs_date' => $data['payment_date'],
            'cs_nominal' => (int) $data['payment_amount'],
            'cs_notes' => ($isMasuk ? 'Setoran sales ' : 'Pengeluaran sales ').$staff->staff_name,
            'cs_type' => 2,
            'cs_transaction' => $direction,
            'status' => 1,
        ];

        if (! empty($data['photos'])) {
            $row['cs_img'] = json_encode($photos->store($data['photos'], 'sales'));
        }

        $csId = (new CashSales())->insertCashSales($row);

        foreach ($data['items'] as $item) {
            (new CashSalesDetail())->insertCashSalesDetail([
                'cs_id' => $csId,
                'csd_nominal' => (int) $item['amount'],
                'csd_notes' => $item['notes'] ?? null,
                'csd_type' => (int) $item['type'],
            ]);
        }

        return CashSales::find($csId);
    }

    /* ------------------------------------------------------------------ */
    /* Persetujuan                                                         */
    /* ------------------------------------------------------------------ */

    /**
     * Setujui pembayaran dengan memakai alur persetujuan yang sudah ada.
     *
     * Sengaja memanggil ReportController, bukan menyalin isinya: persetujuan
     * bukan sekadar mengubah status, tetapi juga menggeser saldo customer atau
     * staff. Menyalin logika itu ke sini berarti ada dua tempat yang harus
     * selalu diubah bersamaan — persis yang dilarang spesifikasi.
     *
     * @return JsonResponse|null null bila berhasil
     */
    private function accept($payment, int $paymentType): ?JsonResponse
    {
        $reports = app(ReportController::class);

        $result = $paymentType === self::TYPE_ARMADA
            ? $reports->acceptCashArmada(new Request(['cr_id' => $payment->cr_id]))
            : $reports->acceptCashSales(new Request(['cs_id' => $payment->cs_id]));

        // Alur yang ada menjawab dengan JSON ber-status negatif saat menolak,
        // dan tidak mengembalikan apa pun saat berhasil.
        if ($result instanceof JsonResponse) {
            $body = $result->getData(true);

            return ApiResponse::error(
                ErrorCatalog::PAYMENT_NOT_ACCEPTABLE,
                $body['message'] ?? 'Pembayaran tidak dapat disetujui.',
                409,
            );
        }

        return null;
    }

    /* ------------------------------------------------------------------ */
    /* Pencarian & penyajian                                               */
    /* ------------------------------------------------------------------ */

    /**
     * Cari pembayaran berdasarkan referensi eksternal di kedua jenis kas.
     *
     * @return array{payment:mixed, type:int}|null
     */
    private function findByRef(string $refPaymentId): ?array
    {
        $armada = (new CashArmada())->findByRefPaymentId($refPaymentId);

        if ($armada) {
            return ['payment' => $armada, 'type' => self::TYPE_ARMADA];
        }

        $sales = (new CashSales())->findByRefPaymentId($refPaymentId);

        if ($sales) {
            return ['payment' => $sales, 'type' => self::TYPE_SALES];
        }

        return null;
    }

    private function reload($payment, int $paymentType)
    {
        return $paymentType === self::TYPE_ARMADA
            ? CashArmada::find($payment->cr_id)
            : CashSales::find($payment->cs_id);
    }

    /**
     * Bentuk respons satu pembayaran.
     *
     * Kolom internal sengaja tidak ikut: created_by / acc_by (id staf),
     * cash_id, cr_aksi, dan saldo customer maupun staff adalah urusan
     * akuntansi internal, bukan milik pemanggil API.
     *
     * @return array<string, mixed>
     */
    private function present($payment, int $paymentType): array
    {
        $isArmada = $paymentType === self::TYPE_ARMADA;
        $id = $isArmada ? $payment->cr_id : $payment->cs_id;

        $details = $isArmada
            ? CashArmadaDetail::where('cr_id', '=', $id)->where('status', '=', 1)->orderBy('crd_id')->get()
            : CashSalesDetail::where('cs_id', '=', $id)->where('status', '=', 1)->orderBy('csd_id')->get();

        $rawPhotos = $isArmada ? $payment->cr_img : $payment->cs_img;
        $photoNames = $rawPhotos ? (json_decode($rawPhotos, true) ?: []) : [];

        return [
            'ref_payment_id' => $payment->ref_payment_id,
            'payment_id' => (int) $id,
            'payment_type' => $paymentType,
            'payment_date' => (string) ($isArmada ? $payment->cr_date : $payment->cs_date),
            'payment_amount' => (int) ($isArmada ? $payment->cr_nominal : $payment->cs_nominal),
            'notes' => (string) ($isArmada ? $payment->cr_notes : $payment->cs_notes),
            'armada_id' => $isArmada ? (int) $payment->customer_id : null,
            'staff_id' => $isArmada ? null : (int) $payment->staff_id,
            'status' => $this->statusLabel((int) $payment->status),
            'items' => $details->map(static fn ($detail) => [
                'amount' => (int) ($isArmada ? $detail->crd_nominal : $detail->csd_nominal),
                'notes' => $isArmada ? $detail->crd_notes : $detail->csd_notes,
                'type' => (int) ($isArmada ? $detail->crd_type : $detail->csd_type),
            ])->all(),
            'photos' => array_map(
                static fn ($name) => PaymentPhotoStore::url($name, $isArmada ? 'armada' : 'sales'),
                $photoNames,
            ),
            'created_at' => optional($payment->created_at)->toIso8601String(),
        ];
    }

    /** status: 1 diajukan, 2 disetujui, 3 ditolak, 0 dihapus. */
    private function statusLabel(int $status): string
    {
        return match ($status) {
            2 => 'accepted',
            3 => 'declined',
            0 => 'deleted',
            default => 'pending',
        };
    }
}
