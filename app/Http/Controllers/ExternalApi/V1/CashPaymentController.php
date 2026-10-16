<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Errors\ErrorCatalog;
use App\ExternalApi\Http\ApiResponse;
use App\ExternalApi\Support\AbortWithResponse;
use App\ExternalApi\Support\PaymentPhotoStore;
use App\ExternalApi\Support\ReturnItemResolver;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use App\Models\Cash;
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
 * ITEM CAMPURAN ARAH (revisi 2026-10-16): items[].type boleh campuran.
 *   - type 1+2 → SATU dokumen Kas Operasional (ref_direction=1), nominal net = masuk−keluar,
 *     rincian tetap per type; ACC dompet wajib (kecuali auto_accept).
 *   - type 3 → langsung Kas Besar kolom Keluar 1 (cash_type=3, status aktif), tanpa ACC;
 *     marker idempotensi di cash_armadas/cash_sales ref_direction=3 (disembunyikan di list operasional).
 * Kunci idempotensi: ref_payment_id + penerima + ref_direction (1 atau 3).
 *
 * EXACTLY-ONCE untuk retry PMO (2026-10-16): PMO mengirim ulang payload identik sampai mendapat
 * jawaban. Pembanding "isi sama" adalah ref_payload_hash (sidik jari item kiriman pertama per
 * arah), BUKAN rincian saat ini — ACC/tolak/hapus oleh staf tidak membuat retry jadi 409. Ref
 * lama berakhiran -K/-K1 ikut dikenali (findExisting). auto_accept berjalan di dalam transaksi,
 * jadi kegagalan apa pun tidak meninggalkan sisa. Test: ExternalApiCashPaymentRetryReplayFlowTest.
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

    /** Bucket idempotensi: 1 = Kas Operasional (item type 1+2), 3 = Kas Besar Keluar 1. */
    private const DIRECTION_OPERASIONAL = 1;
    private const DIRECTION_KELUAR1 = 3;

    /** Jenis item pembayaran (crd_kind/csd_kind). */
    private const KIND_CASH = 'cash';
    private const KIND_POTONGAN = 'potongan';
    private const KIND_POTONGAN_BARANG = 'potongan_barang';

    /**
     * POST /api/external/v1/payments/cash
     *
     * Item boleh campuran. type 1+2 digabung satu dokumen operasional; type 3 ke Kas Besar
     * Keluar 1. Respons data = array pembayaran (0–2 elemen per call).
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request);
        $groups = $this->directionGroups($data['items']);

        // --- Idempotensi -------------------------------------------------
        // Kunci: ref_payment_id + penerima + bucket (1 operasional / 3 keluar 1).
        $existing = $this->findExisting($data);

        if ($conflict = $this->contentConflict($existing, $groups)) {
            return $conflict;
        }

        $order = array_keys($groups);
        $groups = array_diff_key($groups, $existing->all());

        if ($groups === []) {
            return $this->replay($existing, $data, $order);
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

                // ACC hanya untuk dokumen operasional (bucket 1). Keluar 1 sudah status aktif di Kas Besar.
                if (! empty($data['auto_accept'])) {
                    foreach ($payments as $i => $payment) {
                        if ((int) ($payment->ref_direction ?? 0) === self::DIRECTION_KELUAR1) {
                            continue;
                        }

                        $failure = $this->accept($payment, $data['payment_type']);

                        if ($failure !== null) {
                            throw new AbortWithResponse($failure);
                        }

                        $payments[$i] = $this->reload($payment, $data['payment_type']);
                    }
                }

                return $payments;
            });
        } catch (AbortWithResponse $e) {
            $photos->cleanup();
            $this->deleteReturnProofs($returnProofs);

            return $e->response;
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
                // Seluruh kiriman satu transaksi, jadi pemenang balapan sudah membuat semua arah
                // yang tadinya belum ada: cukup cek konflik isi lalu replay.
                return $this->contentConflict($raced, $this->directionGroups($data['items']))
                    ?? $this->replay($raced, $data, $order);
            }

            throw $e;
        } catch (\Throwable $e) {
            // Berkas foto tidak ikut dibatalkan database, jadi dibereskan di
            // sini agar tidak tertinggal sebagai berkas yatim.
            $photos->cleanup();
            $this->deleteReturnProofs($returnProofs);

            throw $e;
        }

        // Arah yang sudah tersimpan ikut dikembalikan, supaya data selalu mencakup seluruh item
        // kiriman ini; replayed_types menandai arah mana yang tidak dibuat ulang.
        $all = $existing->values()->concat($payments)
            ->sortBy(fn ($payment) => array_search((int) $payment->ref_direction, $order, true))
            ->values();

        return ApiResponse::success(
            $all->map(fn ($payment) => $this->present($payment, $data['payment_type']))->all(),
            ['returns' => array_merge($this->findReturns($data, $existing), $returns), 'replayed_types' => $existing->keys()->values()->all()],
            201,
        );
    }

    /**
     * Jawaban kiriman ulang: seluruh baris kas yang sudah ada untuk kunci ini, apa adanya (status
     * terkini, mis. sudah di-ACC), urut sesuai type pada kiriman — sama dengan saat pertama dibuat.
     */
    private function replay(Collection $existing, array $data, array $order): JsonResponse
    {
        $rows = $existing->sortBy(fn ($payment, $direction) => array_search($direction, $order, true))->values();

        return ApiResponse::success(
            $rows->map(fn ($payment) => $this->present($payment, $data['payment_type']))->all(),
            ['idempotent_replay' => true, 'returns' => $this->findReturns($data, $existing)],
        );
    }

    /**
     * Bucket item API: type 1+2 → ref_direction 1 (Kas Operasional), type 3 → ref_direction 3 (Kas Besar).
     *
     * @return array<int, array<int, array>>
     */
    private function directionGroups(array $items): array
    {
        $operasional = [];
        $keluar1 = [];
        foreach ($items as $item) {
            if ((int) $item['type'] === self::DIRECTION_KELUAR1) {
                $keluar1[] = $item;
            } else {
                $operasional[] = $item;
            }
        }

        $groups = [];
        if ($operasional !== []) {
            $groups[self::DIRECTION_OPERASIONAL] = $operasional;
        }
        if ($keluar1 !== []) {
            $groups[self::DIRECTION_KELUAR1] = $keluar1;
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

            if ($sent === null || ! $this->sameContent($payment, $sent)) {
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

    /**
     * Isi kiriman sama dengan yang dulu membuat baris ini? Dibandingkan dengan sidik jari kiriman
     * asli (ref_payload_hash), bukan rincian saat ini — ACC/tolak/hapus oleh staf IPM tidak boleh
     * membuat retry yang sah jadi 409. Baris lama tanpa sidik jari dibandingkan lewat rinciannya.
     */
    private function sameContent($payment, array $sent): bool
    {
        if ($payment->ref_payload_hash !== null) {
            return hash_equals($payment->ref_payload_hash, $this->payloadHash($sent));
        }

        return $this->normalizeItems($sent, false) === $this->storedItems($payment);
    }

    /** sha256 item satu arah (termasuk goods) dalam bentuk ternormalisasi. */
    private function payloadHash(array $items): string
    {
        return hash('sha256', json_encode($this->normalizeItems($items, true)));
    }

    /**
     * Rincian tersimpan dalam bentuk yang sama dengan normalizeItems(), untuk baris lama tanpa
     * ref_payload_hash. Semua status ikut (hapus pembayaran men-nonaktifkan rinciannya), urut id.
     */
    private function storedItems($payment): array
    {
        $isArmada = $payment instanceof CashArmada;
        $details = $isArmada
            ? CashArmadaDetail::where('cr_id', '=', $payment->cr_id)->orderBy('crd_id')->get()
            : CashSalesDetail::where('cs_id', '=', $payment->cs_id)->orderBy('csd_id')->get();

        return $this->normalizeItems($details->map(fn ($d) => [
            'amount' => $isArmada ? $d->crd_nominal : $d->csd_nominal,
            'type' => $isArmada ? $d->crd_type : $d->csd_type,
            'kind' => $isArmada ? $d->crd_kind : $d->csd_kind,
            'notes' => $isArmada ? $d->crd_notes : $d->csd_notes,
            'ref_nota_id' => $d->ref_nota_id,
        ])->all(), false);
    }

    /**
     * Bentuk pembanding item: null = "" = spasi, teks di-trim, kind kosong = cash, angka jadi
     * integer, id (ref_id/satuan_id) tetap string supaya id 16 digit tidak berubah.
     */
    private function normalizeItems(array $items, bool $withGoods): array
    {
        $text = static fn ($v) => ($v === null || trim((string) $v) === '') ? null : trim((string) $v);

        return array_map(function ($item) use ($text, $withGoods) {
            $row = [
                (int) $item['amount'],
                (int) $item['type'],
                $text($item['kind'] ?? null) ?? self::KIND_CASH,
                $text($item['notes'] ?? null),
                $text($item['ref_nota_id'] ?? null),
            ];

            if ($withGoods) {
                $goods = $item['goods'] ?? null;
                $code = $goods === null ? null : $text($goods['armada_code'] ?? null);
                $row[] = $goods === null ? null : [
                    (int) $goods['item_type'],
                    $text($goods['ref_id'] ?? null),
                    (int) $goods['qty'],
                    $text($goods['satuan_id'] ?? null),
                    $code === null ? null : mb_strtoupper($code),
                    $text($goods['ref_shipment_id'] ?? null),
                ];
            }

            return $row;
        }, array_values($items));
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
            // Bucket sama store(): type 1+2 → operasional (1), type 3 → keluar 1 (3).
            $direction = (int) $item['type'] === self::DIRECTION_KELUAR1
                ? self::DIRECTION_KELUAR1
                : self::DIRECTION_OPERASIONAL;
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
     * type 1+2 → satu dokumen Kas Operasional (pending ACC); type 3 → Kas Besar Keluar 1 langsung.
     */
    private function createArmada(array $data, int $direction, array $items, array $photoNames): CashArmada
    {
        $customer = $this->resolveArmadaCustomer($data);
        if ($customer === null) {
            $this->fail('armada_code', 'Armada tidak ditemukan.');
        }

        if ($direction === self::DIRECTION_KELUAR1) {
            return $this->createArmadaKeluar1($data, $customer, $items, $photoNames);
        }

        $net = $this->operasionalNet($items);
        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'ref_direction' => self::DIRECTION_OPERASIONAL,
            'ref_payload_hash' => $this->payloadHash($items),
            'ref_payment_number' => $this->refPaymentNumber($data),
            'customer_id' => (int) $customer->customer_id,
            'cash_id' => 0,
            'cr_date' => $data['payment_date'],
            // Net signed: ACC cr_type=1 memakai += jadi masuk/keluar ikut net.
            'cr_nominal' => $net,
            'cr_notes' => $this->operasionalNotes('armada', $customer->customer_notes, $items),
            'cr_type' => 1,
            'cr_aksi' => 2,
            'status' => 1,
        ];

        if ($photoNames !== []) {
            $row['cr_img'] = json_encode($photoNames);
        }

        $crId = (new CashArmada())->insertCashArmada($row);
        $this->insertArmadaDetails($crId, $items);

        return CashArmada::find($crId);
    }

    /**
     * Judul header Kas Besar untuk Keluar 1 API — singkat; rincian item ada di anakan.
     * Contoh: "Pembayaran Dokumen PB/NAJ/161026/001 — Nanang Joko Suwarno"
     */
    private function keluar1HeaderNotes(array $data, string $penerimaLabel): string
    {
        $number = $this->refPaymentNumber($data);
        $doc = $number !== '' ? $number : (string) ($data['ref_payment_id'] ?? '');
        $label = trim($penerimaLabel);

        return mb_substr(
            'Pembayaran Dokumen'.($doc !== '' ? ' '.$doc : '').($label !== '' ? ' - '.$label : ''),
            0,
            255
        );
    }

    /** Marker idempotensi + baris Kas Besar cash_type=3 status aktif (tanpa ACC). */
    private function createArmadaKeluar1(array $data, Customer $customer, array $items, array $photoNames): CashArmada
    {
        $amount = $this->sumAmount($items);
        $number = $this->refPaymentNumber($data);
        $desc = $this->keluar1HeaderNotes($data, (string) $customer->customer_notes);

        // Sama input manual /cash Keluar 1: status 2 = langsung aktif, tanpa ACC.
        $cashId = (new Cash())->insertCash([
            'person_id' => (int) $customer->customer_id,
            'cash_date' => $data['payment_date'],
            'cash_description' => $desc,
            'cash_nominal' => $amount,
            'cash_type' => 3,
            'cash_tujuan' => 3,
            'status' => 2,
        ]);

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'ref_direction' => self::DIRECTION_KELUAR1,
            'ref_payload_hash' => $this->payloadHash($items),
            'ref_payment_number' => $number,
            'customer_id' => (int) $customer->customer_id,
            'cash_id' => $cashId,
            'cr_date' => $data['payment_date'],
            'cr_nominal' => $amount,
            'cr_notes' => $desc,
            'cr_type' => 3,
            'cr_aksi' => 2,
            'status' => 2,
        ];
        if ($photoNames !== []) {
            $row['cr_img'] = json_encode($photoNames);
        }

        $crId = (new CashArmada())->insertCashArmada($row);
        $this->insertArmadaDetails($crId, $items);

        return CashArmada::find($crId);
    }

    private function insertArmadaDetails(int $crId, array $items): void
    {
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
    }

    private function createSales(array $data, int $direction, array $items, array $photoNames): CashSales
    {
        $staff = $this->resolveSalesStaff($data['staff_id']);
        if ($staff === null) {
            $this->fail('staff_id', 'Sales dengan staff_id (external_ref_id) '.$data['staff_id'].' tidak ditemukan.');
        }

        if ($direction === self::DIRECTION_KELUAR1) {
            return $this->createSalesKeluar1($data, $staff, $items, $photoNames);
        }

        $net = $this->operasionalNet($items);
        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'ref_payment_number' => $this->refPaymentNumber($data),
            'ref_direction' => self::DIRECTION_OPERASIONAL,
            'ref_payload_hash' => $this->payloadHash($items),
            'staff_id' => (int) $staff->staff_id,
            'cash_id' => 0,
            'bank_id' => 0,
            'cs_date' => $data['payment_date'],
            'cs_nominal' => $net,
            'cs_notes' => $this->operasionalNotes('sales', $staff->staff_name, $items),
            'cs_type' => 2,
            // 1 = jalur Masuk di ACC (+= nominal signed).
            'cs_transaction' => 1,
            'status' => 1,
        ];

        if ($photoNames !== []) {
            $row['cs_img'] = json_encode($photoNames);
        }

        $csId = (new CashSales())->insertCashSales($row);
        $this->insertSalesDetails($csId, $items);

        return CashSales::find($csId);
    }

    private function createSalesKeluar1(array $data, Staff $staff, array $items, array $photoNames): CashSales
    {
        $amount = $this->sumAmount($items);
        $number = $this->refPaymentNumber($data);
        $desc = $this->keluar1HeaderNotes($data, (string) $staff->staff_name);

        $cashId = (new Cash())->insertCash([
            'person_id' => (int) $staff->staff_id,
            'cash_date' => $data['payment_date'],
            'cash_description' => $desc,
            'cash_nominal' => $amount,
            'cash_type' => 3,
            'cash_tujuan' => 4,
            'status' => 2,
        ]);

        $row = [
            'ref_payment_id' => $data['ref_payment_id'],
            'ref_payment_number' => $number,
            'ref_direction' => self::DIRECTION_KELUAR1,
            'ref_payload_hash' => $this->payloadHash($items),
            'staff_id' => (int) $staff->staff_id,
            'cash_id' => $cashId,
            'bank_id' => 0,
            'cs_date' => $data['payment_date'],
            'cs_nominal' => $amount,
            'cs_notes' => $desc,
            'cs_type' => 2,
            'cs_transaction' => 3,
            'status' => 2,
        ];
        if ($photoNames !== []) {
            $row['cs_img'] = json_encode($photoNames);
        }

        $csId = (new CashSales())->insertCashSales($row);
        $this->insertSalesDetails($csId, $items);

        return CashSales::find($csId);
    }

    private function insertSalesDetails(int $csId, array $items): void
    {
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
    }

    /** Net Kas Operasional: sum(type 1) − sum(type 2). */
    private function operasionalNet(array $items): int
    {
        $net = 0;
        foreach ($items as $item) {
            $amount = (int) $item['amount'];
            $net += (int) $item['type'] === 2 ? -$amount : $amount;
        }

        return $net;
    }

    private function operasionalNotes(string $kind, string $who, array $items): string
    {
        $types = collect($items)->pluck('type')->unique()->sort()->values()->all();
        $prefix = match (true) {
            $types === [1] => $kind === 'armada' ? 'Setoran armada ' : 'Setoran sales ',
            $types === [2] => $kind === 'armada' ? 'Pengeluaran armada ' : 'Pengeluaran sales ',
            default => $kind === 'armada' ? 'Pembayaran armada ' : 'Pembayaran sales ',
        };

        return $prefix.$who;
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
    private function findReturns(array $data, Collection $payments): array
    {
        $results = [];
        foreach ($data['_return_groups'] as $group) {
            $payment = $payments->get($group['direction']);
            if ($payment === null) {
                continue;
            }
            // Kunci memakai ref baris kasnya sendiri, supaya dokumen milik baris lama "<ref>-K"
            // juga ketemu.
            $ref = $payment->ref_payment_id;
            $existing = CustomerReturnCreation::findByIdempotencyKey($this->returnIdempotencyKey($data, $group, true, $ref))
                ?? CustomerReturnCreation::findByIdempotencyKey($this->returnIdempotencyKey($data, $group, false, $ref));
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
    private function returnIdempotencyKey(array $data, array $group, bool $withDirection = true, ?string $ref = null): string
    {
        $recipient = (int) $data['payment_type'] === self::TYPE_ARMADA
            ? 'armada:'.$data['_armada_customer_id']
            : 'sales:'.$data['_sales_staff_id'];

        $key = [
            'source' => 'payments/cash',
            'ref_payment_id' => $ref ?? $data['ref_payment_id'],
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
     * Akhiran ref lama per arah. Sebelum POST ini menerima item campuran, PMO mengirim pengeluaran
     * dokumen yang sama sebagai ref terpisah: "<ref>-K" (Keluar) dan "<ref>-K1" (Keluar 1). PMO
     * sekarang mengirim ref tanpa akhiran untuk dokumen lama juga, jadi baris lama itu harus ikut
     * dikenali — kalau tidak, pengeluaran dokumen lama tercatat dua kali.
     */
    private const LEGACY_REF_SUFFIX = [2 => '-K', 3 => '-K1'];

    /**
     * Cari baris kas milik kunci idempotensi ini (ref_payment_id + penerima yang sudah diresolusi
     * validatePayload()), dikunci ref_direction — dipakai store() untuk memutuskan arah mana yang
     * sudah ada (replay/409) dan mana yang baru dibuat. Arah Keluar/Keluar 1 yang tidak ada di ref
     * ini dicari juga di ref lama berakhiran -K/-K1 (lihat LEGACY_REF_SUFFIX).
     *
     * protected (bukan private) hanya supaya test balapan bisa mensimulasikan pemeriksaan awal
     * yang belum melihat baris pemenang.
     *
     * @return Collection<int, CashArmada|CashSales>
     */
    protected function findExisting(array $data): Collection
    {
        $isArmada = (int) $data['payment_type'] === self::TYPE_ARMADA;
        $refs = [$data['ref_payment_id']];
        foreach (self::LEGACY_REF_SUFFIX as $suffix) {
            $refs[] = $data['ref_payment_id'].$suffix;
        }

        $rows = $isArmada
            ? CashArmada::whereIn('ref_payment_id', $refs)->where('customer_id', '=', $data['_armada_customer_id'])->orderBy('cr_id')->get()
            : CashSales::whereIn('ref_payment_id', $refs)->where('staff_id', '=', $data['_sales_staff_id'])->orderBy('cs_id')->get();

        $found = $rows->where('ref_payment_id', $data['ref_payment_id'])->keyBy(fn ($row) => (int) $row->ref_direction);

        foreach (self::LEGACY_REF_SUFFIX as $direction => $suffix) {
            $legacy = $rows->firstWhere('ref_payment_id', $data['ref_payment_id'].$suffix);
            if (! $found->has($direction) && $legacy !== null && (int) $legacy->ref_direction === $direction) {
                $found->put($direction, $legacy);
            }
        }

        return $found;
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
