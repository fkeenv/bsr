<?php

namespace App\Http\Middleware;

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
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $allowed = match ($surface) {
            'super-admin' => $user->isSuperAdmin(),
            'officer' => $user->canAccessOfficerSurfaces(),
            'administrator' => $user->canAccessAdministratorSurfaces(),
            default => false,
        };

        if (! $allowed) {
            abort(403);
        }

        return $next($request);
    }
}
