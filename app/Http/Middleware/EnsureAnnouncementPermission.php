<?php

namespace App\Http\Middleware;

use App\Support\PlatformAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAnnouncementPermission
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $grants = PlatformAccess::announcementPermissions($request->user());
        abort_unless(array_intersect($permissions, $grants) !== [], 403);

        return $next($request);
    }
}
