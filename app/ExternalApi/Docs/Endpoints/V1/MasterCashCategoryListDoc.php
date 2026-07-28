<?php

namespace App\ExternalApi\Docs\Endpoints\V1;

use App\ExternalApi\Docs\ApiEndpointDoc;

/**
 * Dokumentasi GET /api/external/v1/master/cash_categories (API-001).
 *
 * Contoh respons diambil dari keluaran endpoint yang sebenarnya terhadap data
 * yang ada, bukan dikarang.
 */
class MasterCashCategoryListDoc extends ApiEndpointDoc
{
    public function key(): string
    {
        return 'master-cash-categories';
    }

    public function title(): string
    {
        return 'Daftar Kategori Kas';
    }

    public function method(): string
    {
        return 'GET';
    }

    public function path(): string
    {
        return '/master/cash_categories';
    }

    public function group(): string
    {
        return 'master';
    }

    public function description(): string
    {
        return 'Mengambil seluruh data master kategori kas yang berstatus aktif, '
            .'beserta jenis arus kasnya (uang masuk atau uang keluar).';
    }

    public function responseExample(): array
    {
        return [
            'success' => true,
            'data' => [
                [
                    'cc_id' => 1,
                    'cc_name' => 'Uang Setoran Kustomer',
                    'cc_type' => 'Masuk',
                ],
                [
                    'cc_id' => 2,
                    'cc_name' => 'Uang Makan',
                    'cc_type' => 'Keluar',
                ],
                [
                    'cc_id' => 3,
                    'cc_name' => 'BBM',
                    'cc_type' => 'Keluar',
                ],
            ],
            'meta' => [
                'total' => 41,
            ],
        ];
    }

    public function notes(): array
    {
        return [
            'Endpoint ini tidak menerima parameter apa pun. Seluruh kategori kas aktif selalu dikembalikan sekaligus.',
            'Hanya kategori berstatus aktif yang muncul. Kategori yang dihapus di Pegasus akan hilang dari daftar ini.',
            'cc_type menyatakan arah arus kas dan pada data aktif hanya bernilai "Masuk" atau "Keluar". Nilainya berupa teks, bukan angka, dan huruf besar-kecilnya mengikuti apa adanya seperti tersimpan di Pegasus.',
            'Urutan daftar bersifat tetap, sehingga dua permintaan berturut-turut atas data yang sama menghasilkan urutan yang sama.',
        ];
    }
}
