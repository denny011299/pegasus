<?php

use App\Support\MissingFileDetector;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.access' => \App\Http\Middleware\checkAccess::class,
            'check.access.any' => \App\Http\Middleware\checkAccessAny::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\LogDashboardActivity::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Manual (non-git) deployments can miss a file on upload -- this turns
        // the resulting "class/view/include not found" crash into a response
        // that names the likely-missing file instead of a bare Server Error,
        // for both full-page loads and this app's jQuery $.ajax calls (which
        // Laravel treats as "expects JSON"). See MissingFileDetector.
        $exceptions->render(function (Throwable $e, Request $request) {
            $detection = MissingFileDetector::detect($e);
            if (!$detection) {
                return null;
            }

            // File-path/class-name detail is only ever shown to a logged-in
            // staff session -- guests just get a generic message, so this
            // doesn't leak internal paths to the public.
            $loggedIn = Session::has('user');

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => -1,
                    'message' => $loggedIn
                        ? $detection['explanation']
                        : 'Terjadi kesalahan pada server.',
                    'detection' => $loggedIn ? $detection : null,
                ], 500);
            }

            return response()->view('errors.deploy-issue', [
                'detection' => $loggedIn ? $detection : null,
            ], 500);
        });
    })->create();
