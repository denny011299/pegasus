<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // External API terpisah penuh dari rute web: tanpa session, tanpa
        // CSRF, dan tanpa middleware checkLogin. Isi per versi ada di
        // routes/external-api/.
        //
        // apiPrefix sengaja dikosongkan: berkas ini dijalankan SEBELUM config
        // dimuat, sehingga config('externalapi.base_path') belum bisa dibaca
        // di sini. Awalan alamat karena itu dipasang di dalam routes/api.php
        // yang dimuat belakangan — supaya awalannya tetap satu sumber di
        // config, bukan tersebar dua tempat.
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.access' => \App\Http\Middleware\checkAccess::class,
            'check.access.any' => \App\Http\Middleware\checkAccessAny::class,
            'external.api.auth' => \App\Http\Middleware\AuthenticateExternalApi::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\LogDashboardActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Klien External API belum tentu mengirim header Accept: application/json,
        // sedangkan bawaan Laravel baru menjawab JSON kalau header itu ada.
        // Tanpa ini, error tak tertangani akan terkirim sebagai halaman HTML
        // milik admin — bentuk yang tidak bisa dibaca sistem pihak ketiga.
        //
        // Closure ini berjalan saat menangani permintaan, bukan saat berkas ini
        // dibaca, jadi config() di dalamnya sudah tersedia.
        $exceptions->shouldRenderJsonWhen(function ($request) {
            return \App\ExternalApi\Support\ExternalApiPath::matches($request) || $request->expectsJson();
        });

        $exceptions->render(function (\Throwable $e, $request) {
            if (! \App\ExternalApi\Support\ExternalApiPath::matches($request)) {
                return null;
            }

            return \App\ExternalApi\Support\ExceptionRenderer::render($e);
        });
    })->create();
