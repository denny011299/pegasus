<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashGudangDetail extends Model
{
    protected $table = "cash_gudang_details";
    protected $primaryKey = "cgd_id";
    public $timestamps = true;
    public $incrementing = true;

    function getCashGudangDetail($data = [])
    {
        $data = array_merge([
            "cg_id" => null,
            "customer_id" => null,
        ], $data);

        $result = CashGudangDetail::where('status', '=', 1);
        if($data["cg_id"]) $result->where('cg_id', '=', $data["cg_id"]);
        if($data["customer_id"]) $result->where('customer_id', '=', $data["customer_id"]);
        $result->orderBy('created_at', 'desc');

        $result = $result->get();
        foreach ($result as $key => $value) {
            $value->customer_notes = Customer::find($value['customer_id'])->customer_notes;
        }
        return $result;
    }

    function insertCashGudangDetail($data)
    {
        $t = new CashGudangDetail();
        $t->cg_id = $data['cg_id'];
        $t->customer_id = $data['customer_id'];
        $t->cgd_nominal = $data["cgd_nominal"];
        $t->cgd_notes = $data["cgd_notes"];
        // Opsional: No./id pengiriman PMO (API atau form terima armada). Kosong = baris lama/non-pengiriman.
        if (array_key_exists('ref_shipment_id', $data)) {
            $ref = trim((string) ($data['ref_shipment_id'] ?? ''));
            $t->ref_shipment_id = $ref !== '' ? $ref : null;
        }
        $t->save();
        return $t->cgd_id;
    }

    function updateCashGudangDetail($data)
    {
        $t = CashGudangDetail::find($data["cgd_id"]);
        $t->cg_id = $data['cg_id'];
        $t->customer_id = $data['customer_id'];
        $t->cgd_nominal = $data["cgd_nominal"];
        $t->cgd_notes = $data["cgd_notes"];
        if (array_key_exists('ref_shipment_id', $data)) {
            $ref = trim((string) ($data['ref_shipment_id'] ?? ''));
            $t->ref_shipment_id = $ref !== '' ? $ref : null;
        }
        $t->save();
        return $t->cgd_id;
    }

    function deleteCashGudangDetail($data)
    {
        $t = CashGudangDetail::find($data["cgd_id"]);
        $t->status = 0;
        $t->save();
    }

    /**
     * Ref pengiriman PMO yang sudah terpakai di detail Kas Gudang aktif (status=1).
     * Dipakai dropdown + guard simpan supaya satu dokumen tidak dipakai dua kali.
     *
     * @param  array<int, int>  $exceptCgdIds  cgd_id yang diabaikan (baris yang sedang diedit)
     * @return array<string, true>  map ref => true (O(1) lookup)
     */
    public static function usedRefShipmentMap(array $exceptCgdIds = []): array
    {
        $q = static::query()
            ->where('status', 1)
            ->whereNotNull('ref_shipment_id')
            ->where('ref_shipment_id', '!=', '');

        if ($exceptCgdIds !== []) {
            $q->whereNotIn('cgd_id', $exceptCgdIds);
        }

        $map = [];
        foreach ($q->pluck('ref_shipment_id') as $ref) {
            $key = trim((string) $ref);
            if ($key !== '') {
                $map[$key] = true;
            }
        }

        return $map;
    }
}
