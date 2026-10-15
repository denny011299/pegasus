<?php

namespace App\ExternalApi\Support;

use Illuminate\Http\JsonResponse;

/**
 * Dilempar dari dalam DB::transaction() untuk membatalkan seluruh transaksi lalu menjawab dengan
 * respons tertentu — mis. persetujuan otomatis yang ditolak pada POST /payments/cash, supaya
 * tidak ada baris yang tertinggal dan retry berikutnya bisa membuat data dengan bersih.
 */
class AbortWithResponse extends \RuntimeException
{
    public function __construct(public readonly JsonResponse $response)
    {
        parent::__construct('Transaksi dibatalkan dengan respons.');
    }
}
