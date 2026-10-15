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
 * ITEM CAMPURAN ARAH (2026-10-15): items[].type boleh campuran Masuk/Keluar/Keluar 1. Satu baris
 * kas hanya bisa satu arah, jadi store() membuat satu baris per type dalam satu transaksi; arah
 * disimpan di ref_direction dan ikut kunci idempotensi (ref_payment_id + penerima + arah). Arah
 * yang belum ada boleh dikirim belakangan (dibuat), arah yang sudah ada harus sama persis (selain
 * itu 409 PAYMENT_CONTENT_MISMATCH). POST menjawab data berupa array pembayaran, dokumen
 * Pengembalian di meta.returns. payment_amount opsional (dicek hanya bila dikirim).
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
     *
     * Item boleh campuran arah (Masuk / Keluar / Keluar 1). Satu baris kas hanya bisa mencatat
     * satu arah (catatan, cr_type/cs_transaction diturunkan dari situ), jadi item dikelompokkan
     * per type dan dibuat SATU baris kas per kelompok, dalam satu transaksi DB. Karena itu data
     * respons selalu array pembayaran; dokumen Pengembalian (satu per arah + armada + pengiriman)
     * dikembalikan di meta.returns.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $groups = $this->directionGroups($data['items']);

        // --- Idempotensi -------------------------------------------------
        // Kunci idempotensi: ref_payment_id + penerima + arah (lihat catatan kelas soal #208 dan
        // migration add_ref_direction). Arah yang sudah tersimpan harus sama persis isinya dengan
        // kiriman ini (selain itu 409 — endpoint ini create-only, tidak bisa mengubah data); arah
        // yang belum ada dibuat. Kalau semua arah sudah ada, kiriman ini murni kiriman ulang.
        $existing = $this->findExisting($data);

        if ($conflict = $this->contentConflict($existing, $groups)) {
            return $conflict;
        }

        $order = array_keys($groups);
        $groups = array_diff_key($groups, $existing->all());

        if ($groups === []) {
            return $this->replay($existing, $data);
        }

        $photos = new PaymentPhotoStore();
        $returnProofs = [];
        $returns = [];

        try {
            $payments = DB::transaction(function () use ($data, $groups, $photos, &$returnProofs, &$returns) {
                $photoNames = ! empty($data['photos'])
                    ? $photos->store($data['photos'], $data['payment_type'] === self::TYPE_ARMADA ? 'armada' : 'sales')
                    : [];

                $payments = [];
                foreach ($groups as $direction => $items) {
                    $payments[] = $data['payment_type'] === self::TYPE_ARMADA
                        ? $this->createArmada($data, $direction, $items, $photoNames)
                        : $this->createSales($data, $direction, $items, $photoNames);
                }

                $returns = $this->createReturns($data, array_keys($groups), $returnProofs);

                return $payments;
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

            if ($raced->isNotEmpty()) {
                return $this->contentConflict($raced, $this->directionGroups($data['items']))
                    ?? $this->replay($raced, $data);
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
            foreach ($payments as $i => $payment) {
                $failure = $this->accept($payment, $data['payment_type']);

                if ($failure !== null) {
                    return $failure;
                }

                $payments[$i] = $this->reload($payment, $data['payment_type']);
            }
        }

        // Arah yang sudah tersimpan ikut dikembalikan, supaya data selalu mencakup seluruh item
        // kiriman ini; replayed_types menandai arah mana yang tidak dibuat ulang.
        $all = $existing->values()->concat($payments)
            ->sortBy(fn ($payment) => array_search((int) $payment->ref_direction, $order, true))
            ->values();

        return ApiResponse::success(
            $all->map(fn ($payment) => $this->present($payment, $data['payment_type']))->all(),
            ['returns' => array_merge($this->findReturns($data, $existing->keys()->all()), $returns), 'replayed_types' => $existing->keys()->values()->all()],
            201,
        );
    }

    /** Jawaban kiriman ulang: seluruh baris kas yang sudah ada untuk kunci ini, apa adanya. */
    private function replay(Collection $existing, array $data): JsonResponse
    {
        return ApiResponse::success(
            $existing->values()->map(fn ($payment) => $this->present($payment, $data['payment_type']))->all(),
            ['idempotent_replay' => true, 'returns' => $this->findReturns($data, $existing->keys()->all())],
        );
    }

    /**
     * Kelompokkan item per type (arah), urut sesuai kemunculan pertama, indeks item asli dibuang.
     *
     * @return array<int, array<int, array>>
     */
    private function directionGroups(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $groups[(int) $item['type']][] = $item;
        }

        return $groups;
    }

    /**
     * 409 bila isi kiriman berbeda dari baris kas yang sudah tersimpan untuk kunci ini: arah yang
     * sudah ada dengan rincian berbeda, atau arah tersimpan yang tidak ikut dikirim lagi. Endpoint
     * ini create-only, jadi perubahan seperti itu tidak boleh diam-diam dianggap kiriman ulang.
     *
     * @param  Collection<int, CashArmada|CashSales>  $existing  dikunci ref_direction
     */
    private function contentConflict(Collection $existing, array $groups): ?JsonResponse
    {
        foreach ($existing as $direction => $payment) {
            $sent = $groups[$direction] ?? null;
            $stored = $this->storedItems($payment);

            if ($sent === null || $this->normalizeItems($sent) !== $stored) {
                return ApiResponse::error(
                    ErrorCatalog::PAYMENT_CONTENT_MISMATCH,
                    'Pembayaran dengan ref_payment_id "'.$payment->ref_payment_id.'" untuk penerima ini sudah tersimpan dengan rincian type '
                        .$direction.' yang berbeda. Endpoint ini tidak bisa mengubah pembayaran — kirim dengan ref_payment_id baru.',
                    409,
                    ['type' => (int) $direction],
                );
            }
        }

        return null;
    }

    /** Rincian tersimpan dalam bentuk yang sama dengan normalizeItems(), untuk dibandingkan. */
    private function storedItems($payment): array
    {
        $isArmada = $payment instanceof CashArmada;
        $details = $isArmada
            ? CashArmadaDetail::where('cr_id', '=', $payment->cr_id)->where('status', '=', 1)->orderBy('crd_id')->get()
            : CashSalesDetail::where('cs_id', '=', $payment->cs_id)->where('status', '=', 1)->orderBy('csd_id')->get();

        return $this->normalizeItems($details->map(fn ($d) => [
            'amount' => $isArmada ? $d->crd_nominal : $d->csd_nominal,
            'type' => $isArmada ? $d->crd_type : $d->csd_type,
            'kind' => $isArmada ? $d->crd_kind : $d->csd_kind,
            'notes' => $isArmada ? $d->crd_notes : $d->csd_notes,
            'ref_nota_id' => $d->ref_nota_id,
        ])->all());
    }

    private function normalizeItems(array $items): array
    {
        $blankToNull = static fn ($v) => ($v === null || trim((string) $v) === '') ? null : trim((string) $v);

        return array_map(fn ($item) => [
            (int) $item['amount'],
            (int) $item['type'],
            ($item['kind'] ?? null) ?: self::KIND_CASH,
            $blankToNull($item['notes'] ?? null),
            $blankToNull($item['ref_nota_id'] ?? null),
        ], array_values($items));
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
            // No. Dokumen Pembayaran PMO (GitHub #216) — hanya label, tidak unik, bukan kunci idempotensi.
            'ref_payment_number' => ['nullable', 'string', 'max:64'],
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
            'payment_amount' => ['nullable', 'integer'],
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

        $this->assertAmountMatchesItems($data);
        $data['_return_groups'] = $this->prepareReturnGroups($data);

        return $data;
    }

    /** ref_payment_number kosong ("" / spasi) disimpan sebagai NULL, bukan string kosong. */
    private function refPaymentNumber(array $data): ?string
    {
        $number = trim((string) ($data['ref_payment_number'] ?? ''));

        return $number !== '' ? $number : null;
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
            // Per arah juga: tiap dokumen retur milik satu baris kas, supaya arah yang dikirim
            // belakangan tidak bertabrakan dengan dokumen arah yang sudah ada.
            $direction = (int) $item['type'];
            $key = $direction.'|'.mb_strtoupper($armadaCode).'|'.$refShipmentId;
            $groups[$key]['direction'] = $direction;
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
            [$supplyDetails, $productDetails] = $resolver->resolveItems($group['items'], 'items.%d.goods');
            $resolver->assertAgainstCatalog($supplyDetails, $productDetails);

            return [
                'direction' => $group['direction'],
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

    /** payment_amount opsional; kalau dikirim, wajib sama dengan jumlah seluruh item. */
    private function assertAmountMatchesItems(array $data): void
    {
        if (! isset($data['payment_amount'])) {
            return;
        }

        $total = $this->sumAmount($data['items']);

        if ((int) $data['payment_amount'] !== $total) {
            $this->fail(
                'payment_amount',
                'payment_amount ('.$data['payment_amount'].') tidak sama dengan jumlah item ('.$total.').'
            );
        }
    }

    private function sumAmount(array $items): int
    {
        return array_sum(array_map(static fn ($item) => (int) $item['amount'], $items));
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
    private function createArmada(array $data, int $direction, array $items, array $photoNames): CashArmada
    {
        $customer = $this->resolveArmadaCustomer($data);
        if ($customer === null) {
            $this->fail('armada_code', 'Armada tidak ditemukan.');
        }

        $isMasuk = $direction === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'ref_direction' => $direction,
            'ref_payment_number' => $this->refPaymentNumber($data),
            'customer_id' => (int) $customer->customer_id,
            'cash_id' => 0,
            'cr_date' => $data['payment_date'],
            'cr_nominal' => $this->sumAmount($items),
            'cr_notes' => ($isMasuk ? 'Setoran armada ' : 'Pengeluaran armada ').$customer->customer_notes,
            'cr_aksi' => 2,
            'status' => 1,
        ];

        if ($isMasuk) {
            $row['cr_type'] = 1;
        }

        if ($photoNames !== []) {
            $row['cr_img'] = json_encode($photoNames);
        }

        $crId = (new CashArmada())->insertCashArmada($row);

        foreach ($items as $item) {
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
    private function createSales(array $data, int $direction, array $items, array $photoNames): CashSales
    {
        $staff = $this->resolveSalesStaff($data['staff_id']);
        if ($staff === null) {
            // Sudah dicek di assertReferencedRecordExists — jaring pengaman race.
            $this->fail('staff_id', 'Sales dengan staff_id (external_ref_id) '.$data['staff_id'].' tidak ditemukan.');
        }

        $isMasuk = $direction === self::DIRECTION_MASUK;

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'ref_payment_number' => $this->refPaymentNumber($data),
            'ref_direction' => $direction,
            'staff_id' => (int) $staff->staff_id,
            'cash_id' => 0,
            // bank_id tidak punya nilai bawaan di model dan tidak dipakai pada
            // transaksi operasional; kolomnya sendiri berdefault 0.
            'bank_id' => 0,
            'cs_date' => $data['payment_date'],
            'cs_nominal' => $this->sumAmount($items),
            'cs_notes' => ($isMasuk ? 'Setoran sales ' : 'Pengeluaran sales ').$staff->staff_name,
            'cs_type' => 2,
            'cs_transaction' => $direction,
            'status' => 1,
        ];

        if ($photoNames !== []) {
            $row['cs_img'] = json_encode($photoNames);
        }

        $csId = (new CashSales())->insertCashSales($row);

        foreach ($items as $item) {
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
    private function createReturns(array $data, array $directions, array &$proofPaths): array
    {
        $groups = array_filter($data['_return_groups'], fn (array $group) => in_array($group['direction'], $directions, true));
        if ($groups === []) {
            return [];
        }

        $profiles = collect($data['armadas'] ?? [])->keyBy(fn ($a) => mb_strtoupper((string) $a['code']));
        $results = [];

        foreach ($groups as $group) {
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
                'ref_payment_number' => $this->refPaymentNumber($data),
            ], $group['supply_details'], $group['product_details']);

            $results[] = $this->presentReturn($result, $group);
        }

        return $results;
    }

    /**
     * Dokumen retur milik arah pembayaran yang sudah ada — dipakai saat kiriman ulang. Dokumen dari
     * sebelum pemecahan per arah memakai kunci tanpa arah, jadi kunci itu dicoba sebagai cadangan.
     */
    private function findReturns(array $data, array $directions): array
    {
        $results = [];
        foreach ($data['_return_groups'] as $group) {
            if (! in_array($group['direction'], $directions, true)) {
                continue;
            }
            $existing = CustomerReturnCreation::findByIdempotencyKey($this->returnIdempotencyKey($data, $group))
                ?? CustomerReturnCreation::findByIdempotencyKey($this->returnIdempotencyKey($data, $group, false));
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
    private function returnIdempotencyKey(array $data, array $group, bool $withDirection = true): string
    {
        $recipient = (int) $data['payment_type'] === self::TYPE_ARMADA
            ? 'armada:'.$data['_armada_customer_id']
            : 'sales:'.$data['_sales_staff_id'];

        $key = [
            'source' => 'payments/cash',
            'ref_payment_id' => $data['ref_payment_id'],
            'recipient' => $recipient,
            'armada_code' => mb_strtoupper($group['armada_code']),
            'ref_shipment_id' => $group['ref_shipment_id'],
        ];
        if ($withDirection) {
            $key['direction'] = $group['direction'];
        }

        return hash('sha256', json_encode($key));
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
     * Cari baris kas milik kunci idempotensi ini (ref_payment_id + penerima yang sudah diresolusi
     * validatePayload()), dikunci ref_direction — dipakai store() untuk memutuskan arah mana yang
     * sudah ada (replay/409) dan mana yang baru dibuat.
     *
     * @return Collection<int, CashArmada|CashSales>
     */
    private function findExisting(array $data): Collection
    {
        if ((int) $data['payment_type'] === self::TYPE_ARMADA) {
            return CashArmada::where('ref_payment_id', '=', $data['ref_payment_id'])
                ->where('customer_id', '=', $data['_armada_customer_id'])
                ->orderBy('cr_id')
                ->get()
                ->keyBy(fn ($row) => (int) $row->ref_direction);
        }

        return CashSales::where('ref_payment_id', '=', $data['ref_payment_id'])
            ->where('staff_id', '=', $data['_sales_staff_id'])
            ->orderBy('cs_id')
            ->get()
            ->keyBy(fn ($row) => (int) $row->ref_direction);
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
            'ref_payment_number' => $payment->ref_payment_number,
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
