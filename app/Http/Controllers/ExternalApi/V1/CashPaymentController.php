<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\PaymentPhotoStore;
use App\ExternalApi\Support\ReturnItemResolver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use App\Models\CashArmada;
use App\Models\CashArmadaDetail;
use App\Models\CashSales;
use App\Models\CashSalesDetail;
use App\Models\Customer;
use App\Models\Staff;
use App\Support\ArmadaUpsert;
use App\Support\CustomerReturnCreation;
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
 * CATATAN PENTING soal istilah: spesifikasi lama menyebut "armada_id" (PK
 * customers.customer_id). Kontrak External API modul lain (shipment, master
 * armada) memakai armada_code = customers.customer_code. Endpoint ini menerima
 * KEDUANYA: utamakan armada_code; armada_id tetap didukung untuk kompatibilitas.
 * Di DB, kas armada tetap tersimpan di cash_armadas.customer_id.
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
 * POTONGAN (PMO issue #28, 2026-10-07): items[].kind membedakan uang tunai ("cash", default)
 * dari potongan nominal ("potongan", mis. cash diskon 3%) dan potongan barang
 * ("potongan_barang", mis. jerigen). Semua jenis ikut dihitung di payment_amount dan tersimpan
 * sebagai rincian kas (crd_kind/csd_kind), supaya total terbayar di IPM sama dengan PMO.
 * Potongan boleh di alur kas mana pun (Masuk/Keluar/Keluar 1) — diterima sementara sampai
 * client mencoba di live (keputusan 2026-10-08). Setiap potongan_barang membawa
 * goods{item_type, ref_id, qty, satuan_id, armada_code, ref_shipment_id}; IPM membuat SATU
 * dokumen Pengembalian per pasangan (armada_code, ref_shipment_id) di dalam transaksi DB yang
 * sama dengan kas — berhasil semua atau gagal semua. Resolusi barisnya memakai
 * ReturnItemResolver yang sama dengan POST /shipments/returns, KECUALI baris bahan dibiarkan
 * tanpa gudang (warehouse_id NULL): staf IPM memilih gudangnya (utama atau eceran) di halaman
 * Pengembalian sebelum ACC. Ini menggantikan panggilan terpisah PMO ke /shipments/returns.
 * Dokumen retur ikut idempoten lewat idempotency_key yang diturunkan dari kunci pembayaran
 * (lihat returnIdempotencyKey()).
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

    /** Jenis item pembayaran (crd_kind/csd_kind). */
    private const KIND_CASH = 'cash';
    private const KIND_POTONGAN = 'potongan';
    private const KIND_POTONGAN_BARANG = 'potongan_barang';

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
                $this->present($existing['payment'], $existing['type']) + ['returns' => $this->findReturns($data)],
                ['idempotent_replay' => true],
            );
        }

        $photos = new PaymentPhotoStore();
        $returnProofs = [];
        $returns = [];

        try {
            $payment = DB::transaction(function () use ($data, $photos, &$returnProofs, &$returns) {
                $payment = $data['payment_type'] === self::TYPE_ARMADA
                    ? $this->createArmada($data, $photos)
                    : $this->createSales($data, $photos);

                $returns = $this->createReturns($data, $returnProofs);

                return $payment;
            });
        } catch (\InvalidArgumentException $e) {
            // Foto yang formatnya tidak sah adalah kesalahan pemanggil, bukan
            // kegagalan server — jadi dijawab 422 seperti validasi lainnya,
            // bukan 500.
            $photos->cleanup();
            $this->deleteReturnProofs($returnProofs);

            throw \Illuminate\Validation\ValidationException::withMessages([
                'photos' => [$e->getMessage()],
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            $photos->cleanup();
            $this->deleteReturnProofs($returnProofs);

            // Dua permintaan dengan pasangan referensi sama yang tiba nyaris bersamaan:
            // pemeriksaan di awal sama-sama belum melihat baris apa pun, lalu
            // unique index menolak yang kalah cepat. Perlakukan sebagai kiriman
            // ulang biasa — yang penting kasnya hanya satu, bukan dua.
            $raced = $this->findExisting($data);

            if ($raced !== null) {
                return ApiResponse::success(
                    $this->present($raced['payment'], $raced['type']) + ['returns' => $this->findReturns($data)],
                    ['idempotent_replay' => true],
                );
            }

            throw $e;
        } catch (\Throwable $e) {
            // Berkas foto tidak ikut dibatalkan database, jadi dibereskan di
            // sini agar tidak tertinggal sebagai berkas yatim.
            $photos->cleanup();
            $this->deleteReturnProofs($returnProofs);

            throw $e;
        }

        if (! empty($data['auto_accept'])) {
            $failure = $this->accept($payment, $data['payment_type']);

            if ($failure !== null) {
                return $failure;
            }

            $payment = $this->reload($payment, $data['payment_type']);
        }

        return ApiResponse::success($this->present($payment, $data['payment_type']) + ['returns' => $returns], [], 201);
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

            // armada_code = customer_code (sama shipment); armada_id = PK internal (legacy).
            'armada_code' => ['nullable', 'string', 'max:64'],
            'armada_id' => ['nullable', 'integer'],
            // staff_id di API = staffs.external_ref_id (kontrak sama /master/sales), bukan PK internal.
            'staff_id' => ['required_if:payment_type,'.self::TYPE_SALES, function (string $attribute, $value, \Closure $fail) {
                if ($value === null || $value === '') {
                    return;
                }
                if (! is_string($value) && ! is_int($value)) {
                    $fail('staff_id wajib berupa teks atau angka (external_ref_id sales).');
                }
            }],

            'payment_date' => ['required', 'date'],
            'payment_amount' => ['required', 'integer'],
            'auto_accept' => ['sometimes', 'boolean'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.amount' => ['required', 'integer'],
            'items.*.notes' => ['nullable', 'string', 'max:255'],
            'items.*.type' => ['required', 'integer', Rule::in([1, 2, 3])],
            'items.*.ref_nota_id' => ['nullable', 'string', 'max:100'],
            'items.*.kind' => ['nullable', 'string', Rule::in([self::KIND_CASH, self::KIND_POTONGAN, self::KIND_POTONGAN_BARANG])],

            // Potongan barang (PMO issue #28) — lihat catatan kelas.
            'ref_shipment_id' => ['nullable', 'string', 'max:100'],
            'items.*.goods' => ['required_if:items.*.kind,'.self::KIND_POTONGAN_BARANG, 'prohibited_unless:items.*.kind,'.self::KIND_POTONGAN_BARANG, 'array'],
            'items.*.goods.item_type' => ['required_with:items.*.goods', 'integer', Rule::in([1, 2])],
            'items.*.goods.ref_id' => ['required_with:items.*.goods'],
            'items.*.goods.qty' => ['required_with:items.*.goods', 'integer', 'min:1'],
            'items.*.goods.satuan_id' => [
                'required_with:items.*.goods', 'integer',
                Rule::exists('units', 'ref_unit_id')->where('status', 1),
            ],
            'items.*.goods.armada_code' => ['nullable', 'string', 'max:64'],
            'items.*.goods.ref_shipment_id' => ['nullable', 'string', 'max:100'],
            // Profil armada untuk upsert otomatis, bentuk sama dengan body.armada /shipments/returns.
            'armadas' => ['nullable', 'array'],
            'armadas.*.code' => ['required', 'string', 'max:64'],
            'armadas.*.pic' => ['nullable', 'string', 'max:255'],
            'armadas.*.pic_phone' => ['nullable', 'string', 'max:50'],
            'armadas.*.nomor_polisi' => ['nullable', 'string'],
            'armadas.*.category' => ['nullable', 'string', 'max:100'],
            'armadas.*.merk_model' => ['nullable', 'string', 'max:255'],
            'armadas.*.tahun_kendaraan' => ['nullable', 'string', 'max:20'],
            'armadas.*.lokasi' => ['nullable', 'string', 'max:255'],

            'photos' => ['nullable', 'array'],
            'photos.*' => ['string'],
        ]);

        $this->assertReferencedRecordExists($data);

        // Id internal penerima, dipakai findExisting() sebagai bagian kunci idempotensi (#208).
        if ((int) $data['payment_type'] === self::TYPE_ARMADA) {
            $data['_armada_customer_id'] = (int) $this->resolveArmadaCustomer($data)->customer_id;
        } else {
            $data['_sales_staff_id'] = (int) $this->resolveSalesStaff($data['staff_id'])->staff_id;
        }

        $this->assertItemsShareOneDirection($data['items']);
        $this->assertAmountMatchesItems($data);
        $data['_return_groups'] = $this->prepareReturnGroups($data);

        return $data;
    }

    private function kindOf(array $item): string
    {
        return $item['kind'] ?? self::KIND_CASH;
    }

    /**
     * Kelompokkan item potongan_barang per (armada_code, ref_shipment_id) — satu kelompok = satu
     * dokumen Pengembalian — lalu resolusikan barisnya SEBELUM transaksi dibuka, supaya galat
     * katalog (bahan/SKU/satuan tidak dikenal) dijawab 422 dengan path items.N.goods.*.
     *
     * goods.armada_code / goods.ref_shipment_id boleh kosong pada pembayaran Armada (diambil dari
     * header); pada pembayaran Sales wajib, karena sales tidak punya armada sendiri.
     *
     * @return array<int, array{armada_code:string, ref_shipment_id:string, supply_details:array, product_details:array}>
     */
    private function prepareReturnGroups(array $data): array
    {
        $isArmada = (int) $data['payment_type'] === self::TYPE_ARMADA;
        $headerArmadaCode = $isArmada ? $this->resolveArmadaCustomer($data)->customer_code : null;
        $headerShipmentId = $data['ref_shipment_id'] ?? null;
        $profileCodes = collect($data['armadas'] ?? [])->map(fn ($a) => mb_strtoupper((string) $a['code']))->all();

        $groups = [];
        foreach ($data['items'] as $index => $item) {
            if ($this->kindOf($item) !== self::KIND_POTONGAN_BARANG) {
                continue;
            }
            $goods = $item['goods'];

            $armadaCode = trim((string) ($goods['armada_code'] ?? '')) ?: $headerArmadaCode;
            if ($armadaCode === null || $armadaCode === '') {
                $this->fail("items.$index.goods.armada_code", 'armada_code wajib diisi untuk potongan barang pada pembayaran Sales.');
            }
            if (! in_array(mb_strtoupper($armadaCode), $profileCodes, true)
                && ! Customer::where('customer_code', $armadaCode)->where('status', 1)->exists()) {
                $this->fail("items.$index.goods.armada_code", 'Armada "'.$armadaCode.'" tidak ditemukan atau tidak aktif. Sertakan profilnya di armadas[] untuk dibuat otomatis.');
            }

            $refShipmentId = trim((string) ($goods['ref_shipment_id'] ?? '')) ?: $headerShipmentId;
            if ($refShipmentId === null || $refShipmentId === '') {
                $this->fail("items.$index.goods.ref_shipment_id", 'ref_shipment_id wajib diisi untuk potongan barang (di item atau di header).');
            }

            $refNotaId = (string) ($item['ref_nota_id'] ?? '');
            $key = mb_strtoupper($armadaCode).'|'.$refShipmentId;
            $groups[$key]['armada_code'] = $armadaCode;
            $groups[$key]['ref_shipment_id'] = $refShipmentId;
            $groups[$key]['items'][$index] = [
                'type' => (int) $goods['item_type'],
                'ref_id' => $goods['ref_id'],
                'qty' => (int) $goods['qty'],
                'satuan_id' => (int) $goods['satuan_id'],
                // Kolom ref_nota_id di detail retur bertipe angka; id nota non-angka tidak dibawa.
                'ref_nota_id' => ctype_digit($refNotaId) ? $refNotaId : null,
            ];
        }

        $resolver = new ReturnItemResolver();

        return array_values(array_map(function (array $group) use ($resolver) {
            [$supplyDetails, $productDetails] = $resolver->resolveItems($group['items'], 'items.%d.goods', true);
            $resolver->assertAgainstCatalog($supplyDetails, $productDetails);

            return [
                'armada_code' => $group['armada_code'],
                'ref_shipment_id' => $group['ref_shipment_id'],
                'supply_details' => $supplyDetails,
                'product_details' => $productDetails,
            ];
        }, $groups));
    }

    /** Armada lewat armada_code (customer_code) atau armada_id (legacy); sales lewat external_ref_id. */
    private function assertReferencedRecordExists(array $data): void
    {
        if ((int) $data['payment_type'] === self::TYPE_ARMADA) {
            $code = trim((string) ($data['armada_code'] ?? ''));
            $hasCode = $code !== '';
            $hasId = array_key_exists('armada_id', $data) && $data['armada_id'] !== null && $data['armada_id'] !== '';

            if (! $hasCode && ! $hasId) {
                $this->fail('armada_code', 'armada_code wajib dikirim bila payment_type = 1 (atau armada_id legacy).');
            }

            if ($this->resolveArmadaCustomer($data) === null) {
                $this->fail(
                    $hasCode ? 'armada_code' : 'armada_id',
                    $hasCode
                        ? 'Armada dengan armada_code "'.$code.'" tidak ditemukan.'
                        : 'Armada dengan id '.$data['armada_id'].' tidak ditemukan.'
                );
            }

            return;
        }

        if ($this->resolveSalesStaff($data['staff_id'] ?? null) === null) {
            $this->fail(
                'staff_id',
                'Sales dengan staff_id (external_ref_id) '.$data['staff_id'].' tidak ditemukan.'
            );
        }
    }

    /**
     * Utamakan armada_code (= customers.customer_code, sama shipment).
     * Fallback armada_id = customers.customer_id.
     */
    private function resolveArmadaCustomer(array $data): ?Customer
    {
        $code = trim((string) ($data['armada_code'] ?? ''));
        if ($code !== '') {
            return Customer::where('customer_code', $code)->where('status', 1)->first();
        }

        if (! empty($data['armada_id'])) {
            return Customer::where('customer_id', (int) $data['armada_id'])->where('status', 1)->first();
        }

        return null;
    }

    /**
     * staff_id pada body External API = staffs.external_ref_id (sama /master/sales).
     * cash_sales.staff_id tetap menyimpan PK internal.
     */
    private function resolveSalesStaff(mixed $externalRefId): ?Staff
    {
        $ref = trim((string) ($externalRefId ?? ''));
        if ($ref === '') {
            return null;
        }

        return Staff::where('external_ref_id', $ref)->where('status', 1)->first();
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
        $customer = $this->resolveArmadaCustomer($data);
        if ($customer === null) {
            $this->fail('armada_code', 'Armada tidak ditemukan.');
        }

        $isMasuk = (int) $data['items'][0]['type'] === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'customer_id' => (int) $customer->customer_id,
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
                'crd_kind' => $this->kindOf($item),
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
        $staff = $this->resolveSalesStaff($data['staff_id']);
        if ($staff === null) {
            // Sudah dicek di assertReferencedRecordExists — jaring pengaman race.
            $this->fail('staff_id', 'Sales dengan staff_id (external_ref_id) '.$data['staff_id'].' tidak ditemukan.');
        }

        $direction = (int) $data['items'][0]['type'];
        $isMasuk = $direction === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'staff_id' => (int) $staff->staff_id,
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
                'csd_kind' => $this->kindOf($item),
                'ref_nota_id' => $item['ref_nota_id'] ?? null,
            ]);
        }

        return CashSales::find($csId);
    }

    /* ------------------------------------------------------------------ */
    /* Pengembalian dari potongan barang                                   */
    /* ------------------------------------------------------------------ */

    /**
     * Buat satu dokumen Pengembalian per kelompok hasil prepareReturnGroups(). Dipanggil di dalam
     * transaksi kas. Armada yang profilnya dikirim di armadas[] di-upsert lebih dulu, sama seperti
     * body.armada pada /shipments/returns. photos[0] (kalau ada) disalin jadi bukti retur, satu
     * berkas per dokumen supaya mengganti bukti satu dokumen tidak menghapus milik dokumen lain.
     *
     * @param  array<int, string|null>  $proofPaths  diisi berkas bukti yang dibuat, untuk dibersihkan bila gagal.
     * @return array<int, array<string, mixed>>
     */
    private function createReturns(array $data, array &$proofPaths): array
    {
        if ($data['_return_groups'] === []) {
            return [];
        }

        $profiles = collect($data['armadas'] ?? [])->keyBy(fn ($a) => mb_strtoupper((string) $a['code']));
        $results = [];

        foreach ($data['_return_groups'] as $group) {
            $profile = $profiles->get(mb_strtoupper($group['armada_code']));
            $customer = $profile !== null
                ? ArmadaUpsert::upsertProfile($profile)
                : Customer::where('customer_code', $group['armada_code'])->where('status', 1)->firstOrFail();

            $proofPath = CustomerReturnCreation::storeProofFromInput($data['photos'][0] ?? null, null, false);
            $proofPaths[] = $proofPath;

            $result = CustomerReturnCreation::create([
                'customer_id' => (int) $customer->customer_id,
                'return_date' => $data['payment_date'],
                'ref_number' => $data['ref_payment_id'],
                'notes' => 'Potongan barang dari pembayaran '.$data['ref_payment_id'],
                'proof_path' => $proofPath,
                'qc_staff_id' => null,
                'created_by' => null,
                'ref_shipment_id' => $group['ref_shipment_id'],
                'idempotency_key' => $this->returnIdempotencyKey($data, $group),
            ], $group['supply_details'], $group['product_details']);

            $results[] = $this->presentReturn($result, $group);
        }

        return $results;
    }

    /** Dokumen retur milik pembayaran ini yang sudah ada — dipakai saat kiriman ulang. */
    private function findReturns(array $data): array
    {
        $results = [];
        foreach ($data['_return_groups'] as $group) {
            $existing = CustomerReturnCreation::findByIdempotencyKey($this->returnIdempotencyKey($data, $group));
            if ($existing !== null) {
                $results[] = $this->presentReturn($existing, $group);
            }
        }

        return $results;
    }

    /**
     * Kunci idempotensi dokumen retur = kunci pembayaran (ref_payment_id + penerima) + kelompoknya.
     * Diawali penanda sumber supaya tidak pernah bertabrakan dengan kunci dari /shipments/returns.
     */
    private function returnIdempotencyKey(array $data, array $group): string
    {
        $recipient = (int) $data['payment_type'] === self::TYPE_ARMADA
            ? 'armada:'.$data['_armada_customer_id']
            : 'sales:'.$data['_sales_staff_id'];

        return hash('sha256', json_encode([
            'source' => 'payments/cash',
            'ref_payment_id' => $data['ref_payment_id'],
            'recipient' => $recipient,
            'armada_code' => mb_strtoupper($group['armada_code']),
            'ref_shipment_id' => $group['ref_shipment_id'],
        ]));
    }

    private function presentReturn(array $result, array $group): array
    {
        $pending = collect($group['supply_details'])->whereNull('warehouse_id')->count()
            + collect($group['product_details'])->whereNull('warehouse_id')->count();

        return [
            'armada_code' => $group['armada_code'],
            'ref_shipment_id' => $group['ref_shipment_id'],
            'return_number' => $result['return_group'],
            'supply_return_id' => $result['supply_return_id'],
            'product_return_id' => $result['product_return_id'],
            'pending_warehouse_items' => $pending,
        ];
    }

    private function deleteReturnProofs(array $proofPaths): void
    {
        foreach ($proofPaths as $path) {
            CustomerReturnCreation::deleteProof($path);
        }
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

        $staffExternalRef = null;
        if (! $isArmada && $payment->staff_id) {
            $staffExternalRef = Staff::where('staff_id', (int) $payment->staff_id)->value('external_ref_id');
        }

        $armadaCode = null;
        if ($isArmada && $payment->customer_id) {
            $armadaCode = Customer::where('customer_id', (int) $payment->customer_id)->value('customer_code');
        }

        return [
            'ref_payment_id' => $payment->ref_payment_id,
            'payment_id' => (int) $id,
            'payment_type' => $paymentType,
            'payment_date' => (string) ($isArmada ? $payment->cr_date : $payment->cs_date),
            'payment_amount' => (int) ($isArmada ? $payment->cr_nominal : $payment->cs_nominal),
            'notes' => (string) ($isArmada ? $payment->cr_notes : $payment->cs_notes),
            'armada_code' => $isArmada ? ($armadaCode !== null ? (string) $armadaCode : null) : null,
            'armada_id' => $isArmada ? (int) $payment->customer_id : null,
            // Kembalikan external_ref_id supaya konsisten dengan body request /master/sales.
            'staff_id' => $isArmada ? null : ($staffExternalRef !== null ? (string) $staffExternalRef : null),
            'status' => $this->statusLabel((int) $payment->status),
            'items' => $details->map(static fn ($detail) => [
                'amount' => (int) ($isArmada ? $detail->crd_nominal : $detail->csd_nominal),
                'notes' => $isArmada ? $detail->crd_notes : $detail->csd_notes,
                'type' => (int) ($isArmada ? $detail->crd_type : $detail->csd_type),
                'kind' => (string) (($isArmada ? $detail->crd_kind : $detail->csd_kind) ?: self::KIND_CASH),
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
