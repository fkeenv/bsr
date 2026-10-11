<?php

namespace App\Http\Middleware;

use App\Support\PlatformAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSurfaceAccess
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $surface): Response
    {
        abort_unless(PlatformAccess::canAccessSurface($request->user(), $surface), 403);

        return $next($request);
    }
}
