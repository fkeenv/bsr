<?php

namespace App\Http\Middleware;

use App\Support\PlatformAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPermission
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        abort_unless(array_intersect($permissions, PlatformAccess::staffPermissions($request->user())) !== [], 403);

        return $next($request);
    }
}
