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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Pembayaran kas untuk sistem eksternal (API-005).
 *
 * Dua jenis pembayaran, mengikuti dua tabel yang sudah ada:
 *   payment_type 1 = Armada  -> cash_armadas  + cash_armada_details
 *   payment_type 2 = Sales   -> cash_sales    + cash_sales_details
 *
 * CATATAN PENTING soal istilah: di basis data tidak ada entitas armada sama
 * sekali. Kas armada tercatat atas nama CUSTOMER (cash_armadas.customer_id)
 * — kendaraan disimpan sebagai pelanggan dengan nomor polisi pada
 * customer_notes, misalnya "W 9518 PG (Agus)".
 *
 * RESOLUSI ARMADA/SALES (GitHub #207, revisi setelah #207 disetujui):
 * semula endpoint ini menerima armada_id (customers.customer_id, integer
 * internal Pegasus) dan staff_id (staffs.staff_id, integer internal
 * Pegasus) — tidak konsisten dengan cara PMO mereferensikan kedua entitas
 * itu di endpoint lain. Diperbaiki supaya sama persis:
 *   - armada_code : customers.customer_code, sama seperti path parameter
 *                   {code} pada MasterArmadaController — bukan id numerik.
 *   - staff_id     : staffs.external_ref_id, sama seperti field "staff_id"
 *                   pada body/path MasterSalesController & MasterStaffController
 *                   — BUKAN staffs.staff_id internal. Lihat catatan "DUA id
 *                   yang berbeda" di MasterSalesController.
 * Response payments/cash mengikuti nama field yang sama (armada_code,
 * staff_id sebagai external_ref_id), bukan id internal Pegasus.
 *
 * REVISI GitHub #208 (belum pernah dijalankan di staging/production, jadi migration-nya
 * ditulis ulang, bukan ditambah — lihat migration ref_nota_id):
 *   - ref_nota_id pindah dari level pembayaran ke items[].ref_nota_id: nota adalah atribut
 *     TIAP ITEM tunai, bukan satu transaksi (satu pembayaran bisa menggabungkan beberapa nota).
 *   - ref_payment_id BUKAN lagi kunci idempotensi sendirian. PMO mengirim satu group pembayaran
 *     Sales sebagai beberapa POST dengan ref_payment_id yang SAMA — satu call per sales
 *     (staff_id) penerima, karena satu baris cash_sales hanya menampung satu staff_id. Kunci
 *     idempotensi sekarang ref_payment_id + customer_id (armada) / ref_payment_id + staff_id
 *     (sales) — lihat migration 2026_09_30_030000_change_ref_payment_id_unique_to_composite.
 *   - Karena itu GET /payments/cash/{ref_payment_id} bisa mencocokkan LEBIH dari satu baris.
 *     data selalu berbentuk array (bukan lagi objek tunggal), dan bisa disaring dengan query
 *     ?armada_code= atau ?staff_id= untuk kembali ke satu baris.
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
        // Kunci idempotensi BUKAN ref_payment_id sendirian (lihat catatan kelas soal #208):
        // ref_payment_id + customer_id (armada) atau ref_payment_id + staff_id (sales). Kalau
        // pasangan itu sudah pernah dipakai, permintaan ini diperlakukan sebagai pengiriman
        // ulang dan pembayaran yang lama dikembalikan apa adanya.
        $existing = $this->findExisting($data);

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

            // Dua permintaan dengan pasangan referensi sama yang tiba nyaris bersamaan:
            // pemeriksaan di awal sama-sama belum melihat baris apa pun, lalu
            // unique index menolak yang kalah cepat. Perlakukan sebagai kiriman
            // ulang biasa — yang penting kasnya hanya satu, bukan dua.
            $raced = $this->findExisting($data);

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
     *
     * data selalu array: satu ref_payment_id bisa mencocokkan beberapa pembayaran (lihat catatan
     * kelas soal #208). ?armada_code= / ?staff_id= menyaring ke penerima tertentu; tanpa filter,
     * seluruh pembayaran armada MAUPUN sales dengan ref itu dikembalikan sekaligus.
     */
    public function show(Request $request, string $refPaymentId): JsonResponse
    {
        $found = $this->findAllByRef($refPaymentId, $request->query('armada_code'), $request->query('staff_id'));

        if ($found->isEmpty()) {
            return ApiResponse::error(
                ErrorCatalog::NOT_FOUND,
                'Pembayaran dengan ref_payment_id "'.$refPaymentId.'" tidak ditemukan.',
                404,
            );
        }

        return ApiResponse::success(
            $found->map(fn (array $row) => $this->present($row['payment'], $row['type']))->all(),
        );
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

            // armada_code menunjuk ke customers.customer_code, staff_id menunjuk ke
            // staffs.external_ref_id (lihat catatan kelas).
            'armada_code' => ['required_if:payment_type,'.self::TYPE_ARMADA, 'string', 'max:191'],
            'staff_id' => ['required_if:payment_type,'.self::TYPE_SALES, 'string', 'max:191'],

            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'integer'],
            'auto_accept' => ['sometimes', 'boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.amount' => ['required', 'integer'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.type' => ['required', 'integer', Rule::in([1, 2, 3])],
            'items.*.ref_nota_id' => ['nullable', 'string', 'max:100'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['string'],
        ]);

        $data = $this->resolveReferencedRecord($data);
        $this->assertItemsShareOneDirection($data['items']);
        $this->assertAmountMatchesItems($data);

        return $data;
    }

    /**
     * Armada dicari via customers.customer_code, sales via staffs.external_ref_id — bukan id
     * internal Pegasus (lihat catatan kelas). Id internal yang ditemukan disisipkan ke
     * '_armada_customer_id' / '_sales_staff_id' untuk dipakai createArmada()/createSales(),
     * supaya sisa alur tetap bekerja dengan FK integer seperti sebelumnya.
     *
     * @return array<string, mixed>
     */
    private function resolveReferencedRecord(array $data): array
    {
        if ((int) $data['payment_type'] === self::TYPE_ARMADA) {
            $customer = Customer::where('customer_code', '=', $data['armada_code'])->first();

            if (! $customer) {
                $this->fail('armada_code', 'Armada dengan code '.$data['armada_code'].' tidak ditemukan.');
            }

            $data['_armada_customer_id'] = $customer->customer_id;

            return $data;
        }

        $staff = Staff::where('external_ref_id', '=', $data['staff_id'])->first();

        if (! $staff) {
            $this->fail('staff_id', 'Sales dengan staff_id '.$data['staff_id'].' tidak ditemukan.');
        }

        $data['_sales_staff_id'] = $staff->staff_id;

        return $data;
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
        $customer = Customer::find($data['_armada_customer_id']);
        $isMasuk = (int) $data['items'][0]['type'] === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'customer_id' => $data['_armada_customer_id'],
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
                'ref_nota_id' => $item['ref_nota_id'] ?? null,
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
        $staff = Staff::find($data['_sales_staff_id']);
        $direction = (int) $data['items'][0]['type'];
        $isMasuk = $direction === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'staff_id' => $data['_sales_staff_id'],
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
                'ref_nota_id' => $item['ref_nota_id'] ?? null,
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
     * Cari SATU pembayaran memakai kunci idempotensi lengkap (ref_payment_id + penerima yang
     * sudah diresolusi validatePayload()) — dipakai store() untuk memutuskan replay atau baru.
     *
     * @return array{payment:mixed, type:int}|null
     */
    private function findExisting(array $data): ?array
    {
        if ((int) $data['payment_type'] === self::TYPE_ARMADA) {
            $armada = CashArmada::where('ref_payment_id', '=', $data['ref_payment_id'])
                ->where('customer_id', '=', $data['_armada_customer_id'])
                ->first();

            return $armada ? ['payment' => $armada, 'type' => self::TYPE_ARMADA] : null;
        }

        $sales = CashSales::where('ref_payment_id', '=', $data['ref_payment_id'])
            ->where('staff_id', '=', $data['_sales_staff_id'])
            ->first();

        return $sales ? ['payment' => $sales, 'type' => self::TYPE_SALES] : null;
    }

    /**
     * Cari SELURUH pembayaran (armada dan/atau sales) dengan ref_payment_id ini — dipakai
     * show(), karena satu ref sekarang bisa mencocokkan lebih dari satu baris (GitHub #208).
     * $armadaCode / $staffId menyaring hasil ke satu penerima tertentu bila diisi.
     *
     * @return Collection<int, array{payment:mixed, type:int}>
     */
    private function findAllByRef(string $refPaymentId, ?string $armadaCode, ?string $staffId): Collection
    {
        $armadas = collect();
        $salesRows = collect();

        if ($staffId === null) {
            $armadaQuery = CashArmada::where('ref_payment_id', '=', $refPaymentId);

            if ($armadaCode !== null) {
                $customerId = Customer::where('customer_code', '=', $armadaCode)->value('customer_id');
                $armadaQuery->where('customer_id', '=', $customerId ?? 0);
            }

            $armadas = $armadaQuery->orderBy('cr_id')->get()
                ->map(fn ($payment) => ['payment' => $payment, 'type' => self::TYPE_ARMADA]);
        }

        if ($armadaCode === null) {
            $salesQuery = CashSales::where('ref_payment_id', '=', $refPaymentId);

            if ($staffId !== null) {
                $staffPk = Staff::where('external_ref_id', '=', $staffId)->value('staff_id');
                $salesQuery->where('staff_id', '=', $staffPk ?? 0);
            }

            $salesRows = $salesQuery->orderBy('cs_id')->get()
                ->map(fn ($payment) => ['payment' => $payment, 'type' => self::TYPE_SALES]);
        }

        return $armadas->concat($salesRows);
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
            'armada_code' => $isArmada
                ? Customer::where('customer_id', '=', $payment->customer_id)->value('customer_code')
                : null,
            'staff_id' => $isArmada
                ? null
                : Staff::where('staff_id', '=', $payment->staff_id)->value('external_ref_id'),
            'status' => $this->statusLabel((int) $payment->status),
            'items' => $details->map(static fn ($detail) => [
                'amount' => (int) ($isArmada ? $detail->crd_nominal : $detail->csd_nominal),
                'notes' => $isArmada ? $detail->crd_notes : $detail->csd_notes,
                'type' => (int) ($isArmada ? $detail->crd_type : $detail->csd_type),
                'ref_nota_id' => $detail->ref_nota_id,
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
