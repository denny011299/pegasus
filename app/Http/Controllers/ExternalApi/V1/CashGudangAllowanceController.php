<?php

namespace App\Http\Controllers\ExternalApi\V1;

use App\ExternalApi\Http\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CashGudangDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Lookup uang sangu / bekal armada dari Kas Gudang (penyerahan kas armada).
 *
 * PMO memakai ini saat pembayaran berdasar pengiriman: cari baris Kas Gudang
 * yang sudah diisi Nama Armada + No. Pengiriman, lalu ambil nominal-nya.
 *
 * Sumber data:
 *   - cash_gudang_details.cgd_notes     = No. Pengiriman (teks dokumen)
 *   - cash_gudang_details.ref_shipment_id = id/ref PMO bila ada
 *   - cash_gudang_details.customer_id   = armada (customers)
 *   - customers.customer_code           = armada_code
 *   - customers.customer_notes          = nomor polisi
 *   - cash_gudang_details.cgd_nominal   = nominal bekal
 */
class CashGudangAllowanceController extends Controller
{
    /**
     * GET /api/external/v1/cash-gudang/armada-allowance
     */
    public function show(Request $request)
    {
        $data = $request->validate([
            'pengiriman_no' => ['nullable', 'string', 'max:120'],
            'ref_shipment_id' => ['nullable', 'string', 'max:120'],
            'armada_code' => ['nullable', 'string', 'max:120'],
            'nomor_polisi' => ['nullable', 'string', 'max:120'],
            // pending = 1, accepted = 2 (ACC). Default accepted — uang belum ACC
            // bisa ditolak, jadi PMO tidak boleh menghitung sangu sebelum ACC.
            'cash_status' => ['nullable', 'string', 'in:pending,accepted,any'],
        ]);

        $pengirimanNo = trim((string) ($data['pengiriman_no'] ?? ''));
        $refShipment = trim((string) ($data['ref_shipment_id'] ?? ''));
        $armadaCode = trim((string) ($data['armada_code'] ?? ''));
        $nomorPolisi = trim((string) ($data['nomor_polisi'] ?? ''));
        $cashStatus = $data['cash_status'] ?? 'accepted';

        if ($pengirimanNo === '' && $refShipment === '') {
            return ApiResponse::error(
                'validation_failed',
                'Wajib isi pengiriman_no atau ref_shipment_id.',
                422,
                ['pengiriman_no' => ['Required unless ref_shipment_id is present.']]
            );
        }

        $hasRefCol = Schema::hasColumn('cash_gudang_details', 'ref_shipment_id');

        $q = CashGudangDetail::query()
            ->from('cash_gudang_details as d')
            ->join('cash_gudangs as g', 'g.cg_id', '=', 'd.cg_id')
            ->join('customers as c', 'c.customer_id', '=', 'd.customer_id')
            ->where('d.status', 1)
            ->where('c.status', 1);

        // 3 = Ditolak — tidak pernah dihitung sebagai uang sangu.
        if ($cashStatus === 'pending') {
            $q->where('g.status', 1);
        } elseif ($cashStatus === 'any') {
            $q->whereIn('g.status', [1, 2]);
        } else {
            $q->where('g.status', 2);
        }

        $q->where(function ($w) use ($pengirimanNo, $refShipment, $hasRefCol) {
            if ($pengirimanNo !== '') {
                $w->orWhere('d.cgd_notes', $pengirimanNo);
                if ($hasRefCol) {
                    $w->orWhere('d.ref_shipment_id', $pengirimanNo);
                }
            }
            if ($refShipment !== '') {
                if ($hasRefCol) {
                    $w->orWhere('d.ref_shipment_id', $refShipment);
                }
                $w->orWhere('d.cgd_notes', $refShipment);
            }
        });

        if ($armadaCode !== '') {
            $q->where('c.customer_code', $armadaCode);
        }
        if ($nomorPolisi !== '') {
            $q->where('c.customer_notes', $nomorPolisi);
        }

        $select = [
            'd.cgd_id',
            'd.cg_id',
            'd.cgd_nominal',
            'd.cgd_notes',
            'd.customer_id',
            'g.cg_date',
            'g.status as cg_status',
            'g.cg_notes',
            'c.customer_code',
            'c.customer_notes',
        ];
        if ($hasRefCol) {
            $select[] = 'd.ref_shipment_id';
        }

        $rows = $q
            ->orderByDesc('g.cg_date')
            ->orderByDesc('d.cgd_id')
            ->get($select);

        if ($rows->isEmpty()) {
            return ApiResponse::error(
                'allowance_not_found',
                'Tidak ada Kas Gudang ACC (uang sangu) untuk pengiriman/armada ini.',
                404
            );
        }

        $payload = $rows->map(function ($row) use ($hasRefCol) {
            $status = (int) $row->cg_status;
            $ref = $hasRefCol ? ($row->ref_shipment_id ?? null) : null;

            return [
                'cgd_id' => (int) $row->cgd_id,
                'cg_id' => (int) $row->cg_id,
                'pengiriman_no' => (string) ($row->cgd_notes ?? ''),
                'ref_shipment_id' => $ref !== null && $ref !== ''
                    ? (string) $ref
                    : null,
                'armada_code' => (string) ($row->customer_code ?? ''),
                'nomor_polisi' => (string) ($row->customer_notes ?? ''),
                'nominal' => (int) $row->cgd_nominal,
                'cash_date' => $row->cg_date
                    ? \Carbon\Carbon::parse($row->cg_date)->toDateString()
                    : null,
                'cash_status' => $status === 2 ? 'accepted' : 'pending',
                'cash_notes' => (string) ($row->cg_notes ?? ''),
            ];
        })->values()->all();

        return ApiResponse::success($payload, [
            'count' => count($payload),
            // Convenience: nominal baris terbaru (index 0) — kasus umum 1 pengiriman = 1 bekal.
            'nominal' => (int) ($payload[0]['nominal'] ?? 0),
        ]);
    }
}
