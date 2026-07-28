<?php

namespace App\Http\Middleware;

use App\ExternalApi\Logging\RequestLogger;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pencatat lalu lintas External API.
 *
 * Middleware ini terminable: handle() hanya mencatat waktu mulai lalu langsung
 * meneruskan permintaan, sedangkan penulisan log dikerjakan terminate() yang
 * berjalan SESUDAH respons dikirim ke klien. Dengan begitu pencatatan tidak
 * menambah waktu tunggu yang dirasakan pemanggil API, dan tidak butuh queue
 * worker yang harus dijaga tetap hidup — proyek ini belum menjalankan worker
 * permanen, jadi antrean justru akan membuat log tidak pernah tertulis.
 *
 * Dipasang di luar autentikasi supaya permintaan yang GAGAL autentikasi pun
 * ikut tercatat; justru itu yang paling berguna saat menelusuri masalah
 * integrasi.
 */
class LogExternalApiRequest
{
    /**
     * Waktu mulai dititipkan di atribut Request, bukan di properti objek.
     * Laravel membuat instance middleware BARU saat memanggil terminate(),
     * jadi properti apa pun yang diisi handle() sudah hilang di sana —
     * sementara objek Request-nya tetap yang sama.
     */
    private const STARTED_AT = 'external_api_started_at';

    public function __construct(private readonly RequestLogger $logger)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::STARTED_AT, microtime(true));

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $startedAt = (float) $request->attributes->get(self::STARTED_AT, microtime(true));

        $this->logger->write($request, $response, $startedAt);
    }
}
