<?php

namespace App\Http\Middleware;

use App\Support\Feature;
use Closure;
use Illuminate\Http\Request;

/** Route fitur yang flag-nya off → 404 untuk semua role (termasuk superadmin). Pakai: ->middleware('feature:nama'). */
class EnsureFeatureEnabled
{
    public function handle(Request $request, Closure $next, string $feature)
    {
        abort_unless(Feature::on($feature), 404);

        return $next($request);
    }
}
