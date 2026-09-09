<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoleSurfaceAccess
{
    /**
     * @param  Closure(Request): Response  $next
     * @param  'officer'|'administrator'  $surface
     */
    public function handle(Request $request, Closure $next, string $surface): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $allowed = match ($surface) {
            'officer' => $user->canAccessOfficerSurfaces(),
            'administrator' => $user->canAccessAdministratorSurfaces(),
            default => false,
        };

        if ($allowed) {
            return $next($request);
        }

        if ($user->mustCompleteMembershipOnboarding()) {
            return redirect()->route('membership-application.create');
        }

        abort(403);
    }
}
