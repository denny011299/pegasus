<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/cash-gudang/armada-allowance
 */
class CashGudangArmadaAllowanceDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'kas-gudang-armada-allowance';
    }

    public function title(): string
    {
        return 'Lookup Uang Sangu / Bekal Armada (Kas Gudang)';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/cash-gudang/armada-allowance';
    }

    public function group(): string
    {
        return 'pembayaran';
    }

    public function description(): string
    {
        return 'Mencari nominal uang sangu/bekal armada yang sudah ACC di Kas Operasional '
            .'(Kas Gudang — penyerahan kas armada), berdasarkan No. Pengiriman dan opsional '
            .'kode armada / nomor polisi. Default hanya status ACC (accepted): sebelum ACC '
            .'uang bisa ditolak, jadi belum boleh dihitung di PMO. Hasil bisa >1 baris; '
            .'meta.nominal = nominal baris terbaru.';
    }

    public function queryParameters(): array
    {
        return [
            ['name' => 'pengiriman_no', 'type' => 'string', 'required' => false,
                'description' => 'No. dokumen pengiriman (contoh 00056/FLMNT/10/2026). Wajib jika ref_shipment_id kosong.'],
            ['name' => 'ref_shipment_id', 'type' => 'string', 'required' => false,
                'description' => 'Referensi pengiriman PMO. Wajib jika pengiriman_no kosong. Boleh sama dengan no. dokumen.'],
            ['name' => 'armada_code', 'type' => 'string', 'required' => false,
                'description' => 'Kode armada (customers.customer_code). Opsional untuk mempersempit hasil.'],
            ['name' => 'nomor_polisi', 'type' => 'string', 'required' => false,
                'description' => 'Nomor polisi armada (contoh W 1289 FE). Opsional.'],
            ['name' => 'cash_status', 'type' => 'string', 'required' => false,
                'description' => 'accepted (default) | pending | any. Hanya ACC yang dihitung untuk pembayaran; Ditolak tidak pernah keluar.'],
        ];
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'cgd_id' => 88,
                    'cg_id' => 42,
                    'pengiriman_no' => '00056/FLMNT/10/2026',
                    'ref_shipment_id' => '12345',
                    'armada_code' => 'ARM-W1289FE',
                    'nomor_polisi' => 'W 1289 FE',
                    'nominal' => 1000000,
                    'cash_date' => '2026-11-10',
                    'cash_status' => 'accepted',
                    'cash_notes' => 'Penyerahan kas armada dari gudang - Okejob 1',
                ],
            ],
            'meta' => [
                'count' => 1,
                'nominal' => 1000000,
            ],
        ];
    }

    public function errors(): array
    {
        return [
            ['code' => 'validation_failed', 'http_status' => 422,
                'message' => 'pengiriman_no / ref_shipment_id wajib, atau parameter tidak valid.'],
            ['code' => 'allowance_not_found', 'http_status' => 404,
                'message' => 'Tidak ada Kas Gudang ACC untuk pengiriman/armada ini (belum ACC / ditolak / tidak ada).'],
        ];
    }
}
