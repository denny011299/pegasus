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
}
