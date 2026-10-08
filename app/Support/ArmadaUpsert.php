<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Database\QueryException;

/**
 * Resolusi armada_code menjadi baris customers, membuatnya secara otomatis (bare — hanya
 * customer_code, tanpa profil) kalau belum ada sama sekali.
 *
 * Dipakai App\Http\Controllers\ExternalApi\V1\ShipmentController::scheduled()/shipped() sejak
 * GitHub #187 — sebelumnya armada_code yang belum tersinkron (lewat Sinkronisasi Armada ATAU
 * PUT /api/external/v1/armada/{code}) ditolak VALIDATION_FAILED, memaksa PMO memanggil armada
 * dulu sebelum shipment apa pun bisa dijadwalkan. Baris yang dibuat di sini SENGAJA minim (tidak
 * ada PIC/No Pol/telepon/saldo — shipment tidak membawa data itu) — kalau armada itu nanti benar-
 * benar disinkronkan lewat Pusat Sinkronisasi atau PUT /armada/{code}, baris yang sama akan
 * diperbarui/diadopsi seperti biasa (customer_code adalah kunci unik, bukan kolom rujukan
 * terpisah seperti ref_armada_id).
 *
 * Semantik "upsert"-nya sama persis dengan MasterArmadaController::update() — bare create kalau
 * belum ada, DIAKTIFKAN KEMBALI kalau sudah ada tapi nonaktif (bukan diperlakukan sebagai belum
 * ada) — cuma tanpa payload profil apa pun untuk ditimpakan, karena shipment tidak membawanya.
 */
class ArmadaUpsert
{
    public static function resolveOrCreate(string $code): Customer
    {
        $customer = Customer::where('customer_code', $code)->first();

        if ($customer !== null) {
            if ((int) $customer->status !== 1) {
                $customer->status = 1;
                $customer->external_api_synced_at = now();
                $customer->save();
            }

            return $customer;
        }

        try {
            return self::create($code);
        } catch (QueryException $e) {
            // Dua permintaan dengan armada_code baru yang sama, nyaris bersamaan: keduanya
            // sama-sama tidak menemukan baris di atas, lalu unique index menolak yang kalah cepat
            // — baris yang barusan dibuat permintaan lain itu yang dipakai, bukan galat 500.
            $existing = Customer::where('customer_code', $code)->first();

            if ($existing === null) {
                throw $e;
            }

            return $existing;
        }
    }

    private static function create(string $code): Customer
    {
        $customer = new Customer();
        $customer->customer_code = $code;
        $customer->status = 1;
        $customer->created_by = null;
        $customer->external_api_synced_at = now();
        $customer->save();

        return $customer;
    }

    /**
     * Field profil armada => kolom customers — SENGAJA sama persis dengan
     * App\Http\Controllers\ExternalApi\V1\MasterArmadaController::FIELD_MAP (PUT /armada/{code}),
     * supaya PMO memakai kosakata field yang sama di kedua tempat (pic/pic_phone/nomor_polisi/
     * category/merk_model/tahun_kendaraan/lokasi) — bukan menciptakan nama field baru untuk
     * konsep yang sama. nomor_polisi -> customer_notes mengikuti kuirk yang sama dengan endpoint
     * itu (menumpang kolom catatan umum, bukan kolom pelat nomor khusus).
     *
     * @var array<string, string>
     */
    private const PROFILE_FIELD_MAP = [
        'pic' => 'customer_pic',
        'pic_phone' => 'customer_pic_phone',
        'nomor_polisi' => 'customer_notes',
        'category' => 'customer_category',
        'merk_model' => 'customer_merk_model',
        'tahun_kendaraan' => 'customer_tahun_kendaraan',
        'lokasi' => 'customer_lokasi',
    ];

    /**
     * Upsert armada dari objek profil (GitHub #203 follow-up, 2026-09-25) — dipakai
     * App\Http\Controllers\ExternalApi\V1\ShipmentReturnController::store() ketika PMO mengirim
     * body.armada (berisi code + field profil) sebagai GANTI armada_code biasa, supaya armada yang
     * belum pernah tersinkron tidak perlu didaftarkan lebih dulu lewat PUT /armada/{code} sebelum
     * bisa membuat retur untuknya.
     *
     * BEDA SENGAJA dari MasterArmadaController::update() (PUT /armada/{code}): method itu
     * MENIMPA PENUH (field yang tidak dikirim di-null-kan, applyPayload() selalu menulis
     * `$data[$field] ?? null`) karena itu memang kanal utama sinkronisasi profil armada. Method
     * INI cuma sisipan di endpoint retur, PMO belum tentu selalu membawa profil lengkap tiap kali
     * bikin retur — jadi HANYA field yang benar-benar dikirim (bukan null/kosong) yang ditulis,
     * field yang tidak disebutkan dibiarkan APA ADANYA (baik pada baris baru maupun baris yang
     * sudah ada). Kalau field profil armada memang perlu ditimpa/dikosongkan, tetap pakai
     * PUT /armada/{code} — bukan lewat sini.
     *
     * @param  array{code:string, pic?:?string, pic_phone?:?string, nomor_polisi?:?string, category?:?string, merk_model?:?string, tahun_kendaraan?:?string, lokasi?:?string}  $data
     */
    public static function upsertProfile(array $data): Customer
    {
        $code = (string) $data['code'];
        $customer = Customer::where('customer_code', $code)->first();

        if ($customer === null) {
            try {
                return self::createWithProfile($code, $data);
            } catch (QueryException $e) {
                $existing = Customer::where('customer_code', $code)->first();
                if ($existing === null) {
                    throw $e;
                }
                $customer = $existing;
            }
        }

        if ((int) $customer->status !== 1) {
            $customer->status = 1;
        }
        self::applyProfileFields($customer, $data);
        $customer->external_api_synced_at = now();
        $customer->save();

        return $customer;
    }

    private static function createWithProfile(string $code, array $data): Customer
    {
        $customer = new Customer();
        $customer->customer_code = $code;
        $customer->status = 1;
        $customer->created_by = null;
        self::applyProfileFields($customer, $data);
        $customer->external_api_synced_at = now();
        $customer->save();

        return $customer;
    }

    private static function applyProfileFields(Customer $customer, array $data): void
    {
        foreach (self::PROFILE_FIELD_MAP as $field => $column) {
            if (! empty($data[$field])) {
                $customer->{$column} = $data[$field];
            }
        }
    }
}
